<?php

declare(strict_types=1);

namespace TDarkCoder\Framework\Tests;

use RuntimeException;
use TDarkCoder\Framework\Tests\Fixtures\UsersController;

final class ApplicationTest extends TestCase
{
    public function testNotFoundRendersDefaultErrorPageWithStatus(): void
    {
        $response = $this->app('GET', '/missing')->handle();

        $this->assertSame(404, $response->status());
        $this->assertStringContainsString('404 | Page not found', $response->content());
    }

    public function testJsonClientsGetJsonErrors(): void
    {
        $response = $this->app('GET', '/missing', server: ['HTTP_ACCEPT' => 'application/json'])->handle();

        $this->assertSame(404, $response->status());
        $this->assertSame(['message' => 'Page not found'], json_decode($response->content(), true));
    }

    public function testCustomErrorViewIsUsed(): void
    {
        $this->writeView('_errors.404', 'custom <?= $exception->getStatusCode() ?>');

        $this->assertSame('custom 404', $this->app('GET', '/missing')->handle()->content());
    }

    public function testUnexpectedExceptionsBecome500WithoutLeakingDetails(): void
    {
        $app = $this->app('GET', '/boom');
        $app->router->get('/boom', function (): never {
            throw new RuntimeException('secret detail');
        });

        $response = $app->handle();

        $this->assertSame(500, $response->status());
        $this->assertStringContainsString('Server error', $response->content());
        $this->assertStringNotContainsString('secret detail', $response->content());
    }

    public function testDebugModeShowsTheOriginalException(): void
    {
        $app = $this->app('GET', '/boom', config: ['debug' => true]);
        $app->router->get('/boom', function (): never {
            throw new RuntimeException('secret detail');
        });

        $this->assertStringContainsString('secret detail', $app->handle()->content());
    }

    public function testAbortHelperProducesHttpErrors(): void
    {
        $app = $this->app('GET', '/forbidden');
        $app->router->get('/forbidden', fn(): never => abort(403));

        $this->assertSame(403, $app->handle()->status());

        $app = $this->app('GET', '/teapot');
        $app->router->get('/teapot', fn(): never => abort(418, 'I am a teapot'));

        $response = $app->handle();

        $this->assertSame(418, $response->status());
        $this->assertStringContainsString('I am a teapot', $response->content());
    }

    public function testValidationFailureRedirectsBackWithErrorsAndOldInput(): void
    {
        $app = $this->app('POST', '/users', [
            'name' => 'Aziz',
            'email' => 'nope',
            'password' => 'short',
        ], ['HTTP_REFERER' => '/register']);
        $app->router->post('/users', [UsersController::class, 'store']);

        $response = $app->handle();

        $this->assertSame(302, $response->status());
        $this->assertSame('/register', $response->headers()['Location']);

        $this->app('GET', '/register');

        $this->assertSame('Aziz', old('name'));
        $this->assertNull(old('password'));
        $this->assertSame('The email must be a valid email address', request()->getError('email'));
        $this->assertSame('The password must be at least 8 characters', request()->getError('password'));

        $this->app('GET', '/register');

        $this->assertNull(old('name'));
        $this->assertSame([], request()->errors());
    }

    public function testValidationFailureReturns422JsonForJsonClients(): void
    {
        $app = $this->app('POST', '/users', ['email' => 'nope'], ['HTTP_ACCEPT' => 'application/json']);
        $app->router->post('/users', [UsersController::class, 'store']);

        $response = $app->handle();
        $payload = json_decode($response->content(), true);

        $this->assertSame(422, $response->status());
        $this->assertSame(['email', 'password'], array_keys($payload['errors']));
    }

    public function testValidationSuccessReturnsValidatedData(): void
    {
        $app = $this->app('POST', '/users', ['email' => 'a@b.co', 'password' => 'long enough']);
        $app->router->post('/users', [UsersController::class, 'store']);

        $this->assertSame('stored a@b.co', $app->handle()->content());
    }

    public function testRedirectHelperFlashesData(): void
    {
        $app = $this->app('GET', '/go');
        $app->router->get('/go', fn() => redirect('/there')->with('status', 'Saved'));

        $response = $app->handle();

        $this->assertSame(302, $response->status());
        $this->assertSame('/there', $response->headers()['Location']);

        $this->app('GET', '/there');

        $this->assertSame('Saved', session()->getFlash('status'));
    }
}

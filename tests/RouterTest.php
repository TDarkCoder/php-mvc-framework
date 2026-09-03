<?php

declare(strict_types=1);

namespace TDarkCoder\Framework\Tests;

use InvalidArgumentException;
use TDarkCoder\Framework\Http\Request;
use TDarkCoder\Framework\Routing\Router;
use TDarkCoder\Framework\Tests\Fixtures\BlockingMiddleware;
use TDarkCoder\Framework\Tests\Fixtures\RecordingMiddleware;
use TDarkCoder\Framework\Tests\Fixtures\UsersController;

final class RouterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        RecordingMiddleware::$log = [];
    }

    public function testStaticRouteReturnsResponse(): void
    {
        $app = $this->app('GET', '/hello');
        $app->router->get('/hello', fn(): string => 'Hello');

        $response = $app->handle();

        $this->assertSame(200, $response->status());
        $this->assertSame('Hello', $response->content());
    }

    public function testParametersAreCastAndRequestIsInjected(): void
    {
        $app = $this->app('GET', '/users/42?tab=posts');
        $app->router->get('/users/{id}', fn(Request $request, int $id): array => ['id' => $id, 'tab' => $request->get('tab')]);

        $response = $app->handle();

        $this->assertSame('application/json', $response->headers()['Content-Type']);
        $this->assertSame(['id' => 42, 'tab' => 'posts'], json_decode($response->content(), true));
    }

    public function testParametersMatchByPositionWhenNamesDiffer(): void
    {
        $app = $this->app('GET', '/posts/7/comments/9');
        $app->router->get('/posts/{post}/comments/{comment}', fn(int $a, int $b): string => "$a-$b");

        $this->assertSame('7-9', $app->handle()->content());
    }

    public function testNonNumericValueForIntParameterIsNotFound(): void
    {
        $app = $this->app('GET', '/users/abc');
        $app->router->get('/users/{id}', fn(int $id): int => $id);

        $this->assertSame(404, $app->handle()->status());
    }

    public function testUnknownRouteIs404AndWrongMethodIs405(): void
    {
        $app = $this->app('POST', '/hello');
        $app->router->get('/hello', fn(): string => 'Hello');

        $this->assertSame(405, $app->handle()->status());

        $app = $this->app('GET', '/missing');

        $this->assertSame(404, $app->handle()->status());
    }

    public function testHeadFallsBackToGetAndTrailingSlashIsIgnored(): void
    {
        $app = $this->app('HEAD', '/hello/');
        $app->router->get('/hello', fn(): string => 'Hello');

        $this->assertSame(200, $app->handle()->status());
    }

    public function testMethodOverrideOnPost(): void
    {
        $app = $this->app('POST', '/users/1', ['_method' => 'DELETE']);
        $app->router->delete('/users/{id}', fn(int $id): string => "deleted $id");

        $this->assertSame('deleted 1', $app->handle()->content());
    }

    public function testControllerActionWithControllerMiddleware(): void
    {
        $app = $this->app('GET', '/users');
        $app->router->get('/users', [UsersController::class, 'index']);

        $response = $app->handle();

        $this->assertSame('users:index', $response->content());
        $this->assertSame(['recording'], RecordingMiddleware::$log);
        $this->assertSame('yes', $response->headers()['X-Recorded']);
    }

    public function testControllerMiddlewareOnlyAppliesToListedMethods(): void
    {
        $app = $this->app('GET', '/users/5');
        $app->router->get('/users/{id}', [UsersController::class, 'show']);

        $this->assertSame('users:show:5', $app->handle()->content());
        $this->assertSame([], RecordingMiddleware::$log);
    }

    public function testGroupPrefixAndMiddleware(): void
    {
        $app = $this->app('GET', '/admin/dashboard');
        $app->router->group(['prefix' => '/admin', 'middleware' => RecordingMiddleware::class], function (Router $router): void {
            $router->get('/dashboard', fn(): string => 'dash')->name('admin.dashboard');
        });

        $this->assertSame('dash', $app->handle()->content());
        $this->assertSame(['recording'], RecordingMiddleware::$log);
        $this->assertSame('/admin/dashboard', route('admin.dashboard'));
    }

    public function testNamedRouteUrlWithParametersAndQuery(): void
    {
        $app = $this->app();
        $app->router->get('/users/{id}/posts/{slug}', fn(): string => '')->name('users.posts');

        $this->assertSame(
            '/users/3/posts/hello%20world?page=2',
            route('users.posts', ['id' => 3, 'slug' => 'hello world', 'page' => 2]),
        );
    }

    public function testMissingRouteParameterThrows(): void
    {
        $app = $this->app();
        $app->router->get('/users/{id}', fn(): string => '')->name('users.show');

        $this->expectException(InvalidArgumentException::class);

        route('users.show');
    }

    public function testBlockingMiddlewareShortCircuits(): void
    {
        $app = $this->app('GET', '/secret');
        $app->router->get('/secret', fn(): string => 'secret')->middleware(BlockingMiddleware::class);

        $response = $app->handle();

        $this->assertSame(403, $response->status());
        $this->assertSame('blocked', $response->content());
    }

    public function testGlobalMiddlewaresRunBeforeRouteMiddlewares(): void
    {
        $app = $this->app('GET', '/', config: ['middlewares' => [RecordingMiddleware::class]]);
        $app->router->get('/', fn(): string => 'home')->middleware(BlockingMiddleware::class);

        $this->assertSame(403, $app->handle()->status());
        $this->assertSame(['recording'], RecordingMiddleware::$log);
    }

    public function testStringActionRendersView(): void
    {
        $this->writeView('about', 'About <?= $section ?>');

        $app = $this->app('GET', '/about/team');
        $app->router->get('/about/{section}', 'about');

        $this->assertSame('About team', $app->handle()->content());
    }

    public function testAnyMatchesAllMethods(): void
    {
        $app = $this->app('PUT', '/ping');
        $app->router->any('/ping', fn(): string => 'pong');

        $this->assertSame('pong', $app->handle()->content());
    }

    public function testEncodedPathSegmentsAreDecoded(): void
    {
        $app = $this->app('GET', '/users/John%20Doe');
        $app->router->get('/users/{name}', fn(string $name): string => $name);

        $this->assertSame('John Doe', $app->handle()->content());
    }
}

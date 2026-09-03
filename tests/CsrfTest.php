<?php

declare(strict_types=1);

namespace TDarkCoder\Framework\Tests;

use TDarkCoder\Framework\Http\Middleware\VerifyCsrfToken;
use TDarkCoder\Framework\Tests\Fixtures\OpenCsrfToken;

final class CsrfTest extends TestCase
{
    private const TOKEN = 'abcdef0123456789abcdef0123456789abcdef0123456789abcdef0123456789';

    protected function setUp(): void
    {
        parent::setUp();

        $_SESSION['_csrf_token'] = self::TOKEN;
    }

    public function testPostWithoutTokenIsRejected(): void
    {
        $this->assertSame(419, $this->submit([])->status());
    }

    public function testPostWithBodyTokenPasses(): void
    {
        $this->assertSame(200, $this->submit(['_token' => self::TOKEN])->status());
    }

    public function testPostWithHeaderTokenPasses(): void
    {
        $this->assertSame(200, $this->submit([], ['HTTP_X_CSRF_TOKEN' => self::TOKEN])->status());
    }

    public function testWrongTokenIsRejected(): void
    {
        $this->assertSame(419, $this->submit(['_token' => strrev(self::TOKEN)])->status());
    }

    public function testReadingRequestsAreNotChecked(): void
    {
        $app = $this->app('GET', '/submit', config: ['middlewares' => [VerifyCsrfToken::class]]);
        $app->router->get('/submit', fn(): string => 'ok');

        $this->assertSame(200, $app->handle()->status());
    }

    public function testExceptPatternsSkipTheCheck(): void
    {
        $app = $this->app('POST', '/webhooks/stripe', config: ['middlewares' => [OpenCsrfToken::class]]);
        $app->router->post('/webhooks/stripe', fn(): string => 'ok');

        $this->assertSame(200, $app->handle()->status());
    }

    private function submit(array $body, array $server = []): \TDarkCoder\Framework\Http\Response
    {
        $app = $this->app('POST', '/submit', $body, $server, ['middlewares' => [VerifyCsrfToken::class]]);
        $app->router->post('/submit', fn(): string => 'ok');

        return $app->handle();
    }
}

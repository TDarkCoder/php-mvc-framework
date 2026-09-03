<?php

declare(strict_types=1);

namespace TDarkCoder\Framework\Tests;

use TDarkCoder\Framework\Application;
use TDarkCoder\Framework\Tests\Fixtures\User;

final class AuthTest extends TestCase
{
    private Application $application;

    protected function setUp(): void
    {
        parent::setUp();

        $this->application = $this->boot();
        $this->createUsersTable($this->application);
        $this->application->database->pdo()->exec('
            CREATE TABLE access_tokens (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER,
                token TEXT,
                device TEXT,
                expires_at TEXT NULL
            )
        ');
    }

    public function testLoginStoresHashedTokenAndLoadsTheUser(): void
    {
        $user = User::create(['name' => 'A', 'email' => 'a@b.co', 'password' => 'x']);
        $csrf = session()->token();

        $user->authorizeToken();

        $plain = session()->get('_auth_token');
        $stored = $this->application->database->pdo()->query('SELECT token FROM access_tokens')->fetchColumn();

        $this->assertSame(hash('sha256', $plain), $stored);
        $this->assertNotSame($plain, $stored);
        $this->assertSame($csrf, session()->token());

        $app = $this->boot();
        $app->handle();

        $this->assertInstanceOf(User::class, $app->user);
        $this->assertSame(1, $app->user->id);
    }

    public function testExpiredTokensAreIgnored(): void
    {
        User::create(['name' => 'A', 'email' => 'a@b.co', 'password' => 'x'])->authorizeToken();

        $this->application->database->pdo()->exec("UPDATE access_tokens SET expires_at = '2000-01-01 00:00:00'");

        $app = $this->boot();
        $app->handle();

        $this->assertNull($app->user);
    }

    public function testTokenLifetimeConfigSetsExpiry(): void
    {
        $app = $this->boot(['auth' => ['token_lifetime' => 3600]]);

        User::create(['name' => 'A', 'email' => 'a@b.co', 'password' => 'x'])->authorizeToken();

        $expiresAt = $app->database->pdo()->query('SELECT expires_at FROM access_tokens')->fetchColumn();

        $this->assertGreaterThan(time() + 3000, strtotime($expiresAt));
    }

    public function testLogoutDeletesTheTokenAndInvalidatesTheSession(): void
    {
        $user = User::create(['name' => 'A', 'email' => 'a@b.co', 'password' => 'x']);
        $user->authorizeToken();
        $csrf = session()->token();

        $user->logout();

        $this->assertSame(0, (int) $this->application->database->pdo()->query('SELECT COUNT(*) FROM access_tokens')->fetchColumn());
        $this->assertFalse(session()->has('_auth_token'));
        $this->assertNotSame($csrf, session()->token());

        $app = $this->boot();
        $app->handle();

        $this->assertNull($app->user);
    }

    private function boot(array $config = []): Application
    {
        $app = $this->app(config: array_replace_recursive(['auth' => ['model' => User::class]], $config));
        $app->router->get('/', fn(): string => 'home');

        return $app;
    }
}

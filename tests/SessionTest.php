<?php

declare(strict_types=1);

namespace TDarkCoder\Framework\Tests;

use TDarkCoder\Framework\Session\Session;

final class SessionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app();
    }

    public function testFlashDataSurvivesExactlyOneRequest(): void
    {
        $session = new Session();
        $session->setFlash('status', 'Saved');

        $this->assertSame('Saved', $session->getFlash('status'));
        $this->assertTrue($session->hasFlash('status'));

        $next = new Session();

        $this->assertSame('Saved', $next->getFlash('status'));

        $later = new Session();

        $this->assertNull($later->getFlash('status'));
        $this->assertSame('default', $later->getFlash('status', 'default'));
    }

    public function testCsrfTokenIsGeneratedOnceAndKept(): void
    {
        $token = (new Session())->token();

        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $token);
        $this->assertSame($token, (new Session())->token());
    }

    public function testInvalidateClearsDataAndRotatesToken(): void
    {
        $session = new Session();
        $session->set('user', 1);
        $token = $session->token();

        $session->invalidate();

        $this->assertFalse($session->has('user'));
        $this->assertNotSame($token, $session->token());
        $this->assertSame('none', $session->get('user', 'none'));
    }
}

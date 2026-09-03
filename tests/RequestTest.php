<?php

declare(strict_types=1);

namespace TDarkCoder\Framework\Tests;

final class RequestTest extends TestCase
{
    public function testMergesQueryAndBodyAndKeepsArrays(): void
    {
        $request = $this->app('POST', '/x?a=1&b=2', ['b' => '3', 'tags' => ['x', 'y']])->request;

        $this->assertSame('1', $request->get('a'));
        $this->assertSame('3', $request->get('b'));
        $this->assertSame(['x', 'y'], $request->get('tags'));
        $this->assertSame(['a' => '1', 'tags' => ['x', 'y']], $request->except(['b']));
        $this->assertSame(['a' => '1', 'missing' => null], $request->only(['a', 'missing']));
        $this->assertSame('fallback', $request->input('nope', 'fallback'));
        $this->assertSame('3', $request->b);
    }

    public function testMethodOverrideIsOnlyHonouredOnPostForWriteVerbs(): void
    {
        $this->assertSame('put', $this->app('POST', '/', ['_method' => 'PUT'])->request->method());
        $this->assertSame('post', $this->app('POST', '/', ['_method' => 'GET'])->request->method());
        $this->assertSame('get', $this->app('GET', '/?_method=DELETE')->request->method());
    }

    public function testPathStripsQueryAndDecodes(): void
    {
        $request = $this->app('GET', '/users/John%20Doe?x=1')->request;

        $this->assertSame('/users/John Doe', $request->path());
        $this->assertTrue($request->isGet());
    }

    public function testHeadersAndJsonDetection(): void
    {
        $request = $this->app('GET', '/', server: ['HTTP_ACCEPT' => 'application/json', 'CONTENT_TYPE' => 'application/json'])->request;

        $this->assertTrue($request->wantsJson());
        $this->assertTrue($request->isJson());
        $this->assertSame('application/json', $request->header('Accept'));
        $this->assertSame('none', $request->header('X-Missing', 'none'));
    }

    public function testFlashLeavesOutSecrets(): void
    {
        $this->app('POST', '/', [
            'name' => 'A',
            'password' => 'p',
            'password_confirmation' => 'p',
            '_token' => 't',
        ])->request->flash();

        $this->app('GET', '/');

        $this->assertSame('A', old('name'));
        $this->assertNull(old('password'));
        $this->assertNull(old('password_confirmation'));
        $this->assertNull(old('_token'));
    }
}

<?php

declare(strict_types=1);

namespace TDarkCoder\Framework\Tests;

use JsonSerializable;
use stdClass;
use TDarkCoder\Framework\Exceptions\ServerErrorException;
use TDarkCoder\Framework\Http\Response;

final class ResponseTest extends TestCase
{
    public function testFromConvertsHandlerResults(): void
    {
        $this->assertSame('text', Response::from('text')->content());
        $this->assertSame('', Response::from(null)->content());
        $this->assertSame('5', Response::from(5)->content());

        $json = Response::from(['a' => 1]);

        $this->assertSame('{"a":1}', $json->content());
        $this->assertSame('application/json', $json->headers()['Content-Type']);

        $serializable = new class () implements JsonSerializable {
            public function jsonSerialize(): array
            {
                return ['ok' => true];
            }
        };

        $this->assertSame('{"ok":true}', Response::from($serializable)->content());

        $response = new Response('same');

        $this->assertSame($response, Response::from($response));
    }

    public function testFromRejectsUnknownObjects(): void
    {
        $this->expectException(ServerErrorException::class);

        Response::from(new stdClass());
    }

    public function testHeadersAreNormalizedAndRedirectsSetLocation(): void
    {
        $response = (new Response())->header('content-type', 'text/plain')->setStatus(201);

        $this->assertSame(['Content-Type' => 'text/plain'], $response->headers());
        $this->assertSame(201, $response->status());

        $redirect = Response::redirect('/home', 301);

        $this->assertSame(301, $redirect->status());
        $this->assertSame('/home', $redirect->headers()['Location']);
    }
}

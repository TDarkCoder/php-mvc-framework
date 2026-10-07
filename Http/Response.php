<?php

declare(strict_types=1);

namespace TDarkCoder\Framework\Http;

use JsonException;
use JsonSerializable;
use Stringable;
use TDarkCoder\Framework\Exceptions\ServerErrorException;

class Response
{
    private array $headers = [];

    final public function __construct(private string $content = '', private int $status = 200, array $headers = [])
    {
        foreach ($headers as $name => $value) {
            $this->header($name, $value);
        }
    }

    /**
     * Convert whatever a route handler returned into a response.
     *
     * @throws ServerErrorException
     * @throws JsonException
     */
    public static function from(mixed $result): static
    {
        if ($result instanceof static) {
            return $result;
        }

        if (is_null($result)) {
            return new static();
        }

        if (is_array($result) || $result instanceof JsonSerializable) {
            return static::json($result);
        }

        if (is_scalar($result) || $result instanceof Stringable) {
            return new static((string) $result);
        }

        throw new ServerErrorException('Unsupported response type ' . get_debug_type($result));
    }

    /**
     * @throws JsonException
     */
    public static function json(mixed $data, int $status = 200, array $headers = []): static
    {
        $content = json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return new static($content, $status, ['Content-Type' => 'application/json'] + $headers);
    }

    public static function redirect(string $url, int $status = 302): static
    {
        return new static('', $status, ['Location' => $url]);
    }

    public function content(): string
    {
        return $this->content;
    }

    public function header(string $name, string $value): static
    {
        $this->headers[ucwords(strtolower($name), '-')] = $value;

        return $this;
    }

    public function headers(): array
    {
        return $this->headers;
    }

    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->status);

            foreach ($this->headers as $name => $value) {
                header("$name: $value", true);
            }
        }

        echo $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function setStatus(int $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function status(): int
    {
        return $this->status;
    }

    /**
     * Flash a value for the next request, typically alongside a redirect.
     */
    public function with(string $key, mixed $value): static
    {
        session()->setFlash($key, $value);

        return $this;
    }
}

<?php

namespace TDarkCoder\Framework\Routing;

use Closure;
use InvalidArgumentException;

class Route
{
    private array $middlewares = [];
    private ?string $name = null;
    private array $parameterNames = [];
    private string $pattern;

    /**
     * @param string[] $methods
     */
    public function __construct(
        private readonly array $methods,
        private readonly string $path,
        private readonly Closure|string|array $action,
    ) {
        $this->compile();
    }

    public function action(): Closure|string|array
    {
        return $this->action;
    }

    public function allows(string $method): bool
    {
        return in_array(strtolower($method), $this->methods, true);
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function matches(string $path): bool
    {
        return preg_match($this->pattern, $path) === 1;
    }

    public function methods(): array
    {
        return $this->methods;
    }

    public function middleware(string|array $middlewares): static
    {
        $this->middlewares = array_merge($this->middlewares, (array) $middlewares);

        return $this;
    }

    public function middlewares(): array
    {
        return $this->middlewares;
    }

    public function name(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Extract the named parameters from a matching path.
     *
     * @return array<string, string>
     */
    public function parameters(string $path): array
    {
        if (preg_match($this->pattern, $path, $matches) !== 1) {
            return [];
        }

        return array_intersect_key($matches, array_flip($this->parameterNames));
    }

    public function path(): string
    {
        return $this->path;
    }

    /**
     * Build a URL for this route. Parameters that are not placeholders are
     * appended as a query string.
     *
     * @throws InvalidArgumentException
     */
    public function url(array $parameters = []): string
    {
        $url = preg_replace_callback('/\{(\w+)\}/', function (array $matches) use (&$parameters): string {
            $name = $matches[1];

            if (!array_key_exists($name, $parameters)) {
                throw new InvalidArgumentException("Missing parameter [$name] for route [$this->path]");
            }

            $value = rawurlencode((string) $parameters[$name]);

            unset($parameters[$name]);

            return $value;
        }, $this->path);

        return $parameters === [] ? $url : $url . '?' . http_build_query($parameters);
    }

    private function compile(): void
    {
        $regex = '';

        foreach (preg_split('/\{(\w+)\}/', $this->path, -1, PREG_SPLIT_DELIM_CAPTURE) as $index => $part) {
            if ($index % 2 === 1) {
                $this->parameterNames[] = $part;
                $regex .= "(?P<$part>[^/]+)";
            } else {
                $regex .= preg_quote($part, '#');
            }
        }

        $this->pattern = '#^' . $regex . '$#u';
    }
}

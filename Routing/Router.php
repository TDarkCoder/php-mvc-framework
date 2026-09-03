<?php

declare(strict_types=1);

namespace TDarkCoder\Framework\Routing;

use Closure;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionType;
use TDarkCoder\Framework\Contracts\Router as RouterContract;
use TDarkCoder\Framework\Database\Model;
use TDarkCoder\Framework\Exceptions\MethodNotAllowedException;
use TDarkCoder\Framework\Exceptions\NotFoundException;
use TDarkCoder\Framework\Exceptions\ServerErrorException;
use TDarkCoder\Framework\Http\Controller;
use TDarkCoder\Framework\Http\Pipeline;
use TDarkCoder\Framework\Http\Request;
use TDarkCoder\Framework\Http\Response;

class Router implements RouterContract
{
    private const METHODS = ['get', 'post', 'put', 'patch', 'delete'];

    private array $groupStack = [];

    /** @var Route[] */
    private array $routes = [];

    public function any(string $path, Closure|string|array $action): Route
    {
        return $this->match(self::METHODS, $path, $action);
    }

    public function delete(string $path, Closure|string|array $action): Route
    {
        return $this->match(['delete'], $path, $action);
    }

    public function get(string $path, Closure|string|array $action): Route
    {
        return $this->match(['get'], $path, $action);
    }

    public function group(array $attributes, Closure $routes): void
    {
        $this->groupStack[] = $attributes;

        $routes($this);

        array_pop($this->groupStack);
    }

    public function match(array $methods, string $path, Closure|string|array $action): Route
    {
        $prefix = implode('', array_map(fn(array $group): string => $this->normalize($group['prefix'] ?? ''), $this->groupStack));
        $route = new Route(array_map('strtolower', $methods), $this->normalize($prefix . $this->normalize($path)), $action);

        foreach ($this->groupStack as $group) {
            if (!empty($group['middleware'])) {
                $route->middleware($group['middleware']);
            }
        }

        $this->routes[] = $route;

        return $route;
    }

    public function patch(string $path, Closure|string|array $action): Route
    {
        return $this->match(['patch'], $path, $action);
    }

    public function post(string $path, Closure|string|array $action): Route
    {
        return $this->match(['post'], $path, $action);
    }

    public function put(string $path, Closure|string|array $action): Route
    {
        return $this->match(['put'], $path, $action);
    }

    /**
     * @throws NotFoundException
     * @throws MethodNotAllowedException
     * @throws ServerErrorException
     */
    public function resolve(): Response
    {
        $request = request();
        $path = $this->normalize($request->path());
        $method = $request->method() === 'head' ? 'get' : $request->method();

        $route = $this->findRoute($method, $path);
        $middlewares = array_merge(config('middlewares') ?? [], $route->middlewares());

        return (new Pipeline($middlewares))->then(
            $request,
            fn(Request $request): mixed => $this->dispatch($route, $request, $path),
        );
    }

    /**
     * @throws ServerErrorException
     */
    public function route(string $name, array $parameters = []): string
    {
        foreach ($this->routes as $route) {
            if ($route->getName() === $name) {
                return $route->url($parameters);
            }
        }

        throw new ServerErrorException("Route [$name] is not defined");
    }

    /**
     * @return Route[]
     */
    public function routes(): array
    {
        return $this->routes;
    }

    /**
     * @throws ServerErrorException
     * @throws NotFoundException
     */
    private function castArgument(string $value, ?ReflectionType $type, ?string $class): mixed
    {
        if ($class !== null && is_subclass_of($class, Model::class)) {
            return $class::findOrFail([(new $class())->primaryKey => $value]);
        }

        if (!$type instanceof ReflectionNamedType) {
            return $value;
        }

        return match ($type->getName()) {
            'int' => is_numeric($value) ? (int) $value : throw new NotFoundException(),
            'float' => is_numeric($value) ? (float) $value : throw new NotFoundException(),
            'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            default => $value,
        };
    }

    /**
     * Controller middlewares are declared as [Middleware::class => '*'],
     * [Middleware::class => 'method'], [Middleware::class => ['a', 'b']] or
     * simply [Middleware::class].
     */
    private function controllerMiddlewares(Controller $controller, string $method): array
    {
        $middlewares = [];

        foreach ($controller->getMiddlewares() as $middleware => $methods) {
            if (is_int($middleware)) {
                [$middleware, $methods] = [$methods, '*'];
            }

            if ($methods === '*' || $methods === $method || (is_array($methods) && in_array($method, $methods, true))) {
                $middlewares[] = $middleware;
            }
        }

        return $middlewares;
    }

    /**
     * @throws ServerErrorException
     * @throws NotFoundException
     */
    private function dispatch(Route $route, Request $request, string $path): mixed
    {
        $action = $route->action();
        $parameters = $route->parameters($path);

        if (is_string($action)) {
            return view($action, $parameters);
        }

        if ($action instanceof Closure) {
            return $action(...$this->resolveArguments(new ReflectionFunction($action), $parameters, $request));
        }

        [$class, $method] = $action;
        $controller = is_object($class) ? $class : new $class();

        if (!method_exists($controller, $method)) {
            throw new ServerErrorException(sprintf('Method %s::%s does not exist', $controller::class, $method));
        }

        $arguments = $this->resolveArguments(new ReflectionMethod($controller, $method), $parameters, $request);
        $handler = static fn(): mixed => $controller->{$method}(...$arguments);

        if (!$controller instanceof Controller) {
            return $handler();
        }

        return (new Pipeline($this->controllerMiddlewares($controller, $method)))->then($request, $handler);
    }

    /**
     * @throws NotFoundException
     * @throws MethodNotAllowedException
     */
    private function findRoute(string $method, string $path): Route
    {
        $pathMatched = false;

        foreach ($this->routes as $route) {
            if (!$route->matches($path)) {
                continue;
            }

            if ($route->allows($method)) {
                return $route;
            }

            $pathMatched = true;
        }

        throw $pathMatched ? new MethodNotAllowedException() : new NotFoundException();
    }

    private function normalize(string $path): string
    {
        return '/' . trim($path, '/');
    }

    /**
     * Resolve handler arguments: the Request is injected by type, route
     * parameters are matched by name and then by position, Model typed
     * parameters are looked up by primary key and scalars are cast to the
     * declared type.
     *
     * @throws ServerErrorException
     * @throws NotFoundException
     */
    private function resolveArguments(ReflectionFunctionAbstract $function, array $parameters, Request $request): array
    {
        $arguments = [];

        foreach ($function->getParameters() as $parameter) {
            $type = $parameter->getType();
            $class = $type instanceof ReflectionNamedType && !$type->isBuiltin() ? $type->getName() : null;

            if ($class !== null && is_a($request, $class)) {
                $arguments[] = $request;

                continue;
            }

            $name = $parameter->getName();
            $bindable = $class === null || is_subclass_of($class, Model::class);

            if (array_key_exists($name, $parameters)) {
                $value = $parameters[$name];

                unset($parameters[$name]);
            } elseif ($parameters !== [] && $bindable) {
                $value = array_shift($parameters);
            } elseif ($parameter->isDefaultValueAvailable()) {
                $arguments[] = $parameter->getDefaultValue();

                continue;
            } elseif ($type?->allowsNull()) {
                $arguments[] = null;

                continue;
            } else {
                throw new ServerErrorException(sprintf('Unable to resolve parameter $%s of %s', $name, $function->getName()));
            }

            $arguments[] = $this->castArgument((string) $value, $type, $class);
        }

        return $arguments;
    }
}

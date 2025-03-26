<?php

namespace Orb\Trait;

use Borsch\Router\Route;
use Borsch\Router\RouterInterface;
use Orb\RequestHandler;
use Psr\Http\Server\RequestHandlerInterface;

trait RoutingTrait
{

    protected RouterInterface $router;
    /** @var Route[] */
    protected array $routes;

    protected const AVAILABLE_METHODS = ['GET', 'HEAD', 'POST', 'PUT', 'DELETE', 'OPTIONS', 'PATCH'];

    public function get(string $uri, mixed $handler, ?string $name = null): void
    {
        $this->match(['GET'], $uri, $handler, $name);
    }

    public function post(string $uri, mixed $handler, ?string $name = null): void
    {
        $this->match(['POST'], $uri, $handler, $name);
    }

    public function put(string $uri, mixed $handler, ?string $name = null): void
    {
        $this->match(['PUT'], $uri, $handler, $name);
    }

    public function delete(string $uri, mixed $handler, ?string $name = null): void
    {
        $this->match(['DELETE'], $uri, $handler, $name);
    }

    public function head(string $uri, mixed $handler, ?string $name = null): void
    {
        $this->match(['HEAD'], $uri, $handler, $name);
    }

    public function options(string $uri, mixed $handler, ?string $name = null): void
    {
        $this->match(['OPTIONS'], $uri, $handler, $name);
    }

    public function patch(string $uri, mixed $handler, ?string $name = null): void
    {
        $this->match(['PATCH'], $uri, $handler, $name);
    }

    public function any(string $path, mixed $handler, ?string $name = null): void
    {
        $this->match(self::AVAILABLE_METHODS, $path, $handler, $name);
    }

    /** @param string[] $methods */
    public function match(array $methods, string $path, mixed $handler, ?string $name = null): void
    {
        if (!$handler instanceof RequestHandlerInterface) {
            $handler = new RequestHandler($handler);
        }

        $this->routes[] = new Route($methods, $path, $handler, $name);
    }

    private function loadRoutes(): void
    {
        foreach ($this->routes as $route) {
            $this->router->addRoute($route);
        }
    }
}

<?php

namespace Orb\Trait;

use Orb\Middleware\{DispatchMiddleware,
    DispatchMiddlewareInterface,
    ErrorHandlerMiddleware,
    ErrorHandlerMiddlewareInterface,
    MethodNotAllowedMiddleware,
    NotFoundMiddleware,
    NotFoundMiddlewareInterface,
    RouteMiddleware,
    RouteMiddlewareInterface};
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Server\MiddlewareInterface;

trait MiddlewareAwareTrait
{

    /** @var MiddlewareInterface[] */
    protected array $middlewares = [];

    public function addMiddleware(MiddlewareInterface $middleware): void
    {
        $this->middlewares[] = $middleware;
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    private function loadMiddlewares(): void
    {
        $error_handler = $this->container->has(ErrorHandlerMiddlewareInterface::class) ?
            $this->container->get(ErrorHandlerMiddlewareInterface::class) :
            new ErrorHandlerMiddleware($this->logger);

        $route = $this->container->has(RouteMiddlewareInterface::class) ?
            $this->container->get(RouteMiddlewareInterface::class) :
            new RouteMiddleware($this->router, $this->logger);

        $dispatcher = $this->container->has(DispatchMiddlewareInterface::class) ?
            $this->container->get(DispatchMiddlewareInterface::class) :
            new DispatchMiddleware($this->logger);

        $method_not_allowed = $this->container->has(MethodNotAllowedMiddleware::class) ?
            $this->container->get(MethodNotAllowedMiddleware::class) :
            new MethodNotAllowedMiddleware($this->logger);

        $not_found = $this->container->has(NotFoundMiddlewareInterface::class) ?
            $this->container->get(NotFoundMiddlewareInterface::class) :
            new NotFoundMiddleware($this->logger);

        $this->stack->push($error_handler);
        $this->stack->push($route);
        $this->stack->push($dispatcher);

        // User defined middlewares
        foreach ($this->middlewares as $middleware) {
            $this->stack->push($middleware);
        }

        $this->stack->push($method_not_allowed);
        $this->stack->push($not_found);
    }
}
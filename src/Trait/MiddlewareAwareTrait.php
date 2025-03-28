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
use League\Container\Container;
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
        // Creating a delegated container for middlewares, and placed at the end of delegation stack so that it does not
        // override the user defined middlewares
        $delegated_container = new Container();
        $delegated_container->add(ErrorHandlerMiddlewareInterface::class, fn() => new ErrorHandlerMiddleware($this->logger));
        $delegated_container->add(RouteMiddlewareInterface::class, fn() => new RouteMiddleware($this->router, $this->logger));
        $delegated_container->add(DispatchMiddlewareInterface::class, fn() => new DispatchMiddleware($this->logger));
        $delegated_container->add(MethodNotAllowedMiddleware::class, fn() => new MethodNotAllowedMiddleware($this->logger));
        $delegated_container->add(NotFoundMiddlewareInterface::class, fn() => new NotFoundMiddleware($this->logger));

        $this->container->delegate($delegated_container);

        // Middleware stack
        $this->stack->push($this->container->get(ErrorHandlerMiddlewareInterface::class));
        $this->stack->push($this->container->get(RouteMiddlewareInterface::class));
        $this->stack->push($this->container->get(DispatchMiddlewareInterface::class));

        // User defined middlewares
        foreach ($this->middlewares as $middleware) {
            $this->stack->push($middleware);
        }

        $this->stack->push($this->container->get(MethodNotAllowedMiddleware::class));
        $this->stack->push($this->container->get(NotFoundMiddlewareInterface::class));
    }
}
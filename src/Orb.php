<?php

namespace Orb;

use Borsch\Router\RouterInterface;
use League\Container\{Container, ContainerAwareInterface, ContainerAwareTrait};
use Orb\Trait\{ContainerDefaultTrait, EmitterTrait, ErrorHandlingTrait, MiddlewareAwareTrait, RoutingTrait};
use Laminas\Diactoros\Response;
use Psr\Container\{ContainerExceptionInterface, ContainerInterface, NotFoundExceptionInterface};
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Log\{LoggerAwareTrait, LoggerInterface};
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use SplStack;
use Throwable;

class Orb implements ContainerAwareInterface, RequestHandlerInterface
{

    use
        ContainerAwareTrait,
        ContainerDefaultTrait,
        LoggerAwareTrait,
        RoutingTrait,
        MiddlewareAwareTrait,
        EmitterTrait,
        ErrorHandlingTrait;

    private float $time_start;

    private SplStack $stack;

    public function __construct()
    {
        $this->time_start = microtime(true);
        $this->stack = new SplStack();
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function initialize(): void
    {
        if ($this->container === null) {
            $this->setDefaultContainer();
        }

        $this->logger = $this->container->get(LoggerInterface::class);
        $this->router = $this->container->get(RouterInterface::class);

        $this->loadRoutes();
        $this->loadMiddlewares();
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->stack->isEmpty()) {
            throw new RuntimeException(sprintf(
                'The middleware stack is empty and no %s has been returned',
                ResponseInterface::class
            ));
        }

        return $this->stack->shift()->process($request, $this);
    }

    public  function run(?ServerRequestInterface $server_request = null): void
    {
        try {
            set_error_handler([$this, 'handleError']);

            $this->initialize();

            $server_request ??= $this->container->get(ServerRequestInterface::class);
            $server_request = $server_request->withAttribute(ContainerInterface::class, $this->container);

            $response = $this->handle($server_request);
        } catch (Throwable $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);
            $response = new Response(status: 500);
        }

        restore_error_handler();

        $this->emit($response);
    }
}

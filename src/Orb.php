<?php

namespace Orb;

use Borsch\Router\Contract\RouterInterface;
use Orb\Trait\{ContainerAwareTrait, EmitterTrait, ErrorHandlingTrait, MiddlewareAwareTrait, RoutingTrait};
use Laminas\Diactoros\Response;
use Psr\Container\{ContainerExceptionInterface, ContainerInterface, NotFoundExceptionInterface};
use Orb\Exception\RuntimeException;
use Psr\Http\{Server\MiddlewareInterface,
    Message\ResponseInterface,
    Message\ServerRequestInterface,
    Server\RequestHandlerInterface};
use Psr\Log\{LoggerAwareTrait, LoggerInterface};
use SplStack;
use Throwable;

class Orb implements RequestHandlerInterface
{

    use
        ContainerAwareTrait,
        LoggerAwareTrait,
        RoutingTrait,
        MiddlewareAwareTrait,
        EmitterTrait,
        ErrorHandlingTrait;

    private float $time_start;

    /** @var SplStack<MiddlewareInterface> */
    private SplStack $stack;

    private bool $is_initialized = false;

    public function __construct()
    {
        $this->time_start = microtime(true);
        $this->stack = new SplStack();

        $this->setContainer();
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function initialize(?ServerRequestInterface $server_request = null): ServerRequestInterface
    {
        if ($this->is_initialized) {
            return $server_request;
        }

        $this->is_initialized = true;

        $this->logger ??= $this->container->get(LoggerInterface::class); // @pest-mutate-ignore
        $this->router = $this->container->get(RouterInterface::class);

        $this->loadRoutes();
        $this->loadMiddlewares();

        return $server_request->withAttribute(ContainerInterface::class, $this->container);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RuntimeException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $request = $this->initialize($request);

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

            $response = $this->handle(
                $server_request ?? $this->container->get(ServerRequestInterface::class)
            );
        } catch (Throwable $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);
            $response = new Response(status: 500);
        }

        restore_error_handler();

        $this->emit($response);
    }
}

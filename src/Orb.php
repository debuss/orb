<?php

namespace Orb;

use Borsch\Router\Contract\RouterInterface;
use Laminas\Diactoros\Response;
use League\Container\Container;
use Orb\Configuration\Configuration;
use Orb\Configuration\ConfigurationFactory;
use Orb\Exception\RuntimeException;
use Orb\Trait\{ContainerAwareTrait, EmitterTrait, ErrorHandlingTrait, MiddlewareAwareTrait, RoutingTrait};
use Psr\Container\{ContainerExceptionInterface, ContainerInterface, NotFoundExceptionInterface};
use Psr\Http\{Message\ResponseInterface,
    Message\ServerRequestInterface,
    Server\MiddlewareInterface,
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

    public function __construct(?Configuration $configuration = null)
    {
        $this->time_start = microtime(true);
        $this->stack = new SplStack();

        $configuration ??= ConfigurationFactory::createDefault();

        $this->container = $configuration->getContainer();
        $this->router = $configuration->getRouter();
        $this->logger = $configuration->getLogger();
//        $this->setContainer();
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

//        $this->logger ??= $this->container->get(LoggerInterface::class); // @pest-mutate-ignore
//        $this->router = $this->container->get(RouterInterface::class);

//        $this->loadRoutes();
//        $this->loadMiddlewares();

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

            $server_request ??= $this->container->get(ServerRequestInterface::class);
            $server_request = $server_request->withAttribute(ContainerInterface::class, $this->container);

            $this->logger->debug('Request starting HTTP/{protocol} {method} {uri}', [
                'protocol' => $server_request->getProtocolVersion(),
                'method' => $server_request->getMethod(),
                'uri' => $server_request->getUri()
            ]);

            $route_result = $this->router->match($server_request);
            $route = $route_result->getMatchedRoute();

            if (!$route) {
                $this->logger->warning('Request URI does not exist');
                $response = new Response(status: 404); // TODO in configuration/container ?
            } elseif (!in_array($server_request->getMethod(), $route->getAllowedMethods())) {
                $this->logger->warning('Method "{method}" not allowed for "{uri}", expected: {allowed}', [
                    'method' => $server_request->getMethod(),
                    'uri' => $server_request->getUri(),
                    'allowed' => $route->getAllowedMethods()
                ]);
                $response = new Response(status: 405, headers: ['Allow' => $route->getAllowedMethods()]); // TODO in configuration/container ?
            } else {
                $this->logger->debug('Executing endpoint "{name}"', ['name' => $route->getName()]);
                $response = $route->getHandler()->handle($server_request);
            }
        } catch (Throwable $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);
            $response = new Response(status: 500); // TODO in configuration/container ?
        }

        restore_error_handler();

        $this->emit($response);
    }
}

<?php

namespace Orb;

use Laminas\Diactoros\Response;
use Orb\Configuration\{Configuration, ConfigurationFactory};
use Orb\Trait\{ContainerAwareTrait, EmitterTrait, ErrorHandlingTrait, RouterAwareTrait};
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerAwareTrait;
use Throwable;

class Orb implements RequestHandlerInterface
{

    use
        ContainerAwareTrait,
        LoggerAwareTrait,
        RouterAwareTrait,
        EmitterTrait,
        ErrorHandlingTrait;

    private float $time_start;

    public function __construct(?Configuration $configuration = null)
    {
        $this->time_start = microtime(true);

        $configuration ??= ConfigurationFactory::createDefault(); // @pest-mutate-ignore

        $this->container = $configuration->getContainer();
        $this->router = $configuration->getRouter();
        $this->logger = $configuration->getLogger();
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $route_result = $this->router->match($request);

        if ($route_result->isMethodFailure()) {
            $this->logger->warning('Method "{method}" not allowed for "{uri}", expected: {allowed}', [
                'method' => $request->getMethod(), // @pest-mutate-ignore
                'uri' => $request->getUri(), // @pest-mutate-ignore
                'allowed' => implode(', ', $route_result->getAllowedMethods()) // @pest-mutate-ignore
            ]);
            $response = new Response(status: 405, headers: ['Allow' => $route_result->getAllowedMethods()]); // TODO in configuration/container ?
        } elseif ($route_result->isFailure()) {
            $this->logger->warning('Request URI does not exist'); // @pest-mutate-ignore
            $response = new Response(status: 404); // TODO in configuration/container ?
        } else {
            $route = $route_result->getMatchedRoute();
            $this->logger->debug('Executing endpoint "{name}"', ['name' => $route->getName()]); // @pest-mutate-ignore
            $response = $route->getHandler()->handle($request);
        }

        return $response;
    }

    public  function run(?ServerRequestInterface $server_request = null): void
    {
        try {
            set_error_handler([$this, 'handleError']);

            $server_request ??= $this->container->get(ServerRequestInterface::class);
            $server_request = $server_request->withAttribute(ContainerInterface::class, $this->container);

            $this->logger->debug('Request starting HTTP/{protocol} {method} {uri}', [
                'protocol' => $server_request->getProtocolVersion(), // @pest-mutate-ignore
                'method' => $server_request->getMethod(), // @pest-mutate-ignore
                'uri' => $server_request->getUri() // @pest-mutate-ignore
            ]);

            $response = $this->handle($server_request);
        } catch (Throwable $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]); // @pest-mutate-ignore
            $response = new Response(status: 500); // TODO in configuration/container ?
        }

        restore_error_handler();

        $this->emit($response);
    }
}

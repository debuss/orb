<?php

namespace Orb;

use Laminas\Diactoros\Response;
use Orb\Configuration\{Configuration, ConfigurationFactory};
use Orb\Trait\{ContainerAwareTrait, EmitterTrait, ErrorHandlingTrait, RouterAwareTrait};
use Laminas\Diactoros\Response\RedirectResponse;
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
    private Configuration $configuration;

    public function __construct(?Configuration $configuration = null)
    {
        $this->time_start = microtime(true);

        $configuration ??= ConfigurationFactory::createDefault(); // @pest-mutate-ignore

        $this->container = $configuration->getContainer();
        $this->router = $configuration->getRouter();
        $this->logger = $configuration->getLogger();

        $this->configuration = $configuration;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Check trailing slash before treating the request
        $uri = $request->getUri();
        if ($uri->getPath() != '/' && str_ends_with($uri->getPath(), '/')) {
            $uri = $uri->withPath(rtrim($uri->getPath(), '/'));

            if ($request->getMethod() == 'GET') {
                return new RedirectResponse((string)$uri, 301);
            }

            $request = $request->withUri($uri);
        }

        $route_result = $this->router->match($request);

        if ($route_result->isMethodFailure()) {
            $this->logger->warning('Method "{method}" not allowed for "{uri}", expected: {allowed}', [
                'method' => $request->getMethod(), // @pest-mutate-ignore
                'uri' => $request->getUri(), // @pest-mutate-ignore
                'allowed' => implode(', ', $route_result->getAllowedMethods()) // @pest-mutate-ignore
            ]);

            $response = $this->configuration->getMethodNotAllowedResponse()->withHeader('Allow', $route_result->getAllowedMethods());
        } elseif ($route_result->isFailure()) {
            $this->logger->warning('Request URI does not exist'); // @pest-mutate-ignore

            $response = $this->configuration->getNotFoundResponse();
        } else {
            $route = $route_result->getMatchedRoute();

            $this->logger->debug('Executing endpoint "{name}"', ['name' => $route->getName()]); // @pest-mutate-ignore

            $response = $route->getHandler()->handle(
                $request->withAttribute('__route_matched_parameters', $route_result->getMatchedParams())
            );
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
            $response = $this->configuration->getInternalServerErrorResponse();
        }

        restore_error_handler();

        $this->emit($response);
    }
}

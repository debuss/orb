<?php

namespace Orb\Middleware;

use Borsch\Router\Contract\{RouteResultInterface, RouterInterface};
use Psr\Log\LoggerInterface;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\RequestHandlerInterface;

readonly class RouteMiddleware implements RouteMiddlewareInterface
{

    public function __construct(
        private RouterInterface $router,
        private LoggerInterface $logger
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->logger->debug('Request starting HTTP/{protocol} {method} {uri}', [
            'protocol' => $request->getProtocolVersion(),
            'method' => $request->getMethod(),
            'uri' => $request->getUri(),
        ]);

        $request = $request->withAttribute(RouteResultInterface::class, $this->router->match($request));

        return $handler->handle($request);
    }
}

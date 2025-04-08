<?php

namespace Orb\Middleware;

use Borsch\Router\Contract\RouteResultInterface;
use Psr\Log\LoggerInterface;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\RequestHandlerInterface;

readonly class DispatchMiddleware implements DispatchMiddlewareInterface
{

    public function __construct(
        private LoggerInterface $logger
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $route_result = $request->getAttribute(RouteResultInterface::class);
        if (!$route_result) {
            return $handler->handle($request);
        }

        if ($route_result->isSuccess()) {
            $this->logger->debug('Executing endpoint "{name}"', ['name' => $route_result->getMatchedRoute()->getName()]);
        }

        return $route_result->process($request, $handler);
    }
}

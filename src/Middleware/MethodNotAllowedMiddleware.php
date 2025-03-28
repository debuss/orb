<?php

namespace Orb\Middleware;

use Borsch\Router\RouteResultInterface;
use Laminas\Diactoros\Response;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

readonly class MethodNotAllowedMiddleware implements MethodNotAllowedMiddlewareInterface
{

    public function __construct(
        private LoggerInterface $logger
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $route_result = $request->getAttribute(RouteResultInterface::class);
        if (!$route_result || !$route_result->isMethodFailure()) {
            return $handler->handle($request);
        }

        $allowed = implode(',', $route_result->getAllowedMethods());

        $this->logger->warning('Method "{method}" not allowed for "{uri}", expected: {allowed}', [
            'method' => $request->getMethod(),
            'uri' => $request->getUri(),
            'allowed' => $allowed
        ]);

        return new Response(status: 405, headers: ['Allow' => $allowed]);
    }
}

<?php

namespace Orb\Middleware;

use Laminas\Diactoros\Response;
use Psr\Log\LoggerInterface;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\RequestHandlerInterface;

readonly class NotFoundMiddleware implements NotFoundMiddlewareInterface
{

    public function __construct(
        private LoggerInterface $logger
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->logger->warning('Request URI does not exist');

        return new Response(status: 404);
    }
}

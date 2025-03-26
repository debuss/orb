<?php

namespace Orb\Middleware;

use ErrorException;
use Laminas\Diactoros\Response;
use Psr\Log\LoggerInterface;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

readonly class ErrorHandlerMiddleware implements ErrorHandlerMiddlewareInterface
{

    public function __construct(
        private LoggerInterface $logger
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            set_error_handler(function (int $errno, string $message, string $file, int $line): bool {
                if (!(error_reporting() & $errno)) {
                    return false;
                }

                throw new ErrorException($message, 500, $errno, $file, $line);
            });

            $response = $handler->handle($request);
        } catch (Throwable $exception) {
            $this->logger->error($exception->getMessage());
            $this->logger->error($exception->getTraceAsString());

            $response = new Response(status: 500);
        }

        restore_error_handler();

        return $response;
    }
}
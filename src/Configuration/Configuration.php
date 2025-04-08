<?php

namespace Orb\Configuration;

use Borsch\Router\Contract\RouterInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class Configuration
{

    private ContainerInterface $container;
    private LoggerInterface $logger;
    private RouterInterface $router;

    private ResponseInterface $method_not_allowed_response;
    private ResponseInterface $not_found_response;
    private ResponseInterface $internal_server_error_response;

    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    public function setContainer(ContainerInterface $container): Configuration
    {
        $this->container = $container;

        return $this;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    public function setLogger(LoggerInterface $logger): Configuration
    {
        $this->logger = $logger;

        return $this;
    }

    public function getRouter(): RouterInterface
    {
        return $this->router;
    }

    public function setRouter(RouterInterface $router): Configuration
    {
        $this->router = $router;

        return $this;
    }

    public function getMethodNotAllowedResponse(): ResponseInterface
    {
        return $this->method_not_allowed_response;
    }

    public function setMethodNotAllowedResponse(ResponseInterface $method_not_allowed_response): Configuration
    {
        $this->method_not_allowed_response = $method_not_allowed_response;

        return $this;
    }

    public function getNotFoundResponse(): ResponseInterface
    {
        return $this->not_found_response;
    }

    public function setNotFoundResponse(ResponseInterface $not_found_response): Configuration
    {
        $this->not_found_response = $not_found_response;

        return $this;
    }

    public function getInternalServerErrorResponse(): ResponseInterface
    {
        return $this->internal_server_error_response;
    }

    public function setInternalServerErrorResponse(ResponseInterface $internal_server_error_response): Configuration
    {
        $this->internal_server_error_response = $internal_server_error_response;

        return $this;
    }
}

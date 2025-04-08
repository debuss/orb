<?php

namespace Orb\Configuration;

use Borsch\Router\Contract\RouterInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class Configuration
{

    private ContainerInterface $container;
    private LoggerInterface $logger;
    private RouterInterface $router;

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
}

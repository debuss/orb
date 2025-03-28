<?php

namespace Orb\Trait;

use Borsch\Router\{FastRouteRouter, RouterInterface};
use Laminas\Diactoros\ServerRequestFactory;
use League\Container\{Container, ReflectionContainer};
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

trait ContainerAwareTrait
{

    protected Container $container;

    public function addContainer(ContainerInterface $container): void
    {
        $this->container->delegate($container);
    }

    private function setContainer(): void
    {
        $this->container = new Container();
        $this->container->defaultToShared();

        $this->container->add(LoggerInterface::class, function(): LoggerInterface {
            $stream = new StreamHandler('php://stdout', Logger::DEBUG);
            $stream->setFormatter(new LineFormatter(dateFormat: 'D M d H:i:s Y', ignoreEmptyContextAndExtra: true));
            $logger = new Logger('Orb');
            $logger->pushHandler($stream);
            $logger->pushProcessor(new PsrLogMessageProcessor('D M d H:i:s Y', true));
            return $logger;
        });

        $this->container->add(RouterInterface::class, fn(): FastRouteRouter => new FastRouteRouter());

        $this->container->add(ServerRequestInterface::class, fn(): ServerRequestInterface => ServerRequestFactory::fromGlobals());

        $this->container->delegate(new ReflectionContainer(true));
    }
}
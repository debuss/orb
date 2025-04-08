<?php

namespace Orb\Configuration;

use Borsch\Router\Contract\RouterInterface;
use Borsch\Router\FastRouteRouter;
use League\Container\Container;
use League\Container\ReflectionContainer;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;
use Psr\Log\LoggerInterface;

class ConfigurationFactory
{

    public static function createDefault(): Configuration
    {
        $config = new Configuration();

        // Logger
        $stream = new StreamHandler('php://stdout', Level::Debug);
        $stream->setFormatter(new LineFormatter(dateFormat: 'D M d H:i:s Y', ignoreEmptyContextAndExtra: true));
        $logger = new Logger('Orb');
        $logger->pushHandler($stream);
        $logger->pushProcessor(new PsrLogMessageProcessor('D M d H:i:s Y', true));
        $config->setLogger($logger);

        // Router
        $router = new FastRouteRouter();
        $config->setRouter($router);

        // Container
        $container = new Container();
        $container->defaultToShared();
        $container->add(LoggerInterface::class, fn (): LoggerInterface => $logger);
        $container->add(RouterInterface::class, fn (): RouterInterface => $router);
        $container->delegate(new ReflectionContainer(true));

        return $config;
    }
}

<?php

use Borsch\Router\RouterInterface;
use League\Container\Container;
use Orb\Middleware\NotFoundMiddlewareInterface;
use Psr\Log\NullLogger;
use Laminas\Diactoros\{Response, ServerRequest};
use Orb\Orb;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};
use Psr\Log\LoggerInterface;

covers(Orb::class);

beforeEach(function () {
    $this->orb = new Orb();
    $this->orb->setLogger(new NullLogger());
});

test('handle throws exception when middleware stack is empty', function () {
    $request = new ServerRequest();

    $not_found_middleware = new class implements NotFoundMiddlewareInterface {
        public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
        {
            return $handler->handle($request);
        }
    };

    $container = new Container();
    $container->add(NotFoundMiddlewareInterface::class, $not_found_middleware);

    $this->orb->addContainer($container);

    expect($this->orb->handle($request)->getStatusCode())->toBe(500);
});

test('container has required services', function () {
    $container = (fn () => $this->container)->call($this->orb);

    expect($container->has(LoggerInterface::class))->toBeTrue()
        ->and($container->has(RouterInterface::class))->toBeTrue()
        ->and($container->has(ServerRequestInterface::class))->toBeTrue();
});

test('middleware is processed in correct order', function () {
    $middleware1 = new class implements MiddlewareInterface {
        public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
            return $handler->handle($request->withAttribute('test1', true));
        }
    };

    $middleware2 = new class implements MiddlewareInterface {
        public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
            expect($request->getAttribute('test1'))->toBeTrue();
            return new Response();
        }
    };

    $this->orb->addMiddleware($middleware1);
    $this->orb->addMiddleware($middleware2);

    $this->orb->get('/', 'hello world');

    $response = $this->orb->handle(new ServerRequest());
    expect($response)->toBeInstanceOf(ResponseInterface::class);
});

test('run method adds container to request attributes', function () {
    $middleware = new class implements MiddlewareInterface {
        public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
            expect($request->getAttribute(ContainerInterface::class))->toBeInstanceOf(ContainerInterface::class);
            return new Response();
        }
    };

    $this->orb->addMiddleware($middleware);

    $this->orb->handle(new ServerRequest());
});

test('error handler middleware catches exceptions', function () {
    $middleware = new class implements MiddlewareInterface {
        public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
            throw new RuntimeException('Test exception');
        }
    };

    $this->orb->addMiddleware($middleware);
    $response = $this->orb->handle(new ServerRequest());

    expect($response)->toBeInstanceOf(ResponseInterface::class)
        ->and($response->getStatusCode())->toBe(500);
});
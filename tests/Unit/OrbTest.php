<?php

use Borsch\Router\Contract\RouterInterface;
use Laminas\Diactoros\ServerRequest;
use Orb\Orb;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Log\{NullLogger, LoggerInterface};

covers(Orb::class);

beforeEach(function () {
    $this->orb = new Orb();
    $this->orb->setLogger(new NullLogger());
});

test('container has required services', function () {
    $container = (fn () => $this->container)->call($this->orb);

    expect($container->has(LoggerInterface::class))->toBeTrue()
        ->and($container->has(RouterInterface::class))->toBeTrue()
        ->and($container->has(ServerRequestInterface::class))->toBeTrue();
});

test('logger exists', function () {
    $logger = (fn () => $this->logger)->call($this->orb);

    expect($logger)->toBeInstanceOf(LoggerInterface::class);
});

test('router exists', function () {
    $router = (fn () => $this->router)->call($this->orb);

    expect($router)->toBeInstanceOf(RouterInterface::class);
});

test('handle() processes valid server request', function () {
    $this->orb->get('/', 'home');
    $response = $this->orb->handle(new ServerRequest(uri: '/', method: 'GET'));

    expect($response)->toBeInstanceOf(ResponseInterface::class)
        ->and($response->getStatusCode())->toBe(200)
        ->and($response->getBody()->getContents())->toBe('home');
});

test('handle() processes 404 server request', function () {
    $this->orb->get('/', 'home');
    $response = $this->orb->handle(new ServerRequest(uri: '/test', method: 'GET'));

    expect($response)->toBeInstanceOf(ResponseInterface::class)
        ->and($response->getStatusCode())->toBe(404)
        ->and($response->getBody()->getContents())->toBe('');
});

test('handle() processes 405 server request', function () {
    $this->orb->get('/', 'home');
    $response = $this->orb->handle(new ServerRequest(uri: '/', method: 'POST'));

    expect($response)->toBeInstanceOf(ResponseInterface::class)
        ->and($response->getStatusCode())->toBe(405)
        ->and($response->getHeaderLine('Allow'))->toBe('GET')
        ->and($response->getBody()->getContents())->toBe('');
});

test('run() processes 500 server request', function () {
    $this->orb->get('/', fn() => throw new Exception('Test exception'));

    ob_start();
    $this->orb->run(new ServerRequest(uri: '/', method: 'GET'));
    $response = ob_get_clean();

    $status_code = http_response_code();
    expect($status_code)->toBe(500)
        ->and($response)->toBe('');
});

test('error handler', function () {
    $this->orb->get('/', fn() => strtoupper($nonexistent_variable));

    ob_start();
    $this->orb->run(new ServerRequest(uri: '/', method: 'GET'));
    $response = ob_get_clean();

    $status_code = http_response_code();
    expect($status_code)->toBe(500)
        ->and($response)->toBe('');
});

test('setContainer()', function () {
    $other_container = new League\Container\Container();
    $this->orb->setContainer($other_container);

    $container = (fn () => $this->container)->call($this->orb);
    expect($container)->toBe($other_container);
});

test('setLogger', function () {
    $logger = new NullLogger();
    $this->orb->setLogger($logger);

    $orb_logger = (fn () => $this->logger)->call($this->orb);
    expect($orb_logger)->toBe($logger);
});

test('setRouter', function () {
    $router = new Borsch\Router\TreeRouter();
    $this->orb->setRouter($router);

    $orb_router = (fn () => $this->router)->call($this->orb);
    expect($orb_router)->toBe($router);
});

test('get()', function () {
    $this->orb->get('/', fn() => 'hello world !');

    $orb_router = (fn () => $this->router)->call($this->orb);

    expect($orb_router)->toBeInstanceOf(RouterInterface::class)
        ->and($orb_router->getRoutes())->toHaveCount(1)
        ->and($orb_router->getRoutes())->toHaveKey('GET^/')
        ->and($orb_router->getRoutes()['GET^/'])->toBeInstanceOf(Borsch\Router\Route::class);
});

test('post()', function () {
    $this->orb->post('/', fn() => 'hello world !');

    $orb_router = (fn () => $this->router)->call($this->orb);

    expect($orb_router)->toBeInstanceOf(RouterInterface::class)
        ->and($orb_router->getRoutes())->toHaveCount(1)
        ->and($orb_router->getRoutes())->toHaveKey('POST^/')
        ->and($orb_router->getRoutes()['POST^/'])->toBeInstanceOf(Borsch\Router\Route::class);
});

test('put()', function () {
    $this->orb->put('/', fn() => 'hello world !');

    $orb_router = (fn () => $this->router)->call($this->orb);

    expect($orb_router)->toBeInstanceOf(RouterInterface::class)
        ->and($orb_router->getRoutes())->toHaveCount(1)
        ->and($orb_router->getRoutes())->toHaveKey('PUT^/')
        ->and($orb_router->getRoutes()['PUT^/'])->toBeInstanceOf(Borsch\Router\Route::class);
});

test('delete()', function () {
    $this->orb->delete('/', fn() => 'hello world !');

    $orb_router = (fn () => $this->router)->call($this->orb);

    expect($orb_router)->toBeInstanceOf(RouterInterface::class)
        ->and($orb_router->getRoutes())->toHaveCount(1)
        ->and($orb_router->getRoutes())->toHaveKey('DELETE^/')
        ->and($orb_router->getRoutes()['DELETE^/'])->toBeInstanceOf(Borsch\Router\Route::class);
});

test('head()', function () {
    $this->orb->head('/', fn() => 'hello world !');

    $orb_router = (fn () => $this->router)->call($this->orb);

    expect($orb_router)->toBeInstanceOf(RouterInterface::class)
        ->and($orb_router->getRoutes())->toHaveCount(1)
        ->and($orb_router->getRoutes())->toHaveKey('HEAD^/')
        ->and($orb_router->getRoutes()['HEAD^/'])->toBeInstanceOf(Borsch\Router\Route::class);
});

test('options()', function () {
    $this->orb->options('/', fn() => 'hello world !');

    $orb_router = (fn () => $this->router)->call($this->orb);

    expect($orb_router)->toBeInstanceOf(RouterInterface::class)
        ->and($orb_router->getRoutes())->toHaveCount(1)
        ->and($orb_router->getRoutes())->toHaveKey('OPTIONS^/')
        ->and($orb_router->getRoutes()['OPTIONS^/'])->toBeInstanceOf(Borsch\Router\Route::class);
});

test('patch()', function () {
    $this->orb->patch('/', fn() => 'hello world !');

    $orb_router = (fn () => $this->router)->call($this->orb);

    expect($orb_router)->toBeInstanceOf(RouterInterface::class)
        ->and($orb_router->getRoutes())->toHaveCount(1)
        ->and($orb_router->getRoutes())->toHaveKey('PATCH^/')
        ->and($orb_router->getRoutes()['PATCH^/'])->toBeInstanceOf(Borsch\Router\Route::class);
});

test('group()', function () {
    $this->orb->group('/api', function ($orb) {
        $orb->get('/', fn() => 'hello world !');
    });

    $orb_router = (fn () => $this->router)->call($this->orb);

    expect($orb_router)->toBeInstanceOf(RouterInterface::class)
        ->and($orb_router->getRoutes())->toHaveCount(1)
        ->and($orb_router->getRoutes())->toHaveKey('GET^/api/')
        ->and($orb_router->getRoutes()['GET^/api/'])->toBeInstanceOf(Borsch\Router\Route::class);
});

test('any()', function () {
    $this->orb->any('/', fn() => 'hello world !');

    $orb_router = (fn () => $this->router)->call($this->orb);

    expect($orb_router)->toBeInstanceOf(RouterInterface::class)
        ->and($orb_router->getRoutes())->toHaveCount(1)
        ->and($orb_router->getRoutes())->toHaveKey('GET:HEAD:POST:PUT:DELETE:OPTIONS:PATCH^/')
        ->and($orb_router->getRoutes()['GET:HEAD:POST:PUT:DELETE:OPTIONS:PATCH^/'])->toBeInstanceOf(Borsch\Router\Route::class);
});

test('match()', function () {
    $this->orb->match(['GET'], '/', fn() => 'hello world !');

    $orb_router = (fn () => $this->router)->call($this->orb);
    expect($orb_router)->toBeInstanceOf(RouterInterface::class)
        ->and($orb_router->getRoutes())->toHaveCount(1)
        ->and($orb_router->getRoutes())->toHaveKey('GET^/')
        ->and($orb_router->getRoutes()['GET^/'])->toBeInstanceOf(Borsch\Router\Route::class);
});

test('error handler is restored', function () {
    $error_handler = function () {
        // do nothing
    };

    $prev_error_handler = set_error_handler($error_handler);

    $this->orb->match(['GET'], '/', fn() => 'hello world !');
    ob_start();
    $this->orb->run(new ServerRequest(uri: '/', method: 'GET'));
    ob_get_clean();

    $next_error_handler = set_error_handler(function () {
        // do nothing
    });

    restore_error_handler();
    restore_error_handler();

    expect($next_error_handler)->toBe($error_handler);
});

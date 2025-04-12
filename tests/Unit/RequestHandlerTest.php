<?php

use Laminas\Diactoros\Response\{HtmlResponse, JsonResponse, XmlResponse};
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use League\Container\Container;
use League\Container\ReflectionContainer;
use Orb\RequestHandler;
use Psr\Container\ContainerInterface;

covers(RequestHandler::class);

class TestClass {
    public function method() {
        return 'result';
    }
}

test('handles direct ResponseInterface', function () {
    $response = new Response();
    $handler = new RequestHandler($response);

    expect($handler->handle(new ServerRequest()))->toBe($response);
});

test('handles string handler as HTML response', function () {
    $handler = new RequestHandler('Hello World');
    $response = $handler->handle(new ServerRequest());

    expect($response)
        ->toBeInstanceOf(HtmlResponse::class)
        ->and((string)$response->getBody())->toBe('Hello World');
});

test('handles array data as JSON response', function () {
    $data = ['key' => 'value'];
    $handler = new RequestHandler($data);

    $response = $handler->handle(new ServerRequest());

    expect($response)
        ->toBeInstanceOf(JsonResponse::class)
        ->and((string)$response->getBody())->toBe(json_encode($data));
});

test('handles object data as JSON response', function () {
    $data = new DateTime();
    $handler = new RequestHandler($data);

    $response = $handler->handle(new ServerRequest());

    expect($response)
        ->toBeInstanceOf(JsonResponse::class)
        ->and((string)$response->getBody())->toBe(json_encode($data));
});

test('handles XML data as XML response', function () {
    $xml = new SimpleXMLElement('<root><item>value</item></root>');
    $handler = new RequestHandler($xml);

    $response = $handler->handle(new ServerRequest());

    expect($response)
        ->toBeInstanceOf(XmlResponse::class)
        ->and((string)$response->getBody())->toContain('<root><item>value</item></root>');
});

test('handles DOMDocument data as XML response', function () {
    $dom = new DOMDocument();
    $dom->loadXML('<root><item>value</item></root>');
    $handler = new RequestHandler($dom);

    $response = $handler->handle(new ServerRequest());

    expect($response)
        ->toBeInstanceOf(XmlResponse::class)
        ->and((string)$response->getBody())->toContain('<root><item>value</item></root>');
});

test('handles callable with class method syntax', function () {
    $container = new Container();
    $container->delegate(new ReflectionContainer(true));

    $request = new ServerRequest();
    $request = $request->withAttribute(ContainerInterface::class, $container);

    $handler = new RequestHandler('TestClass::method');

    $response = $handler->handle($request);

    expect($response)
        ->toBeInstanceOf(HtmlResponse::class)
        ->and((string)$response->getBody())->toBe('result');
});

test('handles callable with class method syntax arrow', function () {
    $container = new Container();
    $container->delegate(new ReflectionContainer(true));

    $request = new ServerRequest();
    $request = $request->withAttribute(ContainerInterface::class, $container);

    $handler = new RequestHandler('TestClass->method');

    $response = $handler->handle($request);

    expect($response)
        ->toBeInstanceOf(HtmlResponse::class)
        ->and((string)$response->getBody())->toBe('result');
});

test('handles function syntax', function () {
    $request = new ServerRequest();
    $request = $request->withAttribute('__route_matched_parameters', ['string' => 'test']);

    $handler = new RequestHandler('strlen');

    $response = $handler->handle($request);

    expect($response)
        ->toBeInstanceOf(HtmlResponse::class)
        ->and((string)$response->getBody())->toBe((string)strlen('test'));
});

test('handles float syntax', function () {
    $request = new ServerRequest();
    $handler = new RequestHandler(pi());

    $response = $handler->handle($request);

    expect($response)
        ->toBeInstanceOf(HtmlResponse::class)
        ->and((string)$response->getBody())->toBe((string)pi());
});

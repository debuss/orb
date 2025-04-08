<?php

namespace Orb;

use Borsch\Router\Contract\RouteResultInterface;
use DOMDocument;
use Laminas\Diactoros\Response\{HtmlResponse, JsonResponse, XmlResponse};
use Psr\Http\{Message\ResponseInterface, Message\ServerRequestInterface, Server\RequestHandlerInterface};
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use SimpleXMLElement;

class RequestHandler implements RequestHandlerInterface
{

    public function __construct(
        protected mixed $handler
    ) {}

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->handler instanceof ResponseInterface) {
            return $this->handler;
        }

        $container = $request->getAttribute(ContainerInterface::class);

        if (is_string($this->handler)) {
            if (str_contains($this->handler, '::') || str_contains($this->handler, '->')) {
                [$class, $method] = preg_split('/::|->/', $this->handler);
                $this->handler = [$container->get($class), $method];
            } elseif (class_exists($this->handler)) {
                $this->handler = $container->get($this->handler);
            }
        }

        $params = $request
            ->withoutAttribute(ContainerInterface::class)
            ->withoutAttribute(RouteResultInterface::class)
            ->getAttributes();

        if (is_callable($this->handler)) {
             $this->handler = call_user_func_array($this->handler, $params);
        }

        return match(true) {
            $this->handler instanceof SimpleXMLElement || $this->handler instanceof DOMDocument => new XmlResponse($this->handler->saveXML()),
            is_array($this->handler) || is_object($this->handler) => new JsonResponse($this->handler),
            default => new HtmlResponse((string)$this->handler)
        };
    }
}

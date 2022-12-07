<?php
namespace Pyncer\Http\Server;

use Countable;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Psr\Http\Message\ServerRequestInterface as PsrServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface as PsrRequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface as PsrMiddlewareInterface;
use Pyncer\Container\ContainerInterface;
use Pyncer\Http\Server\MiddlewareInterface;

interface RequestHandlerInterface extends
    ContainerInterface,
    Countable,
    PsrRequestHandlerInterface
{
    public function append(
        PsrMiddlewareInterface|MiddlewareInterface|callable ...$callable
    ): static;

    public function prepend(
        PsrMiddlewareInterface|MiddlewareInterface|callable ...$callable
    ): static;

    public function next(
        PsrServerRequestInterface $request,
        PsrResponseInterface $response
    ): PsrResponseInterface;
}

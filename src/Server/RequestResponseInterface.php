<?php
namespace Pyncer\Http\Server;

use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Pyncer\Http\Server\RequestHandlerInterface;

interface RequestResponseInterface
{
    /**
    * @return \Psr\Http\Message\ResponseInterface
    */
    public function getResponse(
        RequestHandlerInterface $handler
    ): ?PsrResponseInterface;
}

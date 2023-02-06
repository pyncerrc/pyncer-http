<?php
namespace Pyncer\Http\Server;

use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Pyncer\Http\Server\RequestHandlerInterface;

interface RequestResponseInterface
{
    /**
     * @param \Pyncer\Http\Server\RequestHandlerInterface $handler
     * @return null|\Psr\Http\Message\ResponseInterface
     */
    public function getResponse(
        RequestHandlerInterface $handler
    ): ?PsrResponseInterface;
}

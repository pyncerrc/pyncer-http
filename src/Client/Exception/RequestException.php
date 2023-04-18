<?php
namespace Pyncer\Http\Client\Exception;

use Psr\Http\Client\RequestExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Pyncer\Exception\RuntimeException;

class RequestException extends RuntimeException implements
    RequestExceptionInterface
{
    protected RequestInterface $request;

    public function __construct(
        RequestInterface $request,
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        $this->request = $request;

        parent::__construct(
            $message,
            $code,
            $previous
        );
    }

    public function getRequest(): RequestInterface
    {
        return $this->request;
    }
}

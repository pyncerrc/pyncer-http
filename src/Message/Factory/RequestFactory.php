<?php
namespace Pyncer\Http\Message\Factory;

use Psr\Http\Message\RequestFactoryInterface as PsrRequestFactoryInterface;
use Psr\Http\Message\RequestInterface as PsrRequestInterface;
use Psr\Http\Message\UriInterface as PsrUriInterface;
use Pyncer\Exception\InvalidArgumentException;
use Pyncer\Http\Message\Request;

use function is_string;

class RequestFactory implements PsrRequestFactoryInterface
{
    /**
     * @inheritdoc
     */
    public function createRequest(string $method, $uri): PsrRequestInterface
    {
        if (is_string($uri)) {
            $uri = (new UriFactory())->createUri($uri);
        }

        if (!($uri instanceof PsrUriInterface)) {
            throw new InvalidArgumentException(
                'The specified uri must be a string or Psr\Http\Message\UriInterface implementation.'
            );
        }

        return new Request($method, $uri);
    }
}

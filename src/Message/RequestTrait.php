<?php
namespace Pyncer\Http\Message;

use Psr\Http\Message\UriInterface;
use Pyncer\Exception\InvalidArgumentException;
use Pyncer\Http\Message\Uri;

use function in_array;
use function is_string;
use function preg_match;
use function strtoupper;

trait RequestTrait
{
    private ?string $requestTarget;
    private string $method;
    private ?UriInterface $uri;

    public function getRequestTarget(): string
    {
        if ($this->requestTarget !== null) {
            return $this->requestTarget;
        }

        if ($this->url === null) {
            return '/';
        }

        $target = $this->uri->getPath();

        if ($target === '') {
            $target = '/';
        }

        if ($this->uri->getQuery() !== '') {
            $target .= '?' . $this->uri->getQuery();
        }

        return $target;
    }
    protected function setRequestTarget(?string $value): static
    {
        if ($value !== null && preg_match('/\s/', $value)) {
            throw new InvalidArgumentException(
                'Invalid request target provided; cannot contain whitespace.'
            );
        }

        $this->requestTarget = $value;

        return $this;
    }

    public function withRequestTarget($requestTarget): static
    {
        if (!is_string($requestTarget)) {
            throw new InvalidArgumentException(
                'Invalid request target provided; must be a string.'
            );
        }

        $new = clone $this;
        $new->setRequestTarget($requestTarget);
        return $new;
    }

    public function getMethod(): string
    {
        return $this->method;
    }
    protected function setMethod(string $value): static
    {
        $value = strtoupper($value);

        if (!in_array($value, [
                'CONNECT',
                'DELETE',
                'GET',
                'HEAD',
                'OPTIONS',
                'PATCH',
                'POST',
                'PUT',
                'TRACE'
            ], true)
        ) {
            throw new InvalidArgumentException(sprintf(
                'Unsupported HTTP method "%s" provided.', $value
            ));
        }

        $this->method = $value;

        return $this;
    }

    public function withMethod($method): static
    {
        if (!is_string($method)) {
            throw new InvalidArgumentException(sprintf(
                'Unsupported HTTP method value "%s" provided; must be a string.',
                (is_object($method) ? $method::class : gettype($method))
            ));
        }

        $new = clone $this;
        $new->setMethod($method);
        return $new;
    }

    public function getUri(): UriInterface
    {
        return $this->uri;
    }
    protected function setUri(null|string|UriInterface $value): static
    {
        if (is_string($value)) {
            $value = new Uri($value);
        } elseif ($value !== null && !($value instanceof UriInterface)) {
            throw new InvalidArgumentException(
                'URI must be a string or Psr\Http\Message\UriInterface.'
            );
        }

        $this->uri = $value;

        return $this;
    }

    public function withUri(UriInterface $uri, $preserveHost = false): static
    {
        $new = clone $this;
        $new->setUri($uri);

        if (!$preserveHost || !$new->getHeader('Host')) {
            $host = $uri->getHost();
            if ($host) {
                $port = $this->uri->getPort();
                if ($port) {
                    $host .= ':' . $port;
                }

                $new = $new->withHeader('Host', $host);
            }
        }

        return $new;
    }
}

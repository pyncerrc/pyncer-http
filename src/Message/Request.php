<?php
namespace Pyncer\Http\Message;

use Psr\Http\Message\RequestInterface as PsrRequestInterface;
use Psr\Http\Message\UriInterface as PsrUriInterface;
use Pyncer\Http\Message\MessageTrait;
use Pyncer\Http\Message\RequestTrait;
use Pyncer\Http\Message\Headers;

use function strtolower;

class Request implements PsrRequestInterface
{
    use MessageTrait {
        getHeader as private getHeaderTrait;
        getHeaders as private getHeadersTrait;
    }
    use RequestTrait;

    public function __construct(
        string $method = 'GET',
        null|string|PsrUriInterface $uri = null,
        array|Headers $headers = [],
        mixed $body = 'php://temp',
    ) {
        $this->setMethod($method);
        $this->setUri($uri);
        $this->setHeaders($headers);
        $this->setBody($body);
    }

    /**
     * @inheritdoc
     */
    public function getHeader($header): array
    {
        if (!$this->hasHeader($header)) {
            if (strtolower($header) === 'host'&&
                $this->uri &&
                $this->uri->getHost()
            ) {
                $host = $this->uri->getHost();
                if ($this->uri->getPort()) {
                    $host .= ':' . $this->uri->getPort();
                }
                return [$host];
            }

            return [];
        }

        return $this->getHeaderTrait();
    }

    /**
     * @inheritdoc
     */
    public function getHeaders(): array
    {
        $headers = $this->getHeadersTrait();

        if (!$this->hasHeader('host') &&
            $this->uri &&
            $this->uri->getHost()
        ) {
            $host = $this->uri->getHost();
            if ($this->uri->getPort()) {
                $host .= ':' . $this->uri->getPort();
            }
            $headers['Host'] = [$host];
        }

        return $headers;
    }

    public function __clone()
    {
        $this->headers = clone $this->headers;
    }
}

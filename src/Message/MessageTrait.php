<?php
namespace Pyncer\Http\Message;

use Psr\Http\Message\StreamInterface as PsrStreamInterface;
use Pyncer\Exception\InvalidArgumentException;
use Pyncer\Http\Message\Factory\StreamFactory;
use Pyncer\Http\Message\Headers;

use function array_map;
use function array_merge;
use function implode;
use function is_array;
use function strtolower;
use function trim;

trait MessageTrait
{
    private string $protocolVersion = '1.1';
    private Headers $headers;
    private array $headerNames = [];
    private PsrStreamInterface $stream;

    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }
    protected function setProtocolVersion(string $value): static
    {
        $this->protocolVersion = $value;
        return $this;
    }

    public function withProtocolVersion($version): static
    {
        if ($this->protocolVersion === $version) {
            return $this;
        }

        $new = clone $this;
        $new->protocolVersion = $version;
        return $new;
    }

    public function getHeaders(): array
    {
        return $this->headers->getHeaders();
    }
    protected function setHeaders(array|Headers $headers): static
    {
        if (is_array($headers)) {
            $headers = new Headers($headers);
        }

        $this->headers = $headers;

        return $this;
    }

    public function hasHeader($name): bool
    {
        return $this->headers->hasHeader($name);
    }

    public function getHeader($name): array
    {
        return $this->headers->getHeader($name);
    }

    public function getHeaderLine($name): string
    {
        return $this->headers->getHeaderLine($name);
    }

    public function withHeader($name, $value): static
    {
        $new = clone $this;

        $this->headers->setHeader($name, $value);

        return $new;
    }

    public function withAddedHeader($name, $value): static
    {
        if (!$this->hasHeader($name)) {
            return $this->withHeader($name, $value);
        }

        $new = clone $this;

        $new->headers->addHeader($name. $value);

        return $new;
    }

    public function withoutHeader($name): static
    {
        if (!$this->hasHeader($name)) {
            return $this;
        }

        $new = clone $this;

        $new->headers->setHeader($name, null);

        return $new;
    }

    public function getBody(): PsrStreamInterface
    {
        return $this->stream;
    }
    protected function setBody(mixed $value): static
    {
        $this->stream = $this->cleanBody($value);
        return $this;
    }

    private function cleanBody(mixed $value): PsrStreamInterface
    {
        if ($value instanceof PsrStreamInterface) {
            return $value;
        }

        if ($value === 'php://temp') {
            return (new StreamFactory())->createStreamFromTemp();
        }

        if ($value === 'php://memory') {
            return (new StreamFactory())->createStreamFromMemory();
        }

        if ($value === 'php://input') {
            return (new StreamFactory())->createStreamFromInput();
        }

        if (is_resource($value)) {
            return (new StreamFactory())->createStreamFromResource($value);
        }

        throw new InvalidArgumentException(
            'Body must be a string stream resource identifier, an actual ' .
            'stream resource, or a Psr\Http\Message\StreamInterface ' .
            'implementation.'
        );
    }

    public function withBody(PsrStreamInterface $body): static
    {
        $new = clone $this;
        $new->setBody($body);
        return $new;
    }
}

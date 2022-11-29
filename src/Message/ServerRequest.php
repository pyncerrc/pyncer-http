<?php
namespace Pyncer\Http\Message;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Pyncer\Exception\InvalidArgumentException;
use Pyncer\Http\Message\MessageTrait;
use Pyncer\Http\Message\RequestTrait;

use function array_key_exists;
use function is_array;

class ServerRequest implements ServerRequestInterface
{
    use MessageTrait;
    use RequestTrait;

    private array $serverParams = [];
    private array $cookieParams = [];
    private array $queryParams = [];
    private array $uploadedFiles = [];
    private array $parsedBody = [];
    private array $attributes = [];

    public function __construct(
        string $method = 'GET',
        null|string|UriInterface $uri = null,
        mixed $body = 'php://input',
        array|Headers $headers = [],
        array $serverParams = []
    ) {
        $this->setMethod($method);
        $this->setUri($uri);
        $this->setBody($body);
        $this->setHeaders($headers);
        $this->setServerParams($serverParams);
    }

    /**
     * {@inheritdoc}
     */
    public function getServerParams(): array
    {
        return $this->serverParams;
    }
    protected function setServerParams(array $value): static
    {
        $this->serverParams = $value;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getCookieParams(): array
    {
        return $this->cookieParams;
    }
    protected function setCookieParams(array $value): static
    {
        $this->cookieParams = $value;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function withCookieParams(array $cookies): static
    {
        $new = clone $this;
        $new->setCookieParams($cookies);
        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function getQueryParams(): array
    {
        return $this->queryParams;
    }
    protected function setQueryParams(array $value): static
    {
        $this->queryParams = $value;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function withQueryParams(array $query): static
    {
        $new = clone $this;
        $new->setQueryParams($query);
        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function getUploadedFiles(): array
    {
        return $this->uploadedFiles;
    }
    protected function setUploadedFiles(array $value): static
    {
        if (!$this->isValidUploadedFilesArray($value)) {
            throw new InvalidArgumentException('Invalid uploaded files array.');
        }
        $this->uploadedFiles = $value;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function withUploadedFiles(array $uploadedFiles): static
    {
        $new = clone $this;
        $new->setUploadedFiles($uploadedFiles);
        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function getParsedBody(): null|array|object
    {
        return $this->parsedBody;
    }
    protected function setParsedBody($value): static
    {
        $this->parsedBody = $value;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function withParsedBody($data): static
    {
        $new = clone $this;
        $new->setParsedBody($data);
        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }
    protected function setAttributes(array $value): static
    {
        $this->attributes = $value;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttribute($name, $default = null): mixed
    {
        if (!array_key_exists($name, $this->attributes)) {
            return $default;
        }

        return $this->attributes[$name];
    }

    /**
     * {@inheritdoc}
     */
    public function withAttribute($name, $value): static
    {
        $new = clone $this;
        $new->attributes[$name] = $value;
        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function withoutAttribute($name): static
    {
        $new = clone $this;
        unset($new->attributes[$name]);
        return $new;
    }

    private function isValidUploadedFilesArray(array $uploadedFiles): bool
    {
        foreach ($uploadedFiles as $file) {
            if (is_array($file)) {
                if (!$this->isValidUploadedFilesArray($file)) {
                    return false;
                }
                continue;
            }

            if (!($file instanceof UploadedFileInterface)) {
                return false;
            }
        }

        return true;
    }

    public function __clone()
    {
        $this->headers = clone $this->headers;
    }
}

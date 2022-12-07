<?php
namespace Pyncer\Http\Message;

use Psr\Http\Message\StreamInterface as PsrStreamInterface;
use Pyncer\Exception\InvalidArgumentException;
use Pyncer\Http\Message\Factory\StreamFactory;
use Pyncer\Http\Message\Response;
use Pyncer\Http\Message\DataResponseInterface;
use Pyncer\Http\Message\Status;
use stdClass;

use function count;
use function is_array;
use function json_decode;
use function json_encode;

use const Pyncer\ENCODING as PYNCER_ENCODING;

class DataResponse extends Response implements DataResponseInterface
{
    private array $parsedBody = [];
    private bool $resetBody = true; // True when json stream body needs to be set
    private string $jsonpCallback = '';

    public function __construct(
        Status $status = Status::SUCCESS_200_OK,
        array $body = []
    ) {
        $encoding = strtolower(PYNCER_ENCODING);

        $headers = [
            'Content-Type' => 'application/json; charset=' . $encoding,
            'Content-Disposition' => 'attachment; filename="data.json"'
        ];

        parent::__construct(
            $status,
            $headers,
            'php://temp',
        );

        $this->setParsedBody($body);
    }

    public function getJsonpCallback(): string
    {
        return $this->jsonpCallback;
    }
    public function withJsonpCallback(string $value): static
    {
        $new = clone $this;
        $new->setJsonpCallback($value);
        return $new;
    }
    protected function setJsonpCallback(string $value): static
    {
        $this->jsonpCallback = $value;

        $encoding = strtolower(PYNCER_ENCODING);

        if ($this->jsonpCallback === '') {
            $this->setHeader(
                'Content-Type',
                'application/json; charset=' . $encoding
            );
            $this->setHeader(
                'Content-Disposition',
                'attachment; filename="data.json"'
            );
        } else {
            $this->setHeader(
                'Content-Type',
                'application/javascript; charset=' . $encoding
            );
            $this->setHeader('Content-Disposition', null);
        }

        return $this;
    }

    public function getParsedBody(): array
    {
        return $this->parsedBody;
    }
    public function withParsedBody(array $value): static
    {
        $new = clone $this;
        $new->setParsedBody($value);
        return $new;
    }
    protected function setParsedBody(array $value): static
    {
        if (!is_array($value)) {
            throw new InvalidArgumentException(
                'The specified parsed body value was invalid, expected array.'
            );
        }
        $this->parsedBody = $value;
        $this->resetBody = true;

        return $this;
    }
    public function getBody(): PsrStreamInterface
    {
        if ($this->resetBody) {
            if ($this->getJsonpCallback() !== '') {
                $this->setBody((new StreamFactory())->createStream(
                    $this->getJsonpCallback() . '(' . $this->jsonSerialize() . ');'
                ));
            } else {
                $this->setBody((new StreamFactory())->createStream(
                    $this->jsonSerialize()
                ));
            }
        }

        return parent::getBody();
    }
    protected function setBody(mixed $value): static
    {
        $this->resetBody = false;

        return parent::setBody($value);
    }
    public function withBody(PsrStreamInterface $body): static
    {
        if ($body->isSeekable()) {
            $body->seek(0);
        }

        $parsedBody = $body->getContents();
        $parsedBody = json_decode($parsedBody, true);

        if ($parsedBody === null) {
            throw new InvalidArgumentException('Data response body is invalid.');
        }

        $new = clone $this;
        $new->setParsedBody($parsedBody);
        return $new;
    }
    public function jsonSerialize(): mixed
    {
        $body = $this->getParsedBody();

        if (count($body) === 0) {
            // Ensure empty object vs empty array
            $body = new stdClass();
        }

        return json_encode($body);
    }
}

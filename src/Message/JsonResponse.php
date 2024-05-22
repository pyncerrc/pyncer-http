<?php
namespace Pyncer\Http\Message;

use Psr\Http\Message\StreamInterface as PsrStreamInterface;
use Pyncer\Exception\InvalidArgumentException;
use Pyncer\Http\Message\Factory\StreamFactory;
use Pyncer\Http\Message\Response;
use Pyncer\Http\Message\JsonResponseInterface;
use Pyncer\Http\Message\Status;
use stdClass;

use function count;
use function is_array;
use function json_decode;
use function json_encode;
use function strval;

use const Pyncer\ENCODING as PYNCER_ENCODING;

class JsonResponse extends Response implements JsonResponseInterface
{
    private mixed $parsedBody;
    private bool $resetBody = true; // True when json stream body needs to be set
    private string $callback = '';

    public function __construct(
        int|Status $status = Status::SUCCESS_200_OK,
        mixed $body = []
    ) {
        $encoding = strtolower(PYNCER_ENCODING);

        $headers = [
            'Content-Type' => 'application/json; charset=' . $encoding,
            'Content-Disposition' => 'filename="data.json"'
        ];

        parent::__construct(
            $status,
            $headers,
            'php://temp',
        );

        $this->setParsedBody($body);
    }

    public function getCallback(): string
    {
        return $this->callback;
    }
    protected function setCallback(string $value): static
    {
        $this->callback = $value;

        $encoding = strtolower(PYNCER_ENCODING);

        if ($this->callback === '') {
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
    public function withCallback(string $value): static
    {
        $new = clone $this;
        $new->setCallback($value);
        return $new;
    }

    public function getParsedBody(): mixed
    {
        return $this->parsedBody;
    }
    protected function setParsedBody(mixed $value): static
    {
        $this->parsedBody = $value;
        $this->resetBody = true;

        return $this;
    }
    public function withParsedBody(mixed $value): static
    {
        $new = clone $this;
        $new->setParsedBody($value);
        return $new;
    }

    public function getBody(): PsrStreamInterface
    {
        if ($this->resetBody) {
            $json = json_encode($this->jsonSerialize());

            if ($this->getCallback() !== '') {
                $this->setBody((new StreamFactory())->createStream(
                    $this->getCallback() . '(' . $json . ');'
                ));
            } else {
                $this->setBody((new StreamFactory())->createStream($json));
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
        $parsedBody = strval($response->getBody());
        $parsedBody = json_decode($parsedBody, true);

        if ($parsedBody === null) {
            throw new InvalidArgumentException(
                'JSON response body is invalid.'
            );
        }

        $new = clone $this;
        $new->setParsedBody($parsedBody);
        return $new;
    }

    public function jsonSerialize(): mixed
    {
        $body = $this->getParsedBody();

        // Ensure empty object vs empty array
        if ($body === []) {
            $body = new stdClass();
        } /* else {
            $body = $this->serialize($data);
        } */

        return $body;
    }

    /* protected function serialize(array $data): array
    {
        $serialized = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $serialized[$key] = $this->serialize($value);
            } elseif ($value instanceof JsonSerializable) {
                $serialized[$key] = $value->jsonSerialize();
            } else {
                $serialized[$key] = $value;
            }
        }

        return $serialized;
    } */
}

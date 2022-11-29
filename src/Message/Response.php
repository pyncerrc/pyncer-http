<?php
namespace Pyncer\Http\Message;

use Psr\Http\Message\ResponseInterface;
use Pyncer\Http\Message\MessageTrait;
use Pyncer\Http\Message\Status;
use Pyncer\Http\Message\StatusTrait;

class Response implements ResponseInterface
{
    use MessageTrait;
    use StatusTrait;

    public function __construct(
        Status $status = Status::SUCCESS_200_OK,
        mixed $body = 'php://temp',
        array $headers = []
    ) {
        $this->setStatus($status);
        $this->setBody($body);
        $this->setHeaders($headers);
    }

    /**
     * {@inheritdoc}
     */
    public function withStatus($code, $reasonPhrase = ''): static
    {
        $new = clone $this;

        if ($code instanceof Status) {
            $new->setStatus($code);
        } else {
            $new->setStatusCode($code);
        }
        $new->setReasonPhrase($reasonPhrase);

        return $new;
    }
}

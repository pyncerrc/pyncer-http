<?php
namespace Pyncer\Http\Message;

use Pyncer\Http\Message\Factory\StreamFactory;
use Pyncer\Http\Message\HtmlResponseInterface;
use Pyncer\Http\Message\Response;
use Pyncer\Http\Message\Status;

use function strtolower;
use function strval;

use const Pyncer\ENCODING as PYNCER_ENCODING;

class HtmlResponse extends Response implements HtmlResponseInterface
{
    public function __construct(
        Status $status = Status::SUCCESS_200_OK,
        string $body = ''
    ) {
        $encoding = strtolower(PYNCER_ENCODING);

        $headers = [
            'Content-Type' => 'text/html; charset=' . $encoding,
        ];

        $body = (new StreamFactory())->createStream($body);

        parent::__construct(
            $status,
            $headers,
            $body
        );
    }

    public function jsonSerialize(): mixed
    {
        $body = strval($this->getBody());

        if ($body === '') {
            return null;
        }

        return $body;
    }
}

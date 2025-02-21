<?php
namespace Pyncer\Http\Message;

use Pyncer\Http\Message\DataStreamInterface;
use Pyncer\Http\Message\Stream;

class FormEncodedStream extends Stream implements DataStreamInterface
{
    public function __construct(array $data)
    {
        $resource = fopen('php://temp', 'w+');

        parent::__construct($resource);

        $this->write(http_build_query($data));
        $this->seek(0);
    }

    public function getContentType(): string
    {
        return 'application/x-www-form-urlencoded';
    }
}

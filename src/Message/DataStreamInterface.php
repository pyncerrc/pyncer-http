<?php
namespace Pyncer\Http\Message;

use Psr\Http\Message\StreamInterface as PsrStreamInterface;

interface DataStreamInterface extends PsrStreamInterface
{
    public function getContentType(): string;
}

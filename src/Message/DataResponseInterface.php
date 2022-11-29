<?php
namespace Pyncer\Http\Message;

use JsonSerializable;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;

interface DataResponseInterface extends PsrResponseInterface, JsonSerializable
{
    public function getParsedBody(): array;

    public function withJsonpCallback(string $value): static;

    public function withParsedBody(array $body): static;
}

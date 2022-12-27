<?php
namespace Pyncer\Http\Message;

use JsonSerializable;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;

interface JsonResponseInterface extends PsrResponseInterface, JsonSerializable
{
    public function getParsedBody(): mixed;
    public function withParsedBody(mixed $body): static;

    public function getCallback(): string;
    public function withCallback(string $value): static;
}

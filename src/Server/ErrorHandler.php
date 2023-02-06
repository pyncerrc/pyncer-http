<?php
namespace Pyncer\Http\Server;

use Throwable;

class ErrorHandler
{
    private Throwable $exception;
    private bool $handled = false;

    public function __construct(Throwable $exception)
    {
        $this->exception = $exception;
    }

    public function getException(): Throwable
    {
        return $this->exception;
    }

    public function getHandled(): bool
    {
        return $this->handled;
    }

    public function setHandled(bool $value): void
    {
        $this->handled = $value;
    }
}

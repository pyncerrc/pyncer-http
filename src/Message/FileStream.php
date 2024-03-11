<?php
namespace Pyncer\Http\Message;

use Pyncer\Exception\RuntimeException;
use Pyncer\Http\Message\Stream;

use function fopen;
use function is_resource;
use function readfile;
use function restore_error_handler;
use function set_error_handler;

class FileStream extends Stream
{
    protected bool $useReadFile = false;

    public function __construct(
        protected string $file,
        string $mode = 'r'
    ) {
        set_error_handler(function ($errno, $errstr) {
            throw new RuntimeException(
                'Invalid file provided for stream; must be a valid path with valid permissions.'
            );
        }, E_WARNING);

        $resource = fopen($file, $mode);

        restore_error_handler();

        if (!is_resource($resource)) {
            throw new RuntimeException(
                'Resource could not be created from file.'
            );
        }

        parent::__construct($resource);
    }
    public function getFile(): string
    {
        return $this->file;
    }
    public function getUseReadFile(): bool
    {
        return $this->useReadFile;
    }
    public function setUseReadFile(bool $value): static
    {
        $this->useReadFile = $value;

        return $this;
    }
    public function readFile(): void
    {
        readfile($this->file);
    }
}

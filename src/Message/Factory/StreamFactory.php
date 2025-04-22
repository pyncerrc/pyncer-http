<?php
namespace Pyncer\Http\Message\Factory;

use Psr\Http\Message\StreamFactoryInterface as PsrStreamFactoryInterface;
use Psr\Http\Message\StreamInterface as PsrStreamInterface;
use Pyncer\Exception\RuntimeException;
use Pyncer\Http\Message\DataStreamInterface;
use Pyncer\Http\Message\MultipartStream;
use Pyncer\Http\Message\FormEncodedStream;
use Pyncer\Http\Message\FileStream;
use Pyncer\Http\Message\Stream;

use function is_resource;
use function fopen;

class StreamFactory implements PsrStreamFactoryInterface
{
    /**
     * @inheritdoc
     */
    public function createStream(string $content = ''): PsrStreamInterface
    {
        $stream = $this->createStreamFromTemp('w+');

        $stream->write($content);
        $stream->seek(0);

        return $stream;
    }

    /**
     * @inheritdoc
     */
    public function createStreamFromFile(string $file, string $mode = 'r'): PsrStreamInterface
    {
        return new FileStream($file, $mode);
    }

    /**
     * @inheritdoc
     */
    public function createStreamFromResource($resource): PsrStreamInterface
    {
        if (!is_resource($resource)) {
            throw new InvalidArgumentException('The specified resource is not a resource.');
        }

        return new Stream($resource);
    }

    public function createStreamFromTemp(string $mode = 'w+'): PsrStreamInterface
    {
        $resource = fopen('php://temp', $mode);

        if (!is_resource($resource)) {
            throw new RuntimeException('Temporary file stream could not be opened.');
        }

        return new Stream($resource);
    }

    public function createStreamFromMemory(string $mode = 'w+'): PsrStreamInterface
    {
        $resource = fopen('php://memory', $mode);

        if (!is_resource($resource)) {
            throw new RuntimeException('Memory stream could not be opened.');
        }

        return new Stream($resource);
    }

    public function createStreamFromInput(): PsrStreamInterface
    {
        $resource = fopen('php://input', 'r');

        if (!is_resource($resource)) {
            throw new RuntimeException('Input stream could not be opened.');
        }

        return new Stream($resource);
    }

    public function createStreamFromData(array $data): DataStreamInterface
    {
        $isMultipart = false;

        foreach ($data as $value) {
            if ($value instanceof PsrStreamInterface) {
                $isMultipart = true;
                break;
            }
        }

        if ($isMultipart) {
            return new MultipartStream($data);
        }

        return new FormEncodedStream($data);
    }
}

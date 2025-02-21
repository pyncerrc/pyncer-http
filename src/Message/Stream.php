<?php
namespace Pyncer\Http\Message;

use Psr\Http\Message\StreamInterface as PsrStreamInterface;
use Pyncer\Exception\InvalidArgumentException;
use Pyncer\Exception\RuntimeException;
use Throwable;

use function array_key_exists;
use function clearstatcache;
use function fclose;
use function fread;
use function fseek;
use function ftell;
use function fwrite;
use function is_resource;
use function stream_get_contents;
use function stream_get_meta_data;
use function strstr;
use function var_export;

class Stream implements PsrStreamInterface
{
    protected $stream;
    protected ?int $size;
    protected ?string $uri;
    protected bool $isSeekable;
    protected bool $isReadable;
    protected bool $isWritable;

    public function __construct($stream)
    {
        if (!is_resource($stream)) {
            throw new InvalidArgumentException('Stream must be a resource.');
        }

        $this->stream = $stream;

        $metaData = stream_get_meta_data($this->stream);

        $this->size = null;

        $this->uri = $metaData['uri'];

        $this->isSeekable = $metaData['seekable'];

        $mode = $metaData['mode'];
        $this->isReadable = (strstr($mode, 'r') !== false || strstr($mode, '+') !== false);
        $this->isWritable = (strstr($mode, 'w') !== false || strstr($mode, '+') !== false);
    }

    public function __destruct()
    {
        $this->close();
    }

    /**
     * @inheritdoc
     */
    public function __toString(): string
    {
        try {
            if ($this->isSeekable()) {
                $this->seek(0);
            }

            return $this->getContents();
        } catch (Throwable $e) {
            return '';
        }
    }

    /**
     * @inheritdoc
     */
    public function close(): void
    {
        if ($this->stream !== null) {
            if (is_resource($this->stream)) {
                fclose($this->stream);
            }

            $this->detach();
        }
    }

    /**
     * @inheritdoc
     */
    public function detach(): mixed
    {
        if ($this->stream === null) {
            return null;
        }

        $stream = $this->stream;

        $this->stream = null;
        $this->size = null;
        $this->uri = null;
        $this->isReadable = false;
        $this->isWritable = false;
        $this->isSeekable = false;

        return $stream;
    }

    /**
     * @inheritdoc
     */
    public function getSize(): ?int
    {
        if ($this->size !== null) {
            return $this->size;
        }

        if ($this->stream === null) {
            return null;
        }

        if ($this->uri) {
            clearstatcache(true, $this->uri);
        }

        $stats = fstat($this->stream);
        if ($stats) {
            $this->size = $stats['size'];
            return $this->size;
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function tell(): int
    {
        if ($this->stream === null) {
            throw new RuntimeException('Stream is detached.');
        }

        $tell = ftell($this->stream);

        if ($tell === false) {
            throw new RuntimeException('Unable to get position of stream pointer.');
        }

        return $tell;
    }

    /**
     * @inheritdoc
     */
    public function eof(): bool
    {
        return ($this->stream === null || feof($this->stream));
    }

    /**
     * @inheritdoc
     */
    public function isSeekable(): bool
    {
        return $this->isSeekable;
    }

    /**
     * @inheritdoc
     */
    public function seek($offset, $whence = SEEK_SET): void
    {
        if (!$this->isSeekable()) {
            throw new RuntimeException('Stream is not seekable');
        }

        if (fseek($this->stream, $offset, $whence) === -1) {
            throw new RuntimeException(sprintf(
                'Unable to seek to stream offset %s with whence %s.',
                $offset,
                var_export($whence, true)
            ));
        }
    }

    /**
     * @inheritdoc
     */
    public function rewind(): void
    {
        $this->seek(0);
    }

    /**
     * @inheritdoc
     */
    public function isWritable(): bool
    {
         return $this->isWritable;
    }

    /**
     * @inheritdoc
     */
    public function write($string): int
    {
        if (!$this->isWritable()) {
            throw new RuntimeException('Stream is not writable.');
        }

        // Reset size so it will be updated
        $this->size = null;

        $bytes = fwrite($this->stream, $string);
        if ($bytes === false) {
            throw new RuntimeException('Unable to write to stream.');
        }

        return $bytes;
    }

    /**
     * @inheritdoc
     */
    public function isReadable(): bool
    {
        return $this->isReadable;
    }

    /**
     * @inheritdoc
     */
    public function read($length): string
    {
        if (!$this->isReadable()) {
            throw new RuntimeException('Stream is not readable.');
        }

        return fread($this->stream, $length);
    }

    /**
     * @inheritdoc
     */
    public function getContents(): string
    {
        if ($this->stream === null) {
            throw new RuntimeException('Stream is detached.');
        }

        if (!$this->isReadable()) {
            throw new RuntimeException('Stream is is not readable.');
        }

        $contents = stream_get_contents($this->stream);

        if ($contents === false) {
            throw new RuntimeException('Unable to read stream.');
        }

        return $contents;
    }

    /**
     * @inheritdoc
     */
    public function getMetadata($key = null): mixed
    {
        if ($this->stream === null) {
            return ($key !== null ? null : []);
        }

        $metaData = stream_get_meta_data($this->stream);

        if (!$key) {
            return $metaData;
        }

        if (array_key_exists($key, $metaData[$key])) {
            return $metaData[$key];
        }

        return null;
    }
}

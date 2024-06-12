<?php
namespace Pyncer\Http\Message;

use Psr\Http\Message\StreamInterface as PsrStreamInterface;
use Psr\Http\Message\UploadedFileInterface as PsrUploadedFileInterface;
use Pyncer\Exception\InvalidArgumentException;
use Pyncer\Exception\RuntimeException;
use Pyncer\Http\Message\Factory\StreamFactory;
use Pyncer\Http\Message\Stream;

use function copy;
use function fclose;
use function filesize;
use function fopen;
use function fwrite;
use function is_string;
use function is_uploaded_file;
use function move_uploaded_file;
use function rename;
use function strpos;
use function unlink;

use const UPLOAD_ERR_OK;
use const PHP_SAPI;

class UploadedFile implements PsrUploadedFileInterface
{
    private ?PsrStreamInterface $stream;
    private ?string $file;
    private int $size;
    private int $error;
    private string $clientFilename;
    private string $clientMediaType;
    private bool $hasMoved = false;

    public function __construct(
        string|PsrStreamInterface $streamOrFile,
        ?int $size = null,
        int $error = UPLOAD_ERR_OK,
        ?string $clientFilename = null,
        ?string $clientMediaType = null
    ) {
        if (is_string($streamOrFile)) {
            $this->file = $streamOrFile;

            if ($size === null) {
                $size = filesize($this->file);
            }
        } else {
            $this->stream = $streamOrFile;

            if ($size === null) {
                $size = $stream->getSize();
            }
        }

        $this->size = $size;

        if ($error < 0 || $error > 8) {
            throw new InvalidArgumentException(
                'Invalid error specified; must be an UPLOAD_ERR_* constant.'
            );
        }
        $this->error = $error;

        $this->clientFilename = $clientFilename;

        $this->clientMediaType = $clientMediaType;
    }

    /**
     * @inheritdoc
     */
    public function getStream(): PsrStreamInterface
    {
        if ($this->hasMoved) {
            throw new RuntimeException('Cannot retrieve stream after it has already been moved.');
        }

        if ($this->stream instanceof PsrStreamInterface) {
            return $this->stream;
        }

        $this->stream = (new StreamFactory())->createStreamFromFile($this->file);

        return $this->stream;
    }
    public function getFile(): ?string
    {
        return $this->file;
    }

    /**
     * @inheritdoc
     */
    public function moveTo(string $targetPath): void
    {
        if (!is_string($targetPath) || $targetPath === '') {
            throw new InvalidArgumentException(
                'Invalid path specified; must be a non-empty string.'
            );
        }

        if ($this->hasMoved) {
            throw new RuntimeException('Cannot move file because it has already been moved.');
        }

        if ($this->file) {
            if (strpos($targetPath, '://') !== false) {
                if (!is_writable(dirname($targetPath))) {
                    throw new InvalidArgumentException('File upload target path is not writable');
                }

                if (!copy($this->file, $targetPath)) {
                    throw new RuntimeException('Could not move uploaded file.');
                }

                if (!unlink($this->file)) {
                    throw new RuntimeException('Uploaded file could not be removed after copy.');
                }
            } elseif (PHP_SAPI === 'cli') {
                if (!rename($this->file, $targetPath)) {
                    throw new RuntimeException('Could not move uploaded file.');
                }
            } else {
                if (!is_uploaded_file($this->file)) {
                    throw new RuntimeException('File is not an uploaded file.');
                }

                if (@move_uploaded_file($this->file, $targetPath) === false) {
                    throw new RuntimeException('Could not move uploaded file.');
                }
            }
        } else {
            $this->writeFile($targetPath);
        }

        $this->hasMoved = true;
    }

    /**
     * @inheritdoc
     */
    public function getSize(): ?int
    {
        return $this->size;
    }

    /**
     * @inheritdoc
     */
    public function getError(): int
    {
        return $this->error;
    }

    /**
     * @inheritdoc
     */
    public function getClientFilename(): ?string
    {
        return $this->clientFilename;
    }

    /**
     * @inheritdoc
     */
    public function getClientMediaType(): ?string
    {
        return $this->clientMediaType;
    }

    /**
     * Write internal stream to given path
     *
     * @param string $path
     */
    private function writeFile(string $path): void
    {
        $handle = fopen($path, 'wb+');

        if ($handle === false) {
            throw new RuntimeException('Unable to write to the specified path.');
        }

        $this->stream->rewind();

        while (!$this->stream->eof()) {
            fwrite($handle, $this->stream->read(4096));
        }

        fclose($handle);
    }
}

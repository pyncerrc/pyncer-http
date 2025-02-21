<?php
namespace Pyncer\Http\Message;

use finfo;
use Psr\Http\Message\StreamInterface as PsrStreamInterface;
use Pyncer\Http\Message\DataStreamInterface;
use Pyncer\Http\Message\Factory\StreamFactory;
use Pyncer\Http\Message\FileStream;
use Pyncer\Http\Message\Stream;

use const FILEINFO_MIME_TYPE;

class MultipartStream extends Stream implements DataStreamInterface
{
    protected string $boundary;

    public function __construct(array $data, ?string $boundary = null)
    {
        $this->boundary = $boundary ?: bin2hex(random_bytes(20));

        $stream = fopen('php://temp', 'w+');

        parent::__construct($stream);

        $finfo = null;

        foreach ($data as $name => $part) {
            $this->write('--' . $this->boundary . "\r\n");

            if ($part instanceof FileStream) {
                if ($finfo === null) {
                    $finfo = new finfo(FILEINFO_MIME_TYPE);
                }

                $mediaType = $finfo->file($part->getFile());
                $filename = basename($part->getFile());

                $this->write('Content-Disposition: form-data; name="' . $name . '"; filename="' . $filename . '"' . "\r\n");
                $this->write('Content-Type: ' . $mediaType . "\r\n\r\n");
                stream_copy_to_stream($part->detach(), $this->stream);
            } elseif ($part instanceof PsrStreamInterface) {
                $this->write('Content-Disposition: form-data; name="' . $name . '"' . "\r\n\r\n");
                stream_copy_to_stream($part->detach(), $this->stream);
            } else {
                $this->write('Content-Disposition: form-data; name="' . $name . '"' . "\r\n\r\n");
                $this->write(strval($part));
            }

            $this->write("\r\n");
        }

        $this->write('--' . $this->boundary . '--' . "\r\n");
        $this->seek(0);
    }

    public function getBoundary(): string
    {
        return $this->boundary;
    }

    public function getContentType(): string
    {
        return 'multipart/form-data; boundary=' . $this->getBoundary();
    }
}

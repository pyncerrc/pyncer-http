<?php
namespace Pyncer\Http\Message;

use function Pyncer\Array\set_recursive as array_set_recursive;

use function array_key_exists;
use function array_pop;
use function array_flip;
use function explode;
use function fclose;
use function fwrite;
use function ini_get;
use function intval;
use function is_numeric;
use function preg_match;
use function preg_quote;
use function preg_replace;
use function preg_split;
use function rtrim;
use function stream_get_meta_data;
use function strpos;
use function strtolower;
use function substr;
use function tmpfile;
use function trim;
use function Pyncer\IO\filesize_from_string as pyncer_filesize_from_string;

class MultipartData
{
    private array $data = [];
    private array $files = [];

    public function __construct(string $body, string $boundary)
    {
        $bodyParts = preg_split('/\\R?-+' . preg_quote($boundary, '/') . '/s', $body);

        array_pop($bodyParts); // Trailing --

        foreach ($bodyParts as $bodyPart) {
            if ($bodyPart === '') {
                continue;
            }

            list ($headers, $value) = preg_split('/\\R\\R/', $bodyPart, 2);

            $headers = $this->parseHeaders($headers);

            if (!isset($headers['content-disposition']['name'])) {
                continue;
            }

            if (isset($headers['content-disposition']['filename'])) {
                $file = [
                    'name' => $headers['content-disposition']['filename'],
                    'type' => (
                        array_key_exists('content-type', $headers) ?
                        $headers['content-type'] :
                        'application/octet-stream'
                    ),
                    'size' => pyncer_filesize_from_string($value),
                    'error' => UPLOAD_ERR_OK,
                    'tmp_name' => null,
                    // tmpfile will get removed on fclose, so keep track of handle
                    'tmp_handle' => null
                ];

                if ($file['size'] > $this->getMaxFileSize()) {
                    $file['error'] = UPLOAD_ERR_INI_SIZE;
                } else {
                    $temporaryHandle = tmpfile();

                    if ($temporaryHandle === false) {
                        $file['error'] = UPLOAD_ERR_CANT_WRITE;
                    } else {
                        $meta = stream_get_meta_data($temporaryHandle);

                        $temporaryFilename = $meta['uri'];

                        if ($temporaryFilename === '') {
                            $file['error'] = UPLOAD_ERR_CANT_WRITE;

                            fclose($temporaryHandle);
                        } else {
                            fwrite($temporaryHandle, $value);

                            $file['tmp_name'] = $temporaryFilename;
                            $file['tmp_handle'] = $temporaryHandle;
                        }
                    }
                }

                $keys = $this->getNameArray($headers['content-disposition']['name']);
                $this->files = array_set_recursive($this->files, $keys, $file);
                continue;
            }

            $keys = $this->getNameArray($headers['content-disposition']['name']);
            $this->data = array_set_recursive($this->data, $keys, $value);
        }
    }

    private function getNameArray($name): array
    {
        $parts = explode('[', $name);

        foreach ($parts as $key => $value) {
            $parts[$key] = rtrim($value, ']');
        }

        return $parts;
    }

    public static function boundaryFromContentType(string $contentType): ?string
    {
        if (strpos($contentType, 'multipart/form-data') === false) {
            return null;
        }

        if (!preg_match('/boundary=(.*)$/is', $contentType, $matches)) {
            return null;
        }

        return $matches[1];
    }

    public function getData(): array
    {
        return $this->data;
    }
    public function getFiles(): array
    {
        return $this->files;
    }

    private function parseHeaders(string $headers): array
    {
        $result = [];

        $headerParts = preg_split('/\\R/s', $headers, -1, PREG_SPLIT_NO_EMPTY);

        foreach ($headerParts as $headerPart) {
            if (strpos($headerPart, ':') === false) {
                continue;
            }

            list ($headerName, $headerValue) = explode(':', $headerPart, 2);

            $headerName = strtolower(trim($headerName));
            $headerValue = trim($headerValue);

            if (strpos($headerValue, ';') === false) {
                $result[$headerName] = $headerValue;
            } else {
                $result[$headerName] = [];

                $parts = explode(';', $headerValue);
                foreach ($parts as $part) {
                    $part = trim($part);

                    if (strpos($part, '=') === false) {
                        $result[$headerName][] = $part;
                    } else {
                        list ($name, $value) = explode('=', $part, 2);

                        $name = strtolower(trim($name));
                        $value = trim(trim($value), '"');

                        $result[$headerName][$name] = $value;
                    }
                }
            }
        }

        return $result;
    }
    private function getMaxFilesize(): ?int
    {
        $maxFilesize = ini_get('upload_max_filesize');

        $units = ['B', 'K', 'M', 'G', 'T', 'P'];
        $unitsExtended = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];

        $number = intval(preg_replace("/[^0-9]+/", '', $maxFilesize));
        $suffix = preg_replace("/[^a-zA-Z]+/", '', $maxFilesize);

        // B or no suffix
        if (is_numeric(substr($suffix, 0, 1))) {
            return preg_replace('/[^\d]/', '', $maxFilesize);
        }

        $exponent = array_flip($units)[$suffix] ?? null;

        if ($exponent === null) {
            $exponent = array_flip($unitsExtended)[$suffix] ?? null;
        }

        if ($exponent === null) {
            return null;
        }

        return $number * (1024 ** $exponent);
    }
}

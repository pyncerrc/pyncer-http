<?php
namespace Pyncer\Http\Message\Factory;

use Psr\Http\Message\StreamInterface as PsrStreamInterface;
use Psr\Http\Message\UploadedFileFactoryInterface as PsrUploadedFileFactoryInterface;
use Psr\Http\Message\UploadedFileInterface as PsrUploadedFileInterface;
use Pyncer\Exception\InvalidArgumentException;
use Pyncer\Http\Message\UploadedFile;

use function array_keys;
use function array_pop;
use function array_push;
use function array_reverse;
use function is_array;
use function Pyncer\Array\get_recursive as pyncer_array_get_recursive;
use function Pyncer\Array\set_recursive as pyncer_array_set_recursive;

class UploadedFileFactory implements PsrUploadedFileFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function createUploadedFile(
        PsrStreamInterface $stream,
        ?int $size = null,
        int $error = UPLOAD_ERR_OK,
        ?string $clientFilename = null,
        ?string $clientMediaType = null
    ): PsrUploadedFileInterface {
        if (!$stream->isReadable()) {
            throw new InvalidArgumentException('File resource is not readable.');
        }

        return new UploadedFile(
            $stream,
            $size,
            $error,
            $clientFilename,
            $clientMediaType
        );
    }

    public function createFromGlobals(array $files): array
    {
        $uploadedFiles = [];

        foreach ($files as $key => $file) {
            $uploadedFiles[$key] = $this->normalizeFile($file);
        }

        return $uploadedFiles;
    }

    private function normalizeFile(array $file)
    {
        // If not an array of files, then simply return an UploadedFile instance
        if (!is_array($file["error"])) {
            return new UploadedFile(
                $file['tmp_name'],
                $file['size'],
                $file['error'],
                $file['name'],
                $file['type']
            );
        }

        $normalizedFiles = [];

        $fileKeys = array_keys($file);

        $currentKeys = [
            array_reverse(array_keys($file["error"]))
        ];
        $index = 0;
        $key = array_pop($currentKeys[$index]);
        $nestedKeys = [$key];

        while (true) {
            $value = pyncer_array_get_recursive($file["error"], $nestedKeys);

            // Multiple file upload with array indexes ex. files[index][]
            if (is_array($value)) {
                $currentKeys[] = array_reverse(array_keys($value));
                ++$index;
                $key = array_pop($currentKeys[$index]);
            } else {
                $newFile = [];
                foreach ($fileKeys as $file_key) {
                    $newFile[$file_key] = pyncer_array_get_recursive(
                        $file[$file_key],
                        $nestedKeys
                    );
                }

                $newFile = new UploadedFile(
                    $newFile['tmp_name'],
                    $newFile['size'],
                    $newFile['error'],
                    $newFile['name'],
                    $newFile['type']
                );

                $normalizedFiles = pyncer_array_set_recursive(
                    $normalizedFiles,
                    $nestedKeys,
                    $newFile
                );

                array_pop($nestedKeys);
                $key = array_pop($currentKeys[$index]);
            }

            if (isset($key)) {
                array_push($nestedKeys, $key);
                continue;
            }

            while (!isset($key)) {
                unset($currentKeys[$index]);
                --$index;

                // No more items so break out of main loop
                if ($index < 0) {
                    break 2;
                }

                array_pop($nestedKeys);
                $key = array_pop($currentKeys[$index]);
            }
        }

        return $normalizedFiles;
    }

}

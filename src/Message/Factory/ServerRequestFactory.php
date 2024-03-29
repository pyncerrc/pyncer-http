<?php
namespace Pyncer\Http\Message\Factory;

use Psr\Http\Message\ServerRequestFactoryInterface as PsrServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface as PsrServerRequestInterface;
use Psr\Http\Message\UriInterface as PsrUriInterface;
use Pyncer\Exception\InvalidArgumentException;
use Pyncer\Http\Message\Factory\UploadedFileFactory;
use Pyncer\Http\Message\Factory\UriFactory;
use Pyncer\Http\Message\Headers;
use Pyncer\Http\Message\MultipartData;
use Pyncer\Http\Message\ServerRequest;

use function file_get_contents;
use function function_exists;
use function getallheaders;
use function in_array;
use function is_string;
use function Pyncer\nullify as pyncer_nullify;
use function str_replace;
use function strpos;
use function strstr;
use function strtolower;
use function strtoupper;
use function substr;
use function ucwords;

class ServerRequestFactory implements PsrServerRequestFactoryInterface
{
    /**
     * @inheritdoc
     */
    public function createServerRequest(
        string $method,
        $uri,
        array $serverParams = []
    ): PsrServerRequestInterface
    {
        if (is_string($uri)) {
            $uri = (new UriFactory())->createUri($uri);
        }

        if (!$uri instanceof PsrUriInterface) {
            throw new InvalidArgumentException(
                'The specified uri must be a string or Psr\Http\Message\UriInterface implementation.'
            );
        }

        return new ServerRequest(
            method: $method,
            uri: $uri,
            serverParams: $serverParams
        );
    }

    public function createServerRequestFromGlobals()
    {
        $serverParams = $this->normalizeServerParams($_SERVER);

        $method = $serverParams['REQUEST_METHOD'] ?? 'GET';
        $method = strtoupper($method);

        $headers = $this->createServerRequestHeaders($serverParams);

        $overrideMethod = $this->overrideMethod($method, $serverParams, $headers);

        $uri = (new UriFactory())->createUriFromServerParams($serverParams);

        $request = new ServerRequest(
            method: $overrideMethod,
            uri: $uri,
            headers: $headers,
            serverParams: $serverParams
        );

        // Only requred if not originally POST
        if (in_array($method, ['PATCH', 'PUT'])) {
            $contentType = $headers->getHeaderLine('Content-Type');

            $boundary = MultipartData::boundaryFromContentType(
                $contentType
            );

            if ($boundary) {
                $multipart = new MultipartData(
                    file_get_contents('php://input'),
                    $boundary
                );
                $_POST = $multipart->getData();
                $_FILES = $multipart->getFiles();
            } elseif ($contentType === 'application/x-www-form-urlencoded' ||
                str_starts_with($contentType, 'application/x-www-form-urlencoded;')
            ) {
                parse_str(file_get_contents("php://input"), $_POST);
            }
        }

        $files = (new UploadedFileFactory())->createFromGlobals($_FILES);
        $request = $request->withUploadedFiles($files);

        $request = $request->withCookieParams($_COOKIE);
        $request = $request->withQueryParams($_GET);

        if (in_array($method, ['PATCH', 'POST', 'PUT'])) {
            $contentType = $headers->getHeaderLine('Content-Type');

            if ($contentType === 'application/json' ||
                str_starts_with($contentType, 'application/json;')
            ) {
                $data = json_decode(
                    file_get_contents('php://input'),
                    true
                );

                if (pyncer_nullify($data) === null) {
                    $data = [];
                } elseif (!is_array($data)) {
                    $data = [$data];
                }

                $request = $request->withParsedBody($data);
            } else {
                $request = $request->withParsedBody($_POST);
            }
        }

        return $request;
    }

    private function overrideMethod(
        string $method,
        array $serverParams,
        Headers $headers
    ): string
    {
        $override = null;

        // Override via POST input
        if ($method === 'POST') {
            $override = $_POST['_method'] ?? '';
            $override = ($override !== '' ? $overide : null);
        }

        // X-HTTP-Method-Override
        $overrideHeader = $headers->getHeaderLine('X-HTTP-Method-Override');
        if ($overrideHeader !== '') {
            $override = $overrideHeader;
        }

        // Only allow overrides where body can make sense
        if ($override !== null) {
            $override = strtoupper($override);

            if ($method === 'GET') {
                if ($override !== 'DELETE') {
                    $override = null;
                }
            } elseif (!in_array($override, ['PATCH', 'PUT'])) {
                $override = null;
            }
        }

        return $override ?? $method;
    }

    private function normalizeServerParams(array $serverParams): array
    {
        // This seems to be the only way to get the Authorization header on Apache
        if (isset($serverParams['HTTP_AUTHORIZATION']) ||
            !function_exists('getallheaders')
        ) {
            return $serverParams;
        }

        $headers = getallheaders();
        if (isset($headers['Authorization'])) {
            $serverParams['HTTP_AUTHORIZATION'] = $headers['Authorization'];
        }

        return $serverParams;
    }

    public function createServerRequestHeaders(
        array $serverParams = []
    ): Headers
    {
        $headers = [];

        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            if ($headers !== false) {
                return new Headers($headers);
            }
        }

        foreach ($serverParams as $key => $value) {
            if (!is_array($value) && trim(strval($value)) === '') {
                continue;
            }

            if (str_starts_with($key, 'HTTP_')) {
                $name = strtr(substr($key, 5), '_', ' ');
                $name = strtr(ucwords(strtolower($name)), ' ', '-');
                $name = str_replace('Http-', 'HTTP-', $name);
                $headers[$name] = $value;
                continue;
            }

            if (str_starts_with($key, 'CONTENT_')) {
                $name = substr($key, 8); // Content-
                $name = 'Content-' . (
                    $name == 'MD5' ?
                    $name :
                    ucfirst(strtolower($name))
                );
                $headers[$name] = $value;
                continue;
            }
        }

        return new Headers($headers);
    }
}

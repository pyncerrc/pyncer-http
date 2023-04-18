<?php
namespace Pyncer\Http\Client;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Pyncer\Http\Message\Factory\StreamFactory;
use Pyncer\Http\Message\Response;
use Pyncer\Http\Client\Exception\NetworkException;
use Pyncer\Http\Client\Exception\RequestException;

use const CURL_HTTP_VERSION_1_0;
use const CURL_HTTP_VERSION_1_1;
use const CURL_HTTP_VERSION_2_0;

use const CURLE_COULDNT_CONNECT;
use const CURLE_COULDNT_RESOLVE_HOST;
use const CURLE_COULDNT_RESOLVE_PROXY;
use const CURLE_GOT_NOTHING;
use const CURLE_OPERATION_TIMEOUTED;
use const CURLE_SSL_CONNECT_ERROR;

use const CURLOPT_CONNECTTIMEOUT;
use const CURLOPT_CUSTOMREQUEST;
use const CURLOPT_FOLLOWLOCATION;
use const CURLOPT_HEADER;
use const CURLOPT_HEADERFUNCTION;
use const CURLOPT_HTTP_VERSION;
use const CURLOPT_HTTPHEADER;
use const CURLOPT_INFILESIZE;
use const CURLOPT_NOBODY;
use const CURLOPT_POSTFIELDS;
use const CURLOPT_PROTOCOLS;
use const CURLOPT_READFUNCTION;
use const CURLOPT_RETURNTRANSFER;
use const CURLOPT_TIMEOUT;
use const CURLOPT_UPLOAD;
use const CURLOPT_URL;
use const CURLOPT_WRITEFUNCTION;

use const CURLPROTO_HTTP;
use const CURLPROTO_HTTPS;

use const FILTER_VALIDATE_URL;
use const FILTER_FLAG_SCHEME_REQUIRED;
use const FILTER_FLAG_HOST_REQUIRED;

class Client implements ClientInterface
{
    // https://curl.se/libcurl/c/libcurl-errors.html
    private static array $networkErrorCodes = [
        CURLE_COULDNT_CONNECT,
        CURLE_COULDNT_RESOLVE_HOST,
        CURLE_COULDNT_RESOLVE_PROXY,
        CURLE_GOT_NOTHING,
        CURLE_OPERATION_TIMEOUTED,
        CURLE_SSL_CONNECT_ERROR,
    ];

    // Future constructor options
    private ?int $connectTimeout = 180; // 3 minutes
    private int $maxStringBodySize = 1000000; // 1MB
    private ?int $timeout = null;

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->assertValidRequest($request);

        $ch = curl_init();

        // Set some defaults
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);

        if ($this->timeout !== null) {
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        }

        if ($this->connectTimeout !== null) {
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connectTimeout);
        }

        // Url
        $url = strval($request->getUri()->withFragment(''));
        curl_setopt($ch, CURLOPT_URL, $url);

        // Method
        $method = $request->getMethod();
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        // Limit to HTTP and HTTPS for now.
        curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);

        // Protocol version
        $protocolVersion = $request->getProtocolVersion();
        if ($protocolVersion === '2.0') {
            curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2_0);
        } elseif ($protocolVersion === '1.1') {
            curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        } else {
            curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        }

        // Body
        if ($method === 'POST' || $method === 'PUT' || $method === 'PATCH') {
            $requestBody = $request->getBody();

            $size = $requestBody->getSize();
            if ($request->hasHeader('Content-Length')) {
                $size = intval($request->getHeaderLine('Content-Length'));
            }

            if ($size === 0) {
                $request = $request->withHeader('Content-Length', 0);
            } elseif ($size !== null && $size <= $this->maxStringBodySize) {
                // Send as string
                curl_setopt($ch, CURLOPT_POSTFIELDS, strval($requestBody));

                $request = $request->withoutHeader('Content-Length');
                $request = $request->withoutHeader('Transfer-Encoding');
            } else {
                // Send as file upload
                curl_setopt($ch, CURLOPT_UPLOAD, true);

                if ($size !== null) {
                    curl_setopt($ch, CURLOPT_INFILESIZE, $size);
                    $request = $request->withoutHeader('Content-Length');
                }

                if ($requestBody->isSeekable()) {
                    $requestBody->rewind();
                }

                curl_setopt($ch, CURLOPT_READFUNCTION, static function ($ch, $stream, $length) use ($requestBody) {
                    return $requestBody->read($length);
                });
            }
        } elseif ($method === 'HEAD') {
            curl_setopt($ch, CURLOPT_NOBODY, true);
        }

        // Headers
        $requestHeaders = [];

        foreach ($request->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                if ($value === '') {
                    // Special format for empty headers in curl.
                    $requestHeaders[] = $name . ';';
                } else {
                    $requestHeaders[] = $name . ': ' . $value;
                }
            }
        }

        if ($requestHeaders) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $requestHeaders);
        }

        $responseVersion = '1.1';
        $responseStatus = 200;
        $responseHeaders = [];
        $responseBody = (new StreamFactory())->createStream();

        curl_setopt($ch, CURLOPT_HEADERFUNCTION, static function (
            $ch,
            $hdata
        ) use (
            &$responseVersion,
            &$responseStatus,
            &$responseHeaders
        ) {
            $data = trim($hdata);

            if ($data !== '') {
                if (str_starts_with(strtolower($data), 'http/')) {
                    $parts = explode(' ', $data);
                    $responseVersion = explode('/', $parts[0])[1];
                    $responseStatus = intval($parts[1]);
                } else {
                    $parts = explode(':', $data, 2);
                    $parts = array_map(trim(...), $parts);

                    $responseHeaders[$parts[0]][] = $parts[1] ?? '';
                }
            }

            return strlen($hdata);
        });

        curl_setopt($ch, CURLOPT_WRITEFUNCTION, static function (
            $ch,
            $bdata
        ) use (
            $responseBody
        ) {
            return $responseBody->write($bdata);
        });

        curl_exec($ch);

        $errorCode = curl_errno($ch);
        if ($errorCode) {
            $errorMessage = curl_error($ch);

            curl_close($ch);

            if (in_array($errorCode, static::$networkErrorCodes)) {
                throw new NetworkException($request, $errorMessage);
            } else {
                throw new RequestException($request, $errorMessage);
            }
        }

        curl_close($ch);

        return new Response(
            $responseStatus,
            $responseHeaders,
            $responseBody
        );
    }

    protected function assertValidRequest(RequestInterface $request): void
    {
        $url = strval($request->getUri());

        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            throw new RequestException(
                $request,
                'Invalid request URL. (' . $url . ')'
            );
        }
    }
}

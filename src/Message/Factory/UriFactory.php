<?php
namespace Pyncer\Http\Message\Factory;

use Psr\Http\Message\UriFactoryInterface as PsrUriFactoryInterface;
use Psr\Http\Message\UriInterface as PsrUriInterface;
use Pyncer\Exception\InvalidArgumentException;
use Pyncer\Http\Message\Uri;

use function explode;
use function intval;
use function preg_match;
use function strpos;
use function strval;
use function substr;

class UriFactory implements PsrUriFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function createUri(string $uri = ''): PsrUriInterface
    {
        return new Uri($uri);
    }

    public function createUriFromServerParams(array $serverParams)
    {
        if (isset($serverParams['HTTPS']) && $serverParams['HTTPS'] === 'on') {
            $scheme = 'https';
        } else {
            $scheme = 'http';
        }

        if (isset($serverParams['HTTP_HOST'])) {
            $host = $serverParams['HTTP_HOST'];
        } elseif (isset($serverParams['SERVER_NAME'])) {
            // This doesn't always match the current url
            // ie. not including www.
            $host = $serverParams['SERVER_NAME'];
        }

        $port = $serverParams['SERVER_PORT'] ?? '';

        if (preg_match('/\A(\[[a-fA-F0-9:.]+])(:\d+)?\z/', $host, $matches)) {
            if (isset($matches[2])) {
                $port = substr($matches[2], 1);
            }

            if (isset($matches[1])) {
                $host = $matches[1];
            }
        } elseif (str_contains($host, ':')) {
            $host = explode(':', $host, 2);
            $port = $host[2] ?? '';
            $host = $host[1];
        }

        $port = (
            $port !== '' ?
            intval($port) :
            ($scheme === 'https' ? 443 : 80)
        );

        $path = $serverParams['REQUEST_URI'] ?? '/';
        $query = '';

        $pos = strpos($path, '?');
        if ($pos !== false) {
            $query = strval(substr($path, $pos + 1));
            $path = strval(substr($path, 0, $pos));
        }

        $user = $globals['PHP_AUTH_USER'] ?? '';
        $password = $globals['PHP_AUTH_PW'] ?? null;

        return Uri::fromParts(
            $scheme,
            $host,
            $port,
            $path,
            $query,
            $user,
            $password
        );
    }
}

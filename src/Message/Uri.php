<?php
namespace Pyncer\Http\Message;

use Psr\Http\Message\UriInterface as PsrUriInterface;
use Pyncer\Exception\InvalidArgumentException;

use function filter_var;
use function intval;
use function ltrim;
use function parse_url;
use function sprintf;
use function strtolower;
use function strval;
use function substr;
use function rtrim;
use function Pyncer\Http\encode_url_path as pyncer_encode_url_path;
use function Pyncer\Http\encode_url_query as pyncer_encode_url_query;
use function Pyncer\Http\encode_url_fragment as pyncer_encode_url_fragment;
use function Pyncer\Http\encode_url_user_info as pyncer_encode_url_user_info;

class Uri implements PsrUriInterface
{
    private string $scheme = '';
    private string $userInfo = '';
    private string $host = '';
    private ?int $port = null;
    private string $path = '';
    private string $query = '';
    private string $fragment = '';

    private static $schemes = [
        'http'  => 80,
        'https' => 443
    ];

    public function __construct(?string $uri = null)
    {
        if ($uri !== null) {
            $parts = parse_url($uri);

            if ($parts === false) {
                throw new InvalidArgumentException(
                    'Unable to parse URI: ' .  $uri
                );
            }

            $this->setParts($parts);
        }
    }

    public static function fromParts(
        string $scheme,
        string $host,
        ?int $port,
        string $path,
        string $query,
        string $user = '',
        ?string $password = null
    ): static
    {
        $uri = new static();

        $uri->setScheme($scheme)
            ->setHost($host)
            ->setPort($port)
            ->setPath($path)
            ->setQuery($query)
            ->setUserInfo($user, $password);

        return $uri;
    }

    /**
     * {@inheritdoc}
     */
    public function getScheme(): string
    {
        return $this->scheme;
    }
    protected function setScheme(string $value): static
    {
        $this->scheme = $this->cleanScheme($value);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthority(): string
    {
        $authority = $this->getHost();

        $userInfo = $this->getUserInfo();
        if ($userInfo !== '') {
            $authority = $userInfo . '@' . $authority;
        }

        $port = $this->getPort();
        if (!$this->isStandardPort($this->getScheme(), $port)) {
            $authority .= ':' . $port;
        }

        return $authority;
    }

    /**
     * {@inheritdoc}
     */
    public function getUserInfo(): string
    {
        return $this->userInfo;
    }
    protected function setUserInfo(string $value): static
    {
        $this->userInfo = $this->cleanUserInfo($value);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getHost(): string
    {
        return $this->host;
    }
    protected function setHost(string $value): static
    {
        $this->host = $this->cleanHost($value);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPort(): ?int
    {
        if ($this->isStandardPort($this->getScheme() , $this->port)) {
            return null;
        }

        return $this->port;
    }
    protected function setPort($value): static
    {
        $this->port = $this->cleanPort($value);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPath(): string
    {
        return $this->path;
    }
    protected function setPath(string $value): static
    {
        $this->path = $this->cleanPath($value);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getQuery(): string
    {
        return $this->query;
    }
    protected function setQuery(string $value): static
    {
        $this->query = $this->cleanQuery($value);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getFragment(): string
    {
        return $this->fragment;
    }
    protected function setFragment(string $value): static
    {
        $this->fragment = $this->cleanFragment($value);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function withScheme($cheme): static
    {
        $scheme = $this->cleanScheme($scheme);

        if ($scheme === $this->getScheme()) {
            return $this;
        }

        $new = clone $this;
        $new->setScheme($scheme);
        return $new;
    }
    /**
     * {@inheritdoc}
     */
    public function withUserInfo($user, $password = null): static
    {
        $userInfo = $user;

        if ($user !== '' && $password !== null && $password !== '') {
            $userInfo .= ':' . $password;
        }

        $new = clone $this;
        $new->setUserInfo($userInfo);
        return $new;
    }
    /**
     * {@inheritdoc}
     */
    public function withHost($host): static
    {
        $new = clone $this;
        $new->setHost($host);
        return $new;
    }
    /**
     * {@inheritdoc}
     */
    public function withPort($port): static
    {
        $new = clone $this;
        $new->setPort($port);
        return $new;
    }
    /**
     * {@inheritdoc}
     */
    public function withPath($path): static
    {
        $new = clone $this;
        $new->setPath($path);
        return $new;
    }
    /**
     * {@inheritdoc}
     */
    public function withQuery($query): static
    {
        $new = clone $this;
        $new->setQuery($query);
        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function withFragment($fragment): static
    {
        $new = clone $this;
        $new->setFragment($fragment);
        return $new;
    }

    private function setParts(array $parts): void
    {
        if (isset($parts['scheme'])) {
            $this->setScheme($parts['scheme']);
        }

        if (isset($parts['user'])) {
            if (isset($parts['pass'])) {
                $this->setUserInfo($parts['user'] . ':' . $parts['pass']);
            } else {
                $this->setUserInfo($parts['user']);
            }
        }

        if (isset($parts['host'])) {
            $this->setHost($parts['host']);
        }

        if (isset($parts['port'])) {
            $this->setPort($parts['port']);
        }

        if (isset($parts['path'])) {
            $this->setPath($parts['path']);
        }

        if (isset($parts['query'])) {
            $this->setQuery($parts['query']);
        }

        if (isset($parts['fragment'])) {
            $this->setFragment($parts['fragment']);
        }
    }

    protected function cleanScheme(string $scheme): string
    {
        $scheme = strtolower($scheme);
        $scheme = rtrim($scheme, ':/');
        return $scheme;
    }
    protected function cleanUserInfo(string $value): string
    {
        return pyncer_encode_url_user_info($value);
    }
    protected function cleanHost($host)
    {
        $host = strtolower($host);

        // RFC2373
        if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $host = '[' . $host . ']';
        }

        return $host;
    }
    protected function cleanPath(string $value): string
    {
        return pyncer_encode_url_path($value);
    }
    protected function cleanPort(?int $value): ?int
    {
        if (!$this->isValidPort($value)) {
            throw new InvalidArgumentException(
                sprintf('Invalid port: %d. Must be between 1 and 65535', $value)
            );
        }

        return ($value === null ? null : intval($value));
    }
    protected function isStandardPort(string $scheme, ?int $port): bool
    {
        if ($port === null) {
            return true;
        }

        if ($scheme === '') {
            return false;
        }

        if (!isset(self::$schemes[$scheme])) {
            return false;
        }

        if ($port !== self::$schemes[$scheme]) {
            return false;
        }

        return true;
    }
    protected function isValidPort(?int $port): bool
    {
        if ($port === null) {
            return true;
        }

        return ($port >= 1 && $port <= 65535);
    }
    protected function cleanQuery(string $query): string
    {
        $query = ltrim(strval($query), '?');

        return pyncer_encode_url_query($query);
    }
    protected function cleanFragment(string $fragment): string
    {
        $fragment = ltrim($fragment, '#');

        return pyncer_encode_url_fragment($fragment);
    }

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        $uri = '';

        $part = $this->getScheme();
        if ($part !== '') {
            $uri .= $part . ':';
        }

        $authority = $this->getAuthority();
        if ($authority !== '') {
            $uri .= '//' . $authority;
        }

        $part = $this->getPath();
        if ($part) {
            if ($authority) {
                if (substr($part, 0, 1) !== '/') {
                    $part = '/' . $part;
                }
            } else {
                $part = '/' . ltrim($part, '/');
            }

            $uri .= $part;
        }

        $part = $this->getQuery();
        if ($part !== '') {
            $uri .= '?' . $part;
        }

        $part = $this->getFragment();
        if ($part !== '') {
            $uri .= '#' . $part;
        }

        return $uri;
    }
}

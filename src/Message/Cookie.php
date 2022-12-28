<?php
namespace Pyncer\Http\Message;

use DateTimeInterface;
use Pyncer\Exception\InvalidArgumentException;

use function date;
use function in_array;
use function intval;
use function is_string;
use function preg_match;
use function strtolower;
use function strtotime;
use function strval;
use function time;
use function urlencode;

class Cookie
{
    protected string $name;
    protected string $value;
    protected ?int $expires;
    protected ?string $path;
    protected ?string $domain;
    protected bool $secure;
    protected bool $httpOnly;
    protected ?string $sameSite;

    /**
     * Constructor.
     *
     * @param string $name The name of the cookie
     * @param string $value The value of the cookie
     * @param ?int|string|DateTimeInterface $expire The time the cookie expires
     * @param ?string $path The path on the server in which the cookie will be available on
     * @param ?string $domain The domain that the cookie is available to
     * @param bool $secure Whether the cookie should only be transmitted over a secure HTTPS connection from the client
     * @param bool $httpOnly Whether the cookie will be made accessible only through the HTTP protocol
     * @param ?string $sameSite Whether the cookie should be restricted to same-site context
     *
     * @throws InvalidArgumentException
     */
    public function __construct(
        string $name,
        string $value = '',
        null|int|string|DateTimeInterface $expires = null,
        ?string $path = null,
        ?string $domain = null,
        bool $secure = false,
        bool $httpOnly = false,
        ?string $sameSite = null,
    ) {
        $this->setName($name);
        $this->setValue($value);
        $this->setExpires($expires);
        $this->setPath($path);
        $this->setDomain($domain);
        $this->setSecure($secure);
        $this->setHttpOnly($httpOnly);
        $this->setSameSite($sameSite);
    }

    /**
     * Gets the name of the cookie.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
    public function setName(string $value): static
    {
        $value = strval($value);

        // From PHP source code
        if (preg_match("/[=,; \t\r\n\013\014]/", $value)) {
            throw new InvalidArgumentException(
                'The cookie name, "' . $value . '", contains invalid characters.'
            );
        }

        if ($value === '') {
            throw new InvalidArgumentException('The cookie name cannot be empty.');
        }

        $this->name = $value;

        return $this;
    }

    /**
     * Gets the value of the cookie.
     *
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }
    public function setValue(string $value): static
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Gets the time the cookie expires.
     *
     * @return int
     */
    public function getExpires(): ?int
    {
        return $this->expires;
    }
    public function setExpires(null|int|string|DateTimeInterface $value): static
    {
        // Convert expires time to a Unix timestamp
        if ($value instanceof DateTimeInterface) {
            $value = intval($value->format('U'));
        } elseif (is_string($value)) {
            $value = strtotime($value);

            if ($value === false) {
                throw new InvalidArgumentException('Cookie expires time is invalid.');
            }
        }

        $this->expires = $value;

        return $this;
    }

    /**
     * Gets the path on the server in which the cookie will be available on.
     *
     * @return string
     */
    public function getPath(): ?string
    {
        return $this->path;
    }
    public function setPath(?string $value): static
    {
        if ($value === '') {
            $value = '/';
        }

        $this->path = $value;

        return $this;
    }

    /**
     * Gets the domain that the cookie is available to.
     *
     * @return string
     */
    public function getDomain(): ?string
    {
        return $this->domain;
    }
    public function setDomain(?string $value): static
    {
        $this->domain = $value;

        return $this;
    }

    /**
     * Checks whether the cookie should only be transmitted over a secure HTTPS connection from the client.
     *
     * @return bool
     */
    public function getSecure(): bool
    {
        return $this->secure;
    }
    public function setSecure(bool $value): static
    {
        $this->secure = $value;

        return $this;
    }

    /**
     * Checks whether the cookie will be made accessible only through the HTTP protocol.
     *
     * @return bool
     */
    public function getHttpOnly(): bool
    {
        return $this->httpOnly;
    }
    public function setHttpOnly(bool $value): static
    {
        $this->httpOnly = $value;

        return $this;
    }

    /**
     * Checks whether the cookie will be made accessible only through the HTTP protocol.
     *
     * @return bool
     */
    public function getSameSite(): ?string
    {
        return $this->sameSite;
    }
    public function setSameSite(?string $value): static
    {
        if ($value !== null && !in_array(strtolower($value), ['strict', 'lax', 'none'])) {
            throw new InvalidArgumentException('Same site value is invalid. (' . $value . ')');
        }

        $this->sameSite = $value;

        return $this;
    }

    /**
     * Whether this cookie is about to be cleared.
     *
     * @return bool
     */
    public function isCleared(): bool
    {
        return ($this->expires < time());
    }

    /**
     * Returns the cookie as a string.
     *
     * @return string The cookie
     */
    public function __toString(): string
    {
        $s = urlencode($this->getName()) . '=';

        // If no value, expire cookie
        if ($this->getValue() === '') {
            $s .= '; expires=' . date('D, d-M-Y H:i:s T', time() - 31536001);
        } else {
            $s .= urlencode($this->getValue());

            if ($this->getExpires() !== null && $this->getExpires() !== 0) {
                $s .= '; expires=' . date('D, d-M-Y H:i:s T', $this->getExpires());
            }
        }

        if ($this->getPath() !== null) {
            $s .= '; path=' . $this->getPath();
        }

        if ($this->getDomain() !== null) {
            $s .= '; domain=' . $this->getDomain();
        }

        if ($this->getSecure()) {
            $s .= '; secure';
        }

        if ($this->getHttpOnly()) {
            $s .= '; httponly';
        }

        if ($this->getSameSite() !== null) {
            $s .= '; samesite=' . $this->getSameSite();
        }

        return $s;
    }
}

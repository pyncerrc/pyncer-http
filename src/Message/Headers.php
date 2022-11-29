<?php
namespace Pyncer\Http\Message;

class Headers
{
    private array $headers = [];
    private array $headerNames = [];

    public function __construct(array $headers = [])
    {
        $this->setHeaders($headers);
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function setHeaders(array $headers): static
    {
        $this->headers = [];
        $this->headerNames = [];
        foreach ($headers as $key => $value) {
            if (is_array($value)) {
                $value = array_map('trim', $value);
                $this->headers[$key] = $value;
            } else {
                $this->headers[$key] = [trim($value)];
            }
            $this->headerNames[strtolower($key)] = $key;
        }
        return $this;
    }

    public function hasHeader($name): bool
    {
        $name = strtolower($name);

        return isset($this->headerNames[$name]);
    }

    public function getHeader($name): array
    {
        $name = strtolower($name);

        if (isset($this->headerNames[$name])) {
            return $this->headers[$this->headerNames[$name]];
        }

        return [];
    }

    public function getHeaderLine($name): string
    {
        return implode(',', $this->getHeader($name));
    }

    public function setHeader(string $name, null|string|array $value): static
    {
        // Remove old header first in case name is different case
        if (isset($this->headerNames[strtolower($name)])) {
            unset($this->headers[$this->headerNames[strtolower($name)]]);
            unset($this->headerNames[strtolower($name)]);
        }

        if ($value !== null) {
            if (is_array($value)) {
                $value = array_map('trim', $value);
                $this->headers[$name] = $value;
            } else {
                $this->headers[$name] = [trim($value)];
            }

            $this->headerNames[strtolower($name)] = $name;
        }

        return $this;
    }

    public function addHeader(string $name, string|array $value): static
    {
        $origionalName = $this->headerNames[strtolower($name)];
        $newValues = $this->headers[$origionalName];
        if (is_array($value)) {
            $value = array_map('trim', $value);
            $newValues = array_merge($newValues, $value);
        } else {
            $newValues[] = trim($value);
        }

        unset($this->headers[$origionalName]);
        $this->headers[$name] = $newValues;

        $this->headerNames[strtolower($name)] = $name;

        return $this;
    }
}

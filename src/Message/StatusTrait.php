<?php
namespace Pyncer\Http\Message;

use Pyncer\Exception\InvalidArgumentException;
use Pyncer\Http\Message\Status;

trait StatusTrait
{
    private Status $status = Status::SUCCESS_200_OK;
    private ?string $reasonPhrase = null;

    public function getStatus(): Status
    {
        return $this->status;
    }
    protected function setStatus(Status $value): static
    {
        $this->status = $value;
        $this->reasonPhrase = null;
        return $this;
    }

    /**
     * Gets the response status code.
     *
     * The status code is a 3-digit integer result code of the server's attempt
     * to understand and satisfy the request.
     *
     * @return int Status code.
     */
    public function getStatusCode(): int
    {
        return $this->status->getStatusCode();
    }
    protected function setStatusCode(int $value): static
    {
        $status = Status::tryFrom($value);

        if ($status === null) {
            throw new InvalidArgumentException('The specified HTTP status, \'' . $value . '\', is invalid.');
        }

        return $this->setStatus($status);
    }

    /**
     * Gets the response reason phrase associated with the status code.
     *
     * Because a reason phrase is not a required element in a response
     * status line, the reason phrase value MAY be null. Implementations MAY
     * choose to return the default RFC 7231 recommended reason phrase (or those
     * listed in the IANA HTTP Status Code Registry) for the response's
     * status code.
     *
     * @link http://tools.ietf.org/html/rfc7231#section-6
     * @link http://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
     * @return string Reason phrase; must return an empty string if none present.
     */
    public function getReasonPhrase(): string
    {
        if ($this->reasonPhrase !== null) {
            return $this->reasonPhrase;
        }

        return $this->status->getReasonPhrase();
    }
    protected function setReasonPhrase(?string $value): static
    {
        if ($value === '') {
            $this->reasonPhrase = null;
        } else {
            $this->reasonPhrase = $value;
        }

        return $this;
    }
}

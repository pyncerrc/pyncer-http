<?php
namespace Pyncer\Http\Message;

use Psr\Http\Message\ServerRequestInterface as PsrServerRequestInterface;
use Pyncer\Exception\InvalidArgumentException;
use Pyncer\Utility\Params;

use function array_is_list;
use function in_array;
use function is_array;

final class RequestData extends Params
{
    public static function fromQueryParams(PsrServerRequestInterface $request): static
    {
        return new static($request->getQueryParams());
    }
    public static function fromParsedBody(PsrServerRequestInterface $request): static
    {
        if (!in_array($request->getMethod(), ['PATCH', 'POST', 'PUT'])) {
            throw new InvalidArgumentException(
                'Request method does not suxp data in message body.'
            );
        }

        $parsedBody = $request->getParsedBody();
        if (!is_array($parsedBody) || array_is_list($parsedBody)) {
            $parsedBody = ['data' => $parsedBody];
        }

        return new static($parsedBody);
    }
}

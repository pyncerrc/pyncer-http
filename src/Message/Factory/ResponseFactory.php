<?php
namespace Pyncer\Http\Message\Factory;

use Psr\Http\Message\ResponseFactoryInterface as PsrResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Pyncer\Exception\InvalidArgumentException;
use Pyncer\Http\Message\Response;
use Pyncer\Http\Message\Status;

class ResponseFactory implements PsrResponseFactoryInterface
{
    /**
     * @inheritdoc
     */
    public function createResponse(
        int $code = 200,
        string $reasonPhrase = ''
    ): PsrResponseInterface
    {
        $status = Status::tryFrom($code);

        if ($status === null) {
            throw new InvalidArgumentException('The specified HTTP status, \'' . $value . '\', is invalid.');
        }

        $response = new Response($status);

        if ($reasonPhrase !== '') {
            $response = $response->withStatus($status, $reasonPhrase);
        }

        return $response;
    }
}

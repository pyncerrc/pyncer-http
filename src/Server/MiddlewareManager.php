<?php
namespace Pyncer\Http\Server;

use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Psr\Http\Message\ServerRequestInterface as PsrServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface as PsrMiddlewareInterface;
use Psr\Log\LoggerAwareInterface as PsrLoggerAwareInterface;
use Psr\Log\LoggerAwareTrait as PsrLoggerAwareTrait;
use Pyncer\Container\Container;
use Pyncer\Http\Message\Status;
use Pyncer\Http\Server\ErrorHandler;
use Pyncer\Http\Server\MiddlewareInterface;
use Pyncer\Http\Server\RequestHandlerInterface;
use Throwable;

use function array_merge;
use function array_unshift;
use function call_user_func;
use function call_user_func_array;
use function count;

final class MiddlewareManager implements PsrLoggerAwareInterface
{
    use PsrLoggerAwareTrait;

    private PsrServerRequestInterface $request;
    private PsrResponseInterface $response;
    private RequestHandlerInterface $handler;
    private array $queue = [];
    private bool $isRunning = false;

    private array $beforeCallbacks = [];
    private array $errorCallbacks = [];
    private array $afterCallbacks = [];

    private bool $inCallback = false;
    private PsrServerRequestInterface $inCallbackRequest;

    public function __construct(
        PsrServerRequestInterface $request,
        PsrResponseInterface $response,
        RequestHandlerInterface $handler
    ) {
        $this->setRequest($request);
        $this->setResponse($response);
        $this->setHandler($handler);
    }

    protected function getRequest(): PsrServerResponseInterface
    {
        return $this->request;
    }
    protected function setRequest(PsrServerRequestInterface $request): static
    {
        $this->request = $request;
        return $this;
    }

    protected function getResponse(): PsrResponseInterface
    {
        return $this->response;
    }
    protected function setResponse(PsrResponseInterface $response): static
    {
        $this->response = $response;
        return $this;
    }

    protected function getHandler(): RequestHandlerInterface
    {
        return $this->handler;
    }
    protected function setHandler(RequestHandlerInterface $handler): static
    {
        $this->handler = $handler;
        return $this;
    }

    public function append(
        PsrMiddlewareInterface|MiddlewareInterface|callable ...$callable
    ): static
    {
        $this->queue = array_merge($this->queue, $callable);
        return $this;
    }
    public function prepend(
        PsrMiddlewareInterface|MiddlewareInterface|callable ...$callable
    ): static
    {
        array_unshift($this->queue, $callable);
        return $this;
    }

    public function clear(): static
    {
        $this->queue = [];
        return $this;
    }

    public function count(): int
    {
        $count = count($this->queue);

        // Include the current running item in the count
        if ($this->isRunning) {
            ++$count;
        }

        return $count;
    }

    public function onBefore(callable $callable): static
    {
        // First in, last out
        array_unshift($this->beforeCallbacks, $callable);
        return $this;
    }

    public function onAfter(callable $callable): static
    {
        // First in, last out
        array_unshift($this->afterCallbacks, $callable);
        return $this;
    }

    public function onError(callable $callable): static
    {
        // First in, last out
        array_unshift($this->errorCallbacks, $callable);
        return $this;
    }

    public function run(?PsrResponseInterface $response = null): PsrResponseInterface
    {
        if ($response !== null) {
            $this->setResponse($response);
        }

        return $this->runWith($this->request, $this->response);
    }

    public function handle(PsrServerRequestInterface $request): PsrResponseInterface
    {
        return $this->runWith($request, $this->response);
    }

    private function runWith(
        PsrServerRequestInterface $request,
        PsrResponseInterface $response
    ): PsrResponseInterface
    {
        if ($this->beforeCallbacks) {
            try {
                list ($request, $response) = $this->runCallbacks(
                    $this->beforeCallbacks,
                    $request,
                    $response
                );
            } catch (Throwable $e) {
                $response = $response->withStatus(
                    Status::SERVER_ERROR_500_INTERNAL_SERVER_ERROR->getSatusCode()
                );
                return $response;
            }
        }

        $item = array_shift($this->queue);

        if (!isset($item)) {
            return $response;
        }

        if ($item instanceof PsrLoggerAwareInterface &&
            $this->logger
        ) {
            $item->setLogger($this->logger);
        }

        try {
            if ($item instanceof PsrMiddlewareInterface) {
                $response = $item->process($request, $this->getHandler());
            } else {
                $response = call_user_func(
                    $item,
                    $request,
                    $response,
                    $this->getHandler()
                );
            }
        } catch(Throwable $e) { // Gotta catch em all!
            if ($this->errorCallbacks) {
                $errorHandler = new ErrorHandler($e);

                try {
                    list ($request, $response) = $this->runCallbacks(
                        $this->errorCallbacks,
                        $request,
                        $response,
                        $errorHandler
                    );
                } catch (Throwable $e) {
                    $errorHandler->setHandled(false);
                }

                // Continue on through queue
                if ($errorHandler->getHandled()) {
                    return $this->__invoke($request, $response);
                }
            }

            $response = $response->withStatus(
                Status::SERVER_ERROR_500_INTERNAL_SERVER_ERROR->getStatusCode()
            );

            throw $e;
        }

        //$this->clear();

        return $response;
    }
    private function runCallbacks(
        array $callbacks,
        PsrServerRequestInterface $request,
        PsrResponseInterface$response,
        ErrorHandler $errorHandler = null
    ) {
        $this->inCallback = true; // Prevent callback invoke recursion

        foreach ($callbacks as $callable) {
            $response = call_user_func_array(
                $callable,
                [$request, $response, $this->getHandler(), $errorHandler]
            );

            // Simulate going through normal channels of running in callback
            if (isset($this->inCallbackRequest)) {
                $request = $this->inCallbackRequest;
                unset($this->inCallbackRequest);
            }

            $this->setRequest($request);
            $this->setResponse($response);

            if ($errorHandler->getHandled()) {
                break;
            }
        }

        $this->inCallback = false;

        return [$request, $response];
    }

    public function next(
        PsrServerRequestInterface $request,
        PsrResponseInterface $response
    ): PsrResponseInterface
    {
        if ($this->inCallback) {
            // Store passed request so that it can be fake passed to next item
            $this->inCallbackRequest = $request;
            return $response;
        }

        $this->isRunning = false;
        $this->setRequest($request);
        $this->setResponse($response);

        return $this->runWith($request, $response);
    }
}

<?php

namespace Concat\Http\Middleware;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Promise;

/**
 * Guzzle middleware which logs a request and its response.
 */
class Logger
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \GuzzleHttp\MessageFormatter
     */
    protected $formatter;

    /**
     * Creates a callable middleware for logging requests and responses.
     *
     * @param $logger LoggerInterface
     * @param $formatter MessageFormatter
     */
    public function __construct(
        LoggerInterface $logger,
        MessageFormatter $formatter = null
    ) {
        $this->logger    = $logger;
        $this->formatter = $formatter ?: new MessageFormatter();
    }

    /**
     * Logs a request and its response.
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     */
    protected function log(
        RequestInterface $request,
        ResponseInterface $response
    ) {
        $level   = $this->getLogLevel($response);
        $message = $this->getLogMessage($request, $response);
        $context = compact('request', 'response');

        $this->logger->log($level, $message, $context);
    }

    /**
     * Logs a failed request and its response if it has one.
     *
     * @param RequestInterface $request
     * @param mixed $reason
     */
    protected function logError(RequestInterface $request, $reason)
    {
        $response = $this->getReasonResponse($reason);
        $level    = $this->getErrorLogLevel($response);
        $message  = $this->getErrorLogMessage($request, $response, $reason);
        $context  = compact('request', 'response', 'reason');

        $this->logger->log($level, $message, $context);
    }

    /**
     * Returns a reason's response or null if it can't be determined.
     *
     * @param mixed $reason
     *
     * @return ResponseInterface|null
     */
    protected function getReasonResponse($reason)
    {
        if ($reason instanceof RequestException) {
            return $reason->getResponse();
        }
    }

    /**
     * Formats a request and response as an error log message.
     *
     * @param RequestInterface $request
     * @param ResponseInterface|null $response
     * @param mixed|null $reason
     *
     * @return string The formatted erro message.
     */
    protected function getErrorLogMessage(
        RequestInterface $request,
        ResponseInterface $response = null,
        $reason = null
    ) {
        return $this->formatter->format($request, $response, $reason);
    }

    /**
     * Formats a request and response as a log message.
     *
     * @param RequestInterface $request
     * @param ResponseInterface|null $response
     *
     * @return string The formatted message.
     */
    protected function getLogMessage(
        RequestInterface $request,
        ResponseInterface $response = null
    ) {
        return $this->formatter->format($request, $response);
    }

    /**
     * Returns a log level for a given response.
     *
     * @param ResponseInterface $response The response being logged.
     *
     * @return string LogLevel
     */
    protected function getLogLevel(ResponseInterface $response)
    {
        switch (substr($response->getStatusCode(), 0, 1)) {
            case '4':
            case '5':
                return $this->getErrorLogLevel();
            default:
                return LogLevel::DEBUG;
        }
    }

    /**
     * Returns a erro log level for a given response.
     *
     * @param ResponseInterface $response The response being logged.
     *
     * @return string LogLevel
     */
    protected function getErrorLogLevel(ResponseInterface $response = null)
    {
        if ($response) {
            return $this->getLogLevel($response);
        }

        return LogLevel::NOTICE;
    }

    /**
     * A convenient hook which gives access to a request before a response is
     * received. This is useful for when an extending logger might like to log
     * a request immediately (rather than wait for its response).
     *
     * @param RequestInterface $request The request being made.
     */
    protected function requestHook(RequestInterface $request)
    {
        // Don't log requests by default.
    }

    /**
     * Returns a function which is handled when a request was successful.
     *
     * @param RequestInterface $request
     *
     * @return Closure
     */
    protected function onSuccess(RequestInterface $request)
    {
        return function ($response) use ($request) {
            $this->log($request, $response);
            return $response;
        };
    }

    /**
     * Returns a function which is handled when a request was not successful.
     *
     * @param RequestInterface $request
     *
     * @return Closure
     */
    protected function onFailure(RequestInterface $request)
    {
        return function ($reason) use ($request) {
            $this->logError($request, $reason);
            return Promise\rejection_for($reason);
        };
    }

    /**
     * Called when the middleware is handled by the client.
     */
    public function __invoke(callable $handler)
    {
        return function ($request, array $options) use ($handler) {

            // Hook in here to log requests immediately
            $this->requestHook($request, $options);

            return $handler($request, $options)->then(
                $this->onSuccess($request),
                $this->onFailure($request)
            );
        };
    }
}

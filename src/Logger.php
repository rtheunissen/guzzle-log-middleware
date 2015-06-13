<?php

namespace Concat\Http\Middleware;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\MessageFormatter;

/**
 * Guzzle middleware which logs a request and response cycle.
 */
class Logger
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

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
     * @param RequestInterface $request
     */
    protected function log(RequestInterface $request)
    {
        return function (ResponseInterface $response) use ($request) {

            $code    = $response->getStatusCode();
            $level   = $this->getLogLevel($code);
            $message = $this->getLogMessage($request, $response);
            $context = compact('request', 'response');

            $this->logger->log($level, $message, $context);

            return $response;
        };
    }

    /**
     * Formats a request and response as a log message.
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     *
     * @return string The formatted message
     */
    protected function getLogMessage(
        RequestInterface $request,
        ResponseInterface $response
    ) {
        return $this->formatter->format($request, $response);
    }

    /**
     * Returns the log level for a response status code.
     *
     * @param integer $statusCode Status code of the response.
     *
     * @return string|null Log level constant or null if undetermined.
     */
    protected function getLogLevel($statusCode)
    {
        switch (intval(substr($statusCode, 0, 1))) {
            case '1':
            case '2':
                return LogLevel::INFO;
            case '3':
                return LogLevel::NOTICE;
            case '4':
                return LogLevel::ERROR;
            case '5':
                return LogLevel::CRITICAL;
        }
    }

    /**
     * Called when the middleware is handled by the client.
     */
    public function __invoke(callable $handler)
    {
        return function (RequestInterface $request, $options) use ($handler) {
            return $handler($request, $options)->then($this->log($request));
        };
    }
}

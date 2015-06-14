<?php

namespace Concat\Http\Middleware;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\MessageFormatter;

/**
 * Guzzle middleware which logs a request and response respectively.
 */
class MultiLogger extends Logger
{
    /**
     * Hook into the request to log it immediately.
     *
     * @param RequestInterface $request The request being made.
     */
    protected function requestHook(RequestInterface $request)
    {
        $level   = $this->getRequestLogLevel($request);
        $message = $this->getRequestMessage($request);
        $context = compact('request');

        $this->logger->log($level, $message, $context);
    }

    /**
     * Returns the log message for a given request.
     *
     * @param RequestInterface $request The request being logged.
     *
     * @return string The formatted log message
     */
    protected function getRequestMessage(RequestInterface $request)
    {
        return $this->formatter->format($request);
    }

    /**
     * Returns the log level for a given request.
     *
     * @param RequestInterface $request The request being logged.
     *
     * @return string LogLevel
     */
    protected function getRequestLogLevel(RequestInterface $request)
    {
        return LogLevel::DEBUG;
    }
}

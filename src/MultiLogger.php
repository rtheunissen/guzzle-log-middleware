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
     * Logs a request when it is sent and a response when it is received.
     *
     * @param RequestInterface $request
     * @param array $options
     */
    protected function log(RequestInterface $request, array $options)
    {
        $level   = $this->getRequestLogLevel($request, $options);
        $message = $this->getRequestMessage($request, $options);
        $context = compact('request', 'options');

        $this->logger->log($level, $message, $context);

        return parent::log($request, $options);
    }

    /**
     * {@inheritDoc}
     */
    final protected function getLogMessage(
        RequestInterface $request,
        ResponseInterface $response,
        array $options
    ) {
        return $this->getResponseMessage($request, $response, $options);
    }

    /**
     * {@inheritDoc}
     */
    final protected function getLogLevel(
        RequestInterface $request,
        ResponseInterface $response,
        array $options
    ) {
        return $this->getResponseLogLevel($request, $response, $options);
    }

    /**
     * Returns the log level for a given request.
     *
     * @param RequestInterface $request The request being logged.
     * @param array $options Request options
     *
     * @return string LogLevel
     */
    protected function getRequestLogLevel(
        RequestInterface $request,
        array $options
    ) {
        return LogLevel::DEBUG;
    }

    /**
     * Returns a log level for a given response.
     *
     * @param RequestInterface $response The request being logged.
     * @param ResponseInterface $response The response being logged.
     * @param array $options Request options
     *
     * @return string LogLevel
     */
    protected function getResponseLogLevel(
        RequestInterface $request,
        ResponseInterface $response,
        array $options
    ) {
        return parent::getLogLevel($request, $response, $options);
    }

    /**
     * Returns the log message for a given request.
     *
     * @param RequestInterface $request The request being logged.
     * @param array $options Request options
     *
     * @return string The formatted log message
     */
    protected function getRequestMessage(
        RequestInterface $request,
        array $options
    ) {
        return $this->formatter->format($request);
    }

    /**
     * Returns the log message for a given response.
     *
     * @param RequestInterface $response The request being logged.
     * @param ResponseInterface $response The response being logged.
     * @param array $options Request options
     *
     * @return string The formatted log message
     */
    protected function getResponseMessage(
        RequestInterface $request,
        ResponseInterface $response,
        array $options
    ) {
        return $this->formatter->format($request, $response);
    }
}

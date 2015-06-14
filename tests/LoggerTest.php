<?php

namespace Concat\Http\Middleware\Test;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

use GuzzleHttp\Promise\RejectedPromise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Exception\RequestException;

use Concat\Http\Middleware\Logger;

use \Mockery as m;

class LoggerTest extends \PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    /**
     * @dataProvider providerTestLogger
     */
    public function testLogger($code, $level)
    {
        $formatter = m::mock(MessageFormatter::class);

        $formatter->shouldReceive('format')->once()->with(
            m::type(RequestInterface::class),
            m::type(ResponseInterface::class)
        )->andReturn("ok");

        $logger = m::mock(LoggerInterface::class);
        $logger->shouldReceive('log')->once()->with($level, "ok", m::type('array'));

        $middleware = new Logger($logger, $formatter);

        $promise = m::mock(PromiseInterface::class);
        $promise->shouldReceive('then')->once()->andReturnUsing(function ($a) {
            return $a;
        });

        $handler = function ($request, $options) use ($promise) {
            return $promise;
        };

        $request = m::mock(RequestInterface::class);

        $callback = $middleware->__invoke($handler);
        $result = $callback->__invoke($request, []);

        $response = m::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn($code);
        $result->__invoke($response);
    }

    public function providerTestLogger()
    {
        return [
            ['xxx', LogLevel::DEBUG],
            ['100', LogLevel::DEBUG],
            ['200', LogLevel::DEBUG],
            ['300', LogLevel::DEBUG],
            ['400', LogLevel::NOTICE],
            ['500', LogLevel::NOTICE],
        ];
    }

    public function testErrorLogWithException()
    {
        $formatter = m::mock(MessageFormatter::class);

        $formatter->shouldReceive('format')->once()->with(
            m::type(RequestInterface::class),
            m::type(ResponseInterface::class),
            m::type(RequestException::class)
        )->andReturn("ok");

        $logger = m::mock(LoggerInterface::class);

        $logger->shouldReceive('log')->once()->with(LogLevel::NOTICE, "ok", m::type('array'));

        $middleware = new Logger($logger, $formatter);

        $request = m::mock(RequestInterface::class);

        $promise = m::mock(PromiseInterface::class);
        $promise->shouldReceive('then')->once()->andReturnUsing(function ($a, $b) use ($request) {

            $response = m::mock(ResponseInterface::class);
            $response->shouldReceive('getStatusCode')->andReturn(500);

            $exception = m::mock(RequestException::class);
            $exception->shouldReceive('getResponse')->andReturn($response);

            $b($exception);
        });

        $handler = function ($request, $options) use ($promise) {
            return $promise;
        };

        $callback = $middleware->__invoke($handler);
        $result = $callback->__invoke($request, []);
    }

    public function testErrorLogWithNull()
    {
        $formatter = m::mock(MessageFormatter::class);

        $formatter->shouldReceive('format')->once()->with(
            m::type(RequestInterface::class),
            null,
            null
        )->andReturn("ok");

        $logger = m::mock(LoggerInterface::class);

        $logger->shouldReceive('log')->once()->with(LogLevel::NOTICE, "ok", m::type('array'));

        $middleware = new Logger($logger, $formatter);

        $request = m::mock(RequestInterface::class);

        $promise = m::mock(PromiseInterface::class);
        $promise->shouldReceive('then')->once()->andReturnUsing(function ($a, $b) use ($request) {
            $b(null);
        });

        $handler = function ($request, $options) use ($promise) {
            return $promise;
        };

        $callback = $middleware->__invoke($handler);
        $result = $callback->__invoke($request, []);
    }
}

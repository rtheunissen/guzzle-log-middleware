<?php

namespace Concat\Http\Middleware\Test;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\MessageFormatter;

use Concat\Http\Middleware\Logger;

use \Mockery as m;

class LoggerTest extends \PHPUnit_Framework_TestCase
{
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
        $request->shouldReceive('getMethod')->once()->andReturn('GET');
        $request->shouldReceive('getUri')->once()->andReturn('/');

        $callback = $middleware->__invoke($handler);
        $result = $callback->__invoke($request, []);

        $response = m::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn($code);
        $result->__invoke($response);
    }

    public function providerTestLogger()
    {
        return [
            ['100', LogLevel::INFO],
            ['200', LogLevel::INFO],
            ['300', LogLevel::NOTICE],
            ['400', LogLevel::ERROR],
            ['500', LogLevel::CRITICAL],
            ['xxx', null],
        ];
    }
}

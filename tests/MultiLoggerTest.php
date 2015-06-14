<?php

namespace Concat\Http\Middleware\Test;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Client;
use GuzzleHttp\MessageFormatter;

use Concat\Http\Middleware\MultiLogger;

use \Mockery as m;

class MultiLoggerTest extends \PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testLogger()
    {
        $formatter = m::mock(MessageFormatter::class);

        $formatter->shouldReceive('format')->times(2)->andReturn("ok");

        $logger = m::mock(LoggerInterface::class);
        $logger->shouldReceive('log')->once()->with(LogLevel::DEBUG, "ok", m::type('array'));
        $logger->shouldReceive('log')->once()->with(LogLevel::INFO, "ok", m::type('array'));

        $middleware = new MultiLogger($logger, $formatter);

        $promise = m::mock(PromiseInterface::class);
        $promise->shouldReceive('then')->once()->andReturnUsing(function ($a) {
            return $a;
        });

        $handler = function ($request, $options) use ($promise) {
            return $promise;
        };

        $request = m::mock(RequestInterface::class);
        $request->shouldReceive('getMethod')->andReturn('GET');
        $request->shouldReceive('getUri')->andReturn('/');

        $callback = $middleware->__invoke($handler);
        $result = $callback->__invoke($request, []);

        $response = m::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn(100);
        $result->__invoke($response);
    }
}

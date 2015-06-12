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

use Concat\Http\Middleware\Logger;

use \Mockery as m;

class LoggerTest extends \PHPUnit_Framework_TestCase
{
    private function createClient($logger, array $responses)
    {
        $stack = new HandlerStack();
        $stack->setHandler(new MockHandler($responses));
        $stack->push($logger);
        return new Client(['handler' => $stack]);
    }

    /**
     * @dataProvider getCodeLevels
     */
    public function testCodeLevels($code, $level)
    {
        $response = m::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn($code);
        $response->shouldReceive('getHeaderLine');

        $formatter = m::mock(MessageFormatter::class);

        $formatter->shouldReceive('format')->with(
            m::type(RequestInterface::class),
            m::type(ResponseInterface::class)
        )->andReturn("ok");

        $logger = m::mock(LoggerInterface::class);
        $logger->shouldReceive('log')->with($level, "ok", m::type('array'));

        $middleware = new Logger($logger, $formatter);

        $client = $this->createClient($middleware, [$response]);
        $client->request('GET', 'http://test.com');
    }

    public function getCodeLevels()
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

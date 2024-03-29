<?php

namespace Concat\Http\Middleware\Test;

use Concat\Http\Middleware\Logger;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Promise\RejectedPromise;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LogLevel;
use Psr\Log\LoggerInterface;
use \Mockery as m;

class LoggerTest extends TestCase
{

    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    private function createClient($middleware, array $responses = [])
    {
        $handler = new MockHandler($responses);
        $stack = HandlerStack::create();

        if (is_array($middleware)) {
            foreach ($middleware as $m) {
                $stack->push($m);
            }
        } else {
            $stack->push($middleware);
        }

        $stack->setHandler($handler);
        return new Client(['handler' => $stack]);
    }

    private function createMockResponse($code)
    {
        $response = m::mock(ResponseInterface::class);
        $response->shouldReceive('getStatusCode')->andReturn($code);
        $response->shouldReceive('getBody')->andReturn(\GuzzleHttp\Psr7\Utils::streamFor('test data'));

        return $response;
    }

    private function logBehaviour($logger, $count, $level, $code, $message = "")
    {
        $logger->shouldReceive('log')->times($count)->with(
            $level,
            $message ?: m::pattern("~^.+ ua - \[.+\] \"GET / HTTP/1\.1\" $code .+$~"),
            m::type('array')
        );
    }

    public function testInvalidFormatter()
    {
        $this->expectException(InvalidArgumentException::class);
        $logger = new Logger(m::mock(LoggerInterface::class));
        $logger->setFormatter(false);
    }

    public function testInvalidLogger()
    {
        $this->expectException(InvalidArgumentException::class);
        $logger = new Logger(false);
    }

    public function testLogDefaults()
    {
        $logger = m::mock(LoggerInterface::class);
        $this->logBehaviour($logger, 1, LogLevel::INFO, 200);

        $middleware = new Logger($logger);
        $response = $this->createMockResponse(200);
        $response->shouldReceive('getHeaderLine')->andReturn("length");

        $client = $this->createClient($middleware, [$response]);
        $client->get("/", [
            'headers' => [
                'user-agent' => 'ua',
            ]
        ]);
    }

    public function testRequestLogging()
    {
        $logger = m::mock(LoggerInterface::class);
        $this->logBehaviour($logger, 1, LogLevel::INFO, "NULL");
        $this->logBehaviour($logger, 1, LogLevel::INFO, 200);

        $middleware = new Logger($logger);
        $middleware->setRequestLoggingEnabled(true);

        $response = $this->createMockResponse(200);
        $response->shouldReceive('getHeaderLine')->andReturn("length");

        $client = $this->createClient($middleware, [$response]);
        $client->get("/", [
            'headers' => [
                'user-agent' => 'ua',
            ]
        ]);
    }

    public function testClosureFormatter()
    {
        $logger = m::mock(LoggerInterface::class);
        $this->logBehaviour($logger, 1, LogLevel::INFO, 200, "custom");

        $middleware = new Logger($logger, function () {
            return "custom";
        });

        $response = $this->createMockResponse(200);
        $response->shouldReceive('getHeaderLine')->andReturn("length");

        $client = $this->createClient($middleware, [$response]);
        $client->get("/", [
            'headers' => [
                'user-agent' => 'ua',
            ]
        ]);
    }

    public function testClosureLogger()
    {
        $middleware = new Logger(function ($level, $message, $context) {
            $this->assertEquals($level, LogLevel::INFO);
            $this->assertIsString($message);
            $this->assertIsArray($context);
        });

        $response = $this->createMockResponse(200);
        $response->shouldReceive('getHeaderLine')->andReturn("length");

        $client = $this->createClient($middleware, [$response]);
        $client->get("/", [
            'headers' => [
                'user-agent' => 'ua',
            ]
        ]);
    }

    public function logLevelProvider()
    {
        return [

            // Test explicit level
            [200, "level", "level"],

            // Test callback level
            [200, function () { return "level"; }, "level"],

            // Test default level
            [200, null, LogLevel::INFO],

            // Test default error level
            [400, null, LogLevel::NOTICE]
        ];
    }

    /**
     * @dataProvider logLevelProvider
     */
    public function testLogLevel($code, $level, $expected)
    {
        $logger = m::mock(LoggerInterface::class);
        $this->logBehaviour($logger, 1, $expected, $code);

        $middleware = new Logger($logger);

        //
        if ($level) {
            $middleware->setLogLevel($level);
        }

        $response = $this->createMockResponse($code);
        $response->shouldReceive('getHeaderLine')->andReturn("length");

        $client = $this->createClient($middleware, [$response]);
        $client->get("/", [
            'headers'     => [ 'user-agent' => 'ua'],
            'http_errors' => false,
        ]);
    }

    public function testFailureLog()
    {
        $this->expectException(\GuzzleHttp\Exception\RequestException::class);

        $logger = m::mock(LoggerInterface::class);
        $this->logBehaviour($logger, 1, LogLevel::INFO, "NULL");

        $middleware = new Logger($logger);

        $response = $this->createMockResponse(400);

        $rejection = function($handler) {
            return function ($request, $options) {
                $exception = new RequestException("", $request);
                return new RejectedPromise($exception);
            };
        };

        // Push a rejection middleware to test onRejected
        $middleware = [
            $middleware,
            $rejection
        ];

        $client = $this->createClient($middleware, [$response]);
        $client->get("/", ['headers' => [ 'user-agent' => 'ua']]);
    }

    public function testFailureDoesNotLogTwice()
    {
        $this->expectException(\GuzzleHttp\Exception\RequestException::class);
        
        $logger = m::mock(LoggerInterface::class);
        $this->logBehaviour($logger, 1, LogLevel::INFO, "NULL");

        $middleware = new Logger($logger);
        $middleware->setRequestLoggingEnabled(true);

        $response = $this->createMockResponse(400);

        $rejection = function($handler) {
            return function ($request, $options) {
                $exception = new RequestException("", $request);
                return new RejectedPromise($exception);
            };
        };

        // Push a rejection middleware to test onRejected
        $middleware = [
            $middleware,
            $rejection
        ];

        $client = $this->createClient($middleware, [$response]);
        $client->get("/", ['headers' => [ 'user-agent' => 'ua']]);
    }
}

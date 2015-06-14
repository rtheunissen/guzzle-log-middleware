# Guzzle middleware to log messages

[![Author](http://img.shields.io/badge/author-@rudi_theunissen-blue.svg?style=flat-square)](https://twitter.com/rudi_theunissen)
[![License](https://img.shields.io/packagist/l/rtheunissen/guzzle-log-middleware.svg?style=flat-square)](https://packagist.org/packages/rtheunissen/guzzle-log-middleware)
[![Latest Version](https://img.shields.io/packagist/v/rtheunissen/guzzle-log-middleware.svg?style=flat-square)](https://packagist.org/packages/rtheunissen/guzzle-log-middleware)
[![Build Status](https://img.shields.io/travis/rtheunissen/guzzle-log-middleware.svg?style=flat-square&branch=master)](https://travis-ci.org/rtheunissen/guzzle-log-middleware)
[![Scrutinizer](https://img.shields.io/scrutinizer/g/rtheunissen/guzzle-log-middleware.svg?style=flat-square)](https://scrutinizer-ci.com/g/rtheunissen/guzzle-log-middleware/)
[![Scrutinizer Coverage](https://img.shields.io/scrutinizer/coverage/g/rtheunissen/guzzle-log-middleware.svg?style=flat-square)](https://scrutinizer-ci.com/g/rtheunissen/guzzle-log-middleware/)

## Installation

```bash
composer require rtheunissen/guzzle-log-middleware
```

## Usage
Requires an instance of `Psr\Log\LoggerInterface` and an optional `GuzzleHttp\MessageFormatter`.

`MultiLogger` logs the request when it is sent, and the response when it is received.

`Logger` logs a combined message when the response is received.

```php
use Concat\Http\Middleware\Logger;
use Concat\Http\Middleware\MultiLogger;

$handlerStack->push(new Logger($logger, $formatter));

// or

$handlerStack->push(new MultiLogger($logger, $formatter));
```

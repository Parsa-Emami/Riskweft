<?php

use Monolog\Processor\PsrLogMessageProcessor;

return [
    'default' => env('LOG_CHANNEL', 'stack'),
    'deprecations' => [
        'channel' => 'null',
        'trace' => false,
    ],

    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => ['structured'],
            'ignore_exceptions' => false,
        ],

        // JSON lines on stdout: container-friendly and easy for an OTEL
        // collector / log shipper to parse. Redaction is enforced in
        // App\Infrastructure\Audit and App\Http\Support (see
        // tests/Feature/Api/V1/*RedactionTest.php), never here - a logging
        // channel must not be the last line of defense against leaking a
        // secret or full architecture payload.
        'structured' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'info'),
            'handler' => Monolog\Handler\StreamHandler::class,
            'formatter' => Monolog\Formatter\JsonFormatter::class,
            'with' => ['stream' => 'php://stdout'],
            'processors' => [PsrLogMessageProcessor::class],
        ],

        'stderr' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'info'),
            'handler' => Monolog\Handler\StreamHandler::class,
            'formatter' => Monolog\Formatter\JsonFormatter::class,
            'with' => ['stream' => 'php://stderr'],
        ],

        'null' => [
            'driver' => 'monolog',
            'handler' => Monolog\Handler\NullHandler::class,
        ],
    ],
];

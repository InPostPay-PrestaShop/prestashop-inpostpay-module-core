<?php

namespace izi\prestashop;

use izi\interfaces\LoggerInterface;

class Logger implements LoggerInterface
{
    public static function log(string $message): void
    {
        self::write('general.log', $message);
    }

    public static function response(string $message, string $header = ''): void
    {
        if ('' !== $header) {
            $message = $header . PHP_EOL . $message;
        }

        self::write('response.log', $message);
    }

    public static function request(string $url, string $method, string $payload, int $length, string $response, int $code): void
    {
        $message = "URL: {$url}\nMETHOD: {$method}\nLENGTH:{$length}\nDATA:\n{$payload}\n\nRESPONSE CODE: {$code}\nRESPONSE:\n{$response}";

        self::write('request.log', $message);
    }

    private static function write(string $filename, string $data): void
    {
        file_put_contents(__DIR__ . '/' . $filename, date('Y-m-d H:i:s') . PHP_EOL . $data . PHP_EOL . PHP_EOL, FILE_APPEND);
    }
}

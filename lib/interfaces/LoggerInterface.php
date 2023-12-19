<?php

namespace izi\interfaces;

interface LoggerInterface
{
    public static function log(string $message);

    public static function response(string $message, string $header = '');

    public static function request(string $url, string $method, string $payload, int $length, string $response, int $code);
}

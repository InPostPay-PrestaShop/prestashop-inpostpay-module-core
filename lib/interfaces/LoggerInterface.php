<?php

namespace izi\interfaces;

interface LoggerInterface
{
    /**
     * @param string $message
     */
    public static function log(string $message);

    /**
     * @param string $message
     * @param string $header
     */
    public static function response(string $message, string $header = '');

    /**
     * @param string $url
     * @param string $method
     * @param string $payload
     * @param int $length
     * @param string $response
     * @param int $code
     */
    public static function request(string $url, string $method, string $payload, int $length, string $response, int $code);
}

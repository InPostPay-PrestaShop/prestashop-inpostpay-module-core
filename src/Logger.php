<?php

namespace izi\prestashop;

class Logger
{
    public static function log($data)
    {
        self::write('general.log', $data);
    }

    public static function spam($data)
    {
        self::write('spam.log', $data);
    }

    public static function response($data, $header = '')
    {
        self::write('response.log', $header . PHP_EOL . $data);
    }

    public static function dataRead($data, $header = '')
    {
        self::write('data.log', $header . PHP_EOL . $data);
    }

    public static function request($url, $type, $data, $length, $response, $code, $header = '')
    {
        $data = "URL: {$url}\nMETHOD: {$type}\nLENGTH:{$length}\nDATA:\n{$data}\n\nRESPONSE CODE: {$code}\nRESPONSE:\n{$response}\n\n";
        self::write('request.log', $header . PHP_EOL . $data);
    }

    private static function write($filename, $data)
    {
//        if (defined('IZI_LOGGER')) {
        file_put_contents(__DIR__ . '/' . $filename, date('Y-m-d H:i:s') . PHP_EOL . $data . PHP_EOL . PHP_EOL, FILE_APPEND);
//        }
    }
}

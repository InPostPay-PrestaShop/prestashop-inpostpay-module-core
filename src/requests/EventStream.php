<?php

namespace izi\prestashop\requests;

abstract class EventStream extends Base
{
    public function __construct()
    {
        $this->sseHeaders();
    }

    protected function sseHeaders()
    {
        @ini_set('zlib.output_compression', 0);
        @ini_set('implicit_flush', 1);
        @ini_set('auto_detect_line_endings', 1);
        if (function_exists('apache_setenv')) {
            @apache_setenv('no-gzip', 1);
        }
        ob_end_clean();
        gc_enable();
        ob_implicit_flush(1);

        header('X-Accel-Buffering: no');
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-store');
        header('Connection: keep-alive');
        header('Access-Control-Expose-Headers: X-Events');
    }

    protected function sendEventMessage($event, $data)
    {
        $jsonData = json_encode($data);
        echo "event: $event\n";
        echo "data: $jsonData\n\n";
    }

    protected function sendHelloMessage()
    {
        echo ": start\n\n";
    }
}

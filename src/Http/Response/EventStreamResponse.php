<?php

declare(strict_types=1);

namespace izi\prestashop\Http\Response;

use Symfony\Component\HttpFoundation\StreamedResponse;

final class EventStreamResponse extends StreamedResponse
{
    public function __construct(?callable $callback = null, int $status = 200, array $headers = [])
    {
        $headers = array_merge($headers, [
            'X-Accel-Buffering' => 'no',
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-store',
            'Connection' => 'keep-alive',
            'Access-Control-Expose-Headers' => 'X-Events',
        ]);

        parent::__construct($callback, $status, $headers);
    }

    public function sendContent(): self
    {
        if ($this->streamed) {
            return $this;
        }

        @ini_set('zlib.output_compression', '0');
        @ini_set('implicit_flush', '1');
        @ini_set('auto_detect_line_endings', '1');
        if (function_exists('apache_setenv')) {
            @apache_setenv('no-gzip', '1');
        }

        ob_end_clean();
        gc_enable();
        ob_implicit_flush(80000 <= PHP_VERSION_ID ? true : 1);
        session_write_close();

        parent::sendContent();

        return $this;
    }
}

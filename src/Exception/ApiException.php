<?php

namespace izi\prestashop\Exception;

abstract class ApiException extends \RuntimeException
{
    abstract public function getErrorCode(): string;

    abstract public function getResponseCode(): int;

    final public function sendResponse(): void
    {
        http_response_code($this->getResponseCode());

        die(json_encode([
            'error_code' => $this->getErrorCode(),
            'error_message' => $this->getMessage(),
        ]));
    }
}

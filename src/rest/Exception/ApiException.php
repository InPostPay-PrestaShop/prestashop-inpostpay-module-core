<?php

namespace izi\prestashop\rest\Exception;

abstract class ApiException extends \RuntimeException
{
    abstract public function getErrorCode(): string;

    abstract public function getStatusCode(): int;
}

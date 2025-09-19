<?php

declare(strict_types=1);

namespace izi\prestashop\Extension\Exception;

use izi\prestashop\Http\Exception\HttpExceptionInterface;
use izi\prestashop\Http\Exception\HttpExceptionTrait;

class HttpException extends ExtensionServiceException implements HttpExceptionInterface
{
    use HttpExceptionTrait;
}

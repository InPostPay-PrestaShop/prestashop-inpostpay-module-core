<?php

declare(strict_types=1);

namespace izi\prestashop\Http\Exception;

class ClientException extends \RuntimeException implements HttpExceptionInterface
{
    use HttpExceptionTrait;
}

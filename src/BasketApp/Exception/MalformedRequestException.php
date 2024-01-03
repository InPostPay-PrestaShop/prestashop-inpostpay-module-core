<?php

declare(strict_types=1);

namespace izi\prestashop\BasketApp\Exception;

final class MalformedRequestException extends BadRequestException
{
    public const ERROR_CODE = 'MALFORMED_REQUEST';
}

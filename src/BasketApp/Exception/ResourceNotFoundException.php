<?php

declare(strict_types=1);

namespace izi\prestashop\BasketApp\Exception;

class ResourceNotFoundException extends BasketAppException
{
    public const ERROR_CODE = 'NOT_FOUND';
}

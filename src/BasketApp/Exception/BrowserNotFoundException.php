<?php

declare(strict_types=1);

namespace izi\prestashop\BasketApp\Exception;

final class BrowserNotFoundException extends ResourceNotFoundException
{
    public const ERROR_CODE = 'BROWSER_NOT_FOUND';
}

<?php

declare(strict_types=1);

namespace izi\prestashop\BasketApp\Exception;

final class PublicKeyNotFoundException extends ResourceNotFoundException
{
    public const ERROR_CODE = 'PUBLIC_KEY_NOT_FOUND';
}

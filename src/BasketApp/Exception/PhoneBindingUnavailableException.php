<?php

declare(strict_types=1);

namespace izi\prestashop\BasketApp\Exception;

final class PhoneBindingUnavailableException extends BasketAppException
{
    public const ERROR_CODE = 'PHONE_BINDING_METHOD_UNAVAILABLE';
}

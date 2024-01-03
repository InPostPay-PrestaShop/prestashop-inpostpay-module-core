<?php

declare(strict_types=1);

namespace izi\prestashop\BasketApp\Exception;

final class MerchantDisabledException extends BasketAppException
{
    public const ERROR_CODE = 'MERCHANT_DISABLE';
}

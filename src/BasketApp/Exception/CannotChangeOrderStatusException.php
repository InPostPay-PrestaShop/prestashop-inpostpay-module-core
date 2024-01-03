<?php

declare(strict_types=1);

namespace izi\prestashop\BasketApp\Exception;

final class CannotChangeOrderStatusException extends BasketAppException
{
    public const ERROR_CODE = 'STATUS_ORDER_ERROR';
}

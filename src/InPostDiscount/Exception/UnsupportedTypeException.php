<?php

declare(strict_types=1);

namespace izi\prestashop\InPostDiscount\Exception;

final class UnsupportedTypeException extends \UnexpectedValueException
{
    public static function create(string $type): self
    {
        return new self(\sprintf('Discount type "%s" is not supported.', $type));
    }
}

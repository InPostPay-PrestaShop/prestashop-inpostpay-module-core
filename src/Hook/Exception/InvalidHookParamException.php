<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Exception;

final class InvalidHookParamException extends InvalidArgumentException
{
    public static function unexpectedType(string $paramName, $value, string $expectedType): self
    {
        return new self(\sprintf('Expected parameter "%s" of type "%s", "%s" given.', $paramName, $expectedType, get_debug_type($value)));
    }
}

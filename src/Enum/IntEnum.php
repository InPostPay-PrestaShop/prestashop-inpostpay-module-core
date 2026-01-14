<?php

namespace izi\prestashop\Enum;

/**
 * @extends Enum<int>
 */
abstract class IntEnum extends Enum
{
    /**
     * @return static
     */
    final public static function from(?int $value): self
    {
        $value = (int) $value;
        $cases = static::casesByValue();

        if (!isset($cases[$value])) {
            throw new \UnexpectedValueException(\sprintf('%d is not a valid backing value for enum "%s"', $value, static::class));
        }

        return $cases[$value];
    }

    /**
     * @return static|null
     */
    final public static function tryFrom(?int $value): ?self
    {
        $cases = self::casesByValue();

        return $cases[(int) $value] ?? null;
    }
}

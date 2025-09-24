<?php

namespace izi\prestashop\Enum;

/**
 * @extends Enum<string>
 */
abstract class StringEnum extends Enum
{
    /**
     * @final
     *
     * @return static
     */
    public static function from(?string $value): self
    {
        $value = $value ?? '0';
        $cases = static::casesByValue();

        if (!isset($cases[$value])) {
            throw new \UnexpectedValueException(sprintf('"%s" is not a valid backing value for enum "%s"', $value, static::class));
        }

        return $cases[$value];
    }

    /**
     * @final
     *
     * @return static|null
     */
    public static function tryFrom(?string $value): ?self
    {
        $cases = self::casesByValue();

        return $cases[$value ?? '0'] ?? null;
    }
}

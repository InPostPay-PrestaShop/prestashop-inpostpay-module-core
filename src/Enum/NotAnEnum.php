<?php

declare(strict_types=1);

namespace izi\prestashop\Enum;

/**
 * When modeling as an enum was a mistake...
 *
 * @internal
 */
abstract class NotAnEnum extends StringEnum
{
    /**
     * @var array<string, static>
     */
    protected static $custom = [];

    /**
     * @return static
     */
    final public static function from(?string $value): StringEnum
    {
        $cases = static::casesByValue();

        if (null === $value && !isset($cases['0'])) {
            throw new \UnexpectedValueException(\sprintf('"%s" is not a valid backing value for enum "%s"', $value, static::class));
        }

        return $cases[$value ?? '0'] ?? self::getInstance($value);
    }

    /**
     * @return static|null
     */
    final public static function tryFrom(?string $value): ?StringEnum
    {
        $cases = self::casesByValue();

        if (null === $value && !isset($cases['0'])) {
            return null;
        }

        return $cases[$value ?? '0'] ?? self::getInstance($value);
    }

    /**
     * @return static
     */
    private static function getInstance(string $value): self
    {
        if (isset(static::$custom[$value])) {
            return static::$custom[$value];
        }

        $class = new \ReflectionClass(static::class);

        $constructor = $class->getConstructor();
        if (80100 > \PHP_VERSION_ID) {
            $constructor->setAccessible(true);
        }

        $instance = $class->newInstanceWithoutConstructor();
        $constructor->invoke($instance, 'Other', $value);

        return static::$custom[$value] = $instance;
    }
}

<?php

namespace izi\prestashop\Enum;

/**
 * @template T
 *
 * @property string $name
 * @property T $value
 *
 * @method static static from($value)
 * @method static static|null tryFrom($value)
 *
 * @internal
 */
abstract class Enum implements \JsonSerializable
{
    /**
     * @var string
     *
     * @readonly
     */
    private $_name;

    /**
     * @var T
     *
     * @readonly
     */
    private $_value;

    private static $casesByName = [];
    private static $casesByValue = [];

    /**
     * @param T $value
     */
    final private function __construct(string $name, $value)
    {
        $this->_name = $name;
        $this->_value = $value;
    }

    /**
     * @return static
     */
    final public static function __callStatic(string $name, array $arguments)
    {
        $cases = self::casesByName();

        if (!isset($cases[$name])) {
            throw new \DomainException(\sprintf('Case "%s" does not exist in "%s".', $name, static::class));
        }

        return $cases[$name];
    }

    final public static function compareValues(self $e1, self $e2): int
    {
        return $e1->_value <=> $e2->_value;
    }

    /**
     * @return static[]
     */
    final public static function cases(): array
    {
        return array_values(static::casesByName());
    }

    /**
     * @return string|T|null
     */
    final public function __get(string $property)
    {
        switch ($property) {
            case 'name':
                return $this->_name;
            case 'value':
                return $this->_value;
            default:
                trigger_error(\sprintf('Undefined property: %s::$%s', static::class, $property), \E_USER_WARNING);

                return null;
        }
    }

    final public function __set(string $property, $value): void
    {
        switch ($property) {
            case 'name':
            case 'value':
                throw new \Error(\sprintf('Cannot redefine readonly property %s::$%s', static::class, $property));
            default:
                throw new \Error(\sprintf('Cannot create dynamic property %s::$%s', static::class, $property));
        }
    }

    final public function __isset(string $property): bool
    {
        switch ($property) {
            case 'name':
            case 'value':
                return true;
            default:
                return false;
        }
    }

    final public function __unset(string $property): void
    {
        switch ($property) {
            case 'name':
            case 'value':
                throw new \Error(\sprintf('Cannot unset readonly property %s::$%s', static::class, $property));
            default:
                break;
        }
    }

    /**
     * @return T
     */
    #[\ReturnTypeWillChange]
    final public function jsonSerialize()
    {
        return $this->_value;
    }

    /**
     * @return array<string, static>
     */
    final protected static function casesByName(): array
    {
        $class = static::class;

        if (!isset(self::$casesByName[$class])) {
            self::init($class);
        }

        return self::$casesByName[$class];
    }

    /**
     * @return array<T, static>
     */
    final protected static function casesByValue(): array
    {
        $class = static::class;

        if (!isset(self::$casesByValue[$class])) {
            self::init($class);
        }

        return self::$casesByValue[$class];
    }

    private static function init(string $class): void
    {
        self::$casesByName[$class] = self::$casesByValue[$class] = [];

        $reflection = new \ReflectionClass($class);

        foreach ($reflection->getConstants() as $name => $value) {
            $name = str_replace(' ', '', ucwords(str_replace('_', ' ', strtolower($name))));
            $case = new static($name, $value);
            self::$casesByName[$class][$name] = $case;
            self::$casesByValue[$class][$value] = $case;
        }
    }

    private function __clone()
    {
    }
}

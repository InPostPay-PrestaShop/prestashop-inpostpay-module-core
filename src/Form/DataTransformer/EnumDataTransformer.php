<?php

declare(strict_types=1);

namespace izi\prestashop\Form\DataTransformer;

use izi\prestashop\Enum\Enum;
use izi\prestashop\Enum\IntEnum;
use izi\prestashop\Enum\StringEnum;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

final class EnumDataTransformer implements DataTransformerInterface
{
    /**
     * @var string
     */
    private $className;

    /**
     * @param class-string<StringEnum|IntEnum> $className
     */
    public function __construct(string $className)
    {
        $this->className = $className;
    }

    public function transform($value)
    {
        if (null === $value) {
            return null;
        }

        if (!$value instanceof Enum) {
            throw new TransformationFailedException('Expected an enum.');
        }

        return $value->value;
    }

    public function reverseTransform($value)
    {
        if (null === $value) {
            return null;
        }

        if (!is_string($value) && !is_int($value)) {
            throw new TransformationFailedException('Expected a string or an integer.');
        }

        /** @var class-string<StringEnum|IntEnum> $class */
        $class = $this->className;

        try {
            return $class::from($value);
        } catch (\Exception $e) {
            throw new TransformationFailedException($e->getMessage(), $e->getCode(), $e);
        }
    }
}

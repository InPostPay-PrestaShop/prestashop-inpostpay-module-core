<?php

declare(strict_types=1);

namespace izi\prestashop\Form\DataTransformer;

use izi\prestashop\ObjectModel\ObjectManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * @template T of \ObjectModel
 */
final class ObjectModelToIdTransformer implements DataTransformerInterface
{
    /**
     * @var ObjectManagerInterface
     */
    private $manager;

    /**
     * @var class-string<T>
     */
    private $class;

    /**
     * @var int|null
     */
    private $languageId;

    /**
     * @var int|null
     */
    private $shopId;

    /**
     * @param class-string<T> $class
     */
    public function __construct(ObjectManagerInterface $manager, string $class, ?int $languageId = null, ?int $shopId = null)
    {
        $this->manager = $manager;
        $this->shopId = $shopId;
        $this->languageId = $languageId;
        $this->class = $class;
    }

    /**
     * @param T|null $value
     */
    public function transform($value): ?int
    {
        if (null === $value) {
            return null;
        }

        if (!$value instanceof $this->class) {
            throw new TransformationFailedException(\sprintf('Expected an instance of "%s".', $this->class));
        }

        return (int) $value->id;
    }

    /**
     * @return T|null
     */
    public function reverseTransform($value): ?\ObjectModel
    {
        if (null === $value) {
            return null;
        }

        if (!\is_int($value)) {
            throw new TransformationFailedException('Expected an integer.');
        }

        return $this->manager->find($this->class, $value, $this->languageId, $this->shopId);
    }
}

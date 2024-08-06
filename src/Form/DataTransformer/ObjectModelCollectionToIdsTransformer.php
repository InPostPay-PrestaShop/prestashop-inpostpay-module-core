<?php

declare(strict_types=1);

namespace izi\prestashop\Form\DataTransformer;

use izi\prestashop\ObjectModel\ObjectManagerInterface;
use izi\prestashop\ObjectModel\OrderMaintainingLoaderTrait;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * @template T of \ObjectModel
 */
final class ObjectModelCollectionToIdsTransformer implements DataTransformerInterface
{
    use OrderMaintainingLoaderTrait;

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
        $this->class = $class;
        $this->languageId = $languageId;
        $this->shopId = $shopId;
    }

    public function transform($value): ?array
    {
        if (null === $value) {
            return null;
        }

        if (!is_array($value)) {
            throw new TransformationFailedException('Expected an array.');
        }

        return array_map(function ($element): int {
            if (!$element instanceof $this->class) {
                throw new TransformationFailedException(sprintf('Expected an instance of "%s".', $this->class));
            }

            return (int) $element->id;
        }, $value);
    }

    /**
     * @return T[]|null
     */
    public function reverseTransform($value): ?array
    {
        if (null === $value) {
            return null;
        }

        if (!is_array($value)) {
            throw new TransformationFailedException('Expected an array.');
        }

        return $this->findByIdsMaintainingOrder(
            $this->class,
            $value,
            $this->languageId,
            $this->shopId
        );
    }
}

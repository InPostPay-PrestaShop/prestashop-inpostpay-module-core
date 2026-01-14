<?php

declare(strict_types=1);

namespace izi\prestashop\Form\DataTransformer;

use izi\prestashop\ObjectModel\Repository\CombinationRepository;
use izi\prestashop\ObjectModel\Repository\ObjectRepositoryInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

final class CombinationToAttributeIdsTransformer implements DataTransformerInterface
{
    /**
     * @var CombinationRepository
     */
    private $repository;

    /**
     * @var int
     */
    private $productId;

    /**
     * @param CombinationRepository $repository
     */
    public function __construct(ObjectRepositoryInterface $repository, int $productId)
    {
        $this->repository = $repository;
        $this->productId = $productId;
    }

    /**
     * @param \Combination|null $value
     *
     * @return int[]|null
     */
    public function transform($value): ?array
    {
        if (null === $value) {
            return null;
        }

        if (!$value instanceof \Combination) {
            throw new TransformationFailedException(\sprintf('Expected an instance of %s.', \Combination::class));
        }

        if ($this->productId !== (int) $this->productId) {
            throw new TransformationFailedException(\sprintf('Expected a combination of product #%d.', $this->productId));
        }

        $attributes = $this->repository->getSimpleAttributesByCombinationId((int) $value->id);

        return array_map(static function ($attribute): int {
            return $attribute->id;
        }, $attributes);
    }

    /**
     * @param int[]|null $value
     */
    public function reverseTransform($value): ?\Combination
    {
        if (null === $value) {
            return null;
        }

        if (!\is_array($value)) {
            throw new TransformationFailedException('Expected an array.');
        }

        $attributeIds = array_map('intval', $value);
        $combination = $this->repository->findByProductAndAttributeIds($this->productId, ...$attributeIds);

        if (null === $combination) {
            throw new TransformationFailedException('Combination does not exist.');
        }

        return $combination;
    }
}

<?php

declare(strict_types=1);

namespace izi\prestashop\HotProduct;

use izi\prestashop\HotProduct\Exception\InvalidProductDataException;
use izi\prestashop\Product\ReferenceId;

class HotProduct
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var int
     */
    private $productId;

    /**
     * @var int|null
     */
    private $combinationId;

    /**
     * @var int|null
     */
    private $shopId;

    /**
     * @var ReferenceId
     */
    private $referenceId;

    /**
     * @var \DateTimeImmutable|null
     */
    private $availableFrom;

    /**
     * @var \DateTimeImmutable|null
     */
    private $availableTo;

    /**
     * @var \DateTimeImmutable|null
     */
    private $createdAt;

    /**
     * @var \DateTimeImmutable|null
     */
    private $updatedAt;

    public function __construct(int $productId, ?int $combinationId, ?int $shopId = null, ?\DateTimeImmutable $availableFrom = null, ?\DateTimeImmutable $availableTo = null, ?ReferenceId $referenceId = null)
    {
        if (null !== $referenceId && !self::validateReferenceId($referenceId, $productId, $combinationId)) {
            throw new InvalidProductDataException('Reference ID does not match product or combination ID.');
        }

        $this->productId = $productId;
        $this->combinationId = $combinationId;
        $this->shopId = $shopId;
        $this->referenceId = $referenceId ?? ReferenceId::create($productId, $combinationId);
        $this->setAvailability($availableFrom, $availableTo);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProductId(): int
    {
        return $this->productId;
    }

    public function getCombinationId(): ?int
    {
        return $this->combinationId;
    }

    public function getShopId(): ?int
    {
        return $this->shopId;
    }

    public function getReferenceId(): ReferenceId
    {
        return $this->referenceId;
    }

    public function getAvailableFrom(): ?\DateTimeImmutable
    {
        return $this->availableFrom;
    }

    public function getAvailableTo(): ?\DateTimeImmutable
    {
        return $this->availableTo;
    }

    public function setAvailability(?\DateTimeImmutable $from, ?\DateTimeImmutable $to): void
    {
        if (null !== $from && null !== $to && $from > $to) {
            throw new InvalidProductDataException('Start date cannot be later than end date.');
        }

        $this->availableFrom = $from;
        $this->availableTo = $to;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    private static function validateReferenceId(ReferenceId $referenceId, int $productId, ?int $combinationId): bool
    {
        if ($referenceId->getProductId() !== $productId) {
            return false;
        }

        if (null === $referenceId->getCombinationId()) {
            return true;
        }

        return $combinationId === $referenceId->getCombinationId();
    }
}

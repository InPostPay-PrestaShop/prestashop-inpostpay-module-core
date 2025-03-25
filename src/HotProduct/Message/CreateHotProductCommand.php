<?php

declare(strict_types=1);

namespace izi\prestashop\HotProduct\Message;

use izi\prestashop\HotProduct\MessageHandler\CreateHotProductHandler;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @see CreateHotProductHandler
 */
final class CreateHotProductCommand
{
    /**
     * @var int|null
     *
     * @Assert\GreaterThan(0)
     */
    private $shopId;

    /**
     * @var int|null
     *
     * @Assert\NotNull()
     * @Assert\GreaterThan(0)
     */
    private $productId;

    /**
     * @var int|null
     *
     * @Assert\GreaterThan(0)
     */
    private $combinationId;

    /**
     * @var \DateTimeImmutable|null
     */
    private $availableFrom;

    /**
     * @var \DateTimeImmutable|null
     *
     * @!Assert\GreaterThan(propertyPath="availableFrom") TODO
     */
    private $availableTo;

    public function __construct(?int $shopId = null)
    {
        $this->shopId = $shopId;
    }

    public function getShopId(): ?int
    {
        return $this->shopId;
    }

    public function getProductId(): ?int
    {
        return $this->productId;
    }

    public function setProductId(?int $productId): self
    {
        $this->productId = $productId;

        return $this;
    }

    public function getCombinationId(): ?int
    {
        return $this->combinationId;
    }

    public function setCombinationId(?int $combinationId): self
    {
        $this->combinationId = $combinationId;

        return $this;
    }

    public function getAvailableFrom(): ?\DateTimeImmutable
    {
        return $this->availableFrom;
    }

    public function setAvailableFrom(?\DateTimeImmutable $availableFrom): self
    {
        $this->availableFrom = $availableFrom;

        return $this;
    }

    public function getAvailableTo(): ?\DateTimeImmutable
    {
        return $this->availableTo;
    }

    public function setAvailableTo(?\DateTimeImmutable $availableTo): self
    {
        $this->availableTo = $availableTo;

        return $this;
    }

    /**
     * @internal
     *
     * @Assert\IsTrue(message="Please choose a valid date interval.")
     */
    public function hasValidAvailabilityDates(): bool
    {
        if (null === $this->availableFrom || null === $this->availableTo) {
            return true;
        }

        return $this->availableFrom < $this->availableTo;
    }
}

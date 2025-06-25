<?php

declare(strict_types=1);

namespace izi\prestashop\HotProduct\Message;

use izi\prestashop\HotProduct\HotProduct;
use izi\prestashop\HotProduct\MessageHandler\UpdateHotProductHandler;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @see UpdateHotProductHandler
 */
final class UpdateHotProductCommand
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var bool
     */
    private $updateAvailability = false;

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

    /**
     * @var bool
     */
    private $createIfNotFound = false;

    public function __construct(int $id)
    {
        $this->id = $id;
    }

    public static function for(HotProduct $product): self
    {
        $command = new self($product->getId());
        $command->availableFrom = $product->getAvailableFrom();
        $command->availableTo = $product->getAvailableTo();
        $command->updateAvailability = true;

        return $command;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function updateAvailability(): bool
    {
        return $this->updateAvailability;
    }

    public function setUpdateAvailability(bool $updateAvailability): self
    {
        $this->updateAvailability = $updateAvailability;

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

    public function createIfNotFound(): bool
    {
        return $this->createIfNotFound;
    }

    public function setCreateIfNotFound(bool $createIfNotFound): self
    {
        $this->createIfNotFound = $createIfNotFound;

        return $this;
    }
}

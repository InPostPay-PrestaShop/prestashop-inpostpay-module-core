<?php

declare(strict_types=1);

namespace izi\prestashop\Cart\Storage;

use izi\prestashop\Cart\Storage\Exception\StorageUnavailableException;

final class ChainBasketIdStorage implements BasketIdStorageInterface
{
    /**
     * @var iterable<BasketIdStorageInterface>
     */
    private $storages;

    /**
     * @param iterable<BasketIdStorageInterface> $storages
     */
    public function __construct(iterable $storages)
    {
        if (0 === \count($storages)) {
            throw new \LogicException('At least one storage must be provided.');
        }

        $this->storages = $storages;
    }

    /**
     * @internal
     */
    public static function createDefault(\Context $context): self
    {
        return new self([
            new SessionBasketIdStorage(\InPostIzi::getInstance()->getRequestStack()),
            new CustomerCookieBasketIdStorage($context),
        ]);
    }

    public function getBasketId(): ?string
    {
        foreach ($this->storages as $storage) {
            try {
                return $storage->getBasketId();
            } catch (StorageUnavailableException $e) {
                // continue to the next storage
            }
        }

        \assert(isset($e));

        throw $e;
    }

    public function setBasketId(?string $basketId): void
    {
        $stored = false;

        foreach ($this->storages as $storage) {
            try {
                $storage->setBasketId($basketId);
                $stored = true;
            } catch (StorageUnavailableException $e) {
                // continue to the next storage
            }
        }

        if (!$stored) {
            \assert(isset($e));

            throw $e;
        }
    }
}

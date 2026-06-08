<?php

declare(strict_types=1);

namespace izi\prestashop\Cart\Storage;

interface BasketIdStorageInterface
{
    public function getBasketId(): ?string;

    /**
     * @param string|null $basketId a basket ID or null to remove the current ID from the storage
     */
    public function setBasketId(?string $basketId): void;
}

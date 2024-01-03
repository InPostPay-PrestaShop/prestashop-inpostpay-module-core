<?php

declare(strict_types=1);

namespace izi\prestashop\Repository;

use izi\prestashop\Entities\BasketInterface;
use izi\prestashop\Entities\BasketSessionInterface;

/**
 * @template T of BasketSessionInterface
 */
interface BasketSessionRepositoryInterface
{
    /**
     * @return T|null
     */
    public function findByBasketId(string $basketId): ?BasketSessionInterface;

    /**
     * @param int|string $id native basket ID
     *
     * @return T|null
     */
    public function findByEntityId($id): ?BasketSessionInterface;

    /**
     * @param string $id
     *
     * @return T|null
     */
    public function findByOrderId(string $id): ?BasketSessionInterface;

    /**
     * @return T
     */
    public function createNewSession(BasketInterface $basket): BasketSessionInterface;

    /**
     * @param T $session
     */
    public function persist(BasketSessionInterface $session);

    public function refresh(BasketSessionInterface $session);
}

<?php

declare(strict_types=1);

namespace izi\prestashop\Builder\Order;

use izi\prestashop\Configuration\OrdersConfigurationInterface;
use izi\prestashop\ObjectModel\Repository\ObjectRepositoryInterface;

class OrderStatusDescriptionProvider
{
    /**
     * @var ObjectRepositoryInterface<\OrderState>
     */
    private $repository;

    /**
     * @var OrdersConfigurationInterface
     */
    private $configuration;

    /**
     * @param ObjectRepositoryInterface<\OrderState> $repository
     */
    public function __construct(ObjectRepositoryInterface $repository, OrdersConfigurationInterface $configuration)
    {
        $this->repository = $repository;
        $this->configuration = $configuration;
    }

    public function getStatus(\Order $order): string
    {
        $orderStateId = (int) $order->current_state;
        $languageId = (int) $order->id_lang;

        return $this->configuration->getStatusDescription($orderStateId, $languageId, (int) $order->id_shop)
            ?? $this->getOrderStateName($orderStateId, $languageId);
    }

    private function getOrderStateName(int $orderStateId, int $languageId): string
    {
        if (null === $orderState = $this->repository->find($orderStateId, $languageId)) {
            throw new \RuntimeException(sprintf('Order state #%d does not exist.', $orderStateId));
        }

        return (string) $orderState->name;
    }
}

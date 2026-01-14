<?php

declare(strict_types=1);

namespace izi\prestashop\Builder\Order;

use izi\prestashop\ObjectModel\Repository\ObjectRepositoryInterface;
use Psr\Clock\ClockInterface;

final class OrderEventBuilderFactory implements OrderEventBuilderFactoryInterface
{
    /**
     * @var ObjectRepositoryInterface<\Order>
     */
    private $repository;

    /**
     * @var OrderStatusDescriptionProvider
     */
    private $statusDescriptionProvider;

    /**
     * @var ClockInterface
     */
    private $clock;

    /**
     * @param ObjectRepositoryInterface<\Order> $repository
     */
    public function __construct(ObjectRepositoryInterface $repository, OrderStatusDescriptionProvider $statusDescriptionProvider, ClockInterface $clock)
    {
        $this->repository = $repository;
        $this->statusDescriptionProvider = $statusDescriptionProvider;
        $this->clock = $clock;
    }

    public function create(int $orderId): OrderEventBuilderInterface
    {
        if (null === $order = $this->repository->find($orderId)) {
            throw new \DomainException(\sprintf('Order "%s" does not exist.', $orderId));
        }

        return new OrderEventBuilder($order, $this->clock, $this->statusDescriptionProvider);
    }
}

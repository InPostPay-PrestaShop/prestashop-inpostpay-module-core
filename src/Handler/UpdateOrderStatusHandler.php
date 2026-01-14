<?php

declare(strict_types=1);

namespace izi\prestashop\Handler;

use izi\prestashop\BasketApp\Order\OrdersApiClientInterface;
use izi\prestashop\Builder\Order\OrderEventBuilderFactoryInterface;
use izi\prestashop\Command\UpdateOrderStatusCommand;
use izi\prestashop\Repository\BasketSessionRepositoryInterface;

final class UpdateOrderStatusHandler implements UpdateOrderStatusHandlerInterface
{
    use CommandHandlerTrait;

    /**
     * @var BasketSessionRepositoryInterface
     */
    private $sessionRepository;

    /**
     * @var OrderEventBuilderFactoryInterface
     */
    private $eventBuilderFactory;

    /**
     * @var OrdersApiClientInterface
     */
    private $client;

    public function __construct(BasketSessionRepositoryInterface $sessionRepository, OrderEventBuilderFactoryInterface $eventBuilderFactory, OrdersApiClientInterface $client)
    {
        $this->sessionRepository = $sessionRepository;
        $this->eventBuilderFactory = $eventBuilderFactory;
        $this->client = $client;
    }

    public function __invoke(UpdateOrderStatusCommand $command): void
    {
        if (null === $this->sessionRepository->findByOrderId($orderId = $command->getOrderId())) {
            return;
        }

        $eventTime = $command->getEventTime();
        $eventId = $this->generateEventId($orderId, $eventTime);

        $event = $this->eventBuilderFactory
            ->create((int) $orderId)
            ->setEventId($eventId)
            ->setEventTime($eventTime)
            ->setOrderStatus($command->getStatus())
            ->build();

        $this->client->updateOrder($orderId, $event);
    }

    private function generateEventId(string $orderId, \DateTimeImmutable $eventTime): string
    {
        return \sprintf('OS_%s_%d', $orderId, $eventTime->getTimestamp());
    }
}

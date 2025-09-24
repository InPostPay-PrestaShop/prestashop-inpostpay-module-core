<?php

declare(strict_types=1);

namespace izi\prestashop\EventListener;

use izi\prestashop\Command\UpdateOrderAddressDeliveryCommand;
use izi\prestashop\Command\UpdateOrderStatusCommand;
use izi\prestashop\CommandBusInterface;
use izi\prestashop\Common\Order\MerchantOrderStatus;
use izi\prestashop\Configuration\ApiConfigurationInterface;
use izi\prestashop\Event\OrderEvent;
use izi\prestashop\Event\OrderStatusUpdatedEvent;
use izi\prestashop\ObjectModel\Repository\ObjectRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class OrderListener implements EventSubscriberInterface
{
    /**
     * @var ApiConfigurationInterface
     */
    private $configuration;

    /**
     * @var ObjectRepositoryInterface<\Order>
     */
    private $repository;

    /**
     * @var CommandBusInterface
     */
    private $bus;

    /**
     * @var LoggerInterface
     */
    private $logger;

    private $ordersAddressUpdated = [];

    /**
     * @param ObjectRepositoryInterface<\Order> $repository
     */
    public function __construct(ApiConfigurationInterface $configuration, ObjectRepositoryInterface $repository, CommandBusInterface $bus, LoggerInterface $logger)
    {
        $this->configuration = $configuration;
        $this->repository = $repository;
        $this->bus = $bus;
        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            OrderStatusUpdatedEvent::class => 'onOrderStatusUpdated',
            OrderEvent::BEFORE_UPDATE => 'onBeforeOrderAddressDeliveryUpdated',
            OrderEvent::UPDATED => 'onOrderAddressDeliveryUpdated',
        ];
    }

    public function onOrderStatusUpdated(OrderStatusUpdatedEvent $event): void
    {
        if (null === $this->configuration->getClientCredentials()) {
            return;
        }

        if (0 >= $orderId = $event->getOrderId()) {
            return;
        }

        if (null === $order = $this->repository->find($orderId)) {
            return;
        }

        if ('inpostizi' !== $order->module) {
            return;
        }

        $eventTime = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $order->date_upd);
        $command = new UpdateOrderStatusCommand((string) $event->getOrderId(), $eventTime, $this->getMerchantOrderStatus($order, $event->getNewOrderStatus()));

        try {
            $this->bus->handle($command);
        } catch (\Throwable $e) {
            $this->logger->critical('Could not send order #{orderId} status update event data.', [
                'orderId' => $orderId,
                'exception' => $e,
                'orderStatusId' => (int) $event->getNewOrderStatus()->id,
            ]);
        }
    }

    public function onBeforeOrderAddressDeliveryUpdated(OrderEvent $event): void
    {
        if (null === $this->configuration->getClientCredentials()) {
            return;
        }

        $order = $event->getOrder();

        if ('inpostizi' !== $order->module) {
            return;
        }

        if (0 >= $orderId = (int) $order->id) {
            return;
        }

        if (isset($this->ordersAddressUpdated[$orderId])) {
            return;
        }

        if (null === $currentOrder = $this->repository->find($orderId)) {
            return;
        }

        if ((int) $currentOrder->id_address_delivery === (int) $order->id_address_delivery) {
            return;
        }

        $this->ordersAddressUpdated[$order->id] = true;
    }

    public function onOrderAddressDeliveryUpdated(OrderEvent $event): void
    {
        $order = $event->getOrder();
        $orderId = (int) $event->getOrder()->id;

        if (!isset($this->ordersAddressUpdated[$orderId])) {
            return;
        }

        unset($this->ordersAddressUpdated[$orderId]);

        $eventTime = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $order->date_upd);
        $command = new UpdateOrderAddressDeliveryCommand((string) $orderId, $eventTime);

        try {
            $this->bus->handle($command);
        } catch (\Throwable $e) {
            $this->logger->critical('Could not send order #{orderId} address delivery update event data.', [
                'orderId' => $orderId,
                'exception' => $e,
            ]);
        }
    }

    private function getMerchantOrderStatus(\Order $order, \OrderState $orderState): ?MerchantOrderStatus
    {
        if ($order->getOrderPayments()) {
            return null;
        }

        if ($orderState->id === (int) \Configuration::get('PS_OS_CANCELED')) {
            return MerchantOrderStatus::OrderRejected();
        }

        return null;
    }
}

<?php

declare(strict_types=1);

namespace izi\prestashop\MerchantApi\Handler;

use izi\prestashop\Builder\Order\OrderStatusDescriptionProvider;
use izi\prestashop\Configuration\OrdersConfigurationInterface;
use izi\prestashop\MerchantApi\Command\UpdateOrderCommand;
use izi\prestashop\MerchantApi\Exception\OrderNotFoundException;
use izi\prestashop\MerchantApi\Model\Order\Request\OrderEvent;
use izi\prestashop\MerchantApi\Model\Order\Request\PaymentStatus;
use izi\prestashop\MerchantApi\Model\Order\Response\OrderStatusData;
use izi\prestashop\ObjectModel\Repository\ObjectRepositoryInterface;
use Psr\Log\LoggerInterface;

final class UpdateOrderHandler implements UpdateOrderHandlerInterface
{
    /**
     * @var ObjectRepositoryInterface<\Order>
     */
    private $repository;

    /**
     * @var OrdersConfigurationInterface
     */
    private $configuration;

    /**
     * @var OrderStatusDescriptionProvider
     */
    private $statusDescriptionProvider;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ObjectRepositoryInterface<\Order> $repository
     */
    public function __construct(ObjectRepositoryInterface $repository, OrdersConfigurationInterface $configuration, OrderStatusDescriptionProvider $statusDescriptionProvider, LoggerInterface $logger)
    {
        $this->repository = $repository;
        $this->configuration = $configuration;
        $this->statusDescriptionProvider = $statusDescriptionProvider;
        $this->logger = $logger;
    }

    public static function getHandledCommandClass(): string
    {
        return UpdateOrderCommand::class;
    }

    public function __invoke(UpdateOrderCommand $command): OrderStatusData
    {
        $order = $this->repository->find((int) $command->getOrderId());

        if (null === $order || 'inpostizi' !== $order->module) {
            throw OrderNotFoundException::create();
        }

        $event = $command->getEvent();

        $this->updateOrderStatus($order, $event);
        $this->saveTransactionId($order, $event);

        $status = $this->statusDescriptionProvider->getStatus($order);

        return new OrderStatusData($status);
    }

    private function updateOrderStatus(\Order $order, OrderEvent $event): void
    {
        if (PaymentStatus::Authorized() !== $event->getData()->getPaymentStatus()) {
            return;
        }

        $statusId = $this->configuration->getPaidStatusId((int) $order->id_shop);
        if (0 >= $statusId || $statusId === (int) $order->current_state) {
            return;
        }

        $order->setCurrentState($statusId);

        $this->logger->info('Updated order #{orderId} status to #{statusId}.', [
            'orderId' => $order->id,
            'statusId' => $statusId,
        ]);
    }

    private function saveTransactionId(\Order $order, OrderEvent $event): void
    {
        if (null === $transactionId = $event->getData()->getPaymentId()) {
            return;
        }

        if ([] === $payments = $order->getOrderPayments()) {
            $this->createOrderPayment($order, $transactionId);

            return;
        }

        /** @var \OrderPayment $payment */
        foreach ($payments as $payment) {
            if ($payment->transaction_id === $transactionId || '' !== (string) $payment->transaction_id) {
                continue;
            }

            $payment->transaction_id = $transactionId;

            if (!$payment->update()) {
                throw new \RuntimeException('Could not update order payment.');
            }
        }
    }

    private function createOrderPayment(\Order $order, string $transactionId): void
    {
        // refresh order data in case it has been updated using another Order object in hooks triggered by status change
        $order = $this->repository->find((int) $order->id);

        // this also causes Order update!
        if (!$order->addOrderPayment($order->total_paid, null, $transactionId)) {
            throw new \RuntimeException('Could not create order payment.');
        }

        $this->logger->info('Created payment for order #{orderId}.', [
            'orderId' => $order->id,
        ]);
    }
}

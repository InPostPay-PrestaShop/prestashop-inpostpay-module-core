<?php

declare(strict_types=1);

namespace izi\prestashop\MerchantApi\Handler;

use izi\prestashop\Builder\Order\OrderStatusDescriptionProvider;
use izi\prestashop\Common\Order\MerchantOrderStatusData;
use izi\prestashop\Configuration\OrdersConfigurationInterface;
use izi\prestashop\MerchantApi\Command\UpdateOrderCommand;
use izi\prestashop\MerchantApi\Exception\OrderNotFoundException;
use izi\prestashop\MerchantApi\Model\Order\Request\OrderEvent;
use izi\prestashop\MerchantApi\Model\Order\Request\PaymentStatus;
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

    public function __invoke(UpdateOrderCommand $command): MerchantOrderStatusData
    {
        $order = $this->repository->find((int) $command->getOrderId());

        if (null === $order || 'inpostizi' !== $order->module) {
            throw OrderNotFoundException::create();
        }

        $this->updateOrderStatus($order, $command->getEvent());

        return new MerchantOrderStatusData(
            null,
            $this->statusDescriptionProvider->getStatus($order)
        );
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
}

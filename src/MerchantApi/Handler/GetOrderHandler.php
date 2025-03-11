<?php

declare(strict_types=1);

namespace izi\prestashop\MerchantApi\Handler;

use izi\prestashop\Entities\BasketSession;
use izi\prestashop\Handler\CommandHandlerTrait;
use izi\prestashop\MerchantApi\Command\GetOrderCommand;
use izi\prestashop\MerchantApi\Exception\OrderNotFoundException;
use izi\prestashop\MerchantApi\Model\Order\Response\Order;
use izi\prestashop\ObjectModel\Repository\ObjectRepositoryInterface;
use izi\prestashop\PrestashopOrder;
use izi\prestashop\Repository\BasketSessionRepositoryInterface;

/**
 * @internal expect constructor signature to change
 */
final class GetOrderHandler implements GetOrderHandlerInterface
{
    use CommandHandlerTrait;

    /**
     * @var ObjectRepositoryInterface<\Order>
     */
    private $orderRepository;

    /**
     * @var BasketSessionRepositoryInterface<BasketSession>
     */
    private $sessionRepository;

    /**
     * @param ObjectRepositoryInterface<\Order> $orderRepository
     * @param BasketSessionRepositoryInterface<BasketSession> $repository
     */
    public function __construct(ObjectRepositoryInterface $orderRepository, BasketSessionRepositoryInterface $repository)
    {
        $this->orderRepository = $orderRepository;
        $this->sessionRepository = $repository;
    }

    public function __invoke(GetOrderCommand $command): Order
    {
        $order = $this->orderRepository->find((int) $command->getOrderId());

        if (null === $order || 'inpostizi' !== $order->module) {
            throw OrderNotFoundException::create();
        }

        if (null === $session = $this->sessionRepository->findByOrderId((string) $order->id)) {
            throw new \RuntimeException('Basket session does not exist.');
        }

        \Shop::setContext(\Shop::CONTEXT_SHOP, (int) $order->id_shop);

        return PrestashopOrder::getOrder($order, $session->getBasketId(), $session->getOrderRequest());
    }
}

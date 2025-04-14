<?php

declare(strict_types=1);

namespace izi\prestashop\MerchantApi\Handler;

use izi\prestashop\CommandBusInterface;
use izi\prestashop\Entities\BasketSession;
use izi\prestashop\Handler\CommandHandlerTrait;
use izi\prestashop\MerchantApi\Command\CreateOrderCommand;
use izi\prestashop\MerchantApi\Command\GetOrderCommand;
use izi\prestashop\MerchantApi\Model\Order\Response\Order;
use izi\prestashop\Repository\BasketSessionRepositoryInterface;
use izi\prestashop\rest\order\Create;

/**
 * @internal expect constructor signature to change
 */
final class CreateOrderHandler implements CreateOrderHandlerInterface
{
    use CommandHandlerTrait;

    /**
     * @var BasketSessionRepositoryInterface<BasketSession>
     */
    private $repository;

    /**
     * @var CommandBusInterface
     */
    private $bus;

    /**
     * @param BasketSessionRepositoryInterface<BasketSession> $repository
     */
    public function __construct(BasketSessionRepositoryInterface $repository, CommandBusInterface $bus)
    {
        $this->repository = $repository;
        $this->bus = $bus;
    }

    public function __invoke(CreateOrderCommand $command): Order
    {
        $handler = new Create(null, null, null, null, $this->bus, $this->repository);
        $orderId = $handler->handleRequest($command->getRequest());

        return $this->bus->handle(new GetOrderCommand((string) $orderId));
    }
}

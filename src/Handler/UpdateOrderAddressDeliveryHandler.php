<?php

declare(strict_types=1);

namespace izi\prestashop\Handler;

use izi\prestashop\BasketApp\Order\OrdersApiClientInterface;
use izi\prestashop\BasketApp\Order\Request\Delivery;
use izi\prestashop\Builder\Order\OrderEventBuilderFactoryInterface;
use izi\prestashop\Command\UpdateOrderAddressDeliveryCommand;
use izi\prestashop\ObjectModel\ObjectManagerInterface;
use izi\prestashop\Order\Address\AddressDataMapper;
use izi\prestashop\Repository\BasketSessionRepositoryInterface;

final class UpdateOrderAddressDeliveryHandler implements UpdateOrderAddressDeliveryHandlerInterface
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

    /**
     * @var AddressDataMapper
     */
    private $addressDataMapper;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    public function __construct(BasketSessionRepositoryInterface $sessionRepository, OrderEventBuilderFactoryInterface $eventBuilderFactory, OrdersApiClientInterface $client, AddressDataMapper $addressDataMapper, ObjectManagerInterface $objectManager)
    {
        $this->sessionRepository = $sessionRepository;
        $this->eventBuilderFactory = $eventBuilderFactory;
        $this->client = $client;
        $this->addressDataMapper = $addressDataMapper;
        $this->objectManager = $objectManager;
    }

    public function __invoke(UpdateOrderAddressDeliveryCommand $command): void
    {
        if (null === $this->sessionRepository->findByOrderId($orderId = $command->getOrderId())) {
            return;
        }

        $eventTime = $command->getEventTime();
        $eventId = $this->generateEventId($command->getOrderId(), $eventTime);

        if (null === $order = $this->objectManager->find(\Order::class, (int) $orderId)) {
            throw new \DomainException(sprintf('Order "%s" does not exist.', $orderId));
        }

        $delivery = $this->getDeliveryData((int) $order->id_address_delivery);

        $event = $this->eventBuilderFactory
            ->create((int) $command->getOrderId())
            ->setEventId($eventId)
            ->setEventTime($eventTime)
            ->setDeliveryData($delivery)
            ->build();

        $this->client->updateOrder($command->getOrderId(), $event);
    }

    private function generateEventId(string $orderId, \DateTimeImmutable $eventTime): string
    {
        return sprintf('DA_%s_%d', $orderId, $eventTime->getTimestamp());
    }

    private function getDeliveryData(int $deliveryAddressId): Delivery
    {
        $address = $this->objectManager->find(\Address::class, $deliveryAddressId);

        if (null === $address) {
            throw new \DomainException(sprintf('Address "%d" does not exist.', $deliveryAddressId));
        }

        $deliveryAddress = $this->addressDataMapper->mapDeliveryAddress($address);
        $phoneNumber = $this->addressDataMapper->mapPhoneNumber($address);

        return new Delivery(
            null,
            null,
            $phoneNumber,
            null,
            $deliveryAddress
        );
    }
}

<?php

declare(strict_types=1);

namespace izi\prestashop\EventListener;

use izi\prestashop\Command\UpdateOrderTrackingNumbersCommand;
use izi\prestashop\CommandBusInterface;
use izi\prestashop\Configuration\ApiConfigurationInterface;
use izi\prestashop\Event\ShipmentEvent;
use izi\prestashop\ObjectModel\Repository\ObjectRepositoryInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class ShipmentListener implements EventSubscriberInterface
{
    /**
     * @var ApiConfigurationInterface
     */
    private $configuration;

    /**
     * @var ObjectRepositoryInterface<\InPostShipmentModel>
     */
    private $repository;

    /**
     * @var CommandBusInterface
     */
    private $bus;

    private $trackingNumberUpdated = [];

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ObjectRepositoryInterface<\InPostShipmentModel> $repository
     */
    public function __construct(ApiConfigurationInterface $configuration, ObjectRepositoryInterface $repository, CommandBusInterface $bus, ?LoggerInterface $logger = null)
    {
        $this->configuration = $configuration;
        $this->repository = $repository;
        $this->bus = $bus;
        $this->logger = $logger ?? new NullLogger();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ShipmentEvent::CREATED => 'onShipmentCreated',
            ShipmentEvent::BEFORE_UPDATE => 'onBeforeShipmentUpdated',
            ShipmentEvent::UPDATED => 'onShipmentUpdated',
        ];
    }

    public function onShipmentCreated(ShipmentEvent $event): void
    {
        if (null === $this->configuration->getClientCredentials()) {
            return;
        }

        $shipment = $event->getShipment();

        if ('' === $shipment->tracking_number || null === $shipment->tracking_number) {
            return;
        }

        $this->onTrackingNumberUpdated($shipment);
    }

    public function onBeforeShipmentUpdated(ShipmentEvent $event): void
    {
        if (null === $this->configuration->getClientCredentials()) {
            return;
        }

        $shipment = $event->getShipment();

        if ('' === $shipment->tracking_number || null === $shipment->tracking_number || 0 >= $shipmentId = (int) $shipment->id) {
            return;
        }

        if (null === $currentState = $this->repository->find($shipmentId)) {
            return;
        }

        if ($currentState->tracking_number === $shipment->tracking_number) {
            return;
        }

        $this->trackingNumberUpdated[$shipmentId] = true;
    }

    public function onShipmentUpdated(ShipmentEvent $event): void
    {
        $shipment = $event->getShipment();
        $shipmentId = (int) $shipment->id;

        if (!isset($this->trackingNumberUpdated[$shipmentId])) {
            return;
        }

        unset($this->trackingNumberUpdated[$shipmentId]);

        $this->onTrackingNumberUpdated($shipment);
    }

    private function onTrackingNumberUpdated(\InPostShipmentModel $shipment): void
    {
        $eventTime = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $shipment->date_add);
        $command = new UpdateOrderTrackingNumbersCommand((string) $shipment->id_order, $eventTime);

        try {
            $this->bus->handle($command);
        } catch (\Throwable $e) {
            $this->logger->critical('Could not send order #{orderId} tracking numbers update event data: {error}', [
                'orderId' => (int) $shipment->id_order,
                'error' => $e,
                'shipmentId' => (int) $shipment->id,
            ]);
        }
    }
}

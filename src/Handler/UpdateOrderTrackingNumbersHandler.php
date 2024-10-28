<?php

declare(strict_types=1);

namespace izi\prestashop\Handler;

use izi\prestashop\BasketApp\Order\OrdersApiClientInterface;
use izi\prestashop\Builder\Order\OrderEventBuilderFactoryInterface;
use izi\prestashop\Command\UpdateOrderTrackingNumbersCommand;
use izi\prestashop\Repository\BasketSessionRepositoryInterface;
use izi\prestashop\Shipping\TrackingNumberProviderInterface;

final class UpdateOrderTrackingNumbersHandler implements UpdateOrderTrackingNumbersHandlerInterface
{
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
     * @var iterable|TrackingNumberProviderInterface[]
     */
    private $trackingNumberProviders;

    /**
     * @param iterable<TrackingNumberProviderInterface> $trackingNumberProviders
     */
    public function __construct(BasketSessionRepositoryInterface $sessionRepository, OrderEventBuilderFactoryInterface $eventBuilderFactory, OrdersApiClientInterface $client, iterable $trackingNumberProviders)
    {
        $this->sessionRepository = $sessionRepository;
        $this->eventBuilderFactory = $eventBuilderFactory;
        $this->client = $client;
        $this->trackingNumberProviders = $trackingNumberProviders;
    }

    public static function getHandledCommandClass(): string
    {
        return UpdateOrderTrackingNumbersCommand::class;
    }

    public function __invoke(UpdateOrderTrackingNumbersCommand $command): void
    {
        if (null === $this->sessionRepository->findByOrderId($orderId = $command->getOrderId())) {
            return;
        }

        $trackingNumbers = $this->getTrackingNumbers((int) $orderId);

        $eventTime = $command->getEventTime();
        $eventId = $this->generateEventId($orderId, $eventTime);

        $event = $this->eventBuilderFactory
            ->create((int) $orderId)
            ->setEventId($eventId)
            ->setEventTime($eventTime)
            ->setTrackingNumbers($trackingNumbers)
            ->build();

        $this->client->updateOrder($orderId, $event);
    }

    private function getTrackingNumbers(int $orderId): array
    {
        $trackingNumbers = [];

        foreach ($this->trackingNumberProviders as $provider) {
            $trackingNumbers[] = $provider->getTrackingNumbers($orderId);
        }

        return array_unique(array_merge(...$trackingNumbers));
    }

    private function generateEventId(string $orderId, \DateTimeImmutable $eventTime): string
    {
        return sprintf('TN_%s_%d', $orderId, $eventTime->getTimestamp());
    }
}

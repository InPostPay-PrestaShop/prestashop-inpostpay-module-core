<?php

declare(strict_types=1);

namespace izi\prestashop\Handler;

use izi\prestashop\Command\GetOrderEventsCommand;
use izi\prestashop\Entities\BasketSessionInterface;
use izi\prestashop\Handler\Result\OrderEvent;
use izi\prestashop\Handler\Result\OrderEventStream;
use izi\prestashop\Http\Response\ServerSentEvent;
use izi\prestashop\ObjectModel\ObjectManagerInterface;
use izi\prestashop\Order\ContextCustomerUpdater;
use izi\prestashop\Repository\BasketSessionRepositoryInterface;

/**
 * @deprecated
 */
final class GetOrderEventsHandler implements GetOrderEventsHandlerInterface
{
    /**
     * @var BasketSessionRepositoryInterface
     */
    private $repository;

    /**
     * @var ContextCustomerUpdater
     */
    private $contextUpdater;

    public function __construct(BasketSessionRepositoryInterface $repository, \Context $context, ObjectManagerInterface $manager)
    {
        $this->repository = $repository;
        $this->contextUpdater = new ContextCustomerUpdater($context, $manager);
    }

    public static function getHandledCommandClass(): string
    {
        return GetOrderEventsCommand::class;
    }

    public function __invoke(GetOrderEventsCommand $command): OrderEventStream
    {
        if (null === $session = $this->repository->findByBasketId($command->getBasketId())) {
            throw new \DomainException(sprintf('Basket "%s" does not exist.', $command->getBasketId()));
        }

        if (null !== $orderId = $session->getOrderId()) {
            $this->contextUpdater->updateCustomer((int) $orderId);
        }

        return new OrderEventStream($this->createEventStream($session));
    }

    /**
     * @return \Generator<ServerSentEvent<OrderEvent>>
     */
    private function createEventStream(BasketSessionInterface $session): \Generator
    {
        yield ServerSentEvent::builder()
            ->setComment('start')
            ->build();

        if (null !== $orderConfirmationUrl = $session->getOrderConfirmationUrl()) {
            $session->redirect();
            $this->repository->persist($session);

            yield ServerSentEvent::builder()
                ->setData(OrderEvent::redirect($orderConfirmationUrl))
                ->build();

            return;
        }

        if (null === $session->getBindingConfirmation()) {
            yield ServerSentEvent::builder()
//                ->setEventName('binding_deleted')
                ->setData(json_encode(['action' => 'delete'])) // TODO? adjust front-end?
                ->build();

            return;
        }

        if ($session->wasUpdated()) {
            $this->repository->persist($session);

            yield ServerSentEvent::builder()
                ->setData(OrderEvent::refresh())
                ->build();

            return;
        }

        yield ServerSentEvent::builder()
            ->setRetry(2000)
            ->build();
    }
}

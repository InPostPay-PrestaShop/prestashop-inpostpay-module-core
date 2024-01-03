<?php

declare(strict_types=1);

namespace izi\prestashop\Handler;

use izi\prestashop\Command\GetOrderEventsCommand;
use izi\prestashop\Entities\BasketSessionInterface;
use izi\prestashop\Handler\Result\OrderEvent;
use izi\prestashop\Handler\Result\OrderEventStream;
use izi\prestashop\Http\Response\ServerSentEvent;
use izi\prestashop\ObjectModel\ObjectManagerInterface;
use izi\prestashop\Repository\BasketSessionRepositoryInterface;

final class GetOrderEventsHandler implements GetOrderEventsHandlerInterface
{
    /**
     * @var BasketSessionRepositoryInterface
     */
    private $repository;

    /**
     * @var \Context
     */
    private $context;

    /**
     * @var ObjectManagerInterface
     */
    private $manager;

    public function __construct(BasketSessionRepositoryInterface $repository, \Context $context, ObjectManagerInterface $manager)
    {
        $this->repository = $repository;
        $this->context = $context;
        $this->manager = $manager;
    }

    public function __invoke(GetOrderEventsCommand $command): OrderEventStream
    {
        if (null === $session = $this->repository->findByBasketId($command->getBasketId())) {
            throw new \DomainException('Basket does not exist.');
        }

        if (null !== $orderId = $session->getOrderId()) {
            $this->updateCustomer((int) $orderId);
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
            // binding was deleted
            yield ServerSentEvent::builder()
                ->setData(OrderEvent::refresh())
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

    private function updateCustomer(int $orderId): void
    {
        $order = $this->manager->getRepository(\Order::class)->find($orderId);

        if (null === $order) {
            throw new \RuntimeException('Order does not exist.');
        }

        if ((int) $this->context->customer->id === $customerId = (int) $order->id_customer) {
            return;
        }

        $customer = $this->manager->getRepository(\Customer::class)->find($customerId);

        if (null === $customer) {
            throw new \RuntimeException('Customer does not exist.');
        }

        if (!$customer->is_guest) {
            return;
        }

        $this->context->updateCustomer($customer);
    }
}

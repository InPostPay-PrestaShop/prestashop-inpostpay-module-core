<?php

declare(strict_types=1);

namespace izi\prestashop\Order;

use izi\prestashop\ObjectModel\ObjectManagerInterface;

class ContextCustomerUpdater
{
    /**
     * @var \Context
     */
    private $context;

    /**
     * @var ObjectManagerInterface
     */
    private $manager;

    public function __construct(\Context $context, ObjectManagerInterface $manager)
    {
        $this->context = $context;
        $this->manager = $manager;
    }

    public function updateCustomer(int $orderId): void
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
            throw new \DomainException('Customer is not a guest.');
        }

        $this->context->updateCustomer($customer);
    }
}

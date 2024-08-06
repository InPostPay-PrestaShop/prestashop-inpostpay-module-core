<?php

declare(strict_types=1);

namespace izi\prestashop\Builder\Order;

use izi\prestashop\BasketApp\Order\Request\OrderEvent;
use izi\prestashop\Common\Order\MerchantOrderStatus;

/**
 * @method $this setCustomerOrderId(?string $customerOrderId)
 */
interface OrderEventBuilderInterface
{
    public function build(): OrderEvent;

    /**
     * @return static
     */
    public function setEventId(string $eventId): self;

    /**
     * @return static
     */
    public function setEventTime(\DateTimeImmutable $time): self;

    /**
     * @return static
     */
    public function setOrderStatus(?MerchantOrderStatus $status): self;

    /**
     * @param string[]|null $numbers
     *
     * @return static
     */
    public function setTrackingNumbers(?array $numbers): self;
}

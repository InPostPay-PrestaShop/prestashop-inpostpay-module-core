<?php

declare(strict_types=1);

namespace izi\prestashop\BasketApp\Order\Request;

use izi\prestashop\Common\Order\MerchantOrderStatus;

final class OrderEventData implements \JsonSerializable
{
    /**
     * @var MerchantOrderStatus|null
     */
    private $order_status;

    /**
     * @var string|null
     */
    private $order_merchant_status_description;

    /**
     * @var string[]|null
     */
    private $delivery_references_list;

    /**
     * @var Delivery|null
     */
    private $delivery;

    /**
     * @param string[]|null $delivery_references_list
     */
    public function __construct(?MerchantOrderStatus $order_status = null, ?string $order_merchant_status_description = null, ?array $delivery_references_list = null, ?Delivery $delivery = null)
    {
        $this->order_status = $order_status;
        $this->order_merchant_status_description = $order_merchant_status_description;
        $this->delivery_references_list = $delivery_references_list;
        $this->delivery = $delivery;
    }

    public function getStatus(): ?MerchantOrderStatus
    {
        return $this->order_status;
    }

    public function getStatusDescription(): ?string
    {
        return $this->order_merchant_status_description;
    }

    /**
     * @return string[]|null
     */
    public function getDeliveryReferencesList(): ?array
    {
        return $this->delivery_references_list;
    }

    public function getDelivery(): ?Delivery
    {
        return $this->delivery;
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}

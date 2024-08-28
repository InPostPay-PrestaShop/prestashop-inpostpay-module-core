<?php

declare(strict_types=1);

namespace izi\prestashop\Common\Product;

use izi\prestashop\Common\Delivery\DeliveryType;

final class DeliveryProduct implements \JsonSerializable
{
    /**
     * @var DeliveryType
     */
    private $delivery_type;

    /**
     * @var bool
     */
    private $if_delivery_available;

    public function __construct(DeliveryType $delivery_type, bool $if_delivery_available)
    {
        $this->delivery_type = $delivery_type;
        $this->if_delivery_available = $if_delivery_available;
    }

    public function getDeliveryType(): DeliveryType
    {
        return $this->delivery_type;
    }

    public function getDeliveryAvailable(): bool
    {
        return $this->if_delivery_available;
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}

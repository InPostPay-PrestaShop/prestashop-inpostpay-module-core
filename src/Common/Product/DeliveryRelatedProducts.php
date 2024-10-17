<?php

declare(strict_types=1);

namespace izi\prestashop\Common\Product;

use izi\prestashop\Common\Delivery\DeliveryType;

final class DeliveryRelatedProducts implements \JsonSerializable
{
    /**
     * @var DeliveryType
     */
    private $delivery_type;

    /**
     * @var bool
     */
    private $if_delivery_available;

    /**
     * @var bool
     */
    private $if_delivery_free;

    public function __construct(DeliveryType $delivery_type, bool $if_delivery_available, bool $if_delivery_free)
    {
        $this->delivery_type = $delivery_type;
        $this->if_delivery_available = $if_delivery_available;
        $this->if_delivery_free = $if_delivery_free;
    }

    public function getDeliveryType(): DeliveryType
    {
        return $this->delivery_type;
    }

    public function isDeliveryAvailable(): bool
    {
        return $this->if_delivery_available;
    }

    public function isDeliveryFree(): bool
    {
        return $this->if_delivery_free;
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}

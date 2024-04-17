<?php

declare(strict_types=1);

namespace izi\prestashop\Common\Basket;

use izi\prestashop\Common\Delivery\DeliveryType;
use izi\prestashop\Common\Price;

final class DeliveryOption implements \JsonSerializable
{
    /**
     * @var DeliveryType
     */
    private $delivery_type;

    /**
     * @var \DateTimeImmutable
     */
    private $delivery_date;

    /**
     * @var Price
     */
    private $delivery_price;

    /**
     * @var OptionalService[]
     */
    private $delivery_options;

    /**
     * @var float|null
     */
    private $free_delivery_minimum_gross_price;

    /**
     * @param OptionalService[] $delivery_options
     */
    public function __construct(DeliveryType $delivery_type, \DateTimeImmutable $delivery_date, Price $delivery_price, array $delivery_options = [], ?float $free_delivery_minimum_gross_price = null)
    {
        $this->delivery_type = $delivery_type;
        $this->delivery_date = $delivery_date;
        $this->delivery_price = $delivery_price;
        $this->delivery_options = $delivery_options;
        $this->free_delivery_minimum_gross_price = $free_delivery_minimum_gross_price;
    }

    public function getType(): DeliveryType
    {
        return $this->delivery_type;
    }

    public function getDeliveryDate(): \DateTimeImmutable
    {
        return $this->delivery_date;
    }

    public function getPrice(): Price
    {
        return $this->delivery_price;
    }

    /**
     * @return OptionalService[]
     */
    public function getAvailableServices(): array
    {
        return $this->delivery_options;
    }

    public function getFreeDeliveryMinimumGrossPrice(): ?float
    {
        return $this->free_delivery_minimum_gross_price;
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}

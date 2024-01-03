<?php

declare(strict_types=1);

namespace izi\prestashop\Common\Basket;

use izi\prestashop\Common\Delivery\ServiceCode;
use izi\prestashop\Common\Price;

final class OptionalService implements \JsonSerializable
{
    /**
     * @var string
     */
    private $delivery_name;

    /**
     * @var ServiceCode
     */
    private $delivery_code_value;

    /**
     * @var Price
     */
    private $delivery_option_price;

    public function __construct(string $delivery_name, ServiceCode $delivery_code_value, Price $delivery_option_price)
    {
        $this->delivery_name = $delivery_name;
        $this->delivery_code_value = $delivery_code_value;
        $this->delivery_option_price = $delivery_option_price;
    }

    public function getName(): string
    {
        return $this->delivery_name;
    }

    public function getCode(): ServiceCode
    {
        return $this->delivery_code_value;
    }

    public function getPrice(): Price
    {
        return $this->delivery_option_price;
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}

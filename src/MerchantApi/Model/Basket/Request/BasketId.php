<?php

declare(strict_types=1);

namespace izi\prestashop\MerchantApi\Model\Basket\Request;

use izi\prestashop\Common\PhoneNumber;

final class BasketId implements \JsonSerializable
{
    /**
     * @var string|null
     */
    private $basket_id;

    /**
     * @var PhoneNumber|null
     */
    private $phone_number;

    public function __construct(?string $basket_id = null, ?PhoneNumber $phone_number = null)
    {
        $this->basket_id = $basket_id;
        $this->phone_number = $phone_number;
    }

    public function getBasketId(): ?string
    {
        return $this->basket_id;
    }

    public function getPhoneNumber(): ?PhoneNumber
    {
        return $this->phone_number;
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}

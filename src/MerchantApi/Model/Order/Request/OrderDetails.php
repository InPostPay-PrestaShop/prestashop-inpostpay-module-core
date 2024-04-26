<?php

declare(strict_types=1);

namespace izi\prestashop\MerchantApi\Model\Order\Request;

use izi\prestashop\Common\Currency;
use izi\prestashop\Common\PaymentType;
use izi\prestashop\Common\Price;

final class OrderDetails implements \JsonSerializable
{
    /**
     * @var string|null
     */
    private $order_comments;

    /**
     * @var string
     */
    private $basket_id;

    /**
     * @var Currency
     */
    private $currency;

    /**
     * @var Price
     */
    private $basket_price;

    /**
     * @var PaymentType
     */
    private $payment_type;

    public function __construct(string $basket_id, Currency $currency, Price $basket_price, PaymentType $payment_type, ?string $order_comments = null)
    {
        $this->order_comments = $order_comments;
        $this->basket_id = $basket_id;
        $this->currency = $currency;
        $this->basket_price = $basket_price;
        $this->payment_type = $payment_type;
    }

    public function getOrderComments(): ?string
    {
        return $this->order_comments;
    }

    public function getBasketId(): string
    {
        return $this->basket_id;
    }

    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    public function getBasketPrice(): Price
    {
        return $this->basket_price;
    }

    public function getPaymentType(): PaymentType
    {
        return $this->payment_type;
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}

<?php

declare(strict_types=1);

namespace izi\prestashop\MerchantApi\Model\Order\Request;

use izi\prestashop\Common\Currency;
use izi\prestashop\Common\Order\BasketAdditionalParameter;
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

    /**
     * @var BasketAdditionalParameter[]
     */
    private $basket_additional_parameters;

    /**
     * @var InPostDiscount[]
     */
    private $inpost_discounts;

    /**
     * @param BasketAdditionalParameter[] $basket_additional_parameters
     * @param InPostDiscount[] $inpost_discounts
     */
    public function __construct(string $basket_id, Currency $currency, Price $basket_price, PaymentType $payment_type, ?string $order_comments = null, array $basket_additional_parameters = [], array $inpost_discounts = [])
    {
        $this->order_comments = $order_comments;
        $this->basket_id = $basket_id;
        $this->currency = $currency;
        $this->basket_price = $basket_price;
        $this->payment_type = $payment_type;
        $this->basket_additional_parameters = $basket_additional_parameters;
        $this->inpost_discounts = $inpost_discounts;
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

    public function getBasketAdditionalParameters(): array
    {
        return $this->basket_additional_parameters;
    }

    public function getInpostDiscounts(): array
    {
        return $this->inpost_discounts;
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}

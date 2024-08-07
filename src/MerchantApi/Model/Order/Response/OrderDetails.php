<?php

declare(strict_types=1);

namespace izi\prestashop\MerchantApi\Model\Order\Response;

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
    private $order_id;

    /**
     * @var string
     */
    private $pos_id;

    /**
     * @var \DateTimeImmutable
     */
    private $order_creation_date;

    /**
     * @var string
     */
    private $basket_id;

    /**
     * @var string
     */
    private $order_merchant_status_description;

    /**
     * @var PaymentType
     */
    private $payment_type;

    /**
     * @var Price
     */
    private $order_base_price;

    /**
     * @var Price
     */
    private $order_final_price;

    /**
     * @var Currency
     */
    private $currency;

    /**
     * @var string[]
     */
    private $delivery_references_list;

    /**
     * @var float|null
     */
    private $order_discount;

    /**
     * @param string[] $delivery_references_list
     */
    public function __construct(string $order_id, string $pos_id, \DateTimeImmutable $order_creation_date, string $basket_id, string $order_merchant_status_description, PaymentType $payment_type, Price $order_base_price, Price $order_final_price, Currency $currency, array $delivery_references_list = [], ?string $order_comments = null, ?float $order_discount = null)
    {
        $this->order_comments = $order_comments;
        $this->order_id = $order_id;
        $this->pos_id = $pos_id;
        $this->order_creation_date = $order_creation_date;
        $this->basket_id = $basket_id;
        $this->order_merchant_status_description = $order_merchant_status_description;
        $this->payment_type = $payment_type;
        $this->order_base_price = $order_base_price;
        $this->order_final_price = $order_final_price;
        $this->currency = $currency;
        $this->delivery_references_list = $delivery_references_list;
        $this->order_discount = $order_discount;
    }

    public function getComments(): ?string
    {
        return $this->order_comments;
    }

    public function getId(): string
    {
        return $this->order_id;
    }

    public function getPosId(): string
    {
        return $this->pos_id;
    }

    public function getCreationDate(): \DateTimeImmutable
    {
        return $this->order_creation_date;
    }

    public function getBasketId(): string
    {
        return $this->basket_id;
    }

    public function getMerchantStatusDescription(): string
    {
        return $this->order_merchant_status_description;
    }

    public function getPaymentType(): PaymentType
    {
        return $this->payment_type;
    }

    public function getBasePrice(): Price
    {
        return $this->order_base_price;
    }

    public function getFinalPrice(): Price
    {
        return $this->order_final_price;
    }

    public function getDiscount(): ?float
    {
        return $this->order_discount;
    }

    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    /**
     * @return string[]
     */
    public function getDeliveryReferencesList(): array
    {
        return $this->delivery_references_list;
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}

<?php

declare(strict_types=1);

namespace izi\prestashop\Common\Basket;

use izi\prestashop\Common\Currency;
use izi\prestashop\Common\PaymentType;
use izi\prestashop\Common\Price;

final class Summary implements \JsonSerializable
{
    /**
     * @var Price
     */
    private $basket_base_price;

    /**
     * @var Price|null
     */
    private $basket_final_price;

    /**
     * @var Price|null
     */
    private $basket_promo_price;

    /**
     * @var Currency
     */
    private $currency;

    /**
     * @var \DateTimeImmutable|null
     */
    private $basket_expiration_date;

    /**
     * @var string|null
     */
    private $basket_additional_information;

    /**
     * @var PaymentType[]
     */
    private $payment_type;

    /**
     * @var Notice|null
     */
    private $basket_notice;

    /**
     * @param PaymentType[] $payment_type
     */
    public function __construct(Price $basket_base_price, Currency $currency, array $payment_type, ?Price $basket_final_price = null, ?Price $basket_promo_price = null, ?\DateTimeImmutable $basket_expiration_date = null, ?string $basket_additional_information = null, ?Notice $basket_notice = null)
    {
        $this->basket_base_price = $basket_base_price;
        $this->basket_final_price = $basket_final_price;
        $this->basket_promo_price = $basket_promo_price;
        $this->currency = $currency;
        $this->basket_expiration_date = $basket_expiration_date;
        $this->basket_additional_information = $basket_additional_information;
        $this->payment_type = $payment_type;
        $this->basket_notice = $basket_notice;
    }

    public function getBasePrice(): Price
    {
        return $this->basket_base_price;
    }

    public function getFinalPrice(): ?Price
    {
        return $this->basket_final_price;
    }

    public function getPromoPrice(): ?Price
    {
        return $this->basket_promo_price;
    }

    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    public function getExpirationDate(): ?\DateTimeImmutable
    {
        return $this->basket_expiration_date;
    }

    public function getAdditionalInformation(): ?string
    {
        return $this->basket_additional_information;
    }

    /**
     * @return PaymentType[]
     */
    public function getPaymentType(): array
    {
        return $this->payment_type;
    }

    public function getNotice(): ?Notice
    {
        return $this->basket_notice;
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}

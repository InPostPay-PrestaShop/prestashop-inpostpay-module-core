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
     * @param PaymentType[] $payment_type deprecated since 3.4.0 - to be removed in a future version of the BasketApp API
     */
    public function __construct(Price $basket_base_price, Currency $currency, array $payment_type = [], ?Price $basket_final_price = null, ?Price $basket_promo_price = null, ?\DateTimeImmutable $basket_expiration_date = null, ?string $basket_additional_information = null, ?Notice $basket_notice = null)
    {
        if ([] !== $payment_type) {
            @trigger_error(\sprintf('Passing a non-empty array as $payment_type to "%s()" is deprecated since version 3.4.0.', __METHOD__), \E_USER_DEPRECATED);
        }

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

    public function withBasePrice(Price $price): self
    {
        $summary = clone $this;
        $summary->basket_base_price = $price;

        return $summary;
    }

    public function getFinalPrice(): ?Price
    {
        return $this->basket_final_price;
    }

    public function withFinalPrice(?Price $price): self
    {
        $summary = clone $this;
        $summary->basket_final_price = $price;

        return $summary;
    }

    public function getPromoPrice(): ?Price
    {
        return $this->basket_promo_price;
    }

    public function withPromoPrice(?Price $price): self
    {
        $summary = clone $this;
        $summary->basket_promo_price = $price;

        return $summary;
    }

    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    public function withCurrency(Currency $currency): self
    {
        $summary = clone $this;
        $summary->currency = $currency;

        return $summary;
    }

    public function getExpirationDate(): ?\DateTimeImmutable
    {
        return $this->basket_expiration_date;
    }

    public function withExpirationDate(?\DateTimeImmutable $date): self
    {
        $summary = clone $this;
        $summary->basket_expiration_date = $date;

        return $summary;
    }

    public function getAdditionalInformation(): ?string
    {
        return $this->basket_additional_information;
    }

    public function withAdditionalInformation(?string $info): self
    {
        $summary = clone $this;
        $summary->basket_additional_information = $info;

        return $summary;
    }

    /**
     * @return PaymentType[]
     */
    public function getPaymentType(): array
    {
        return $this->payment_type;
    }

    /**
     * @param PaymentType[] $types
     *
     * @deprecated since 3.4.0
     */
    public function withPaymentTypes(array $types): self
    {
        @trigger_error(\sprintf('Method "%s()" is deprecated since version 3.4.0.', __METHOD__), \E_USER_DEPRECATED);
        $summary = clone $this;
        $summary->payment_type = $types;

        return $summary;
    }

    public function getNotice(): ?Notice
    {
        return $this->basket_notice;
    }

    public function withNotice(?Notice $notice): self
    {
        $summary = clone $this;
        $summary->basket_notice = $notice;

        return $summary;
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}

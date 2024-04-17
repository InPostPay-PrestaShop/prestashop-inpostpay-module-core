<?php

declare(strict_types=1);

namespace izi\prestashop\MerchantApi\Model\Basket\Request;

use izi\prestashop\Common\PhoneNumber;
use izi\prestashop\Common\PromoCode;

final class BasketEvent implements \JsonSerializable
{
    /**
     * @var string
     */
    private $event_id;

    /**
     * @var \DateTimeImmutable
     */
    private $event_data_time;

    /**
     * @var EventType
     */
    private $event_type;

    /**
     * @var PhoneNumber|null
     */
    private $phone_number;

    /**
     * @var QuantityData[]
     */
    private $quantity_event_data;

    /**
     * @var PromoCode[]
     */
    private $promo_codes_event_data;

    /**
     * @var RelatedProductData[]
     */
    private $related_products_event_data;

    /**
     * @param QuantityData[] $quantity_event_data
     * @param PromoCode[] $promo_codes_event_data
     * @param RelatedProductData[] $related_products_event_data
     */
    public function __construct(string $event_id, \DateTimeImmutable $event_data_time, EventType $event_type, ?PhoneNumber $phone_number = null, array $quantity_event_data = [], array $promo_codes_event_data = [], array $related_products_event_data = [])
    {
        $this->event_id = $event_id;
        $this->event_data_time = $event_data_time;
        $this->event_type = $event_type;
        $this->phone_number = $phone_number;
        $this->quantity_event_data = $quantity_event_data;
        $this->promo_codes_event_data = $promo_codes_event_data;
        $this->related_products_event_data = $related_products_event_data;
    }

    public function getId(): string
    {
        return $this->event_id;
    }

    public function getDateTime(): \DateTimeImmutable
    {
        return $this->event_data_time;
    }

    public function getType(): EventType
    {
        return $this->event_type;
    }

    public function getPhoneNumber(): ?PhoneNumber
    {
        return $this->phone_number;
    }

    /**
     * @return QuantityData[]
     */
    public function getQuantityEventData(): array
    {
        return $this->quantity_event_data;
    }

    /**
     * @return PromoCode[]
     */
    public function getPromoCodesEventData(): array
    {
        return $this->promo_codes_event_data;
    }

    /**
     * @return RelatedProductData[]
     */
    public function getRelatedProductsEventData(): array
    {
        return $this->related_products_event_data;
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}

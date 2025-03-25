<?php

declare(strict_types=1);

namespace izi\prestashop\Common\Basket;

final class AvailablePromotion implements \JsonSerializable
{
    /**
     * @var PromotionType
     */
    private $type;

    /**
     * @var string
     */
    private $promo_code_value;

    /**
     * @var string
     */
    private $description;

    /**
     * @var \DateTimeImmutable|null
     */
    private $start_date;

    /**
     * @var \DateTimeImmutable|null
     */
    private $end_date;

    /**
     * @var int|null
     */
    private $priority;

    /**
     * @var PromoDetails
     */
    private $details;

    public function __construct(PromotionType $type, string $promo_code_value, string $description, PromoDetails $details, ?\DateTimeImmutable $start_date = null, ?\DateTimeImmutable $end_date = null, ?int $priority = null)
    {
        $this->type = $type;
        $this->promo_code_value = $promo_code_value;
        $this->description = $description;
        $this->start_date = $start_date;
        $this->end_date = $end_date;
        $this->priority = $priority;
        $this->details = $details;
    }

    public function getType(): PromotionType
    {
        return $this->type;
    }

    public function getPromoCode(): string
    {
        return $this->promo_code_value;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getStartDate(): ?\DateTimeImmutable
    {
        return $this->start_date;
    }

    public function getEndDate(): ?\DateTimeImmutable
    {
        return $this->end_date;
    }

    public function getPriority(): ?int
    {
        return $this->priority;
    }

    public function getDetails(): PromoDetails
    {
        return $this->details;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}

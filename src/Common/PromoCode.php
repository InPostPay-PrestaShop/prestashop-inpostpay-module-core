<?php

declare(strict_types=1);

namespace izi\prestashop\Common;

final class PromoCode implements \JsonSerializable
{
    public const REGULATION_TYPE_OMNIBUS = 'OMNIBUS';

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $promo_code_value;

    /**
     * @var string|null
     */
    private $regulation_type;

    public function __construct(string $name, string $promo_code_value, ?string $regulation_type = null)
    {
        $this->name = $name;
        $this->promo_code_value = $promo_code_value;
        $this->regulation_type = $regulation_type;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCode(): string
    {
        return $this->promo_code_value;
    }

    public function getRegulationType(): ?string
    {
        return $this->regulation_type;
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
<?php

declare(strict_types=1);

namespace izi\prestashop\Common;

final class PromoCode implements \JsonSerializable
{
    private $name;

    private $promo_code_value;

    public function __construct(string $name, string $promo_code_value)
    {
        $this->name = $name;
        $this->promo_code_value = $promo_code_value;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCode(): string
    {
        return $this->promo_code_value;
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}

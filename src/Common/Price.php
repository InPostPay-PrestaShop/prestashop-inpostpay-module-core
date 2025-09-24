<?php

declare(strict_types=1);

namespace izi\prestashop\Common;

final class Price implements \JsonSerializable
{
    /**
     * @var float
     */
    private $net;

    /**
     * @var float
     */
    private $gross;

    /**
     * @var float
     */
    private $vat;

    public function __construct(float $net, float $gross, float $vat)
    {
        $this->net = $net;
        $this->gross = $gross;
        $this->vat = $vat;
    }

    public function getNet(): float
    {
        return $this->net;
    }

    public function getGross(): float
    {
        return $this->gross;
    }

    public function getVat(): float
    {
        return $this->vat;
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }

    public function add(self $price): self
    {
        return new self(
            $this->net + $price->net,
            $this->gross + $price->gross,
            $this->vat + $price->vat
        );
    }

    public function sub(self $price): self
    {
        return new self(
            $this->net - $price->net,
            $this->gross - $price->gross,
            $this->vat - $price->vat
        );
    }

    /**
     * @param float|int $multiplier
     */
    public function multiply($multiplier): self
    {
        return new self(
            $this->net * $multiplier,
            $this->gross * $multiplier,
            $this->vat * $multiplier
        );
    }
}

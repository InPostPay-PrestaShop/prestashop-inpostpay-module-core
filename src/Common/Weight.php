<?php

declare(strict_types=1);

namespace izi\prestashop\Common;

final class Weight
{
    /**
     * @var float
     */
    private $weight;

    public function __construct(float $weight)
    {
        $this->weight = $weight;
    }

    public function getWeight(): float
    {
        return $this->weight;
    }

    public function add(self $weight): self
    {
        return new self(
            $this->getWeight() + $weight->getWeight()
        );
    }

    public function sub(self $weight): self
    {
        return new self(
            $this->getWeight() - $weight->getWeight()
        );
    }

    /**
     * @param $multiplier float|int
     * @return self
     */
    public function multiply($multiplier): self
    {
        return new self(
            $this->getWeight() * $multiplier
        );
    }

    public function equals(self $weight): bool
    {
        return $this->getWeight() === $weight->getWeight();
    }

    public function greaterThan(self $weight): bool
    {
        return $this->getWeight() > $weight->getWeight();
    }

    public function greaterThanOrEqual(self $weight): bool
    {
        return $this->getWeight() >= $weight->getWeight();
    }

    public function lessThan(self $weight): bool
    {
        return $this->getWeight() < $weight->getWeight();
    }

    public function lessThanOrEqual(self $weight): bool
    {
        return $this->getWeight() <= $weight->getWeight();
    }
}

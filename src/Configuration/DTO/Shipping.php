<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final class Shipping
{
    /**
     * @var int|null
     *
     * @Assert\NotNull()
     * @Assert\GreaterThanOrEqual(0)
     */
    private $carrierId;

    /**
     * @var float|null
     *
     * @Assert\NotNull()
     * @Assert\GreaterThanOrEqual(0)
     */
    private $shippingPrice;

    /**
     * @var int|null
     *
     * @Assert\NotNull()
     * @Assert\Range(min=Day::MIN_DAY, max=Day::MAX_DAY)
     */
    private $shippingAvailableFromDay;

    /**
     * @var int|null
     *
     * @Assert\NotNull()
     * @Assert\Range(min=Day::MIN_DAY, max=Day::MAX_DAY)
     */
    private $shippingAvailableToDay;

    /**
     * @var int|null
     *
     * @Assert\NotNull()
     * @Assert\Range(min=Hour::MIN_HOUR, max=Hour::MAX_HOUR)
     */
    private $shippingAvailableFromHour;

    /**
     * @var int|null
     *
     * @Assert\NotNull()
     * @Assert\Range(min=Hour::MIN_HOUR, max=Hour::MAX_HOUR)
     */
    private $shippingAvailableToHour;

    /**
     * @var float|null
     *
     * @Assert\NotNull()
     * @Assert\GreaterThanOrEqual(0)
     */
    private $shippingCodPrice;

    /**
     * @var int|null
     *
     * @Assert\NotNull()
     * @Assert\Range(min=Day::MIN_DAY, max=Day::MAX_DAY)
     */
    private $shippingCodAvailableFromDay;

    /**
     * @var int|null
     *
     * @Assert\NotNull()
     * @Assert\Range(min=Day::MIN_DAY, max=Day::MAX_DAY)
     */
    private $shippingCodAvailableToDay;

    /**
     * @var int|null
     *
     * @Assert\NotNull()
     * @Assert\Range(min=Hour::MIN_HOUR, max=Hour::MAX_HOUR)
     */
    private $shippingCodAvailableFromHour;

    /**
     * @var int|null
     *
     * @Assert\NotNull()
     * @Assert\Range(min=Hour::MIN_HOUR, max=Hour::MAX_HOUR)
     */
    private $shippingCodAvailableToHour;

    public function getCarrierId(int $shopId = null): ?int
    {
        return $this->carrierId;
    }

    public function setCarrierId(?\Carrier $carrier): self
    {
        $this->carrierId = $carrier instanceof \Carrier ? (int) $carrier->id_reference : null;

        return $this;
    }

    public function getShippingPrice(): ?float
    {
        return $this->shippingPrice;
    }

    public function setShippingPrice(?float $shippingPrice): self
    {
        $this->shippingPrice = $shippingPrice;

        return $this;
    }

    public function getShippingAvailableFromDay(): ?int
    {
        return $this->shippingAvailableFromDay;
    }

    public function setShippingAvailableFromDay(?Day $shippingAvailableFromDay): self
    {
        $this->shippingAvailableFromDay = $shippingAvailableFromDay instanceof Day ? $shippingAvailableFromDay->getId() : null;

        return $this;
    }

    public function getShippingAvailableToDay(): ?int
    {
        return $this->shippingAvailableToDay;
    }

    public function setShippingAvailableToDay(?Day $shippingAvailableToDay): self
    {
        $this->shippingAvailableToDay = $shippingAvailableToDay instanceof Day ? $shippingAvailableToDay->getId() : null;

        return $this;
    }

    public function getShippingAvailableFromHour(): ?int
    {
        return $this->shippingAvailableFromHour;
    }

    public function setShippingAvailableFromHour(?Hour $shippingAvailableFromHour): self
    {
        $this->shippingAvailableFromHour = $shippingAvailableFromHour instanceof Hour ? $shippingAvailableFromHour->getId() : null;

        return $this;
    }

    public function getShippingAvailableToHour(): ?int
    {
        return $this->shippingAvailableToHour;
    }

    public function setShippingAvailableToHour(?Hour $shippingAvailableToHour): self
    {
        $this->shippingAvailableToHour = $shippingAvailableToHour instanceof Hour ? $shippingAvailableToHour->getId() : null;

        return $this;
    }

    public function getShippingCodPrice(): ?float
    {
        return $this->shippingCodPrice;
    }

    public function setShippingCodPrice(?float $shippingCodPrice): self
    {
        $this->shippingCodPrice = $shippingCodPrice;

        return $this;
    }

    public function getShippingCodAvailableFromDay(): ?int
    {
        return $this->shippingCodAvailableFromDay;
    }

    public function setShippingCodAvailableFromDay(?Day $shippingCodAvailableFromDay): self
    {
        $this->shippingCodAvailableFromDay = $shippingCodAvailableFromDay instanceof Day ? $shippingCodAvailableFromDay->getId() : null;

        return $this;
    }

    public function getShippingCodAvailableToDay(): ?int
    {
        return $this->shippingCodAvailableToDay;
    }

    public function setShippingCodAvailableToDay(?Day $shippingCodAvailableToDay): self
    {
        $this->shippingCodAvailableToDay = $shippingCodAvailableToDay instanceof Day ? $shippingCodAvailableToDay->getId() : null;

        return $this;
    }

    public function getShippingCodAvailableFromHour(): ?int
    {
        return $this->shippingCodAvailableFromHour;
    }

    public function setShippingCodAvailableFromHour(?Hour $shippingCodAvailableFromHour): self
    {
        $this->shippingCodAvailableFromHour = $shippingCodAvailableFromHour instanceof Hour ? $shippingCodAvailableFromHour->getId() : null;

        return $this;
    }

    public function getShippingCodAvailableToHour(): ?int
    {
        return $this->shippingCodAvailableToHour;
    }

    public function setShippingCodAvailableToHour(?Hour $shippingCodAvailableToHour): self
    {
        $this->shippingCodAvailableToHour = $shippingCodAvailableToHour instanceof Hour ? $shippingCodAvailableToHour->getId() : null;

        return $this;
    }
}

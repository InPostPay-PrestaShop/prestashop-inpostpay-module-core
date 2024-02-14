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
    private $weekendDeliveryPrice;

    /**
     * @var int|null
     *
     * @Assert\NotNull()
     * @Assert\Range(min=Day::MIN_DAY, max=Day::MAX_DAY)
     */
    private $weekendDeliveryAvailableFromDay;

    /**
     * @var int|null
     *
     * @Assert\NotNull()
     * @Assert\Range(min=Day::MIN_DAY, max=Day::MAX_DAY)
     */
    private $weekendDeliveryAvailableToDay;

    /**
     * @var int|null
     *
     * @Assert\NotNull()
     * @Assert\Range(min=Hour::MIN_HOUR, max=Hour::MAX_HOUR)
     */
    private $weekendDeliveryAvailableFromHour;

    /**
     * @var int|null
     *
     * @Assert\NotNull()
     * @Assert\Range(min=Hour::MIN_HOUR, max=Hour::MAX_HOUR)
     */
    private $weekendDeliveryAvailableToHour;

    /**
     * @var float|null
     *
     * @Assert\NotNull()
     * @Assert\GreaterThanOrEqual(0)
     */
    private $codPrice;

    /**
     * @var int|null
     *
     * @Assert\NotNull()
     * @Assert\Range(min=Day::MIN_DAY, max=Day::MAX_DAY)
     */
    private $codAvailableFromDay;

    /**
     * @var int|null
     *
     * @Assert\NotNull()
     * @Assert\Range(min=Day::MIN_DAY, max=Day::MAX_DAY)
     */
    private $codAvailableToDay;

    /**
     * @var int|null
     *
     * @Assert\NotNull()
     * @Assert\Range(min=Hour::MIN_HOUR, max=Hour::MAX_HOUR)
     */
    private $codAvailableFromHour;

    /**
     * @var int|null
     *
     * @Assert\NotNull()
     * @Assert\Range(min=Hour::MIN_HOUR, max=Hour::MAX_HOUR)
     */
    private $codAvailableToHour;

    public function getCarrierId(): ?int
    {
        return $this->carrierId;
    }

    public function setCarrierId(?\Carrier $carrier): self
    {
        $this->carrierId = $carrier instanceof \Carrier ? (int) $carrier->id_reference : null;

        return $this;
    }

    public function getWeekendDeliveryPrice(): ?float
    {
        return $this->weekendDeliveryPrice;
    }

    public function setWeekendDeliveryPrice(?float $weekendDeliveryPrice): self
    {
        $this->weekendDeliveryPrice = $weekendDeliveryPrice;

        return $this;
    }

    public function getWeekendDeliveryAvailableFromDay(): ?int
    {
        return $this->weekendDeliveryAvailableFromDay;
    }

    public function setWeekendDeliveryAvailableFromDay(?Day $weekendDeliveryAvailableFromDay): self
    {
        $this->weekendDeliveryAvailableFromDay = $weekendDeliveryAvailableFromDay instanceof Day ? $weekendDeliveryAvailableFromDay->getId() : null;

        return $this;
    }

    public function getWeekendDeliveryAvailableToDay(): ?int
    {
        return $this->weekendDeliveryAvailableToDay;
    }

    public function setWeekendDeliveryAvailableToDay(?Day $weekendDeliveryAvailableToDay): self
    {
        $this->weekendDeliveryAvailableToDay = $weekendDeliveryAvailableToDay instanceof Day ? $weekendDeliveryAvailableToDay->getId() : null;

        return $this;
    }

    public function getWeekendDeliveryAvailableFromHour(): ?int
    {
        return $this->weekendDeliveryAvailableFromHour;
    }

    public function setWeekendDeliveryAvailableFromHour(?Hour $weekendDeliveryAvailableFromHour): self
    {
        $this->weekendDeliveryAvailableFromHour = $weekendDeliveryAvailableFromHour instanceof Hour ? $weekendDeliveryAvailableFromHour->getId() : null;

        return $this;
    }

    public function getWeekendDeliveryAvailableToHour(): ?int
    {
        return $this->weekendDeliveryAvailableToHour;
    }

    public function setWeekendDeliveryAvailableToHour(?Hour $weekendDeliveryAvailableToHour): self
    {
        $this->weekendDeliveryAvailableToHour = $weekendDeliveryAvailableToHour instanceof Hour ? $weekendDeliveryAvailableToHour->getId() : null;

        return $this;
    }

    public function getCodPrice(): ?float
    {
        return $this->codPrice;
    }

    public function setCodPrice(?float $codPrice): self
    {
        $this->codPrice = $codPrice;

        return $this;
    }

    public function getCodAvailableFromDay(): ?int
    {
        return $this->codAvailableFromDay;
    }

    public function setCodAvailableFromDay(?Day $codAvailableFromDay): self
    {
        $this->codAvailableFromDay = $codAvailableFromDay instanceof Day ? $codAvailableFromDay->getId() : null;

        return $this;
    }

    public function getCodAvailableToDay(): ?int
    {
        return $this->codAvailableToDay;
    }

    public function setCodAvailableToDay(?Day $codAvailableToDay): self
    {
        $this->codAvailableToDay = $codAvailableToDay instanceof Day ? $codAvailableToDay->getId() : null;

        return $this;
    }

    public function getCodAvailableFromHour(): ?int
    {
        return $this->codAvailableFromHour;
    }

    public function setCodAvailableFromHour(?Hour $codAvailableFromHour): self
    {
        $this->codAvailableFromHour = $codAvailableFromHour instanceof Hour ? $codAvailableFromHour->getId() : null;

        return $this;
    }

    public function getCodAvailableToHour(): ?int
    {
        return $this->codAvailableToHour;
    }

    public function setCodAvailableToHour(?Hour $codAvailableToHour): self
    {
        $this->codAvailableToHour = $codAvailableToHour instanceof Hour ? $codAvailableToHour->getId() : null;

        return $this;
    }
}

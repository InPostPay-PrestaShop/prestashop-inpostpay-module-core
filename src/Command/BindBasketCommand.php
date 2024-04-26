<?php

declare(strict_types=1);

namespace izi\prestashop\Command;

use izi\prestashop\BasketApp\Basket\Request\Browser;
use izi\prestashop\Common\BindingPlace;
use izi\prestashop\Common\PhoneNumber;
use izi\prestashop\Entities\BasketInterface;
use izi\prestashop\Handler\BindBasketHandler;

/**
 * @see BindBasketHandler
 */
final class BindBasketCommand
{
    /**
     * @var BasketInterface
     */
    private $basket;

    /**
     * @var Browser
     */
    private $browser;

    /**
     * @var PhoneNumber|null
     */
    private $phoneNumber;

    /**
     * @var string|null
     */
    private $browserId;

    /**
     * @var BindingPlace|null
     */
    private $bindingPlace;

    public function __construct(BasketInterface $basket, Browser $browser, ?PhoneNumber $phoneNumber = null, ?string $browserId = null, ?BindingPlace $bindingPlace = null)
    {
        $this->basket = $basket;
        $this->browser = $browser;
        $this->phoneNumber = $phoneNumber;
        $this->browserId = $browserId;
        $this->bindingPlace = $bindingPlace;
    }

    public function getBasket(): BasketInterface
    {
        return $this->basket;
    }

    public function getBrowser(): Browser
    {
        return $this->browser;
    }

    public function getPhoneNumber(): ?PhoneNumber
    {
        return $this->phoneNumber;
    }

    public function getBrowserId(): ?string
    {
        return $this->browserId;
    }

    public function getBindingPlace(): ?BindingPlace
    {
        return $this->bindingPlace;
    }
}

<?php

declare(strict_types=1);

namespace izi\prestashop\BasketApp\Basket\Request;

use izi\prestashop\Common\BindingPlace;
use izi\prestashop\Common\PhoneNumber;

/**
 * @deprecated
 */
final class BindingRequest implements \JsonSerializable
{
    /**
     * @var Browser
     */
    private $browser;

    /**
     * @var BindingMethod|null
     */
    private $binding_method;

    /**
     * @var BindingPlace|null
     */
    private $binding_place;

    /**
     * @var PhoneNumber|null
     */
    private $phone_number;

    private function __construct(Browser $browser, ?BindingMethod $binding_method = null, ?BindingPlace $binding_place = null, ?PhoneNumber $phone_number = null)
    {
        $this->browser = $browser;
        $this->binding_method = $binding_method;
        $this->binding_place = $binding_place;
        $this->phone_number = $phone_number;
    }

    public static function byPhoneNumber(Browser $browser, PhoneNumber $phone_number, ?BindingPlace $binding_place = null): self
    {
        return new self($browser, BindingMethod::Phone(), $binding_place, $phone_number);
    }

    public static function byDeepLink(Browser $browser, ?BindingPlace $binding_place = null): self
    {
        return new self($browser, BindingMethod::DeepLink(), $binding_place);
    }

    public function getBrowser(): Browser
    {
        return $this->browser;
    }

    public function getMethod(): ?BindingMethod
    {
        return $this->binding_method;
    }

    public function getPlace(): ?BindingPlace
    {
        return $this->binding_place;
    }

    public function getPhoneNumber(): ?PhoneNumber
    {
        return $this->phone_number;
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}

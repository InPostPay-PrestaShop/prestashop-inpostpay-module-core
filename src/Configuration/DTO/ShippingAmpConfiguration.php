<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration\DTO;

use izi\prestashop\Configuration\ShippingAmpConfigurationInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class ShippingAmpConfiguration implements ShippingAmpConfigurationInterface
{
    /**
     * @var Shipping|null
     *
     * @Assert\NotNull()
     * @Assert\Valid()
     */
    private $shipping;

    public function __construct(
        Shipping $shipping = null
    ) {
        $this->shipping = $shipping;
    }

    public function getAmpShipping(): Shipping
    {
        return $this->shipping;
    }

    public function setAmpShipping(Shipping $shipping): ShippingAmpConfigurationInterface
    {
        $this->shipping = $shipping;

        return $this;
    }

}

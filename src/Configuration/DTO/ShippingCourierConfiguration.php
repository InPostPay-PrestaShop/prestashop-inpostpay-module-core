<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration\DTO;

use izi\prestashop\Configuration\ShippingAmpConfigurationInterface;
use izi\prestashop\Configuration\ShippingCourierConfigurationInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class ShippingCourierConfiguration implements ShippingCourierConfigurationInterface
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

    public function getCourierShipping(?int $idShop = null): Shipping
    {
        return $this->shipping;
    }

    public function setCourierShipping(Shipping $shipping): ShippingCourierConfigurationInterface
    {
        $this->shipping = $shipping;

        return $this;
    }
}

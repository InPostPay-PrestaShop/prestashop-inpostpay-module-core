<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration\DTO;

use izi\prestashop\Common\BindingPlace;
use izi\prestashop\Configuration\GuiConfigurationInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class GuiConfiguration implements GuiConfigurationInterface
{
    /**
     * @var WidgetDisplayConfiguration|null
     *
     * @Assert\Valid()
     */
    private $cartWidgetDisplayConfiguration;

    /**
     * @var WidgetDisplayConfiguration|null
     *
     * @Assert\Valid()
     */
    private $productWidgetDisplayConfiguration;

    /**
     * @var WidgetDisplayConfiguration|null
     *
     * @Assert\Valid()
     */
    private $loginPageWidgetDisplayConfiguration;

    /**
     * @var WidgetDisplayConfiguration|null
     *
     * @Assert\Valid()
     */
    private $registerFormPageWidgetDisplayConfiguration;

    /**
     * @var WidgetDisplayConfiguration|null
     *
     * @Assert\Valid()
     */
    private $checkoutPageWidgetDisplayConfiguration;

    /**
     * @var WidgetDisplayConfiguration|null
     *
     * @Assert\Valid()
     */
    private $miniCartPageWidgetDisplayConfiguration;

    public function __construct(?WidgetDisplayConfiguration $cartWidgetDisplayConfiguration = null, ?WidgetDisplayConfiguration $productWidgetDisplayConfiguration = null, ?WidgetDisplayConfiguration $loginPageWidgetDisplayConfiguration = null, ?WidgetDisplayConfiguration $registerFormPageWidgetDisplayConfiguration = null, ?WidgetDisplayConfiguration $checkoutPageWidgetDisplayConfiguration = null, ?WidgetDisplayConfiguration $miniCartPageWidgetDisplayConfiguration = null)
    {
        $this->cartWidgetDisplayConfiguration = $cartWidgetDisplayConfiguration;
        $this->productWidgetDisplayConfiguration = $productWidgetDisplayConfiguration;
        $this->loginPageWidgetDisplayConfiguration = $loginPageWidgetDisplayConfiguration;
        $this->registerFormPageWidgetDisplayConfiguration = $registerFormPageWidgetDisplayConfiguration;
        $this->checkoutPageWidgetDisplayConfiguration = $checkoutPageWidgetDisplayConfiguration;
        $this->miniCartPageWidgetDisplayConfiguration = $miniCartPageWidgetDisplayConfiguration;
    }

    public function getCartWidgetDisplayConfiguration(): WidgetDisplayConfiguration
    {
        return $this->cartWidgetDisplayConfiguration ?? new WidgetDisplayConfiguration(BindingPlace::BasketSummary());
    }

    public function setCartWidgetDisplayConfiguration(?WidgetDisplayConfiguration $cartWidgetDisplayConfiguration): self
    {
        $this->cartWidgetDisplayConfiguration = $cartWidgetDisplayConfiguration;

        return $this;
    }

    public function getProductWidgetDisplayConfiguration(): WidgetDisplayConfiguration
    {
        return $this->productWidgetDisplayConfiguration ?? new WidgetDisplayConfiguration(BindingPlace::ProductCard());
    }

    public function setProductWidgetDisplayConfiguration(?WidgetDisplayConfiguration $productWidgetDisplayConfiguration): self
    {
        $this->productWidgetDisplayConfiguration = $productWidgetDisplayConfiguration;

        return $this;
    }

    public function getLoginPageWidgetDisplayConfiguration(): WidgetDisplayConfiguration
    {
        return $this->loginPageWidgetDisplayConfiguration ?? new WidgetDisplayConfiguration(BindingPlace::LoginPage());
    }

    public function setLoginPageWidgetDisplayConfiguration(?WidgetDisplayConfiguration $loginPageWidgetDisplayConfiguration): self
    {
        $this->loginPageWidgetDisplayConfiguration = $loginPageWidgetDisplayConfiguration;

        return $this;
    }

    public function getRegisterFormPageWidgetDisplayConfiguration(): WidgetDisplayConfiguration
    {
        return $this->registerFormPageWidgetDisplayConfiguration ?? new WidgetDisplayConfiguration(BindingPlace::RegisterFormPage());
    }

    public function setRegisterFormPageWidgetDisplayConfiguration(?WidgetDisplayConfiguration $registerFormPageWidgetDisplayConfiguration): self
    {
        $this->registerFormPageWidgetDisplayConfiguration = $registerFormPageWidgetDisplayConfiguration;

        return $this;
    }

    public function getCheckoutPageWidgetDisplayConfiguration(): WidgetDisplayConfiguration
    {
        return $this->checkoutPageWidgetDisplayConfiguration ?? new WidgetDisplayConfiguration(BindingPlace::CheckoutPage());
    }

    public function setCheckoutPageWidgetDisplayConfiguration(?WidgetDisplayConfiguration $checkoutPageWidgetDisplayConfiguration): self
    {
        $this->checkoutPageWidgetDisplayConfiguration = $checkoutPageWidgetDisplayConfiguration;

        return $this;
    }

    public function getMiniCartPageWidgetDisplayConfiguration(): WidgetDisplayConfiguration
    {
        return $this->miniCartPageWidgetDisplayConfiguration ?? new WidgetDisplayConfiguration(BindingPlace::MiniCartPage());
    }

    public function setMiniCartPageWidgetDisplayConfiguration(?WidgetDisplayConfiguration $miniCartPageWidgetDisplayConfiguration): self
    {
        $this->miniCartPageWidgetDisplayConfiguration = $miniCartPageWidgetDisplayConfiguration;

        return $this;
    }
}

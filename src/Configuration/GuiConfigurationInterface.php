<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration;

use izi\prestashop\Common\BindingPlace;

/**
 * @method static BindingPlace[] getSupportedBindingPlaces()
 * @method WidgetDisplayConfigurationInterface getDisplayConfiguration(BindingPlace $bindingPlace)
 */
interface GuiConfigurationInterface
{
    /**
     * @deprecated use {@see getDisplayConfiguration()} instead
     *
     * @return WidgetDisplayConfigurationInterface
     */
    public function getCartWidgetDisplayConfiguration();

    /**
     * @deprecated use {@see getDisplayConfiguration()} instead
     *
     * @return WidgetDisplayConfigurationInterface
     */
    public function getProductWidgetDisplayConfiguration();

    /**
     * @deprecated use {@see getDisplayConfiguration()} instead
     *
     * @return WidgetDisplayConfigurationInterface
     */
    public function getLoginPageWidgetDisplayConfiguration();

    /**
     * @deprecated use {@see getDisplayConfiguration()} instead
     *
     * @return WidgetDisplayConfigurationInterface
     */
    public function getRegisterFormPageWidgetDisplayConfiguration();

    /**
     * @deprecated use {@see getDisplayConfiguration()} instead
     *
     * @return WidgetDisplayConfigurationInterface
     */
    public function getCheckoutPageWidgetDisplayConfiguration();

    /**
     * @deprecated use {@see getDisplayConfiguration()} instead
     *
     * @return WidgetDisplayConfigurationInterface
     */
    public function getMiniCartPageWidgetDisplayConfiguration();
}

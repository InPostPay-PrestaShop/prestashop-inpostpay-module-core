<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration;

use izi\prestashop\Common\BindingPlace;

interface GuiConfigurationInterface
{
    /**
     * @return BindingPlace[]
     */
    public static function getSupportedBindingPlaces(): array;

    /**
     * @return WidgetDisplayConfigurationInterface|ProductAwareWidgetDisplayConfigurationInterface
     */
    public function getDisplayConfiguration(BindingPlace $bindingPlace): WidgetDisplayConfigurationInterface;
}

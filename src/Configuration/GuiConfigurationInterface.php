<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration;

use izi\prestashop\Configuration\DTO\HtmlStyles;
use izi\prestashop\Configuration\DTO\WidgetDisplayConfiguration;
use izi\prestashop\View\Widget\Configuration;

interface GuiConfigurationInterface
{
    public function getCartWidgetDisplayConfiguration(): WidgetDisplayConfiguration;

    public function getProductWidgetDisplayConfiguration(): WidgetDisplayConfiguration;

    public function getLoginPageWidgetDisplayConfiguration(): WidgetDisplayConfiguration;

    public function getRegisterFormPageWidgetDisplayConfiguration(): WidgetDisplayConfiguration;

    public function getCheckoutPageWidgetDisplayConfiguration(): WidgetDisplayConfiguration;

    public function getMiniCartPageWidgetDisplayConfiguration(): WidgetDisplayConfiguration;
}

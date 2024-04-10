<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration;

use izi\prestashop\Common\BindingPlace;
use izi\prestashop\Configuration\DTO\HtmlStyles;
use izi\prestashop\View\Widget\Configuration;

interface WidgetDisplayConfigurationInterface
{
    public function getBinding(): BindingPlace;

    public function getWidgetConfiguration(): Configuration;

    public function isDisplayed(): bool;

    public function getHtmlStyles(): HtmlStyles;
}

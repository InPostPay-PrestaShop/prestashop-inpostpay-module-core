<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration;

use izi\prestashop\View\Widget\Configuration;

interface WidgetDisplayConfigurationInterface
{
    public function isDisplayed(): bool;

    public function getWidgetConfiguration(): Configuration;

    /**
     * @return iterable<string, string> CSS values by property
     */
    public function getHtmlStyles();
}

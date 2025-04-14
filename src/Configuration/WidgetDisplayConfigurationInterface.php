<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration;

use izi\prestashop\View\Widget\WidgetConfigurationInterface;

/**
 * @template T of WidgetConfigurationInterface
 */
interface WidgetDisplayConfigurationInterface
{
    public function isDisplayed(): bool;

    /**
     * @return T
     */
    public function getWidgetConfiguration(): WidgetConfigurationInterface;

    /**
     * @return iterable<string, string> CSS values by property
     */
    public function getHtmlStyles();
}

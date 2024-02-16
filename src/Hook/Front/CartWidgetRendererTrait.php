<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Front;

use izi\prestashop\Configuration\GuiConfigurationInterface;
use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

trait CartWidgetRendererTrait
{
    /**
     * @var GuiConfigurationInterface
     */
    private $configuration;

    /**
     * @var WidgetInterface
     */
    private $module;

    private function renderWidget(array $parameters, string $hookName = null): string
    {
        if (!$this->configuration->isWidgetDisplayedOnCartPage()) {
            return '';
        }

        return $this->module->renderWidget($hookName, [
            'config' => $this->configuration->getCartPageWidgetConfiguration(),
            'request' => $parameters['request'] ?? null,
        ]);
    }

    private function getHtmlStyles(): array
    {
        $styles = $this->configuration->getCartPageHtmlStyles();

        return is_array($styles)
            ? $styles
            : iterator_to_array($styles);
    }
}

<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Front;

use izi\prestashop\Common\BindingPlace;
use izi\prestashop\Configuration\GuiConfigurationInterface;
use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

trait ButtonWidgetRendererTrait
{
    /**
     * @var GuiConfigurationInterface
     */
    private $configuration;

    /**
     * @var WidgetInterface
     */
    private $module;

    private function renderWidget(BindingPlace $bindingPlace, array $parameters, ?string $hookName = null): string
    {
        $configuration = $this->configuration->getDisplayConfiguration($bindingPlace);

        if (!$configuration->isDisplayed()) {
            return '';
        }

        return $this->module->renderWidget($hookName, [
            'config' => $configuration->getWidgetConfiguration(),
            'request' => $parameters['request'] ?? null,
            'cart' => $parameters['cart'] ?? null,
        ]);
    }

    private function getHtmlStyles(BindingPlace $bindingPlace): array
    {
        $configuration = $this->configuration->getDisplayConfiguration($bindingPlace);
        $styles = $configuration->getHtmlStyles();

        if ($styles instanceof \Traversable) {
            $styles = iterator_to_array($styles);
        }

        return $styles;
    }
}

<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Front;

use izi\prestashop\Configuration\GuiConfigurationInterface;
use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

trait ProductWidgetRendererTrait
{
    /**
     * @var GuiConfigurationInterface
     */
    private $configuration;

    /**
     * @var WidgetInterface
     */
    private $module;

    private function renderWidget(int $productId, array $parameters, string $hookName = null): string
    {
        if (0 >= $productId) {
            return '';
        }

        if (!$this->configuration->isWidgetDisplayedOnProductCard()) {
            return '';
        }

        $configuration = $this->configuration
            ->getProductCardWidgetConfiguration()
            ->setProductId((string) $productId);

        return $this->module->renderWidget($hookName, [
            'config' => $configuration,
            'request' => $parameters['request'] ?? null,
        ]);
    }

    private function getHtmlStyles(): array
    {
        $styles = $this->configuration->getProductCardHtmlStyles();

        return is_array($styles)
            ? $styles
            : iterator_to_array($styles);
    }
}

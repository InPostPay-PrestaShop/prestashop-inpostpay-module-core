<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Front;

use izi\prestashop\Configuration\GeneralConfigurationInterface;
use izi\prestashop\Configuration\GuiConfigurationInterface;
use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

trait ProductWidgetRendererTrait
{
    /**
     * @var GuiConfigurationInterface
     */
    private $configuration;

    /**
     * @var GeneralConfigurationInterface
     */
    private $generalConfiguration;

    /**
     * @var WidgetInterface
     */
    private $module;

    private function renderWidget(int $productId, array $parameters, ?string $hookName = null): string
    {
        if (0 >= $productId) {
            return '';
        }

        $productWidget = $this->configuration->getProductWidgetDisplayConfiguration();

        if (!$productWidget->isDisplayed() || !$this->shouldDisplayWidget($hookName)) {
            return '';
        }

        $configuration = $productWidget->getWidgetConfiguration()
            ->setProductId((string) $productId);

        return $this->module->renderWidget($hookName, [
            'config' => $configuration,
            'request' => $parameters['request'] ?? null,
        ]);
    }

    private function shouldDisplayWidget(string $hookName): bool
    {
        return $hookName === $this->generalConfiguration->getProductCardDisplayHook();
    }

    private function getHtmlStyles(): array
    {
        $productWidget = $this->configuration->getProductWidgetDisplayConfiguration();
        $styles = $productWidget->getHtmlStyles();

        return is_array($styles)
            ? $styles
            : iterator_to_array($styles);
    }
}

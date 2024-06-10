<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Front;

use izi\prestashop\Configuration\GeneralConfigurationInterface;
use izi\prestashop\Configuration\GuiConfigurationInterface;
use PrestaShop\PrestaShop\Adapter\Presenter\Product\ProductLazyArray;
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

    /**
     * @param ProductLazyArray|array{id_product: int, add_to_cart_url: string|null} $product
     */
    private function renderWidget($product, array $parameters, string $hookName): string
    {
        if (0 >= $productId = (int) $product['id_product']) {
            return '';
        }

        if (!$this->shouldDisplayWidget($hookName, $product)) {
            return '';
        }

        $productWidget = $this->configuration->getProductWidgetDisplayConfiguration();

        if (!$productWidget->isDisplayed()) {
            return '';
        }

        $configuration = $productWidget
            ->getWidgetConfiguration()
            ->setProductId((string) $productId);

        return $this->module->renderWidget($hookName, [
            'config' => $configuration,
            'request' => $parameters['request'] ?? null,
        ]);
    }

    /**
     * @param ProductLazyArray|array{add_to_cart_url: string|null} $product
     */
    private function shouldDisplayWidget(string $hookName, $product): bool
    {
        // If add_to_cart_url is not set, the product is not available for sale.
        if (null === $product['add_to_cart_url']) {
            return false;
        }

        return $hookName === $this->generalConfiguration->getProductCardDisplayHook();
    }

    private function getHtmlStyles(): array
    {
        $productWidget = $this->configuration->getProductWidgetDisplayConfiguration();
        $styles = $productWidget->getHtmlStyles();

        return iterator_to_array($styles);
    }
}

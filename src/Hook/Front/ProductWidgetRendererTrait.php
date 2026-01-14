<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Front;

use izi\prestashop\Common\BindingPlace;
use izi\prestashop\Configuration\GeneralConfigurationInterface;
use izi\prestashop\Configuration\GuiConfigurationInterface;
use izi\prestashop\Configuration\ProductAwareWidgetDisplayConfigurationInterface;
use izi\prestashop\Hook\Exception\InvalidHookParamException;
use izi\prestashop\Repository\BasketSessionRepositoryInterface;
use PrestaShop\PrestaShop\Adapter\Presenter\Product\ProductLazyArray;
use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

/**
 * @internal
 */
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
     * @var \Context
     */
    private $context;

    /**
     * @var BasketSessionRepositoryInterface
     */
    private $basketSessionRepository;

    private function renderWidget(ProductLazyArray $product, array $parameters, string $hookName): string
    {
        if (0 >= $productId = (int) $product['id_product']) {
            return '';
        }

        if (!$this->shouldDisplayWidget($product)) {
            return '';
        }

        $configuration = $this->configuration->getDisplayConfiguration(BindingPlace::ProductCard());

        $widgetConfig = $configuration
            ->getWidgetConfiguration()
            ->setProductId((string) $productId);

        return $this->module->renderWidget($hookName, [
            'config' => $widgetConfig,
            'request' => $parameters['request'] ?? null,
            'cart' => $parameters['cart'] ?? null,
        ]);
    }

    private function shouldDisplayWidget(ProductLazyArray $product): bool
    {
        return $this->checkProductAvailability($product) || $this->isCartBound();
    }

    private function checkProductAvailability(ProductLazyArray $product): bool
    {
        if (!$product['available_for_order']) {
            return false;
        }

        return $product['allow_oosp']
            || \StockAvailable::getQuantityAvailableByProduct($product['id_product'], $product['id_product_attribute'], $this->context->shop->id) >= $product['minimal_quantity'];
    }

    private function isCartBound(): bool
    {
        $basketSession = $this->basketSessionRepository->findByEntityId($this->context->cart->id);

        if (null === $basketSession) {
            return false;
        }

        return $basketSession->isBasketBound();
    }

    private function getHtmlStyles(): array
    {
        $styles = $this->configuration
            ->getDisplayConfiguration(BindingPlace::ProductCard())
            ->getHtmlStyles();

        if (!\is_array($styles)) {
            $styles = iterator_to_array($styles);
        }

        return $styles;
    }

    private function getProduct(array $parameters): ProductLazyArray
    {
        $product = $parameters['product'] ?? null;

        if (!$product instanceof ProductLazyArray) {
            throw InvalidHookParamException::unexpectedType('product', $product, ProductLazyArray::class);
        }

        return $product;
    }

    private function isWidgetDisplayed(ProductLazyArray $product): bool
    {
        $configuration = $this->configuration->getDisplayConfiguration(BindingPlace::ProductCard());

        return $configuration instanceof ProductAwareWidgetDisplayConfigurationInterface
            ? $configuration->isDisplayed($product)
            : $configuration->isDisplayed();
    }
}

<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Front;

use izi\prestashop\Common\BindingPlace;
use izi\prestashop\Configuration\GeneralConfigurationInterface;
use izi\prestashop\Configuration\GuiConfigurationInterface;
use izi\prestashop\Hook\Exception\InvalidHookParamException;
use izi\prestashop\Repository\BasketSessionRepositoryInterface;
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
     * @var \Context
     */
    private $context;

    /**
     * @var BasketSessionRepositoryInterface
     */
    private $basketSessionRepository;

    /**
     * @param ProductLazyArray|array{id_product: int} $product
     */
    private function renderWidget($product, array $parameters, string $hookName): string
    {
        if (0 >= $productId = (int) $product['id_product']) {
            return '';
        }

        if (!$this->shouldDisplayWidget($hookName, $product)) {
            return '';
        }

        $configuration = $this->configuration->getDisplayConfiguration(BindingPlace::ProductCard());

        if (!$configuration->isDisplayed($product)) {
            return '';
        }

        $widgetConfig = $configuration
            ->getWidgetConfiguration()
            ->setProductId((string) $productId);

        return $this->module->renderWidget($hookName, [
            'config' => $widgetConfig,
            'request' => $parameters['request'] ?? null,
            'cart' => $parameters['cart'] ?? null,
        ]);
    }

    /**
     * @param ProductLazyArray|array $product
     */
    private function shouldDisplayWidget(string $hookName, $product): bool
    {
        return $this->checkProductAvailability($product) || $this->isCartBound();
    }

    /**
     * @param ProductLazyArray|array $product
     */
    private function checkProductAvailability($product): bool
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

        if ($styles instanceof \Traversable) {
            $styles = iterator_to_array($styles);
        }

        return $styles;
    }

    /**
     * @return array|\ArrayAccess
     */
    private function getProduct(array $parameters)
    {
        $product = $parameters['product'] ?? null;

        if (!is_array($product) && !$product instanceof \ArrayAccess) {
            throw InvalidHookParamException::unexpectedType('product', $product, 'array|ArrayAccess');
        }

        if (!isset($product['id_product'])) {
            throw new InvalidHookParamException('Expected offset "id_product" in parameter "product".');
        }

        if (!is_numeric($product['id_product'])) {
            throw new InvalidHookParamException('Expected offset "id_product" of parameter "product" to be numeric.');
        }

        return $product;
    }
}

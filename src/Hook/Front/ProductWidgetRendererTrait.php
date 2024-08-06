<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Front;

use izi\prestashop\Common\BindingPlace;
use izi\prestashop\Configuration\GeneralConfigurationInterface;
use izi\prestashop\Configuration\GuiConfiguration;
use izi\prestashop\Configuration\GuiConfigurationInterface;
use izi\prestashop\Repository\BasketSessionRepositoryInterface;
use PrestaShop\PrestaShop\Adapter\Presenter\Product\ProductLazyArray;
use PrestaShop\PrestaShop\Core\Module\WidgetInterface;
use Symfony\Component\HttpFoundation\Request;

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

        $productWidget = GuiConfiguration::getDisplayConfig($this->configuration, BindingPlace::ProductCard());

        if (!$productWidget->isDisplayed($product)) {
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
        $productWidget = GuiConfiguration::getDisplayConfig($this->configuration, BindingPlace::ProductCard());
        $styles = $productWidget->getHtmlStyles();

        if ($styles instanceof \Traversable) {
            $styles = iterator_to_array($styles);
        }

        return $styles;
    }

    private function shouldRenderCacheableHookContent(?Request $request): bool
    {
        if (!$this->generalConfiguration->isFullPageCacheModuleInUse()) {
            return false;
        }

        return null === $request || !$request->isXmlHttpRequest();
    }
}

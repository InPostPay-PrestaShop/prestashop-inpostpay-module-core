<?php

declare(strict_types=1);

namespace izi\prestashop\View\Asset\Provider\Front;

use izi\prestashop\Configuration\GeneralConfigurationInterface;
use izi\prestashop\Hook\Front\DisplayProductActions;
use izi\prestashop\View\Asset\Provider\AssetsProviderInterface;
use izi\prestashop\View\Asset\Provider\DTO\Assets;

final class ProductPageAssetsProvider implements AssetsProviderInterface
{
    /**
     * @var GeneralConfigurationInterface
     */
    private $configuration;

    /**
     * @var \Context
     */
    private $context;

    public function __construct(GeneralConfigurationInterface $configuration, \Context $context)
    {
        $this->configuration = $configuration;
        $this->context = $context;
    }

    public function getAssets(): ?Assets
    {
        if (!$this->context->controller instanceof \ProductControllerCore) {
            return null;
        }

        $assets = new Assets();

        if (DisplayProductActions::HOOK_NAME === $this->configuration->getProductCardDisplayHook()) {
            $assets->addStyleSheet('product.css');
        }

        $product = $this->context->controller->getProduct();

        if (\Validate::isLoadedObject($product)) {
            $assets->addJavaScriptVariable('inpostizi_product_page_id_product', (int) $product->id);
        }

        return $assets;
    }
}

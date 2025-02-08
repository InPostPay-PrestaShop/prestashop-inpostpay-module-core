<?php

declare(strict_types=1);

namespace izi\prestashop\Hook;

use izi\prestashop\View\Asset\AssetManagerInterface;
use izi\prestashop\View\Asset\Provider\AssetsProviderInterface;

trait AssetRegistryUpdaterTrait
{
    /**
     * @var AssetManagerInterface
     */
    private $assetManager;

    private function registerAssets(AssetsProviderInterface $assetsProvider): void
    {
        if (null === $assets = $assetsProvider->getAssets()) {
            return;
        }

        foreach ($assets->getJavaScripts() as $path => $options) {
            $this->assetManager->registerJavaScript($path, $options);
        }

        foreach ($assets->getStyleSheets() as $path => $options) {
            $this->assetManager->registerStyleSheet($path, $options);
        }

        if ([] !== $jsVars = $assets->getJavaScriptVariables()) {
            $this->assetManager->registerJavaScriptVariables($jsVars);
        }
    }
}

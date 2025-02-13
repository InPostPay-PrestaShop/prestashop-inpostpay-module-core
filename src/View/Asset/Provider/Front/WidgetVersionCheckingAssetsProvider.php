<?php

declare(strict_types=1);

namespace izi\prestashop\View\Asset\Provider\Front;

use izi\prestashop\Configuration\ApiConfigurationInterface;
use izi\prestashop\Configuration\WidgetVersionCheckerTrait;
use izi\prestashop\View\Asset\Provider\AssetsProviderInterface;
use izi\prestashop\View\Asset\Provider\DTO\Assets;

/**
 * @internal
 */
final class WidgetVersionCheckingAssetsProvider implements AssetsProviderInterface
{
    use WidgetVersionCheckerTrait;

    /**
     * @var AssetsProviderInterface
     */
    private $provider;

    /**
     * @param CommonAssetsProvider $provider
     */
    public function __construct(AssetsProviderInterface $provider, ApiConfigurationInterface $apiConfiguration)
    {
        $this->provider = $provider;
        $this->apiConfiguration = $apiConfiguration;
    }

    public function getAssets(): ?Assets
    {
        $assets = $this->provider->getAssets();

        if (!$this->isWidgetV2Enabled()) {
            return $assets;
        }

        if (!$this->hasV2Configuration()) {
            return null;
        }

        return $assets
            ->removeJavaScript('prestashopizi.js')
            ->addJavaScript('v2.js');
    }
}

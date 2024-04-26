<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration\Initializer;

use izi\prestashop\View\Asset\AssetManagerInterface;
use Symfony\Component\Asset\Packages;

final class AssetPackageInitializer implements ConfigurationInitializerInterface
{
    /**
     * @var Packages
     */
    private $packages;

    /**
     * @var AssetManagerInterface
     */
    private $assetManager;

    /**
     * @var string
     */
    private $packageName;

    public function __construct(Packages $packages, AssetManagerInterface $assetManager, string $packageName)
    {
        $this->packages = $packages;
        $this->assetManager = $assetManager;
        $this->packageName = $packageName;
    }

    public function init(): void
    {
        $this->packages->addPackage($this->packageName, $this->assetManager->getPackage());
    }
}

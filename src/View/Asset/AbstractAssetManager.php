<?php

declare(strict_types=1);

namespace izi\prestashop\View\Asset;

use Symfony\Component\Asset\Context\ContextInterface;
use Symfony\Component\Asset\PackageInterface;
use Symfony\Component\Asset\PathPackage;
use Symfony\Component\Asset\VersionStrategy\StaticVersionStrategy;

abstract class AbstractAssetManager implements AssetManagerInterface
{
    private $module;
    private $context;

    private $package;

    public function __construct(\Module $module, ContextInterface $context = null)
    {
        $this->module = $module;
        $this->context = $context;
    }

    public function registerJavaScriptVariables(array $variables): AssetManagerInterface
    {
        \Media::addJsDef($variables);

        return $this;
    }

    /**
     * @return PathPackage
     */
    public function getPackage(): PackageInterface
    {
        return $this->package ?? ($this->package = $this->createPackage());
    }

    private function createPackage(): PathPackage
    {
        $basePath = sprintf('modules/%s/views', $this->module->name);
        $versionStrategy = new StaticVersionStrategy($this->module->version, '%s?v=%s');

        return new PathPackage($basePath, $versionStrategy, $this->context);
    }
}

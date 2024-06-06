<?php

declare(strict_types=1);

namespace izi\prestashop\View\Asset;

use Symfony\Component\Asset\Context\ContextInterface;
use Symfony\Component\Asset\PackageInterface;
use Symfony\Component\Asset\PathPackage;
use Symfony\Component\Asset\VersionStrategy\StaticVersionStrategy;
use Symfony\Component\Asset\VersionStrategy\VersionStrategyInterface;

abstract class AbstractAssetManager implements AssetManagerInterface
{
    /**
     * @var \Module
     */
    protected $module;

    /**
     * @var ContextInterface|null
     */
    private $context;

    /**
     * @var VersionStrategyInterface|null
     */
    private $versionStrategy;

    /**
     * @var PathPackage|null
     */
    private $package;

    public function __construct(\Module $module, ?ContextInterface $context = null, ?VersionStrategyInterface $versionStrategy = null)
    {
        $this->module = $module;
        $this->context = $context;
        $this->versionStrategy = $versionStrategy;
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

    abstract protected function getBasePath(): string;

    private function createPackage(): PathPackage
    {
        $basePath = $this->getBasePath();
        $versionStrategy = $this->getVersionStrategy();

        return new PathPackage($basePath, $versionStrategy, $this->context);
    }

    private function getVersionStrategy(): VersionStrategyInterface
    {
        return $this->versionStrategy ?? ($this->versionStrategy = new StaticVersionStrategy($this->module->version, '%s?v=%s'));
    }
}

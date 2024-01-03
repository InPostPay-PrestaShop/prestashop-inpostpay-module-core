<?php

declare(strict_types=1);

namespace izi\prestashop\View\Asset;

use Symfony\Component\Asset\PackageInterface;

interface AssetManagerInterface
{
    /**
     * @return static
     */
    public function registerJavaScript(string $path, array $options = []): self;

    /**
     * @return static
     */
    public function registerStyleSheet(string $path, array $options = []): self;

    /**
     * @return static
     */
    public function registerJavaScriptVariables(array $variables): self;

    public function getPackage(): PackageInterface;
}

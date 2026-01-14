<?php

declare(strict_types=1);

namespace izi\prestashop\View\Asset;

final class AdminAssetManager extends AbstractAssetManager
{
    /**
     * @var \Context
     */
    private $context;

    public function __construct(\Module $module, \Context $context)
    {
        parent::__construct($module);

        $this->context = $context;
    }

    public function registerJavaScript(string $path, array $options = []): AssetManagerInterface
    {
        $controller = $this->getController();
        $url = $this->getPackage()->getUrl($path);

        $controller->removeJS($url, false);
        $controller->addJS($url);

        return $this;
    }

    public function registerStyleSheet(string $path, array $options = []): AssetManagerInterface
    {
        $controller = $this->getController();
        $url = $this->getPackage()->getUrl($path);
        $mediaType = $options['media'] ?? 'all';

        $controller->removeCSS($url, $mediaType);
        $controller->addCSS($url, $mediaType, null, false);

        return $this;
    }

    protected function getBasePath(): string
    {
        return \sprintf('%s/views', rtrim($this->module->getPathUri(), '/'));
    }

    private function getController(): \AdminController
    {
        return $this->context->controller;
    }
}

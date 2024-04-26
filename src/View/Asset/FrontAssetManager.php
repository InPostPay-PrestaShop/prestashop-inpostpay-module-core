<?php

declare(strict_types=1);

namespace izi\prestashop\View\Asset;

use izi\prestashop\View\Asset\VersionStrategy\JsonManifestVersionStrategy as VersionStrategyPolyfill;
use Symfony\Component\Asset\VersionStrategy\JsonManifestVersionStrategy;
use Symfony\Component\Asset\VersionStrategy\VersionStrategyInterface;

final class FrontAssetManager extends AbstractAssetManager
{
    /**
     * @var \Module
     */
    private $module;

    /**
     * @var \Context
     */
    private $context;

    public function __construct(\Module $module, \Context $context)
    {
        parent::__construct($module, null, $this->createVersionStrategy($module));

        $this->module = $module;
        $this->context = $context;
    }

    public function registerJavaScript(string $path, array $options = []): AssetManagerInterface
    {
        $options = $this->resolveOptions($path, $options);
        $this->getController()->registerJavascript($options['id'], $path, $options);

        return $this;
    }

    public function registerStyleSheet(string $path, array $options = []): AssetManagerInterface
    {
        $options = $this->resolveOptions($path, $options);
        $this->getController()->registerStylesheet($options['id'], $path, $options);

        return $this;
    }

    private function resolveOptions(string &$path, array $options): array
    {
        $options['id'] = $options['id'] ?? $this->getAssetId($path);
        if ($this->isAbsoluteUrl($path)) {
            $options['server'] = $options['server'] ?? 'remote';
        } else {
            $path = $this->getPackage()->getUrl($path);
        }

        return $options;
    }

    private function getController(): \FrontController
    {
        return $this->context->controller;
    }

    private function getAssetId(string $path): string
    {
        return sprintf('modules-%s-%s', $this->module->name, pathinfo($path, PATHINFO_FILENAME));
    }

    private function isAbsoluteUrl(string $url): bool
    {
        return false !== strpos($url, '://') || 0 === strpos($url, '//');
    }

    private function createVersionStrategy(\Module $module): VersionStrategyInterface
    {
        $manifestPath = sprintf('%s/views/manifest.json', rtrim($module->getLocalPath(), '/'));

        return class_exists(JsonManifestVersionStrategy::class)
            ? new JsonManifestVersionStrategy($manifestPath)
            : new VersionStrategyPolyfill($manifestPath);
    }
}

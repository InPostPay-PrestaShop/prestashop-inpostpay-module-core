<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Admin;

use izi\prestashop\Hook\AssetRegistryUpdaterTrait;
use izi\prestashop\Hook\HookInterface;
use izi\prestashop\View\Asset\AssetManagerInterface;
use izi\prestashop\View\Asset\Provider\AssetsProviderInterface;

final class ActionAdminControllerSetMedia implements HookInterface
{
    use AssetRegistryUpdaterTrait;

    public const HOOK_NAME = 'actionAdminControllerSetMedia';

    /**
     * @var iterable<AssetsProviderInterface>
     */
    private $assetsProviders;

    /**
     * @param AssetManagerInterface $assetManager
     * @param iterable<AssetsProviderInterface> $assetsProviders
     */
    public function __construct(AssetManagerInterface $assetManager, iterable $assetsProviders)
    {
        $this->assetManager = $assetManager;
        $this->assetsProviders = $assetsProviders;
    }

    public static function getHookName(): string
    {
        return self::HOOK_NAME;
    }

    public function execute(array $parameters): void
    {
        foreach ($this->assetsProviders as $assetsProvider) {
            $this->registerAssets($assetsProvider);
        }
    }
}

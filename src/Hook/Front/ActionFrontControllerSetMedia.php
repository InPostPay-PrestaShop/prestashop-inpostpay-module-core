<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Front;

use izi\prestashop\Configuration\ApiConfigurationInterface;
use izi\prestashop\Hook\AssetRegistryUpdaterTrait;
use izi\prestashop\Hook\HookInterface;
use izi\prestashop\Security\Voter\BindingWidgetVoter;
use izi\prestashop\View\Asset\AssetManagerInterface;
use izi\prestashop\View\Asset\Provider\AssetsProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class ActionFrontControllerSetMedia implements HookInterface
{
    use AssetRegistryUpdaterTrait;

    public const HOOK_NAME = 'actionFrontControllerSetMedia';

    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * @var iterable<AssetsProviderInterface>
     */
    private $assetsProviders;

    /**
     * @var ApiConfigurationInterface
     */
    private $configuration;

    /**
     * @param iterable<AssetsProviderInterface> $assetsProviders
     */
    public function __construct(AssetManagerInterface $assetManager, AuthorizationCheckerInterface $authorizationChecker, iterable $assetsProviders, ApiConfigurationInterface $configuration)
    {
        $this->assetManager = $assetManager;
        $this->authorizationChecker = $authorizationChecker;
        $this->assetsProviders = $assetsProviders;
        $this->configuration = $configuration;
    }

    public static function getHookName(): string
    {
        return self::HOOK_NAME;
    }

    /**
     * @param array{request?: Request} $parameters
     */
    public function execute(array $parameters): void
    {
        $request = $parameters['request'] ?? null;

        if ($request instanceof Request && $request->isXmlHttpRequest()) {
            return;
        }

        if (!$this->hasRequiredConfiguration()) {
            return;
        }

        if (!$this->authorizationChecker->isGranted(BindingWidgetVoter::VIEW, $request)) {
            return;
        }

        foreach ($this->assetsProviders as $assetsProvider) {
            $this->registerAssets($assetsProvider);
        }

        // $this->module->l('Something went wrong. Please try again later.', self::HOOK_NAME) kept for \AdminTranslationsController translatable message discovery
    }

    private function hasRequiredConfiguration(): bool
    {
        return null !== $this->configuration->getClientCredentials() && null !== $this->configuration->getMerchantClientId();
    }
}

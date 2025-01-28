<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Front;

use izi\prestashop\Configuration\Adapter\Configuration;
use izi\prestashop\Configuration\GeneralConfiguration;
use izi\prestashop\Configuration\GeneralConfigurationInterface;
use izi\prestashop\Hook\AssetRegistryUpdaterTrait;
use izi\prestashop\Hook\HookInterface;
use izi\prestashop\Security\AuthorizationChecker;
use izi\prestashop\Security\Voter\BindingWidgetVoter;
use izi\prestashop\View\Asset\AssetManagerInterface;
use izi\prestashop\View\Asset\Provider\AssetsProviderInterface;
use izi\prestashop\View\Asset\Provider\Front\CommonAssetsProvider;
use izi\prestashop\View\Asset\Provider\Front\ProductPageAssetsProvider;
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
     * @var GeneralConfigurationInterface
     */
    private $configuration;

    /**
     * @param AssetManagerInterface $assetManager
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param iterable<AssetsProviderInterface> $assetsProviders
     * @param GeneralConfigurationInterface $configuration
     */
    public function __construct(/* \Module $module, \Context $context, EnvironmentInterface $environment, */ $assetManager, $authorizationChecker, $assetsProviders = [], $configuration = null)
    {
        $arguments = func_get_args();
        if (isset($arguments[3]) && $arguments[3] instanceof AssetManagerInterface) {
            @trigger_error(sprintf('Passing $module, $context and $environment as arguments for "%s::__construct()" is deprecated.', self::class), E_USER_DEPRECATED);
            $configuration = new GeneralConfiguration(new Configuration());
            $assetManager = $arguments[3];
            $authorizationChecker = AuthorizationChecker::create([new BindingWidgetVoter($configuration, $arguments[1])]);
            $assetsProviders = [
                new CommonAssetsProvider($arguments[0], $arguments[1], $arguments[2]),
                new ProductPageAssetsProvider($configuration, $arguments[1]),
            ];
        } elseif (null === $configuration) {
            $configuration = new GeneralConfiguration(new Configuration());
        }

        if (!$assetManager instanceof AssetManagerInterface) {
            throw new \InvalidArgumentException(sprintf('Expected $assetManager to be instance of "%s", "%s" given', AssetManagerInterface::class, get_debug_type($assetManager)));
        }

        if (!$authorizationChecker instanceof AuthorizationCheckerInterface) {
            throw new \InvalidArgumentException(sprintf('Expected $authorizationChecker to be instance of "%s", "%s" given', AuthorizationCheckerInterface::class, get_debug_type($authorizationChecker)));
        }

        if (!is_iterable($assetsProviders)) {
            throw new \InvalidArgumentException(sprintf('Expected $assetsProviders to be iterable, "%s" given', get_debug_type($assetsProviders)));
        }

        if (!$configuration instanceof GeneralConfigurationInterface) {
            throw new \InvalidArgumentException(sprintf('Expected $configuration to be instance of "%s", "%s" given', GeneralConfigurationInterface::class, get_debug_type($configuration)));
        }

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

        if (
            !$this->configuration->isFullPageCacheModuleInUse()
            && !$this->authorizationChecker->isGranted(BindingWidgetVoter::VIEW, $request)
        ) {
            return;
        }

        foreach ($this->assetsProviders as $assetsProvider) {
            $this->registerAssets($assetsProvider);
        }

        // $this->module->l('Something went wrong. Please try again later.', self::HOOK_NAME) kept for \AdminTranslationsController translatable message discovery
    }
}

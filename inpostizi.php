<?php

use izi\prestashop\CacheClearer\SymfonyCacheClearer;
use izi\prestashop\Common\Currency;
use izi\prestashop\Configuration\AdvancedConfigurationInterface;
use izi\prestashop\DependencyInjection\ContainerFactory;
use izi\prestashop\DependencyInjection\Exception\ContainerNotFoundException;
use izi\prestashop\Hook\Exception\HookNotFoundException;
use izi\prestashop\Hook\Exception\HookNotImplementedException;
use izi\prestashop\Hook\Exception\InvalidHookParamException;
use izi\prestashop\Hook\HookExecutorInterface;
use izi\prestashop\HttpFoundation\RequestStackProvider;
use izi\prestashop\Installer\Exception\CoreInstallationException;
use izi\prestashop\Installer\Exception\InstallerException;
use izi\prestashop\Installer\Installer;
use izi\prestashop\Installer\InstallerInterface;
use izi\prestashop\Installer\UninstallerInterface;
use izi\prestashop\Module\Exception\ModuleErrorInterface;
use izi\prestashop\Translation\Util\TranslationFinder;
use PrestaShop\PrestaShop\Adapter\ContainerBuilder as PrestaShopContainerBuilder;
use PrestaShop\PrestaShop\Adapter\SymfonyContainer;
use PrestaShop\PrestaShop\Core\Exception\ContainerNotFoundException as PrestaShopContainerNotFoundException;
use PrestaShop\PrestaShop\Core\Module\WidgetInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

if (!defined('_PS_VERSION_')) {
    exit;
}

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

class InPostIzi extends PaymentModule implements WidgetInterface
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var WidgetInterface
     */
    private $widget;

    public function __construct()
    {
        $this->name = 'inpostizi';
        $this->version = '3.1.0';
        $this->author = 'InPost S.A.';
        $this->tab = 'payments_gateways';

        $this->ps_versions_compliancy = [
            'min' => '1.7.6.0',
            'max' => '9.0.99',
        ];

        parent::__construct();

        try {
            $this->initTranslations();
        } catch (Exception $e) {
            // ignore silently
        }
        $this->displayName = $this->trans('InPost Pay', [], 'Modules.Inpostizi.Admin');
        $this->description = $this->trans('Official InPost Pay integration module for PrestaShop', [], 'Modules.Inpostizi.Admin');
        $this->confirmUninstall = $this->trans('Are you sure you want to uninstall this module?', [], 'Admin.Notifications.Warning');
    }

    /**
     * @return self
     */
    public static function getInstance(): Module
    {
        return self::getInstanceByName('inpostizi');
    }

    /**
     * @return true
     */
    public function isUsingNewTranslationSystem(): bool
    {
        return true;
    }

    public function install(): bool
    {
        if (70205 > PHP_VERSION_ID) {
            $this->_errors[] = $this->trans('This module requires PHP {min_version} or later.', ['{min_version}' => '7.2.5'], 'Modules.Inpostizi.Installer');

            return false;
        }

        try {
            $this->getInstaller()->install($this);

            return true;
        } catch (CoreInstallationException $e) {
            // the core `install()` method should have already added errors
            return false;
        } catch (InstallerException $e) {
            $this->_errors[] = $e->getMessage();

            return false;
        }
    }

    public function uninstall(?bool $keepData = null): bool
    {
        try {
            $this->getUninstaller()->uninstall($this, $keepData ?? $this->isResetAction());

            return true;
        } catch (CoreInstallationException $e) {
            // the core `uninstall()` method should have already added errors
            return false;
        } catch (InstallerException $e) {
            $this->_errors[] = $e->getMessage();

            return false;
        }
    }

    public function runUpgradeModule(): array
    {
        try {
            $result = parent::runUpgradeModule();

            if (!$result['available_upgrade']) {
                return $result;
            }

            if ($result['success']) {
                $this->getLogger()->info('Upgraded module to version {version}.', [
                    'version' => $result['upgraded_to'],
                ]);
            } else {
                $this->getLogger()->error('Could not upgrade module.', [
                    'details' => $result,
                ]);
            }

            return $result;
        } catch (Throwable $e) {
            $this->getLogger()->critical('An error occurred while upgrading module.', [
                'exception' => $e,
            ]);

            throw $e;
        }
    }

    /**
     * @param int[] $shops shop IDs
     */
    public function addCheckboxCurrencyRestrictionsForModule(array $shops = []): bool
    {
        if ([] === $shops) {
            $shops = Shop::getShops(true, null, true);
        }

        $data = [];

        foreach ($shops as $shopId) {
            foreach (Currency::cases() as $currency) {
                if (0 >= $currencyId = (int) \Currency::getIdByIsoCode($currency->value, $shopId)) {
                    continue;
                }

                $data[] = [
                    'id_module' => (int) $this->id,
                    'id_shop' => (int) $shopId,
                    'id_currency' => $currencyId,
                ];
            }
        }

        return Db::getInstance()->insert('module_currency', $data);
    }

    public function getContent(): void
    {
        if ($this->isContainerCacheStale() && $this->isContainerConfigLoaded()) {
            $this->clearCacheAndRedirectBackToConfigPage();
        }

        /** @var UrlGeneratorInterface $router */
        $router = $this->get('router');

        try {
            Tools::redirectAdmin($router->generate('admin_inpost_izi_config_general'));
        } catch (RouteNotFoundException $e) {
            if ($this->isContainerConfigLoaded()) {
                throw $e;
            }

            $this->addFlash($this->trans('To access the configuration page, the module must be active.', [], 'Modules.Inpostizi.Admin'));
            Tools::redirectAdmin($router->generate('admin_module_manage'));
        }
    }

    /**
     * Handles hook calls.
     *
     * @return mixed hook result
     */
    public function __call(string $methodName, array $arguments)
    {
        $hookName = str_starts_with($methodName, 'hook')
            ? lcfirst(Tools::substr($methodName, 4))
            : $methodName;

        try {
            $parameters = $this->normalizeHookParameters($arguments[0] ?? []);

            return $this->get(HookExecutorInterface::class)->execute($hookName, $parameters);
        } catch (ModuleErrorInterface $e) {
            throw $e;
        } catch (InvalidHookParamException $e) {
            $this->getLogger()->debug('Invalid params passed to hook "{hookName}".', [
                'hookName' => $hookName,
                'exception' => $e,
            ]);

            if (_PS_MODE_DEV_) {
                throw $e;
            }

            return null;
        } catch (HookNotImplementedException $e) {
            $this->getLogger()->warning('Hook "{hookName}" is not implemented.', [
                'hookName' => $hookName,
            ]);

            return null;
        } catch (Throwable $e) {
            if ($this->isContainerCacheRelated($e)) {
                $this->getLogger()->error('Error executing hook "{hookName}": container cache is stale.', [
                    'hookName' => $hookName,
                ]);

                return null;
            }

            $this->getLogger()->critical('Error executing hook "{hookName}".', [
                'hookName' => $hookName,
                'exception' => $e,
            ]);

            if (_PS_MODE_DEV_ && $this->isDebugEnabled()) {
                throw $e;
            }

            return null;
        }
    }

    /**
     * @template T
     *
     * @param string|class-string<T> $serviceName
     *
     * @return T|object
     *
     * @phpstan-return ($serviceName is class-string<T> ? T : object)
     */
    public function get($serviceName): object
    {
        return $this->getContainer()->get($serviceName);
    }

    public function getContainer(): ContainerInterface
    {
        if (Tools::version_compare(_PS_VERSION_, '1.7.7')) {
            return $this->getPS176Container();
        }

        if (null !== $container = SymfonyContainer::getInstance()) {
            return $container;
        }

        try {
            return parent::getContainer();
        } catch (PrestaShopContainerNotFoundException $e) {
            return $this->getFrontOfficeLegacyContainer();
        }
    }

    /**
     * @param string|null $hookName
     */
    public function renderWidget($hookName, array $configuration): string
    {
        $configuration = $this->normalizeHookParameters($configuration);

        return $this->getWidget()->renderWidget($hookName, $configuration);
    }

    /**
     * @param string|null $hookName
     * @param array<string, mixed> $configuration
     *
     * @return array<string, mixed>
     */
    public function getWidgetVariables($hookName, array $configuration): array
    {
        $configuration = $this->normalizeHookParameters($configuration);

        return $this->getWidget()->getWidgetVariables($hookName, $configuration);
    }

    /**
     * @internal
     */
    public function getCurrentRequest(): ?Request
    {
        return $this->getRequestStack()->getCurrentRequest();
    }

    /**
     * @internal
     */
    public function getRequestStack(): RequestStack
    {
        return $this->requestStack
            ?? $this->requestStack = (new RequestStackProvider($this->getContainer()))->getRequestStack();
    }

    /**
     * @internal
     */
    public function getLogger(): LoggerInterface
    {
        try {
            return $this->get('inpost.izi.logger');
        } catch (Exception $e) {
            return new Psr\Log\NullLogger();
        }
    }

    private function getInstaller(): InstallerInterface
    {
        try {
            return $this->get(InstallerInterface::class);
        } catch (ServiceNotFoundException $e) {
            return $this->createInstaller();
        }
    }

    private function getUninstaller(): UninstallerInterface
    {
        try {
            return $this->get(UninstallerInterface::class);
        } catch (ServiceNotFoundException $e) {
            return $this->createInstaller();
        }
    }

    /**
     * @return (InstallerInterface&UninstallerInterface)
     */
    private function createInstaller(): object
    {
        /** @var Container|null $container */
        $container = SymfonyContainer::getInstance();

        if (null === $container) {
            throw new RuntimeException('Cannot create installer: kernel container is not available.');
        }

        $installer = (new ContainerFactory($container))
            ->buildContainer([
                $this->getLocalPath() . 'config/services/installer.yml',
            ], Installer::REQUIRED_CONTAINER_PARAMS)
            ->get(Installer::class);

        $container->set(InstallerInterface::class, $installer);
        $container->set(UninstallerInterface::class, $installer);

        return $installer;
    }

    private function isResetAction(): bool
    {
        if (null !== $request = $this->getCurrentRequest()) {
            return 'admin_module_manage_action' === $request->attributes->get('_route') && 'reset' === $request->attributes->get('action');
        }

        if ('cli' !== PHP_SAPI) {
            return false;
        }

        $input = new ArgvInput();

        return 'prestashop:module' === $input->getFirstArgument() && 'reset' === $input->getArgument('action');
    }

    private function isDebugEnabled(): bool
    {
        try {
            return $this->get(AdvancedConfigurationInterface::class)->isDebugEnabled();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * {@see Module::get()} does not check if {@see Controller::$container} is set on PS 1.7.6.
     */
    private function getPS176Container(): ContainerInterface
    {
        if (isset($this->context->container)) {
            return $this->context->container;
        }

        if (null !== $container = SymfonyContainer::getInstance()) {
            return $container;
        }

        if ($this->context->controller instanceof Controller && null !== $container = $this->context->controller->getContainer()) {
            return $container;
        }

        return $this->getFrontOfficeLegacyContainer();
    }

    /**
     * Access container before {@see FrontController::$container} is set in {@see Controller::init()}.
     */
    private function getFrontOfficeLegacyContainer(): ContainerInterface
    {
        if (!class_exists(PrestaShopContainerBuilder::class)) {
            throw ContainerNotFoundException::create();
        }

        try {
            return PrestaShopContainerBuilder::getContainer('front', _PS_MODE_DEV_);
        } catch (Exception $e) {
            throw ContainerNotFoundException::create($e);
        }
    }

    /**
     * @param array<string, mixed> $parameters
     *
     * @return array<string, mixed>
     */
    private function normalizeHookParameters(array $parameters): array
    {
        if (!isset($parameters['request'])) {
            $parameters['request'] = $this->getCurrentRequest();
        }

        if (!isset($parameters['cart'])) {
            $parameters['cart'] = $this->context->cart;
        }

        return $parameters;
    }

    private function getWidget(): WidgetInterface
    {
        return $this->widget ?? $this->widget = $this->get('inpost.izi.widget');
    }

    private function isContainerCacheRelated(Throwable $e): bool
    {
        if (!$e instanceof HookNotFoundException && !$e instanceof ServiceNotFoundException && !$e instanceof TypeError) {
            return false;
        }

        return $this->isContainerCacheStale();
    }

    private function isContainerCacheStale(): bool
    {
        try {
            $container = $this->getContainer();
        } catch (ContainerNotFoundException $e) {
            return false; // fingers crossed
        }

        if (!$container->hasParameter('inpost.izi.container_version')) {
            return true;
        }

        return $this->version !== $container->getParameter('inpost.izi.container_version');
    }

    /**
     * @return bool whether PS automatically loads the module's container configuration
     */
    private function isContainerConfigLoaded(): bool
    {
        if ($this->active) {
            return true;
        }

        if (Tools::version_compare(_PS_VERSION_, '8.0.0')) {
            return true;
        }

        return $this->hasShopAssociations();
    }

    private function clearCacheAndRedirectBackToConfigPage(): void
    {
        if ($this->getCurrentRequest()->query->getBoolean('cache_cleared')) {
            $this->addFlash($this->trans('An attempt to clear the Symfony container cache may have failed. If you continue to experience problems, try clearing the cache manually.', [], 'Modules.Inpostizi.Admin'));

            return;
        }

        $this->getLogger()->warning('Container cache is stale, attempting to clear.');
        SymfonyCacheClearer::getInstance()->clear();

        Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules', true, [], [
            'configure' => $this->name,
            'cache_cleared' => true,
        ]));
    }

    private function addFlash(string $message, string $type = 'error'): void
    {
        $request = $this->getCurrentRequest();

        if (null === $request || !$request->hasSession()) {
            return;
        }

        /** @var Session $session */
        $session = $request->getSession();
        $session->getFlashBag()->add($type, $message);
    }

    /**
     * Loads translations if the module is not installed.
     */
    private function initTranslations(): void
    {
        if (null !== $this->id) {
            return;
        }

        $translator = $this->getTranslator();
        $catalogue = (new TranslationFinder())->getCatalogue($this->getLocalPath() . 'translations', $translator->getLocale());

        if (null === $catalogue) {
            return;
        }

        $translator->getCatalogue()->addCatalogue($catalogue);
    }
}

<?php

use izi\prestashop\AdminKernel;
use izi\prestashop\Common\Currency;
use izi\prestashop\Configuration\AdvancedConfigurationInterface;
use izi\prestashop\DependencyInjection\Compiler\AnalyzeServiceReferencesPass;
use izi\prestashop\DependencyInjection\Compiler\ProvideServiceLocatorFactoriesPass;
use izi\prestashop\DependencyInjection\Compiler\TaggedIteratorsCollectorPass;
use izi\prestashop\DependencyInjection\ContainerFactory;
use izi\prestashop\DependencyInjection\Exception\ContainerNotFoundException;
use izi\prestashop\Hook\Exception\HookNotFoundException;
use izi\prestashop\Hook\Exception\HookNotImplementedException;
use izi\prestashop\Hook\HookExecutor;
use izi\prestashop\Hook\HookExecutorInterface;
use izi\prestashop\Installer\DatabaseInstaller;
use izi\prestashop\Module\Exception\ModuleErrorInterface;
use PrestaShop\PrestaShop\Adapter\ContainerBuilder as PrestaShopContainerBuilder;
use PrestaShop\PrestaShop\Adapter\SymfonyContainer;
use PrestaShop\PrestaShop\Core\Exception\ContainerNotFoundException as PrestaShopContainerNotFoundException;
use PrestaShop\PrestaShop\Core\Module\WidgetInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\EventDispatcher\DependencyInjection\RegisterListenersPass;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;
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
    private static $loggerServiceId = 'inpost.izi.general_logger';

    /**
     * @var ContainerInterface|null
     */
    private $legacyContainer;

    /**
     * @var KernelInterface|null
     */
    private $adminKernel;

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
        $this->version = '2.2.0';
        $this->author = 'InPost S.A.';
        $this->tab = 'payments_gateways';

        $this->ps_versions_compliancy = [
            'min' => '1.7.1.0',
            'max' => '8.2.99',
        ];

        parent::__construct();

        $this->displayName = $this->l('InPost Pay');
    }

    /**
     * @return false
     */
    public function isUsingNewTranslationSystem()
    {
        return false;
    }

    /**
     * @return bool
     */
    public function install()
    {
        if (70103 > PHP_VERSION_ID) {
            $this->_errors[] = $this->l('This module requires PHP 7.1.3 or later.');

            return false;
        }

        try {
            (new DatabaseInstaller())->install($this);
        } catch (Exception $e) {
            $this->_errors[] = $this->l('Could not update the database schema.');
            $this->getLogger()->critical('Installer error: {exception}.', [
                'exception' => $e,
            ]);

            return false;
        }

        $this->setUpRoutingLoaderResolver();

        return parent::install()
            && $this->registerHook(HookExecutor::getHooksToInstall(_PS_VERSION_));
    }

    /**
     * @return bool
     */
    public function uninstall()
    {
        $this->setUpRoutingLoaderResolver();

        return parent::uninstall();
    }

    /**
     * @return array
     */
    public function runUpgradeModule()
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
            $this->getLogger()->critical('Upgrade error: {exception}.', [
                'exception' => $e,
            ]);

            throw $e;
        }
    }

    /**
     * @param int[] $shops shop IDs
     *
     * @return bool
     */
    public function addCheckboxCurrencyRestrictionsForModule(array $shops = [])
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

    public function getContent()
    {
        if ($this->isContainerCacheStale() && $this->isContainerConfigLoaded()) {
            $this->clearCacheAndRedirectBackToConfigPage();
        }

        try {
            /** @var UrlGeneratorInterface $router */
            $router = $this->get('router');

            Tools::redirectAdmin($router->generate('admin_inpost_izi_config_general'));
        } catch (ServiceNotFoundException $e) {
            $this->handleConfigPageRequest();
        } catch (RouteNotFoundException $e) {
            if ($this->isContainerConfigLoaded()) {
                throw $e;
            }

            $this->addFlash($this->l('To access the configuration page, the module must be active.'));
            Tools::redirectAdmin($router->generate('admin_module_manage'));
        }
    }

    /**
     * Handles hook calls.
     *
     * @param string $methodName
     *
     * @return mixed hook result
     */
    public function __call($methodName, array $arguments)
    {
        $hookName = 0 === strpos($methodName, 'hook')
            ? lcfirst(Tools::substr($methodName, 4))
            : $methodName;

        try {
            $parameters = $this->normalizeHookParameters(isset($arguments[0]) ? $arguments[0] : []);

            return $this
                ->get(HookExecutorInterface::class)
                ->execute($hookName, $parameters);
        } catch (ModuleErrorInterface $e) {
            throw $e;
        } catch (HookNotImplementedException $e) {
            $this->getLogger()->warning('Hook "{hookName}" is not implemented.', [
                'hookName' => $e->getHookName(),
            ]);

            return null;
        } catch (Throwable $e) {
            if ($this->isContainerCacheRelated($e)) {
                $this->getLogger()->error('Error executing hook "{hookName}": container cache is stale.', [
                    'hookName' => $hookName,
                ]);

                return null;
            }

            $this->getLogger()->critical('Error executing hook "{hookName}": {exception}', [
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
     * @phpstan-return ($serviceName is class-string<T> ? T : object)
     *
     * @return T|object
     */
    public function get($serviceName)
    {
        return $this->doGetContainer()->get($serviceName);
    }

    /**
     * @param string|null $hookName
     *
     * @return string
     */
    public function renderWidget($hookName, array $configuration)
    {
        $configuration = $this->normalizeHookParameters($configuration);

        return $this->getWidget()->renderWidget($hookName, $configuration);
    }

    /**
     * @param string|null $hookName
     *
     * @return array
     */
    public function getWidgetVariables($hookName, array $configuration)
    {
        $configuration = $this->normalizeHookParameters($configuration);

        return $this->getWidget()->getWidgetVariables($hookName, $configuration);
    }

    /**
     * @return Request
     *
     * @internal
     */
    public function getCurrentRequest()
    {
        return $this->getRequestStack()->getCurrentRequest() ?: Request::createFromGlobals();
    }

    /**
     * @return RequestStack
     *
     * @internal
     */
    public function getRequestStack()
    {
        if (isset($this->requestStack)) {
            return $this->requestStack;
        }

        try {
            /** @var RequestStack $requestStack */
            $requestStack = $this->get('request_stack');
            if (null !== $requestStack->getCurrentRequest()) {
                return $this->requestStack = $requestStack;
            }
        } catch (ServiceNotFoundException $e) {
        }

        $this->requestStack = new RequestStack();
        $this->requestStack->push(Request::createFromGlobals());

        return $this->requestStack;
    }

    /**
     * @return LoggerInterface
     *
     * @internal
     */
    public function getLogger()
    {
        try {
            return $this->get(self::$loggerServiceId);
        } catch (ContainerNotFoundException $e) {
            return $this->getLegacyContainer()->get(self::$loggerServiceId);
        } catch (Exception $e) {
            return new Psr\Log\NullLogger();
        }
    }

    /**
     * @return ContainerInterface
     */
    private function doGetContainer()
    {
        if (Tools::version_compare(_PS_VERSION_, '1.7.6')) {
            return $this->getLegacyContainer();
        }

        if (Tools::version_compare(_PS_VERSION_, '1.7.7')) {
            return $this->getPS176Container();
        }

        if (null !== $container = SymfonyContainer::getInstance()) {
            return $container;
        }

        try {
            return $this->getContainer();
        } catch (PrestaShopContainerNotFoundException $e) {
            return $this->getFrontOfficeLegacyContainer();
        }
    }

    /**
     * @return ContainerInterface
     */
    private function getLegacyContainer()
    {
        if (!isset($this->legacyContainer)) {
            $this->legacyContainer = $this->createContainer();
        }

        return $this->legacyContainer;
    }

    /**
     * @return ContainerInterface
     */
    private function createContainer()
    {
        $cacheDir = sprintf('%s/inpost/izi/', rtrim(_PS_CACHE_DIR_, '/'));

        if (Tools::version_compare(_PS_VERSION_, '1.7.4')) {
            $className = sprintf('InPost\\Izi\\Container_%s', str_replace('.', '_', $this->version));
            $resources = $this->getSf28ConfigResources();
        } else {
            $type = $this->context->controller instanceof AdminControllerCore ? 'admin' : 'front';
            $className = sprintf('InPost\\Izi\\%sContainer_%s', ucfirst($type), str_replace('.', '_', $this->version));
            $resources = [sprintf('%s/config/%s/services.yml', rtrim($this->getLocalPath(), '/'), $type)];
        }

        return (new ContainerFactory($cacheDir))->create($className, $resources);
    }

    /**
     * @return iterable
     */
    private function getSf28ConfigResources()
    {
        yield sprintf('%s/config/services/sf28.yml', rtrim($this->getLocalPath(), '/'));
        yield static function (ContainerBuilder $container) {
            $container->addResource(new FileResource(__FILE__));
            $container->addCompilerPass(new RegisterListenersPass('inpost.izi.event_dispatcher'), PassConfig::TYPE_BEFORE_REMOVING);
            $container->addCompilerPass(new ProvideServiceLocatorFactoriesPass('inpost.izi.service_locator'));
            $container->addCompilerPass(new TaggedIteratorsCollectorPass());
            AnalyzeServiceReferencesPass::decorateRemovingPasses($container, 'inpost.izi.service_locator');
        };
    }

    /**
     * Accesses the public "routing.loader" service to provide a @see \Symfony\Component\Config\Loader\LoaderResolverInterface
     * to the routing configuration loader used by @see \PrestaShop\PrestaShop\Adapter\Module\Tab\ModuleTabRegister
     */
    private function setUpRoutingLoaderResolver()
    {
        if (Tools::version_compare(_PS_VERSION_, '1.7.7')) {
            return;
        }

        try {
            $this->get('routing.loader');
        } catch (Exception $e) {
            // ignore silently
        }
    }

    private function handleConfigPageRequest()
    {
        $request = $this->getCurrentRequest();
        $request->query->remove('controllerUri');

        $kernel = $this->getAdminKernel();
        $response = $kernel->handle($request);

        $this->context->cookie->write();
        $response->send();

        if ($kernel instanceof TerminableInterface) {
            $kernel->terminate($request, $response);
        }

        exit;
    }

    /**
     * @return KernelInterface
     */
    private function getAdminKernel()
    {
        if (isset($this->adminKernel)) {
            return $this->adminKernel;
        }

        global $kernel;

        if (!$kernel instanceof KernelInterface) {
            throw new RuntimeException('PS application kernel instance was not found.');
        }

        $this->adminKernel = new AdminKernel($kernel, _PS_VERSION_);
        $this->adminKernel->boot();

        $psContainer = $kernel->getContainer();
        $this->adminKernel->getContainer()->set('prestashop.security.admin.provider', $psContainer->get('prestashop.security.admin.provider'));

        // In some very early 1.7 versions, the session may not have yet been started by PS application.
        $psContainer->get('session')->start();

        return $this->adminKernel;
    }

    /**
     * @return bool
     */
    private function isDebugEnabled()
    {
        try {
            return $this->get(AdvancedConfigurationInterface::class)->isDebugEnabled();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * {@see \Module::get()} does not check if {@see \Controller::$container} is set on PS 1.7.6.
     *
     * @return ContainerInterface
     */
    private function getPS176Container()
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
     * Access container before {@see \FrontController::$container} is set in {@see \Controller::init()}.
     *
     * @return ContainerInterface
     */
    private function getFrontOfficeLegacyContainer()
    {
        if (!$this->context->controller instanceof FrontController || !class_exists(PrestaShopContainerBuilder::class)) {
            throw ContainerNotFoundException::create();
        }

        try {
            return PrestaShopContainerBuilder::getContainer('front', _PS_MODE_DEV_);
        } catch (Exception $e) {
            throw ContainerNotFoundException::create($e);
        }
    }

    /**
     * @return array
     */
    private function normalizeHookParameters(array $parameters)
    {
        if (!isset($parameters['request'])) {
            $parameters['request'] = $this->getCurrentRequest();
        }

        if (!isset($parameters['cart'])) {
            $parameters['cart'] = $this->context->cart;
        }

        return $parameters;
    }

    /**
     * @return WidgetInterface
     */
    private function getWidget()
    {
        if (isset($this->widget)) {
            return $this->widget;
        }

        return $this->widget = $this->get('inpost.izi.widget');
    }

    /**
     * @return bool
     */
    private function isContainerCacheRelated(Throwable $e)
    {
        if (!$e instanceof HookNotFoundException && !$e instanceof ServiceNotFoundException && !$e instanceof TypeError) {
            return false;
        }

        return $this->isContainerCacheStale();
    }

    /**
     * @return bool
     */
    private function isContainerCacheStale()
    {
        try {
            $container = $this->doGetContainer();
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
    private function isContainerConfigLoaded()
    {
        if ($this->active) {
            return true;
        }

        if (Tools::version_compare(_PS_VERSION_, '8.0.0')) {
            return true;
        }

        return $this->hasShopAssociations();
    }

    private function clearCacheAndRedirectBackToConfigPage()
    {
        if (Tools::getValue('cache_cleared')) {
            $this->addFlash($this->l('An attempt to clear the cache may have failed. Please try to clear the cache manually.'));

            return;
        }

        $this->getLogger()->warning('Container cache is stale, attempting to clear.');

        Tools::clearSf2Cache();
        Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules', true, null, [
            'configure' => $this->name,
            'cache_cleared' => true,
        ]));
    }

    /**
     * @param string $message
     * @param string $type
     */
    private function addFlash($message, $type = 'error')
    {
        try {
            /** @var Session $session */
            $session = $this->get('session');
            $session->getFlashBag()->add($type, $message);
        } catch (ServiceNotFoundException $e) {
            // ignore silently
        }
    }
}

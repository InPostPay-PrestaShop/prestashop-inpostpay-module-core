<?php

use izi\prestashop\AdminKernel;
use izi\prestashop\Common\Currency;
use izi\prestashop\DependencyInjection\Compiler\AnalyzeServiceReferencesPass;
use izi\prestashop\DependencyInjection\Compiler\ProvideServiceLocatorFactoriesPass;
use izi\prestashop\DependencyInjection\Compiler\TaggedIteratorsCollectorPass;
use izi\prestashop\DependencyInjection\ContainerFactory;
use izi\prestashop\DependencyInjection\Exception\ContainerNotFoundException;
use izi\prestashop\Handler\UpdateOrderTrackingNumbersHandler;
use izi\prestashop\Hook\HookExecutor;
use izi\prestashop\Hook\HookExecutorInterface;
use izi\prestashop\Hook\WidgetConfigurationResolver;
use izi\prestashop\Hook\WidgetRenderer;
use izi\prestashop\Installer\DatabaseInstaller;
use PrestaShop\PrestaShop\Adapter\ContainerBuilder as PrestaShopContainerBuilder;
use PrestaShop\PrestaShop\Adapter\SymfonyContainer;
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
use Symfony\Component\HttpKernel\KernelInterface;
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

    public function __construct()
    {
        $this->name = 'inpostizi';
        $this->version = '1.6.0';
        $this->author = 'InPost S.A.';
        $this->tab = 'payments_gateways';

        $this->ps_versions_compliancy = [
            'min' => '1.7.0.0',
            'max' => '8.1.99',
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

        $dbInstaller = new DatabaseInstaller();

        if (!$dbInstaller->install($this)) {
            $this->_errors[] = $this->l('Could not update the database schema.');

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
     * @param int[] $shops shop IDs
     *
     * @return bool
     */
    public function addCheckboxCurrencyRestrictionsForModule(array $shops = [])
    {
        if ([] === $shops) {
            $shops = \Shop::getShops(true, null, true);
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

        return \Db::getInstance()->insert('module_currency', $data);
    }

    public function getContent()
    {
        try {
            /** @var UrlGeneratorInterface $router */
            $router = $this->get('router');

            \Tools::redirectAdmin($router->generate('admin_inpost_izi_config_general'));
        } catch (ServiceNotFoundException $e) {
            $this->handleConfigPageRequest();
        }
    }

    /**
     * @param string $methodName
     */
    public function __call($methodName, array $arguments)
    {
        $hookName = 0 === strpos($methodName, 'hook')
            ? lcfirst(\Tools::substr($methodName, 4))
            : $methodName;

        try {
            $parameters = isset($arguments[0]) ? $arguments[0] : [];
            if (!isset($parameters['request'])) {
                $parameters['request'] = $this->getCurrentRequest();
            }

            return $this
                ->get(HookExecutorInterface::class)
                ->execute($hookName, $parameters);
        } catch (\Throwable $e) {
            $this->getLogger()->critical('Error executing hook "{hookName}": {error}', [
                'hookName' => $hookName,
                'error' => $e,
            ]);

            if (!defined('_PS_MODE_DEV_') || _PS_MODE_DEV_) {
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
     */
    public function get($serviceName)
    {
        if (\Tools::version_compare(_PS_VERSION_, '1.7.6')) {
            return $this->getLegacyContainer()->get($serviceName);
        }

        try {
            $service = parent::get($serviceName);
        } catch (ServiceNotFoundException $exception) {
            if ($this->isSymfonyContext() || null === $container = SymfonyContainer::getInstance()) {
                throw $exception;
            }

            return $container->get($serviceName);
        }

        if (false !== $service) {
            return $service;
        }

        if (!$this->context->controller instanceof \FrontController || !class_exists(PrestaShopContainerBuilder::class)) {
            throw ContainerNotFoundException::create();
        }

        try {
            $container = PrestaShopContainerBuilder::getContainer('front', _PS_MODE_DEV_);
        } catch (\Exception $e) {
            throw ContainerNotFoundException::create($e);
        }

        return $container->get($serviceName);
    }

    /**
     * @param string|null $hookName
     *
     * @return string
     */
    public function renderWidget($hookName, array $configuration)
    {
        if (isset($configuration['request'])) {
            $request = $configuration['request'];
        } else {
            $request = $this->getCurrentRequest();
        }

        if ([] === $parameters = $this->getWidgetVariables($hookName, $configuration)) {
            return '';
        }

        return $this
            ->get(WidgetRenderer::class)
            ->render($parameters['attributes'], $request);
    }

    /**
     * @param string $hookName
     *
     * @return array
     */
    public function getWidgetVariables($hookName, array $configuration)
    {
        $config = $this
            ->get(WidgetConfigurationResolver::class)
            ->resolve($configuration);

        if (null === $config) {
            return [];
        }

        return ['attributes' => $config];
    }

    /**
     * @return Request
     *
     * @interal
     */
    public function getCurrentRequest()
    {
        try {
            /** @var RequestStack $requestStack */
            $requestStack = $this->get('request_stack');
            $request = $requestStack->getCurrentRequest();
        } catch (ServiceNotFoundException $e) {
            $request = null;
        }

        return null !== $request ? $request : $this->createRequestFromGlobals();
    }

    /**
     * @return LoggerInterface
     *
     * @interal
     */
    public function getLogger()
    {
        try {
            return $this->get(self::$loggerServiceId);
        } catch (ContainerNotFoundException $e) {
            return $this->getLegacyContainer()->get(self::$loggerServiceId);
        }
    }

    /**
     * @return Request
     */
    private function createRequestFromGlobals()
    {
        static $request;

        if (!isset($request)) {
            $request = Request::createFromGlobals();
        }

        return $request;
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

        if (\Tools::version_compare(_PS_VERSION_, '1.7.4')) {
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
     * @return array
     */
    private function getSf28ConfigResources()
    {
        $configurator = static function (ContainerBuilder $container) {
            $container->addResource(new FileResource(__FILE__));
            $container->addCompilerPass(new RegisterListenersPass('inpost.izi.event_dispatcher'), PassConfig::TYPE_BEFORE_REMOVING);
            $container->addCompilerPass(new ProvideServiceLocatorFactoriesPass('inpost.izi.service_locator'));
            $container->addCompilerPass(new TaggedIteratorsCollectorPass(UpdateOrderTrackingNumbersHandler::class));
            AnalyzeServiceReferencesPass::decorateRemovingPasses($container, 'inpost.izi.service_locator');
        };

        return [
            sprintf('%s/config/services/sf28.yml', rtrim($this->getLocalPath(), '/')),
            $configurator,
        ];
    }

    /**
     * Accesses the public "routing.loader" service in order to provide a @see \Symfony\Component\Config\Loader\LoaderResolverInterface
     * to the routing configuration loader used by @see \PrestaShop\PrestaShop\Adapter\Module\Tab\ModuleTabRegister
     */
    private function setUpRoutingLoaderResolver()
    {
        if (\Tools::version_compare(_PS_VERSION_, '1.7.7')) {
            return;
        }

        try {
            $this->get('routing.loader');
        } catch (\Exception $e) {
            // ignore silently
        }
    }

    private function handleConfigPageRequest()
    {
        $request = $this->getCurrentRequest();
        $request->query->remove('controllerUri');

        $response = $this->getAdminKernel()->handle($request);

        $this->context->cookie->write();
        $response->send();

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
            throw new \RuntimeException('PS application kernel instance was not found.');
        }

        // In case of some very early 1.7 versions, session may not have already been started by PS application.
        $kernel->getContainer()->get('session')->start();

        $this->adminKernel = new AdminKernel($kernel, _PS_VERSION_);
        $this->adminKernel->boot();

        return $this->adminKernel;
    }
}

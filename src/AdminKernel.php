<?php

declare(strict_types=1);

namespace izi\prestashop;

use izi\prestashop\DependencyInjection\Compiler\AnalyzeServiceReferencesPass;
use izi\prestashop\DependencyInjection\Compiler\FilterResourcesPass;
use izi\prestashop\DependencyInjection\Compiler\ProvideServiceLocatorFactoriesPass;
use izi\prestashop\DependencyInjection\Compiler\TaggedIteratorsCollectorPass;
use izi\prestashop\DependencyInjection\Dumper\PhpDumper;
use PrestaShopBundle\PrestaShopBundle;
use Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollectionBuilder;

/**
 * @internal
 */
final class AdminKernel extends Kernel
{
    use MicroKernelTrait;

    public const SYNTHETIC_SERVICE_IDS = [
        'prestashop.security.admin.provider',
        'prestashop.module.manager',
    ];

    private $logDir;
    private $cacheDir;
    private $secret;
    private $psVersion;

    private $prestaShopBundle;

    public function __construct(KernelInterface $kernel, string $psVersion)
    {
        $this->rootDir = $kernel->getRootDir();
        $this->logDir = $kernel->getLogDir() . '/inpost/izi';
        $this->cacheDir = $kernel->getCacheDir() . '/inpost/izi';
        $this->secret = $kernel->getContainer()->getParameter('kernel.secret');

        $this->psVersion = $psVersion;
        $this->name = 'inpostizi';

        parent::__construct($kernel->getEnvironment(), $kernel->isDebug());
    }

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new TwigBundle();
        yield new SecurityBundle();

        if (class_exists(SensioFrameworkExtraBundle::class)) {
            yield new SensioFrameworkExtraBundle();
        }
    }

    /**
     * @override for the purpose of locating Twig templates included by PS using legacy bundle path syntax.
     */
    public function getBundle($name, $first = true)
    {
        if ('PrestaShopBundle' === $name) {
            $bundle = $this->getPrestaShopBundle();

            return $first ? $bundle : [$bundle];
        }

        return parent::getBundle(...func_get_args());
    }

    public function getLogDir(): string
    {
        return $this->logDir;
    }

    public function getCacheDir(): string
    {
        return $this->cacheDir;
    }

    protected function configureRoutes(RouteCollectionBuilder $routes): void
    {
        $configDir = $this->getConfigDir();

        $routes->import(sprintf('%s/routes.yml', $configDir));
        $routes->addRoute($this->getConfigIndexRoute(), 'admin_inpost_izi_config_general');
    }

    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        $configDir = $this->getConfigDir();

        $loader->load(sprintf('%s/services.yml', $configDir));
        $loader->load(__DIR__ . '/Resources/config/admin.yml');

        $container->loadFromExtension('framework', [
            'secret' => $this->secret,
        ]);

        if (\Tools::version_compare($this->psVersion, '1.7.4', '>=')) {
            $loader->load(sprintf('%s/services/sf34.yml', $configDir));
        } else {
            $loader->load(sprintf('%s/services/sf28.yml', $configDir));
            $loader->load(__DIR__ . '/Resources/config/admin28.yml');

            $container->addCompilerPass(new ProvideServiceLocatorFactoriesPass('inpost.izi.service_locator'));
            AnalyzeServiceReferencesPass::decorateRemovingPasses($container, 'inpost.izi.service_locator');
            $container->addCompilerPass(new TaggedIteratorsCollectorPass());
        }

        $container->addCompilerPass(new FilterResourcesPass(), PassConfig::TYPE_BEFORE_REMOVING);
    }

    protected function dumpContainer(ConfigCache $cache, ContainerBuilder $container, $class, $baseClass): void
    {
        if (\Tools::version_compare($this->psVersion, '1.7.4', '>=')) {
            parent::dumpContainer(...func_get_args());

            return;
        }

        $dumper = new PhpDumper($container);

        $content = $dumper->dump([
            'class' => $class,
            'base_class' => $baseClass,
            'file' => $cache->getPath(),
            'debug' => $this->debug,
        ]);

        $cache->write($content, $container->getResources());
    }

    private function getPrestaShopBundle(): BundleInterface
    {
        return $this->prestaShopBundle ?? ($this->prestaShopBundle = new PrestaShopBundle());
    }

    private function getConfigIndexRoute(): Route
    {
        return (new Route('/'))
            ->setMethods(['GET', 'POST'])
            ->setDefault('_controller', 'izi\prestashop\Controller\Admin\ConfigurationController:generalConfig');
    }

    private function getConfigDir(): string
    {
        return __DIR__ . '/../config';
    }
}

<?php

declare(strict_types=1);

namespace izi\prestashop\Installer;

use izi\prestashop\CacheClearer\SymfonyCacheClearer;
use PrestaShop\PrestaShop\Adapter\SymfonyContainer;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @internal
 */
final class RuntimeConfigAdjustingInstaller implements InstallerInterface, UninstallerInterface
{
    /**
     * @var InstallerInterface|UninstallerInterface
     */
    private $installer;

    /**
     * @var string
     */
    private $psVersion;

    /**
     * @var bool
     */
    private $configModified = false;

    public function __construct(InstallerInterface $installer, string $psVersion = _PS_VERSION_)
    {
        $this->installer = $installer;
        $this->psVersion = $psVersion;
    }

    public function install(\Module $module): void
    {
        $this->modifyConfig($module);
        $this->installer->install($module);
    }

    public function uninstall(\Module $module, bool $keepData = false): void
    {
        if (!$this->installer instanceof UninstallerInterface) {
            return;
        }

        $this->modifyConfig($module);
        $this->installer->uninstall($module, $keepData);
    }

    private function modifyConfig(\Module $module): void
    {
        if ($this->configModified) {
            return;
        }

        $container = \is_callable([$module, 'getContainer']) ? $module->getContainer() : SymfonyContainer::getInstance();

        $this->setUpRoutingLoaderResolver($container);
        $this->resetCircularReferencesBeforeCacheClear($container);

        $this->configModified = true;
    }

    /**
     * Accesses the public "routing.loader" service to provide a @see \Symfony\Component\Config\Loader\LoaderResolverInterface
     * to the routing configuration loader used by @see \PrestaShop\PrestaShop\Adapter\Module\Tab\ModuleTabRegister
     */
    private function setUpRoutingLoaderResolver(ContainerInterface $container): void
    {
        if (\Tools::version_compare($this->psVersion, '1.7.7')) {
            return;
        }

        try {
            $container->get('routing.loader');
        } catch (\Exception $e) {
            // ignore silently
        }
    }

    private function resetCircularReferencesBeforeCacheClear(ContainerInterface $container): void
    {
        if (\Tools::version_compare($this->psVersion, '1.7.8')) {
            return;
        }

        if (null === $listener = SymfonyCacheClearer::getStaticCircularReferencesClearer()) {
            return;
        }

        try {
            /** @var EventDispatcherInterface $dispatcher */
            $dispatcher = $container->get('event_dispatcher');
            $dispatcher->addListener('cli' === \PHP_SAPI ? ConsoleEvents::TERMINATE : KernelEvents::TERMINATE, $listener, -1000);
        } catch (\Exception $e) {
            // ignore silently
        }
    }
}

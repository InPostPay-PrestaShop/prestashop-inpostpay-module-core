<?php

declare(strict_types=1);

namespace izi\prestashop\Handler\Config;

use izi\prestashop\Cache\ConfigurationCache;
use izi\prestashop\Command\Config\DownloadModuleDataCommand;
use izi\prestashop\Configuration\ApiConfiguration;
use izi\prestashop\Handler\CommandHandlerTrait;
use izi\prestashop\ObjectModel\ObjectManagerInterface;
use izi\prestashop\ObjectModel\Repository\ConfigurationRepository;
use izi\prestashop\ObjectModel\Repository\HookRepository;
use Symfony\Component\Finder\Finder;
use ZipStream\Option\Archive;
use ZipStream\ZipStream;

final class DownloadModuleDataHandler implements DownloadModuleDataHandlerInterface
{
    use CommandHandlerTrait;

    /**
     * @var \Module
     */
    private $module;

    /**
     * @var \Context
     */
    private $context;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var string
     */
    private $logsDirectory;

    public function __construct(\Module $module, \Context $context, ObjectManagerInterface $objectManager, string $logsDirectory)
    {
        $this->logsDirectory = $logsDirectory;
        $this->module = $module;
        $this->context = $context;
        $this->objectManager = $objectManager;
    }

    public function __invoke(DownloadModuleDataCommand $command): callable
    {
        return function () {
            $options = new Archive();
            $options->setFlushOutput(true);

            $zip = new ZipStream(null, $options);

            foreach ($this->getLogFiles() as $file) {
                $zip->addFileFromPath($file->getFilename(), $file->getRealPath());
            }

            $configInfo = $this->getConfigInformation();
            $zip->addFile('config.json', json_encode($configInfo, JSON_PRETTY_PRINT));

            $zip->finish();
        };
    }

    /**
     * @return iterable<\SplFileInfo>
     */
    private function getLogFiles(): iterable
    {
        return Finder::create()
            ->in($this->logsDirectory)
            ->name('izi*.log')
            ->files();
    }

    private function getConfigInformation(): array
    {
        return [
            'php_version' => phpversion(),
            'prestashop_version' => _PS_VERSION_,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? null,
            'database_version' => $this->objectManager->getConnection()->getPlatformVersion(),
            'database_engine' => _MYSQL_ENGINE_,
            'theme' => $this->context->shop->theme_name,
            'module_version' => $this->module->version,
            'module_hooks' => $this->getRegisteredHooks(),
            'module_config' => $this->getModuleConfiguration(),
        ];
    }

    private function getRegisteredHooks(): array
    {
        /** @var HookRepository $repository */
        $repository = $this->objectManager->getRepository(\Hook::class);

        return array_map(static function (\Hook $hook): string {
            return $hook->name;
        }, $repository->findByModuleId((int) $this->module->id));
    }

    private function getModuleConfiguration(): array
    {
        $config = [];

        /** @var ConfigurationRepository $repository */
        $repository = $this->objectManager->getRepository(\Configuration::class);
        $cachePrefix = ConfigurationCache::getConfigKeyPrefix();

        foreach ($repository->findByNamePrefix('INPOST_PAY') as $configuration) {
            if (0 === strpos($configuration->name, $cachePrefix)) {
                continue;
            }

            if (ApiConfiguration::ACCESS_TOKEN === $configuration->name) {
                continue;
            }

            if (ApiConfiguration::OAUTH2_CLIENT_SECRET === $configuration->name) {
                $value = $configuration->value ? 'secret' : $configuration->value;
            } else {
                $value = $configuration->value;
            }

            $config[$configuration->name][] = [
                'id_shop' => $configuration->id_shop,
                'id_shop_group' => $configuration->id_shop_group,
                'value' => $value,
            ];
        }

        return $config;
    }
}

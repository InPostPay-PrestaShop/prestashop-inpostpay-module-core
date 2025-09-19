<?php

declare(strict_types=1);

namespace izi\prestashop\Extension\MessageHandler;

use izi\prestashop\Extension\Exception\ExtensionNotFoundException;
use izi\prestashop\Extension\Exception\RuntimeException;
use izi\prestashop\Extension\ExtensionsServiceInterface;
use izi\prestashop\Extension\ExtensionVersion;
use izi\prestashop\Extension\Message\InstallExtensionCommand;
use izi\prestashop\Handler\CommandHandlerTrait;
use PrestaShop\PrestaShop\Core\Addon\AddonManagerInterface;
use PrestaShop\PrestaShop\Core\Module\ModuleManagerInterface;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;

final class InstallExtensionHandler
{
    use CommandHandlerTrait;

    /**
     * @var ExtensionsServiceInterface
     */
    private $service;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var AddonManagerInterface|ModuleManagerInterface
     */
    private $moduleManager;

    /**
     * @param ModuleManagerInterface|AddonManagerInterface $moduleManager
     */
    public function __construct(ExtensionsServiceInterface $service, Filesystem $filesystem, $moduleManager)
    {
        if (!$moduleManager instanceof ModuleManagerInterface && !$moduleManager instanceof AddonManagerInterface) {
            throw new \InvalidArgumentException(sprintf('Expected $moduleManager to be an instance of "%s" or "%s", "%s" given".', ModuleManagerInterface::class, AddonManagerInterface::class, get_debug_type($moduleManager)));
        }

        $this->service = $service;
        $this->filesystem = $filesystem;
        $this->moduleManager = $moduleManager;
    }

    public function __invoke(InstallExtensionCommand $command): void
    {
        $extension = $this->getExtensionData($command->getName(), $command->getVersion());
        $fileName = $this->downloadExtension($extension);
        $this->installOrUpgradeExtension($command->getName(), $fileName);
    }

    private function getExtensionData(string $name, string $version): ExtensionVersion
    {
        $extensions = $this->service->getExtensions();

        foreach ($extensions as $extension) {
            if ($extension->getName() !== $name) {
                continue;
            }

            foreach ($extension->getVersions() as $extVersion) {
                if ($extVersion->getVersion() === $version) {
                    return $extVersion;
                }
            }
        }

        throw new ExtensionNotFoundException(sprintf('Could not find extension "%s" version "%s".', $name, $version));
    }

    /**
     * HTTP 1.1 option is set in default stream context to prevent 426 responses from the remote server.
     * {@see Filesystem::copy()} uses {@see fopen()} with the default context to read the file
     * and before PHP8 the "protocol_version" option defaults to 1.0.
     */
    private function downloadExtension(ExtensionVersion $extension): string
    {
        try {
            $tmpFile = $this->filesystem->tempnam(sys_get_temp_dir(), 'izi');
            stream_context_set_default(['http' => ['protocol_version' => '1.1']]);
            $this->filesystem->copy($extension->getUrl(), $tmpFile);
        } catch (IOExceptionInterface $e) {
            throw new RuntimeException('Could not download the extension.', 0, $e);
        }

        if ($extension->getChecksum() !== hash_file('md5', $tmpFile)) {
            throw new RuntimeException('The downloaded file is corrupted.');
        }

        return $tmpFile;
    }

    private function installOrUpgradeExtension(string $name, string $source): void
    {
        $args = $this->moduleManager instanceof ModuleManagerInterface ? [$name, $source] : [$source];

        if ($this->moduleManager->isInstalled($name)) {
            if (!$this->moduleManager->isEnabled($name)) {
                try {
                    $result = $this->moduleManager->enable($name);
                } catch (\Exception $e) {
                    $result = false;
                }

                if (!$result) {
                    throw new RuntimeException('Could not enable the module.', 0, $e ?? null);
                }
            }

            try {
                $result = $this->moduleManager->upgrade(...$args);
            } catch (\Exception $e) {
                $result = false;
            }

            if (!$result) {
                throw new RuntimeException('Could not upgrade the module.', 0, $e ?? null);
            }
        } else {
            try {
                $result = $this->moduleManager->install(...$args);
            } catch (\Exception $e) {
                $result = false;
            }

            if (!$result) {
                throw new RuntimeException('Could not install the module.', 0, $e ?? null);
            }
        }
    }
}

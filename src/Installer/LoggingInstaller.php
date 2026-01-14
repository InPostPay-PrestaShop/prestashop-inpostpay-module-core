<?php

declare(strict_types=1);

namespace izi\prestashop\Installer;

use Psr\Log\LoggerInterface;

final class LoggingInstaller implements InstallerInterface, UninstallerInterface
{
    /**
     * @var InstallerInterface|UninstallerInterface
     */
    private $installer;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var bool
     */
    private $isReset = false;

    public function __construct(InstallerInterface $installer, LoggerInterface $logger)
    {
        $this->installer = $installer;
        $this->logger = $logger;
    }

    public function install(\Module $module): void
    {
        try {
            $this->installer->install($module);

            $this->logger->notice($this->isReset ? 'Successful reset' : 'Successful installation');
        } catch (\Throwable $e) {
            $this->logger->critical($this->isReset ? 'Reset failed' : 'Installation failed', [
                'exception' => $e,
            ]);

            throw $e;
        } finally {
            $this->isReset = false;
        }
    }

    public function uninstall(\Module $module, bool $keepData = false): void
    {
        if (!$this->installer instanceof UninstallerInterface) {
            return;
        }

        $this->isReset = $keepData;

        try {
            $this->installer->uninstall($module, $keepData);
        } catch (\Throwable $e) {
            $this->logger->critical($this->isReset ? 'Reset failed' : 'Uninstallation failed', [
                'exception' => $e,
            ]);

            throw $e;
        }

        if (!$this->isReset) {
            $this->logger->notice('Successful uninstallation');
        }
    }
}

<?php

declare(strict_types=1);

namespace izi\prestashop\Handler\Config;

use izi\prestashop\Command\Config\DownloadModuleDataCommand;

interface DownloadModuleDataHandlerInterface
{
    /**
     * @return callable streams a ZIP archive to output
     */
    public function __invoke(DownloadModuleDataCommand $command): callable;
}

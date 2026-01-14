<?php

declare(strict_types=1);

namespace izi\prestashop\Installer\Exception;

final class CoreInstallationException extends InstallerException
{
    public static function create(\Module $module): self
    {
        $message = (string) current($module->getErrors());

        return new self($message);
    }
}

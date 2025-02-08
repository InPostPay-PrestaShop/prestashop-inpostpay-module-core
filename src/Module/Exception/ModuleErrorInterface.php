<?php

declare(strict_types=1);

namespace izi\prestashop\Module\Exception;

use PrestaShop\PrestaShop\Core\Module\Exception\ModuleErrorInterface as BaseModuleErrorInterface;

if (interface_exists(BaseModuleErrorInterface::class)) {
    interface ModuleErrorInterface extends BaseModuleErrorInterface
    {
    }
} else {
    interface ModuleErrorInterface extends \Throwable
    {
    }
}

<?php

declare(strict_types=1);

namespace izi\prestashop\Environment;

use izi\prestashop\Enum\IntEnum;

/**
 * @method static self Uat()
 * @method static self Production()
 * @method static self Sandbox()
 */
final class EnvironmentType extends IntEnum
{
    private const UAT = 1;
    private const PRODUCTION = 2;
    private const SANDBOX = 3;

    public function createEnvironment(): EnvironmentInterface
    {
        switch ($this) {
            case self::Uat():
                return new UatEnvironment();
            case self::Sandbox():
                return new SandboxEnvironment();
            default:
                return new ProductionEnvironment();
        }
    }
}

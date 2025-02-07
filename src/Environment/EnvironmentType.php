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

    /**
     * @deprecated
     */
    public function createEnvironment(): EnvironmentInterface
    {
        @trigger_error(sprintf('"%s::%s()" is deprecated, use "%s::createEnvironment()" instead.', __CLASS__, __METHOD__, EnvironmentFactory::class), E_USER_DEPRECATED);

        return (new EnvironmentFactory())->createEnvironment($this);
    }
}

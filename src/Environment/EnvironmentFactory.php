<?php

declare(strict_types=1);

namespace izi\prestashop\Environment;

final class EnvironmentFactory implements EnvironmentFactoryInterface
{
    public function createEnvironment(EnvironmentType $type, bool $widgetV2 = false): EnvironmentInterface
    {
        switch ($type) {
            case EnvironmentType::Production():
                return new ProductionEnvironment($widgetV2);
            case EnvironmentType::Sandbox():
                return new SandboxEnvironment($widgetV2);
            case EnvironmentType::Uat():
                if (class_exists(UatEnvironment::class)) {
                    return new UatEnvironment();
                }

                // no break
            default:
                throw new \LogicException('Unsupported environment type.');
        }
    }
}

<?php

declare(strict_types=1);

namespace izi\prestashop\Environment;

final class EnvironmentFactory implements EnvironmentFactoryInterface
{
    /**
     * @var array<string, EnvironmentInterface> environments by type name
     */
    private static $environments = [];

    public function createEnvironment(EnvironmentType $type): EnvironmentInterface
    {
        $name = $type->name;

        return self::$environments[$name] ?? (self::$environments[$name] = $this->doCreate($type));
    }

    private function doCreate(EnvironmentType $type): EnvironmentInterface
    {
        switch ($type) {
            case EnvironmentType::Production():
                return new ProductionEnvironment();
            case EnvironmentType::Sandbox():
                return new SandboxEnvironment();
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

<?php

declare(strict_types=1);

namespace izi\prestashop\Environment;

final class EnvironmentFactory implements EnvironmentFactoryInterface
{
    /**
     * @var array<string, array<string, EnvironmentInterface>> environments by name and widget version
     */
    private static $environments = [];

    /**
     * @param bool $widgetV2 The parameter is deprecated since version 1.11.0. It will have no effect from version 3 of the module.
     */
    public function createEnvironment(EnvironmentType $type, bool $widgetV2 = false): EnvironmentInterface
    {
        $name = $type->name;
        $version = $widgetV2 ? 'v2' : 'v1';

        return self::$environments[$name][$version] ?? (self::$environments[$name][$version] = $this->doCreate($type, $widgetV2));
    }

    private function doCreate(EnvironmentType $type, bool $widgetV2 = false): EnvironmentInterface
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

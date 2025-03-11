<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration\DTO;

use izi\prestashop\Common\BindingPlace;
use izi\prestashop\Configuration\GuiConfigurationInterface;
use izi\prestashop\Configuration\WidgetDisplayConfigurationInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class GuiConfiguration implements GuiConfigurationInterface, \ArrayAccess
{
    /**
     * @var array<string, WidgetDisplayConfigurationInterface> configurations by BindingPlace value
     *
     * @Assert\Valid
     * @Assert\All({
     *     @Assert\NotNull,
     * })
     */
    private $displayConfigurations = [];

    private static $supportedBindingPlaces;

    /**
     * @param WidgetDisplayConfigurationInterface[] $displayConfigurations
     */
    public function __construct(array $displayConfigurations = [])
    {
        foreach ($displayConfigurations as $displayConfiguration) {
            $this->addDisplayConfiguration($displayConfiguration);
        }
    }

    public static function getSupportedBindingPlaces(): array
    {
        if (isset(self::$supportedBindingPlaces)) {
            return self::$supportedBindingPlaces;
        }

        self::$supportedBindingPlaces = [];

        foreach (BindingPlace::getBindingWidgetDisplayPlaces() as $bindingPlace) {
            self::$supportedBindingPlaces[$bindingPlace->value] = $bindingPlace;
        }

        return self::$supportedBindingPlaces;
    }

    public function getDisplayConfiguration(BindingPlace $bindingPlace): WidgetDisplayConfigurationInterface
    {
        $offset = $bindingPlace->value;

        return $this[$offset] ?? WidgetDisplayConfiguration::for($bindingPlace);
    }

    public function addDisplayConfiguration(WidgetDisplayConfigurationInterface $displayConfiguration): void
    {
        $offset = $displayConfiguration->getWidgetConfiguration()->getBindingPlace()->value;

        $this[$offset] = $displayConfiguration;
    }

    /**
     * @return WidgetDisplayConfigurationInterface|null[]
     */
    public function getDisplayConfigurations(): array
    {
        return $this->displayConfigurations;
    }

    public function offsetExists($offset): bool
    {
        $supportedBindingPlaces = self::getSupportedBindingPlaces();

        return isset($supportedBindingPlaces[$offset]);
    }

    public function offsetGet($offset): ?WidgetDisplayConfigurationInterface
    {
        if (!isset($this[$offset])) {
            throw new \DomainException(sprintf('Undefined offset: "%s".', $offset));
        }

        return $this->displayConfigurations[$offset] ?? null;
    }

    public function offsetSet($offset, $value): void
    {
        if (!isset($this[$offset])) {
            throw new \DomainException(sprintf('Undefined offset: "%s".', $offset));
        }

        if (null !== $value && !$value instanceof WidgetDisplayConfigurationInterface) {
            throw new \InvalidArgumentException(sprintf('Expected null or an instance of "%s", "%s" given.', WidgetDisplayConfigurationInterface::class, get_debug_type($value)));
        }

        $this->displayConfigurations[$offset] = $value;
    }

    public function offsetUnset($offset): void
    {
        if (!isset($this[$offset])) {
            return;
        }

        $this->displayConfigurations[$offset] = null;
    }
}

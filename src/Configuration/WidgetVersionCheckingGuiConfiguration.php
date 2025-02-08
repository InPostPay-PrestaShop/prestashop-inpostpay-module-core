<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration;

use izi\prestashop\Common\BindingPlace;

/**
 * @internal
 *
 * @implements PersistentConfigurationInterface<GuiConfigurationInterface>
 */
final class WidgetVersionCheckingGuiConfiguration implements GuiConfigurationInterface, PersistentConfigurationInterface
{
    use WidgetVersionCheckerTrait;

    /**
     * @var GuiConfigurationInterface
     */
    private $configuration;

    public function __construct(GuiConfigurationInterface $configuration, ApiConfigurationInterface $apiConfiguration)
    {
        $this->configuration = $configuration;
        $this->apiConfiguration = $apiConfiguration;
    }

    public static function getSupportedBindingPlaces(): array
    {
        return BindingPlace::cases();
    }

    public function getDisplayConfiguration(BindingPlace $bindingPlace): WidgetDisplayConfigurationInterface
    {
        $configuration = GuiConfiguration::getDisplayConfig($this->configuration, $bindingPlace);

        return $this->decorateDisplayConfiguration($configuration);
    }

    public function getCartWidgetDisplayConfiguration(): WidgetDisplayConfigurationInterface
    {
        $configuration = $this->configuration->getCartWidgetDisplayConfiguration();

        return $this->decorateDisplayConfiguration($configuration);
    }

    public function getProductWidgetDisplayConfiguration(): WidgetDisplayConfigurationInterface
    {
        $configuration = $this->configuration->getProductWidgetDisplayConfiguration();

        return $this->decorateDisplayConfiguration($configuration);
    }

    public function getLoginPageWidgetDisplayConfiguration(): WidgetDisplayConfigurationInterface
    {
        $configuration = $this->configuration->getLoginPageWidgetDisplayConfiguration();

        return $this->decorateDisplayConfiguration($configuration);
    }

    public function getRegisterFormPageWidgetDisplayConfiguration(): WidgetDisplayConfigurationInterface
    {
        $configuration = $this->configuration->getRegisterFormPageWidgetDisplayConfiguration();

        return $this->decorateDisplayConfiguration($configuration);
    }

    public function getCheckoutPageWidgetDisplayConfiguration(): WidgetDisplayConfigurationInterface
    {
        $configuration = $this->configuration->getCheckoutPageWidgetDisplayConfiguration();

        return $this->decorateDisplayConfiguration($configuration);
    }

    public function getMiniCartPageWidgetDisplayConfiguration(): WidgetDisplayConfigurationInterface
    {
        $configuration = $this->configuration->getMiniCartPageWidgetDisplayConfiguration();

        return $this->decorateDisplayConfiguration($configuration);
    }

    private function decorateDisplayConfiguration(WidgetDisplayConfigurationInterface $configuration): WidgetDisplayConfigurationInterface
    {
        if (!$this->isWidgetV2Enabled()) {
            return $configuration;
        }

        return new WidgetV2DisplayConfiguration($configuration);
    }

    public function copy()
    {
        if (!$this->configuration instanceof PersistentConfigurationInterface) {
            throw new \BadMethodCallException(sprintf('Inner configuration is not a "%s".', PersistentConfigurationInterface::class));
        }

        return $this->configuration->copy();
    }

    public function persist(GuiConfigurationInterface $configuration): void
    {
        if (!$this->configuration instanceof PersistentConfigurationInterface) {
            return;
        }

        $this->configuration->persist($configuration);
    }
}

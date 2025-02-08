<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration;

use izi\prestashop\View\Widget\Configuration;
use izi\prestashop\View\Widget\WidgetConfigurationInterface;

/**
 * @internal
 */
final class WidgetV2DisplayConfiguration implements ProductAwareWidgetDisplayConfigurationInterface
{
    /**
     * @var WidgetDisplayConfigurationInterface
     */
    private $configuration;

    public function __construct(WidgetDisplayConfigurationInterface $configuration)
    {
        $this->configuration = $configuration;
    }

    public function isDisplayed($product = null): bool
    {
        if (!$this->configuration instanceof ProductAwareWidgetDisplayConfigurationInterface) {
            return $this->configuration->isDisplayed();
        }

        return $this->configuration->isDisplayed($product);
    }

    public function getWidgetConfiguration(): WidgetConfigurationInterface
    {
        $configuration = $this->configuration->getWidgetConfiguration();

        if (!$configuration instanceof Configuration) {
            return $configuration;
        }

        return $configuration->asV2Configuration();
    }

    public function getHtmlStyles(): iterable
    {
        $configuration = $this->configuration->getWidgetConfiguration();

        if (!$configuration instanceof Configuration) {
            return $this->configuration->getHtmlStyles();
        }

        yield from $this->configuration->getHtmlStyles();
        yield from $configuration->getV2ContainerStyles();
    }
}

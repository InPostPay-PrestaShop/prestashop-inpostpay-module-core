<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Front;

use izi\prestashop\Common\BindingPlace;
use izi\prestashop\Configuration\GuiConfiguration;
use izi\prestashop\Configuration\GuiConfigurationInterface;
use izi\prestashop\Configuration\WidgetDisplayConfigurationInterface;
use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

trait ButtonWidgetRendererTrait
{
    /**
     * @var GuiConfigurationInterface
     */
    private $configuration;

    /**
     * @var WidgetInterface
     */
    private $module;

    private function renderWidget(BindingPlace $bindingPlace, array $parameters, ?string $hookName = null): string
    {
        $configuration = $this->getConfigurationForBinding($bindingPlace);

        if (!$configuration->isDisplayed()) {
            return '';
        }

        return $this->module->renderWidget($hookName, [
            'config' => $configuration->getWidgetConfiguration(),
            'request' => $parameters['request'] ?? null,
        ]);
    }

    private function getHtmlStyles(BindingPlace $bindingPlace): array
    {
        $configuration = $this->getConfigurationForBinding($bindingPlace);
        $styles = $configuration->getHtmlStyles();

        if ($styles instanceof \Traversable) {
            $styles = iterator_to_array($styles);
        }

        return $styles;
    }

    private function getConfigurationForBinding(BindingPlace $bindingPlace): WidgetDisplayConfigurationInterface
    {
        switch ($bindingPlace) {
            case BindingPlace::BasketSummary():
            case BindingPlace::LoginPage():
            case BindingPlace::RegisterFormPage():
            case BindingPlace::CheckoutPage():
            case BindingPlace::MiniCartPage():
                return GuiConfiguration::getDisplayConfig($this->configuration, $bindingPlace);
            default:
                throw new \DomainException(sprintf('Unsupported binding place "%s".', $bindingPlace->value));
        }
    }
}

<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Front;

use izi\prestashop\Common\BindingPlace;
use izi\prestashop\Configuration\DTO\WidgetDisplayConfiguration;
use izi\prestashop\Configuration\GuiConfigurationInterface;
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

        return iterator_to_array($styles);
    }

    private function getConfigurationForBinding(BindingPlace $bindingPlace): WidgetDisplayConfiguration
    {
        switch ($bindingPlace) {
            case BindingPlace::BasketSummary():
                return $this->configuration->getCartWidgetDisplayConfiguration();
            case BindingPlace::LoginPage():
                return $this->configuration->getLoginPageWidgetDisplayConfiguration();
            case BindingPlace::RegisterFormPage():
                return $this->configuration->getRegisterFormPageWidgetDisplayConfiguration();
            case BindingPlace::CheckoutPage():
                return $this->configuration->getCheckoutPageWidgetDisplayConfiguration();
            case BindingPlace::MiniCartPage():
                return $this->configuration->getMiniCartPageWidgetDisplayConfiguration();
            default:
                throw new \InvalidArgumentException(sprintf('Unknown binding place "%s".', $bindingPlace->value));
        }
    }
}

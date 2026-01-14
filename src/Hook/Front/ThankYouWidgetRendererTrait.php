<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Front;

use izi\prestashop\Configuration\GeneralConfigurationInterface;

trait ThankYouWidgetRendererTrait
{
    /**
     * @var GeneralConfigurationInterface
     */
    private $configuration;

    /**
     * @var \PaymentModule
     */
    private $paymentModule;

    private function shouldBeRendered(string $hookName, \Order $order): bool
    {
        return $this->paymentModule->name === $order->module
            && $this->shouldDisplayHook($hookName);
    }

    private function shouldDisplayHook(string $hookName): bool
    {
        return $this->configuration->getThankYouDisplayHook() === $hookName;
    }

    private function renderWidgetBlock(): string
    {
        return '<inpost-thank-you></inpost-thank-you>';
    }
}

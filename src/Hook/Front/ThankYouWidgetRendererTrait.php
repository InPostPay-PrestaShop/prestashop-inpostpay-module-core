<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Front;

use izi\prestashop\Configuration\ThankYouWidgetConfiguration;

trait ThankYouWidgetRendererTrait
{
    /**
     * @var ThankYouWidgetConfiguration
     */
    private $configuration;

    /**
     * @var \PaymentModule
     */
    private $paymentModule;

    /**
     * @param string $hookName
     * @param \Order $order
     * @return bool
     */
    private function shouldBeRendered(string $hookName, \Order $order): bool
    {
        if ($this->paymentModule->name !== $order->module ||
            !$this->configuration->shouldDisplayHook($hookName)
        ) {
            return false;
        }

        return true;
    }

    /**
     * @return string
     */
    private function renderWidgetBlock(): string
    {
        return '<inpost-thank-you></inpost-thank-you>';
    }
}

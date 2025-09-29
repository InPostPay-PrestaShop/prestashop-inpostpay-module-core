<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Front;

use izi\prestashop\Configuration\GeneralConfigurationInterface;
use izi\prestashop\Hook\Exception\InvalidHookParamException;
use izi\prestashop\Hook\HookInterface;

final class DisplayPaymentReturn implements HookInterface
{
    use ThankYouWidgetRendererTrait;

    public const HOOK_NAME = 'displayPaymentReturn';

    public function __construct(\PaymentModule $paymentModule, GeneralConfigurationInterface $configuration)
    {
        $this->paymentModule = $paymentModule;
        $this->configuration = $configuration;
    }

    public static function getHookName(): string
    {
        return self::HOOK_NAME;
    }

    /**
     * @param array{order?: \Order} $parameters
     */
    public function execute(array $parameters): string
    {
        $order = $parameters['order'] ?? null;

        if (!$order instanceof \Order) {
            throw InvalidHookParamException::unexpectedType('order', $order, \Order::class);
        }

        if ($this->shouldBeRendered(self::HOOK_NAME, $order)) {
            return $this->renderWidgetBlock();
        }

        return '';
    }
}

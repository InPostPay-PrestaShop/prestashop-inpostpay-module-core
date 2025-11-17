<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Front;

use izi\prestashop\Configuration\GeneralConfigurationInterface;
use izi\prestashop\Hook\Exception\InvalidHookParamException;
use izi\prestashop\Hook\HookInterface;
use PrestaShop\PrestaShop\Adapter\Presenter\Order\OrderLazyArray;

final class DisplayIziThankYou implements HookInterface
{
    use ThankYouWidgetRendererTrait;

    public const HOOK_NAME = 'displayIziThankYou';

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
     * @param array{order?: OrderLazyArray|array} $parameters
     *
     * @return string
     */
    public function execute(array $parameters): string
    {
        $order = $parameters['order'] ?? null;

        if (!is_array($order) && !$order instanceof \ArrayAccess) {
            throw InvalidHookParamException::unexpectedType('order', $order, 'array|ArrayAccess');
        }

        if (!isset($order['details']['module'])) {
            throw new InvalidHookParamException('Expected offset "details[module]" in parameter "order".');
        }

        if (
            $this->paymentModule->name !== $order['details']['module']
            || !$this->shouldDisplayHook(self::HOOK_NAME)
        ) {
            return '';
        }

        return $this->renderWidgetBlock();
    }
}

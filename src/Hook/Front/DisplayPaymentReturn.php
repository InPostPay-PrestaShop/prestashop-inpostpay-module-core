<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Front;

use izi\prestashop\Hook\HookInterface;

final class DisplayPaymentReturn implements HookInterface
{
    public const HOOK_NAME = 'displayPaymentReturn';

    /**
     * @var \PaymentModule
     */
    private $paymentModule;

    public function __construct(\PaymentModule $paymentModule)
    {
        $this->paymentModule = $paymentModule;
    }

    public static function getHookName(): string
    {
        return self::HOOK_NAME;
    }

    /**
     * @param array{order: \Order} $parameters
     */
    public function execute(array $parameters): string
    {
        $order = $parameters['order'] ?? null;

        if (!$order instanceof \Order) {
            throw new \InvalidArgumentException(sprintf('Parameter "cart" expected to be an instance of "%s", "%s" given.', \Order::class, is_object($order) ? get_class($order) : gettype($order)));
        }

        if ($this->paymentModule->name !== $order->module) {
            return '';
        }

        return '<inpost-thank-you/>';
    }
}

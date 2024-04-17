<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Common;

use izi\prestashop\Command\UnbindBasketCommand;
use izi\prestashop\CommandBusInterface;
use izi\prestashop\Hook\HookInterface;

final class ActionValidateOrder implements HookInterface
{
    public const HOOK_NAME = 'actionValidateOrder';

    /**
     * @var \PaymentModule
     */
    private $paymentModule;

    /**
     * @var CommandBusInterface
     */
    private $bus;

    public function __construct(\PaymentModule $paymentModule, CommandBusInterface $bus)
    {
        $this->paymentModule = $paymentModule;
        $this->bus = $bus;
    }

    public static function getHookName(): string
    {
        return self::HOOK_NAME;
    }

    /**
     * @param array{order?: \Order} $parameters
     */
    public function execute(array $parameters): void
    {
        $order = $parameters['order'] ?? null;

        if (!$order instanceof \Order) {
            throw new \InvalidArgumentException(sprintf('Parameter "order" expected to be an instance of "%s", "%s" given.', \Order::class, get_debug_type($order)));
        }

        if ($this->paymentModule->name === $order->module) {
            return;
        }

        $command = new UnbindBasketCommand((int) $order->id_cart, true);

        $this->bus->handle($command);
    }
}

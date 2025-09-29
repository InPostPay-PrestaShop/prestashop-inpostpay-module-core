<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Common;

use izi\prestashop\Command\UnbindBasketCommand;
use izi\prestashop\CommandBusInterface;
use izi\prestashop\Hook\Exception\InvalidHookParamException;
use izi\prestashop\Hook\HookInterface;

final class ActionCartDeleteBefore implements HookInterface
{
    public const HOOK_NAME = 'actionObjectCartDeleteBefore';

    /**
     * @var CommandBusInterface
     */
    private $bus;

    public function __construct(CommandBusInterface $bus)
    {
        $this->bus = $bus;
    }

    public static function getHookName(): string
    {
        return self::HOOK_NAME;
    }

    /**
     * @param array{object?: \Cart} $parameters
     */
    public function execute(array $parameters): void
    {
        $cart = $parameters['object'] ?? null;

        if (!$cart instanceof \Cart) {
            throw InvalidHookParamException::unexpectedType('object', $cart, \Cart::class);
        }

        if (0 >= $cartId = (int) $cart->id) {
            return;
        }

        $command = new UnbindBasketCommand($cartId);

        $this->bus->handle($command);
    }
}

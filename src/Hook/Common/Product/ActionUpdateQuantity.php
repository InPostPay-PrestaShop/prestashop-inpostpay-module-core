<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Common\Product;

use izi\prestashop\Event\EventDispatcherInterface;
use izi\prestashop\Hook\Exception\InvalidHookParamException;
use izi\prestashop\Hook\HookInterface;
use izi\prestashop\Product\Event\StockQuantityUpdatedEvent;

final class ActionUpdateQuantity implements HookInterface
{
    public const HOOK_NAME = 'actionUpdateQuantity';

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var \Context
     */
    private $context;

    public function __construct(EventDispatcherInterface $dispatcher, \Context $context)
    {
        $this->dispatcher = $dispatcher;
        $this->context = $context;
    }

    public static function getHookName(): string
    {
        return self::HOOK_NAME;
    }

    /**
     * @param array{
     *     id_product: int,
     *     id_product_attribute: int,
     *     id_shop?: int,
     *     quantity: int,
     *     delta_quantity?: int,
     * } $parameters
     */
    public function execute(array $parameters): void
    {
        $productId = $this->getParamValue($parameters, 'id_product');
        $combinationId = $this->getParamValue($parameters, 'id_product_attribute');
        $shopId = $this->getParamValue($parameters, 'id_shop', true) ?? (int) $this->context->shop->id;
        $quantity = $this->getParamValue($parameters, 'quantity');
        $deltaQuantity = $this->getParamValue($parameters, 'delta_quantity', true);

        if (0 >= $productId) {
            throw new InvalidHookParamException('Expected parameter "id_product" to be greater than 0.');
        }

        if (0 >= $shopId) {
            throw new InvalidHookParamException('Expected parameter "id_shop" to be greater than 0.');
        }

        $this->dispatcher->dispatch(new StockQuantityUpdatedEvent($productId, $combinationId, $shopId, $quantity, $deltaQuantity));
    }

    private function getParamValue(array $parameters, string $name, bool $optional = false): ?int
    {
        $value = $parameters[$name] ?? null;

        if (null === $value && $optional) {
            return null;
        }

        if (!is_int($value) && (!is_string($value) || !ctype_digit($value))) {
            throw InvalidHookParamException::unexpectedType($name, $value, 'int');
        }

        return (int) $value;
    }
}

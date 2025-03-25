<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Common\Product;

use izi\prestashop\Event\EventDispatcherInterface;
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
     *     id_shop: int,
     *     quantity: int,
     *     delta_quantity?: int
     * } $parameters
     */
    public function execute(array $parameters): void
    {
        $productId = (int) ($parameters['id_product'] ?? 0);
        $combinationId = (int) ($parameters['id_product_attribute'] ?? 0);
        $shopId = (int) ($parameters['id_shop'] ?? $this->context->shop->id);
        $quantity = (int) ($parameters['quantity'] ?? 0);
        $deltaQuantity = isset($parameters['delta_quantity']) ? (int) $parameters['delta_quantity'] : null;

        if ($productId <= 0) {
            throw new \InvalidArgumentException('Invalid product ID');
        }

        if ($shopId <= 0) {
            throw new \InvalidArgumentException('Invalid shop ID');
        }

        $this->dispatcher->dispatch(new StockQuantityUpdatedEvent($productId, $combinationId, $shopId, $quantity, $deltaQuantity));
    }
}

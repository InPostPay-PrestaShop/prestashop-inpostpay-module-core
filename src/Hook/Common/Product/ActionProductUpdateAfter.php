<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Common\Product;

use izi\prestashop\Event\EventDispatcherInterface;
use izi\prestashop\Hook\Exception\InvalidHookParamException;
use izi\prestashop\Hook\HookInterface;
use izi\prestashop\Product\Event\ProductEvent;

final class ActionProductUpdateAfter implements HookInterface
{
    public const HOOK_NAME = 'actionObjectProductUpdateAfter';

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public static function getHookName(): string
    {
        return self::HOOK_NAME;
    }

    /**
     * @param array{object: \Product} $parameters
     */
    public function execute(array $parameters): void
    {
        $product = $parameters['object'] ?? null;

        if (!$product instanceof \Product) {
            throw InvalidHookParamException::unexpectedType('object', $product, \Product::class);
        }

        $this->dispatcher->dispatch(new ProductEvent($product), ProductEvent::UPDATED);
    }
}

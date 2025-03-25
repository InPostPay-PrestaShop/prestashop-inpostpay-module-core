<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Common\Product;

use izi\prestashop\Event\EventDispatcherInterface;
use izi\prestashop\Hook\HookInterface;
use izi\prestashop\Product\Event\ProductEvent;

final class ActionProductDeleteAfter implements HookInterface
{
    public const HOOK_NAME = 'actionObjectProductDeleteAfter';

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
            throw new \InvalidArgumentException(sprintf('Expected parameter "object" to be an instance of "%s", "%s" given.', \Product::class, get_debug_type($product)));
        }

        $this->dispatcher->dispatch(new ProductEvent($product), ProductEvent::DELETED);
    }
}

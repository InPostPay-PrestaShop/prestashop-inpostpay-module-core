<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Common\Product;

use izi\prestashop\Event\EventDispatcherInterface;
use izi\prestashop\Hook\HookInterface;
use izi\prestashop\Product\Event\SpecificPriceEvent;

final class ActionSpecificPriceUpdateAfter implements HookInterface
{
    public const HOOK_NAME = 'actionObjectSpecificPriceUpdateAfter';

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
     * @param array{object: \SpecificPrice} $parameters
     */
    public function execute(array $parameters): void
    {
        $price = $parameters['object'] ?? null;

        if (!$price instanceof \SpecificPrice) {
            throw new \InvalidArgumentException(sprintf('Expected parameter "object" to be an instance of "%s", "%s" given.', \SpecificPrice::class, get_debug_type($price)));
        }

        $this->dispatcher->dispatch(new SpecificPriceEvent($price), SpecificPriceEvent::UPDATED);
    }
}

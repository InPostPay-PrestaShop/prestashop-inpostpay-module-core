<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Common\Product;

use izi\prestashop\Event\EventDispatcherInterface;
use izi\prestashop\Hook\HookInterface;
use izi\prestashop\Product\Event\CombinationEvent;

final class ActionCombinationDeleteBefore implements HookInterface
{
    public const HOOK_NAME = 'actionObjectCombinationDeleteBefore';

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
     * @param array{object: \Combination} $parameters
     */
    public function execute(array $parameters): void
    {
        $combination = $parameters['object'] ?? null;

        if (!$combination instanceof \Combination) {
            throw new \InvalidArgumentException(sprintf('Expected parameter "object" to be an instance of "%s", "%s" given.', \Combination::class, get_debug_type($combination)));
        }

        $this->dispatcher->dispatch(new CombinationEvent($combination), CombinationEvent::DELETION);
    }
}

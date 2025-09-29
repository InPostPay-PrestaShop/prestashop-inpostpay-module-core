<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Common\Product;

use izi\prestashop\Event\EventDispatcherInterface;
use izi\prestashop\Hook\Exception\InvalidHookParamException;
use izi\prestashop\Hook\HookInterface;
use izi\prestashop\Product\Event\CombinationEvent;

final class ActionCombinationDeleteAfter implements HookInterface
{
    public const HOOK_NAME = 'actionObjectCombinationDeleteAfter';

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
            throw InvalidHookParamException::unexpectedType('object', $combination, \Combination::class);
        }

        $this->dispatcher->dispatch(new CombinationEvent($combination), CombinationEvent::DELETED);
    }
}

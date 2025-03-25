<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Common\Product;

use izi\prestashop\Event\EventDispatcherInterface;
use izi\prestashop\Hook\HookInterface;
use izi\prestashop\Product\Event\ImageEvent;

final class ActionImageDeleteAfter implements HookInterface
{
    public const HOOK_NAME = 'actionObjectImageDeleteAfter';

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
     * @param array{object: \Image} $parameters
     */
    public function execute(array $parameters): void
    {
        $image = $parameters['object'] ?? null;

        if (!$image instanceof \Image) {
            throw new \InvalidArgumentException(sprintf('Expected parameter "object" to be an instance of "%s", "%s" given.', \Image::class, get_debug_type($image)));
        }

        $this->dispatcher->dispatch(new ImageEvent($image), ImageEvent::DELETED);
    }
}

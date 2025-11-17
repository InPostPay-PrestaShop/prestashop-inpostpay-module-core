<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Common\Product;

use izi\prestashop\Event\EventDispatcherInterface;
use izi\prestashop\Hook\Exception\InvalidHookParamException;
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
            throw InvalidHookParamException::unexpectedType('object', $image, \Image::class);
        }

        $this->dispatcher->dispatch(new ImageEvent($image), ImageEvent::DELETED);
    }
}

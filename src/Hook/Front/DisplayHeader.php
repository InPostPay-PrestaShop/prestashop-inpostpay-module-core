<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Front;

use izi\prestashop\Event\EventDispatcherInterface;
use izi\prestashop\Hook\Front\Event\RenderHeaderEvent;
use izi\prestashop\Hook\HookInterface;
use Symfony\Component\HttpFoundation\Request;

final class DisplayHeader implements HookInterface
{
    public const HOOK_NAME = 'displayHeader';

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public static function getHookName(): string
    {
        return self::HOOK_NAME;
    }

    /**
     * @param array{request?: Request} $parameters
     */
    public function execute(array $parameters): string
    {
        $request = $parameters['request'] ?? null;

        if (!$request instanceof Request) {
            $request = null;
        }

        return $this->eventDispatcher->dispatch(new RenderHeaderEvent($request))->getContent();
    }
}

<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Front;

use izi\prestashop\Event\CartUpdatedEvent;
use izi\prestashop\Event\EventDispatcherInterface;
use izi\prestashop\Hook\HookInterface;
use Symfony\Component\HttpFoundation\Request;

final class ActionCartControllerAjaxUpdateResponse implements HookInterface
{
    public const HOOK_NAME = 'actionAjaxDieCartControllerdisplayAjaxUpdateBefore';

    /**
     * @var \Context
     */
    private $context;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    public function __construct(\Context $context, EventDispatcherInterface $dispatcher)
    {
        $this->context = $context;
        $this->dispatcher = $dispatcher;
    }

    public static function getHookName(): string
    {
        return self::HOOK_NAME;
    }

    /**
     * @param array{request?: Request, value?: string} $parameters
     */
    public function execute(array $parameters): void
    {
        if (!$this->context->cart instanceof \Cart) {
            return;
        }

        $request = $parameters['request'] ?? null;

        if (!$request instanceof Request) {
            return;
        }

        if (null === $request->get('addDiscount') && null === $request->get('deleteDiscount')) {
            return;
        }

        $this->dispatcher->dispatch(new CartUpdatedEvent($this->context->cart));
    }
}

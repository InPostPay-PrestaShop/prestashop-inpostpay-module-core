<?php

namespace izi\prestashop\Hook\Admin;

use izi\prestashop\Event\CreateShipmentRequestProcessedEvent;
use izi\prestashop\Event\EventDispatcherInterface;
use izi\prestashop\Hook\HookInterface;

final class ActionAdminInPostConfirmedShipmentsControllerAfter implements HookInterface
{
    public const HOOK_NAME = 'actionAdminInPostConfirmedShipmentsControllerAfter';

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

    public function execute(array $parameters)
    {
        $request = $parameters['request'] ?? null;

        if (null === $request || 'createShipment' !== $request->query->get('action')) {
            return;
        }

        $this->dispatcher->dispatch(new CreateShipmentRequestProcessedEvent($parameters['controller']), CreateShipmentRequestProcessedEvent::class);
    }
}

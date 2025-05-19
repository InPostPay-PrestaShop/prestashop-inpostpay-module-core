<?php

namespace izi\prestashop\Hook\Common;

use izi\prestashop\Event\EventDispatcherInterface;
use izi\prestashop\Hook\HookInterface;
use izi\prestashop\Mail\Event\SendEmailEvent;

final class ActionEmailSendBefore implements HookInterface
{
    public const HOOK_NAME = 'actionEmailSendBefore';

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

    public function execute(array $parameters): bool
    {
        $event = $this->createEvent($parameters);
        $this->dispatcher->dispatch($event);

        if ($event->isPropagationStopped()) {
            return false;
        }

        $parameters['to'] = $event->getRecipients();
        $parameters['bcc'] = $event->getBcc();

        return true;
    }

    private function createEvent(array $parameters): SendEmailEvent
    {
        $event = new SendEmailEvent($parameters['template'], $parameters['templateVars']);

        if (is_array($parameters['to'])) {
            $event->setRecipients(...$parameters['to']);
        } else {
            $event->setRecipients($parameters['to']);
        }

        if (null === $parameters['bcc']) {
            return $event;
        }

        if (is_array($parameters['bcc'])) {
            $event->setBcc(...$parameters['bcc']);
        } else {
            $event->setBcc($parameters['bcc']);
        }

        return $event;
    }
}

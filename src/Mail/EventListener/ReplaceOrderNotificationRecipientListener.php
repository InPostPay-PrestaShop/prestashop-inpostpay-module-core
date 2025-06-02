<?php

declare(strict_types=1);

namespace izi\prestashop\Mail\EventListener;

use izi\prestashop\Mail\Event\SendEmailEvent;
use izi\prestashop\Mail\Resolver\OrderMailRecipientResolver;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class ReplaceOrderNotificationRecipientListener implements EventSubscriberInterface
{
    /**
     * @var OrderMailRecipientResolver
     */
    private $recipientResolver;

    public function __construct(OrderMailRecipientResolver $recipientResolver)
    {
        $this->recipientResolver = $recipientResolver;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SendEmailEvent::class => 'onSendEmail',
        ];
    }

    public function onSendEmail(SendEmailEvent $event): void
    {
        if (!$event->hasParameter('{id_order}')) {
            return;
        }

        $recipient = $this->recipientResolver->resolve(
            (string) $event->getParameter('{id_order}'),
            $event->getRecipients(),
            $event->getBcc() ?? []
        );

        $event->setRecipients(...$recipient->getEmails());
        $event->setBcc(...$recipient->getBcc());
    }
}

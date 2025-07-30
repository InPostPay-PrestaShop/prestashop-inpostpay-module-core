<?php

declare(strict_types=1);

namespace izi\prestashop\Mail\EventListener;

use izi\prestashop\Mail\Event\SendEmailEvent;
use izi\prestashop\Repository\OrderDataRepositoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class AddDigitalDeliveryRecipientListener implements EventSubscriberInterface
{
    /**
     * @var OrderDataRepositoryInterface
     */
    private $repository;

    public function __construct(OrderDataRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SendEmailEvent::class => ['onSendEmail', 100],
        ];
    }

    public function onSendEmail(SendEmailEvent $event): void
    {
        if ('download_product' !== $event->getTemplate()) {
            return;
        }

        if (!$event->hasParameter('{id_order}')) {
            return;
        }

        $orderId = (string) $event->getParameter('{id_order}');

        if (null === $data = $this->repository->getOrderData($orderId)) {
            return;
        }

        if (null === $email = $data->getDelivery()->getDigitalDeliveryEmail()) {
            return;
        }

        if ($event->hasRecipient($email) || $event->hasBcc($email)) {
            return;
        }

        $event->addBcc($email);
    }
}

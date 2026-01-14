<?php

namespace izi\prestashop\EventListener;

use izi\prestashop\Event\CreateShipmentRequestEvent;
use izi\prestashop\Event\CreateShipmentRequestProcessedEvent;
use izi\prestashop\Repository\OrderDataRepositoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class CreateShipmentListener implements EventSubscriberInterface
{
    /**
     * @var OrderDataRepositoryInterface
     */
    private $orderDataRepository;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var string|null
     */
    private $error;

    public function __construct(OrderDataRepositoryInterface $orderDataRepository, TranslatorInterface $translator)
    {
        $this->orderDataRepository = $orderDataRepository;
        $this->translator = $translator;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CreateShipmentRequestEvent::class => 'onCreateShipmentRequest',
            CreateShipmentRequestProcessedEvent::class => 'onShipmentRequestProcessed',
        ];
    }

    public function onCreateShipmentRequest(CreateShipmentRequestEvent $event): void
    {
        if (!$email = $event->getRequest()->get('email')) {
            return;
        }

        if (0 >= $orderId = (int) $event->getRequest()->get('id_order')) {
            return;
        }

        if (null === $orderData = $this->orderDataRepository->getOrderData($orderId)) {
            return;
        }

        $deliveryEmail = $orderData->getDelivery()->getEmail();

        if (null === $deliveryEmail || $email === $deliveryEmail) {
            return;
        }

        $this->error = $this->translator->trans('In order for the shipment to be processed correctly by InPost Pay, the email address must match the one received when creating the order ({email}).', [
            '{email}' => $deliveryEmail,
        ], 'Modules.Inpostizi.Validators');

        unset($_POST['service']);
    }

    public function onShipmentRequestProcessed(CreateShipmentRequestProcessedEvent $event): void
    {
        if (null === $this->error) {
            return;
        }

        $event->getController()->errors[] = $this->error;
        $this->error = null;
    }
}

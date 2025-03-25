<?php

namespace izi\prestashop\EventListener;

use izi\prestashop\Event\CreateShipmentRequestEvent;
use izi\prestashop\Event\CreateShipmentRequestProcessedEvent;
use izi\prestashop\Repository\OrderDataRepositoryInterface;
use izi\prestashop\Translation\LegacyTranslator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CreateShipmentListener implements EventSubscriberInterface
{
    private const TRANSLATION_SOURCE = 'createshipmentlistener';

    /**
     * @var OrderDataRepositoryInterface
     */
    private $orderDataRepository;

    /**
     * @var LegacyTranslator
     */
    private $translator;

    /**
     * @var bool
     */
    private $processed = false;

    /**
     * @var string
     */
    private $validMail;

    public function __construct(OrderDataRepositoryInterface $orderDataRepository, LegacyTranslator $translator)
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
        $idOrder = $event->getRequest()->get('id_order');
        $email = $event->getRequest()->get('email');
        if (!$this->isValidParams($idOrder, $email)) {
            return;
        }

        if (null === $orderData = $this->orderDataRepository->getOrderData($idOrder)) {
            return;
        }

        if (null !== $orderData->getDelivery()->getEmail() && $email !== $orderData->getDelivery()->getEmail()) {
            $this->validMail = $orderData->getDelivery()->getEmail();
            $this->removeService();
        }
    }

    private function isValidParams($idOrder, $email)
    {
        return !empty($idOrder) && !empty($email);
    }

    private function removeService()
    {
        unset($_POST['service']);
        $this->processed = true;
    }

    public function onShipmentRequestProcessed(CreateShipmentRequestProcessedEvent $event)
    {
        if (!$this->processed) {
            return;
        }
        $event->getController()->errors = [sprintf($this->translator->l('In order for the shipment to be processed correctly by InPost Pay, the email address must match the one received when creating the order (%s).', self::TRANSLATION_SOURCE), $this->validMail)];
        $this->processed = false;
        $this->validMail = null;
    }
}

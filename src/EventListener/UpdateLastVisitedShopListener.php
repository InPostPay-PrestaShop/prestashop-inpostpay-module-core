<?php

declare(strict_types=1);

namespace izi\prestashop\EventListener;

use izi\prestashop\Event\CartUpdatedEvent;
use izi\prestashop\Event\EventDispatcherInterface;
use izi\prestashop\Hook\Front\Event\RenderHeaderEvent;
use izi\prestashop\Repository\BasketSessionRepositoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class UpdateLastVisitedShopListener implements EventSubscriberInterface
{
    /**
     * @var BasketSessionRepositoryInterface
     */
    private $sessionRepository;

    /**
     * @var \Context
     */
    private $context;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    public function __construct(\Context $context, BasketSessionRepositoryInterface $sessionRepository, EventDispatcherInterface $eventDispatcher)
    {
        $this->context = $context;
        $this->sessionRepository = $sessionRepository;
        $this->eventDispatcher = $eventDispatcher;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            RenderHeaderEvent::class => 'onFrontOfficeRequest',
        ];
    }

    public function onFrontOfficeRequest(?RenderHeaderEvent $event = null): void
    {
        if (null === $event) {
            @trigger_error(\sprintf('Not passing an $event to "%s()" is deprecated since version 3.3.0.', __METHOD__), \E_USER_DEPRECATED);
        }

        if (!isset($this->context->cart->id)) {
            return;
        }

        if (!$this->context->cookie->exists()) {
            return;
        }

        if (!$this->context->shop->getGroup()->share_order) {
            return;
        }

        if (null === $session = $this->sessionRepository->findByEntityId($this->context->cart->id)) {
            return;
        }

        if ($session->getShopId() === $shopId = (int) $this->context->shop->id) {
            return;
        }

        $session->setShopId($shopId);
        $this->sessionRepository->persist($session);

        $this->eventDispatcher->dispatch(new CartUpdatedEvent($this->context->cart, $event ? $event->getRequest() : null));
    }
}

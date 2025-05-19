<?php

namespace izi\prestashop\Hook\Front;

use izi\prestashop\Event\CartUpdatedEvent;
use izi\prestashop\Event\EventDispatcherInterface;
use izi\prestashop\Hook\HookInterface;
use izi\prestashop\Repository\BasketSessionRepositoryInterface;

final class DisplayHeader implements HookInterface
{
    public const HOOK_NAME = 'displayHeader';

    /**
     * @var BasketSessionRepositoryInterface
     */
    private $basketSessionRepository;

    /**
     * @var \Context
     */
    private $context;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    public function __construct(\Context $context, BasketSessionRepositoryInterface $basketSessionRepository, EventDispatcherInterface $eventDispatcher)
    {
        $this->basketSessionRepository = $basketSessionRepository;
        $this->context = $context;
        $this->eventDispatcher = $eventDispatcher;
    }

    public static function getHookName(): string
    {
        return self::HOOK_NAME;
    }

    public function execute(array $parameters): void
    {
        if (!$this->context->cookie->exists()) {
            return;
        }

        if (!isset($this->context->cart->id) || !$this->context->shop->getGroup()->share_order) {
            return;
        }

        $session = $this->basketSessionRepository->findByEntityId($this->context->cart->id);

        if (null === $session || $session->getShopId() === (int) $this->context->shop->id) {
            return;
        }

        $session->setShopId($this->context->shop->id);
        $this->basketSessionRepository->persist($session);

        $this->eventDispatcher->dispatch(new CartUpdatedEvent($this->context->cart));
    }
}

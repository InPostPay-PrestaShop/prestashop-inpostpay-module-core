<?php

declare(strict_types=1);

namespace izi\prestashop\EventListener;

use izi\prestashop\Event\CartUpdatedEvent;
use izi\prestashop\Event\EventDispatcherInterface;
use izi\prestashop\Hook\Front\ActionCartControllerAjaxUpdateResponse;
use izi\prestashop\Hook\Front\Event\RenderHeaderEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class DetectCartDiscountsUpdateListener implements EventSubscriberInterface
{
    /**
     * @var \Context
     */
    private $context;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var string
     */
    private $psVersion;

    public function __construct(\Context $context, EventDispatcherInterface $eventDispatcher, string $psVersion = _PS_VERSION_)
    {
        $this->context = $context;
        $this->eventDispatcher = $eventDispatcher;
        $this->psVersion = $psVersion;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            RenderHeaderEvent::class => 'onFrontOfficeRequest',
        ];
    }

    public function onFrontOfficeRequest(RenderHeaderEvent $event): void
    {
        if (null === $request = $event->getRequest()) {
            return;
        }

        $controller = $this->context->controller;

        if (!$controller instanceof \CartControllerCore) {
            return;
        }

        $isAjaxRequest = (bool) $request->get('ajax');

        if ($isAjaxRequest && 'update' === $request->get('action')) {
            /* @see ActionCartControllerAjaxUpdateResponse should be executed later */
            return;
        }

        if (null === $request->get('addDiscount') && null === $request->get('deleteDiscount')) {
            return;
        }

        if ($this->hasErrors($controller, $isAjaxRequest)) {
            return;
        }

        $this->eventDispatcher->dispatch(new CartUpdatedEvent($this->context->cart, $request));
    }

    /**
     * @return bool whether the controller has errors that prevented the discounts update
     */
    private function hasErrors(\CartControllerCore $controller, bool $isAjaxRequest): bool
    {
        if ([] === $controller->errors) {
            return false;
        }

        $cart = $this->context->cart;

        if (!$cart->checkQuantities()) {
            return true;
        }

        if (\Tools::version_compare($this->psVersion, '1.7.7')) {
            return true;
        }

        /* @see \CartControllerCore::initContent() might have added errors after the disounts had been updated */
        return !$isAjaxRequest || !$this->hasMinQuantityError($cart);
    }

    private function hasMinQuantityError(\Cart $cart): bool
    {
        foreach ($cart->getProducts() as $product) {
            if ($product['minimal_quantity'] > $product['cart_quantity']) {
                return true;
            }
        }

        return false;
    }
}

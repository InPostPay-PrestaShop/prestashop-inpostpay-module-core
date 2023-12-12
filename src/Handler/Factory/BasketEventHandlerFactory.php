<?php

namespace izi\prestashop\Handler\Factory;

use izi\prestashop\Handler\BasketEventHandlerInterface;
use izi\prestashop\Handler\ProductsQuantityEventHandler;
use izi\prestashop\Handler\PromoCodesEventHandler;
use izi\prestashop\Handler\RelatedProductsEventHandler;

class BasketEventHandlerFactory
{
    private const HANDLER_MAP = [
        ProductsQuantityEventHandler::EVENT_TYPE => ProductsQuantityEventHandler::class,
        PromoCodesEventHandler::EVENT_TYPE => PromoCodesEventHandler::class,
        RelatedProductsEventHandler::EVENT_TYPE => RelatedProductsEventHandler::class,
    ];

    /**
     * @param object{event_type: string} $event
     */
    public static function create(\Context $context, $event): BasketEventHandlerInterface
    {
        if (!isset(self::HANDLER_MAP[$event->event_type])) {
            throw new \UnexpectedValueException(sprintf('Unknown event type "%s".', $event->event_type));
        }

        $handlerClass = self::HANDLER_MAP[$event->event_type];

        return new $handlerClass($context);
    }
}

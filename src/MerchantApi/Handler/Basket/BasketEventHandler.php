<?php

declare(strict_types=1);

namespace izi\prestashop\MerchantApi\Handler\Basket;

use izi\prestashop\Common\Basket\Notice;
use izi\prestashop\Common\Basket\NoticeType;
use izi\prestashop\ContextManager;
use izi\prestashop\DependencyInjection\ServiceSubscriberInterface;
use izi\prestashop\Entities\BasketInterface;
use izi\prestashop\MerchantApi\Model\Basket\Request\BasketEvent;
use izi\prestashop\MerchantApi\Model\Basket\Request\EventType;
use Psr\Container\ContainerInterface;

final class BasketEventHandler implements BasketEventHandlerInterface, ServiceSubscriberInterface
{
    /**
     * @var ContainerInterface
     */
    private $locator;

    /**
     * @var \Context
     */
    private $context;

    /**
     * @var ContextManager
     */
    private $contextManager;

    public function __construct(ContainerInterface $locator, \Context $context, ContextManager $contextManager)
    {
        $this->locator = $locator;
        $this->context = $context;
        $this->contextManager = $contextManager;
    }

    public static function getSubscribedServices(): array
    {
        return [
            EventType::ProductsQuantity()->value => ProductsQuantityEventHandler::class,
            EventType::PromoCodes()->value => PromoCodesEventHandler::class,
            EventType::RelatedProducts()->value => RelatedProductsEventHandler::class,
        ];
    }

    public function handle(BasketInterface $basket, BasketEvent $event): ?Notice
    {
        /** @var BasketEventHandlerInterface $handler */
        $handler = $this->locator->get($event->getType()->value);

        try {
            $this->contextManager->changeContext($basket->getEntity());

            $notice = $handler->handle($basket, $event);

            if (null === $notice || NoticeType::Error() !== $notice->getType()) {
                \CartRule::autoRemoveFromCart($this->context);
                \CartRule::autoAddToCart($this->context);
            }

            return $notice;
        } finally {
            $this->contextManager->restoreContext();
        }
    }
}

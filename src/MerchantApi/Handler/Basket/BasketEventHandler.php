<?php

declare(strict_types=1);

namespace izi\prestashop\MerchantApi\Handler\Basket;

use izi\prestashop\Common\Basket\Notice;
use izi\prestashop\Common\Basket\NoticeType;
use izi\prestashop\ContextManager;
use izi\prestashop\DependencyInjection\ServiceSubscriberInterface;
use izi\prestashop\Entities\BasketInterface;
use izi\prestashop\Event\EventDispatcherInterface;
use izi\prestashop\MerchantApi\Event\CartUpdatedEvent;
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
     * @var ContextManager
     */
    private $contextManager;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @param ContainerInterface $locator locator of {@see BasketEventHandlerInterface} by handled {@see EventType} value
     */
    public function __construct(ContainerInterface $locator, ContextManager $contextManager, EventDispatcherInterface $dispatcher)
    {
        $this->locator = $locator;
        $this->contextManager = $contextManager;
        $this->dispatcher = $dispatcher;
    }

    public static function getSubscribedServices(): array
    {
        return [
            EventType::ProductsQuantity()->value => ProductsQuantityEventHandler::class,
            EventType::PromoCodes()->value => PromoCodesEventHandler::class,
            EventType::RelatedProducts()->value => RelatedProductsEventHandler::class,
        ];
    }

    public function handle(BasketInterface $basket, BasketEvent $event, ?int $shopId = null): ?Notice
    {
        $cart = $basket->getEntity();

        if (!$cart instanceof \Cart) {
            throw new \InvalidArgumentException(\sprintf('Expected basket entity to be an instance of "%s", "%s" given.', \Cart::class, \get_class($cart)));
        }

        /** @var BasketEventHandlerInterface $handler */
        $handler = $this->locator->get($event->getType()->value);

        try {
            $this->contextManager->changeContext($cart, [
                'shop_id' => $shopId,
            ]);

            $notice = $handler->handle($basket, $event);

            if (null === $notice || NoticeType::Error() !== $notice->getType()) {
                $this->dispatcher->dispatch(new CartUpdatedEvent($cart));
            }

            return $notice;
        } finally {
            $this->contextManager->restoreContext();
        }
    }
}

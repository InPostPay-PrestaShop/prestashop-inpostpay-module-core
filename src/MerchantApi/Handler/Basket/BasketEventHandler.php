<?php

declare(strict_types=1);

namespace izi\prestashop\MerchantApi\Handler\Basket;

use izi\prestashop\Common\Basket\Notice;
use izi\prestashop\Common\Basket\NoticeType;
use izi\prestashop\ContextManager;
use izi\prestashop\DependencyInjection\ServiceSubscriberInterface;
use izi\prestashop\Entities\BasketInterface;
use izi\prestashop\Event\Adapter\EventDispatcher as EventDispatcherAdapter;
use izi\prestashop\Event\EventDispatcherInterface;
use izi\prestashop\MerchantApi\Event\CartUpdatedEvent;
use izi\prestashop\MerchantApi\EventListener\UpdateCartRulesListener;
use izi\prestashop\MerchantApi\Model\Basket\Request\BasketEvent;
use izi\prestashop\MerchantApi\Model\Basket\Request\EventType;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

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
     * @param ContextManager $contextManager
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(ContainerInterface $locator, $contextManager, $dispatcher)
    {
        $this->locator = $locator;

        if ($dispatcher instanceof ContextManager) {
            $contextManager = $dispatcher;
            $dispatcher = $this->createEventDispatcher($contextManager->getContext());
        }

        if (!$contextManager instanceof ContextManager) {
            throw new \InvalidArgumentException(sprintf('Expected parameter $contextManager to be an instance of "%s", "%s" given.', ContextManager::class, get_debug_type($contextManager)));
        }

        if (!$dispatcher instanceof EventDispatcherInterface) {
            throw new \InvalidArgumentException(sprintf('Expected parameter $dispatcher to be an instance of "%s", "%s" given.', EventDispatcherInterface::class, get_debug_type($dispatcher)));
        }

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
        /** @var BasketEventHandlerInterface $handler */
        $handler = $this->locator->get($event->getType()->value);

        try {
            $cart = $basket->getEntity();
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

    private function createEventDispatcher(\Context $context): EventDispatcherInterface
    {
        @trigger_error(sprintf('Passing $context as the second argument for "%s::__construct()" is deprecated.', __CLASS__), E_USER_DEPRECATED);

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new UpdateCartRulesListener($context));

        return new EventDispatcherAdapter($dispatcher);
    }
}

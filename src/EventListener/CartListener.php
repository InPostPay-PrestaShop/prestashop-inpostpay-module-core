<?php

declare(strict_types=1);

namespace izi\prestashop\EventListener;

use izi\prestashop\Command\UpdateBasketCommand;
use izi\prestashop\CommandBusInterface;
use izi\prestashop\Configuration\ApiConfigurationInterface;
use izi\prestashop\Entities\BasketSession;
use izi\prestashop\Entities\Cart;
use izi\prestashop\Event\CartUpdatedEvent;
use izi\prestashop\Event\TerminateEvent;
use izi\prestashop\Repository\BasketSessionRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class CartListener implements EventSubscriberInterface
{
    /**
     * @var ApiConfigurationInterface
     */
    private $configuration;

    /**
     * @var \Context
     */
    private $context;

    /**
     * @var BasketSessionRepositoryInterface<BasketSession>
     */
    private $sessionRepository;

    /**
     * @var CommandBusInterface
     */
    private $bus;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var array<int, int>
     */
    private $updatedCartIds = [];

    /**
     * @param BasketSessionRepositoryInterface<BasketSession> $sessionRepository
     */
    public function __construct(ApiConfigurationInterface $configuration, \Context $context, BasketSessionRepositoryInterface $sessionRepository, CommandBusInterface $bus, LoggerInterface $logger)
    {
        $this->configuration = $configuration;
        $this->context = $context;
        $this->sessionRepository = $sessionRepository;
        $this->bus = $bus;
        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CartUpdatedEvent::class => 'onCartUpdated',
        ];
    }

    public function onCartUpdated(CartUpdatedEvent $event/*, string $eventName, EventDispatcherInterface $dispatcher*/): void
    {
        if (null === $this->configuration->getClientCredentials()) {
            return;
        }

        $cart = $event->getCart();

        if (0 >= $cartId = (int) $cart->id) {
            return;
        }

        if ([] === $this->updatedCartIds) {
            $args = \func_get_args();
            $dispatcher = $args[2] ?? null;

            if ($dispatcher instanceof EventDispatcherInterface) {
                $dispatcher->addListener(TerminateEvent::class, [$this, 'onTerminate']);
            } else {
                @trigger_error(\sprintf('Not passing $eventName and $dispatcher to "%s()" is deprecated since version 3.3.0.', __METHOD__), \E_USER_DEPRECATED);
                register_shutdown_function([$this, 'onTerminate']);
            }
        }

        $this->updatedCartIds[$cartId] = $cartId;
    }

    public function onTerminate(TerminateEvent $event): void
    {
        if ([] === $this->updatedCartIds) {
            return;
        }

        /* @see https://github.com/PrestaShop/PrestaShop/pull/28267 \Shop instatiation on PS 1.7.8 might result in an error when attempting to read/write from a file given by a relative path */
        if (\Tools::version_compare(_PS_VERSION_, '1.7.8', '>=') && \Tools::version_compare(_PS_VERSION_, '8.0.0')) {
            \Configuration::set('_PS_CACHE_DIR_', _PS_CACHE_DIR_);
        }

        try {
            $this->updateBaskets();
        } finally {
            $this->updatedCartIds = [];
        }
    }

    private function updateBaskets(): void
    {
        foreach ($this->updatedCartIds as $cartId) {
            try {
                $this->handleCurrentCartChange($cartId);
                $command = new UpdateBasketCommand($cartId);

                $this->bus->handle($command);
            } catch (\Throwable $e) {
                $this->logger->critical('Could not send updated cart #{cartId} data.', [
                    'cartId' => $cartId,
                    'exception' => $e,
                ]);
            }
        }
    }

    private function handleCurrentCartChange(int $cartId): void
    {
        if (null !== $this->sessionRepository->findByEntityId($cartId)) {
            return;
        }

        if (!$this->context->controller instanceof \FrontControllerCore || $cartId !== (int) $this->context->cart->id) {
            return;
        }

        // the user changed his current cart (e.g. using the reorder option)

        if (null === $currentBasketId = $this->context->cookie->inpostizi_basket_id ?? null) {
            return;
        }

        if (null === $session = $this->sessionRepository->findByBasketId($currentBasketId)) {
            return;
        }

        if (null !== $orderId = $session->getOrderId()) {
            $this->logger->notice('Order #{orderId}: basket ID was not removed from customer cookie.', [
                'orderId' => $orderId,
            ]);
        } else {
            $session->switchBasket(new Cart($this->context->cart));
            $this->sessionRepository->persist($session);
        }
    }
}

<?php

declare(strict_types=1);

namespace izi\prestashop\MerchantApi\Handler;

use izi\prestashop\Builder\Basket\BasketBuilderFactoryInterface;
use izi\prestashop\CommandBusInterface;
use izi\prestashop\ContextManager;
use izi\prestashop\Entities\BasketSession;
use izi\prestashop\Entities\BasketSessionInterface;
use izi\prestashop\Entities\Cart;
use izi\prestashop\Event\EventDispatcherInterface;
use izi\prestashop\Handler\CommandHandlerTrait;
use izi\prestashop\MerchantApi\Command\AddProductToBasketCommand;
use izi\prestashop\MerchantApi\Command\Basket\AddProductToCartCommand;
use izi\prestashop\MerchantApi\Command\Basket\CreateCartCommand;
use izi\prestashop\MerchantApi\Event\CartUpdatedEvent;
use izi\prestashop\MerchantApi\Exception\BasketNotFoundException;
use izi\prestashop\MerchantApi\Exception\OrderExistsException;
use izi\prestashop\MerchantApi\Model\Basket\Request\BasketEvent;
use izi\prestashop\MerchantApi\Model\Basket\Request\EventType;
use izi\prestashop\MerchantApi\Model\Basket\Response\IdentifiableBasket;
use izi\prestashop\Repository\BasketSessionRepositoryInterface;

final class AddProductToBasketHandler implements AddProductToBasketHandlerInterface
{
    use CommandHandlerTrait;

    /**
     * @var BasketSessionRepositoryInterface
     */
    private $repository;

    /**
     * @var ContextManager
     */
    private $contextManager;

    /**
     * @var CommandBusInterface
     */
    private $bus;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var BasketBuilderFactoryInterface
     */
    private $basketBuilderFactory;

    public function __construct(BasketSessionRepositoryInterface $repository, ContextManager $contextManager, CommandBusInterface $bus, EventDispatcherInterface $dispatcher, BasketBuilderFactoryInterface $basketBuilderFactory)
    {
        $this->repository = $repository;
        $this->contextManager = $contextManager;
        $this->bus = $bus;
        $this->dispatcher = $dispatcher;
        $this->basketBuilderFactory = $basketBuilderFactory;
    }

    public function __invoke(AddProductToBasketCommand $command): IdentifiableBasket
    {
        $basketId = $command->getRequest()->getBasketId();
        $session = $this->findOrCreateSession($basketId);

        if (!str_contains($productId = $command->getProductId(), '.')) {
            $productId = (int) $productId;
            $combinationId = null;
        } else {
            [$productId, $combinationId] = array_map('intval', explode('.', $productId));
        }

        $cart = $session->getBasket()->getEntity();

        try {
            $this->contextManager->changeContext($cart);

            $this->bus->handle(new AddProductToCartCommand($cart, $productId, $combinationId));
            $this->dispatcher->dispatch(new CartUpdatedEvent($cart));
            $this->updateSession($session);

            return $this->buildResponse($session);
        } finally {
            $this->contextManager->restoreContext();
        }
    }

    private function findOrCreateSession(?string $basketId): BasketSessionInterface
    {
        if (null === $basketId) {
            return $this->createNewSession();
        }

        if (null === $session = $this->repository->findByBasketId($basketId)) {
            throw BasketNotFoundException::create();
        }

        if (BasketSession::isFinalized($session)) {
            throw OrderExistsException::create();
        }

        return $session;
    }

    private function createNewSession(): BasketSessionInterface
    {
        /** @var Cart $cart */
        $cart = $this->bus->handle(new CreateCartCommand());

        $session = $this->repository->createNewSession($cart);
        $this->repository->persist($session);

        return $session;
    }

    /**
     * Needed to trigger Widget v1 view update.
     */
    private function updateSession(BasketSessionInterface $session): void
    {
        $now = new \DateTimeImmutable();

        $session->updatedBy(new BasketEvent(sprintf('AP_%d', $now->getTimestamp()), $now, EventType::ProductsQuantity()));
        $this->repository->persist($session);
    }

    private function buildResponse(BasketSessionInterface $session): IdentifiableBasket
    {
        $basket = $this->basketBuilderFactory
            ->createResponseBuilder($session->getBasket())
            ->build();

        return new IdentifiableBasket(
            $session->getBasketId(),
            $basket->getSummary(),
            $basket->getDelivery(),
            $basket->getProducts(),
            $basket->getConsents(),
            $basket->getPromoCodes(),
            $basket->getRelatedProducts()
        );
    }
}

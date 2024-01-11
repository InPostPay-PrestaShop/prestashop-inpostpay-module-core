<?php

declare(strict_types=1);

namespace izi\prestashop\Handler;

use izi\prestashop\BasketApp\Basket\BasketsApiClientInterface;
use izi\prestashop\BasketApp\Basket\Request\BindingRequest;
use izi\prestashop\BasketApp\Basket\Response\BasketBindingResponse;
use izi\prestashop\BasketApp\Exception\BasketAlreadyBoundException;
use izi\prestashop\BasketApp\Exception\BasketAppException;
use izi\prestashop\Builder\Basket\BasketBuilderFactoryInterface;
use izi\prestashop\Command\BindBasketCommand;
use izi\prestashop\Entities\BasketInterface;
use izi\prestashop\Entities\BasketSessionInterface;
use izi\prestashop\Handler\Result\BasketBindingResult;
use izi\prestashop\MerchantApi\Model\Basket\Request\BindingConfirmation;
use izi\prestashop\Repository\BasketSessionRepositoryInterface;

final class BindBasketHandler implements BindBasketHandlerInterface
{
    /**
     * @var BasketSessionRepositoryInterface
     */
    private $repository;

    /**
     * @var BasketsApiClientInterface
     */
    private $client;

    /**
     * @var BasketBuilderFactoryInterface
     */
    private $builderFactory;

    public function __construct(BasketSessionRepositoryInterface $repository, BasketsApiClientInterface $client, BasketBuilderFactoryInterface $builderFactory)
    {
        $this->repository = $repository;
        $this->client = $client;
        $this->builderFactory = $builderFactory;
    }

    public static function getHandledCommandClass(): string
    {
        return BindBasketCommand::class;
    }

    public function __invoke(BindBasketCommand $command): BasketBindingResult
    {
        $session = $this->findOrCreateSession($command->getBasket());
        $binding = $this->client->getBasketBinding($session->getBasketId(), $command->getBrowserId());

        if ($binding->isBasketLinked()) {
            return $this->saveConfirmation($command, $session, $binding);
        }

        if ($binding->isBrowserTrusted()) {
            return $this->upsertBasket($command, $session, $binding);
        }

        try {
            return $this->initBindingProcess($command, $session);
        } catch (BasketAlreadyBoundException $e) {
            $binding = $this->client->getBasketBinding($session->getBasketId(), $command->getBrowserId());

            return $this->saveConfirmation($command, $session, $binding);
        } catch (BasketAppException $e) {
            return BasketBindingResult::error($session->getBasketId(), $e->getError());
        }
    }

    private function findOrCreateSession(BasketInterface $basket): BasketSessionInterface
    {
        if (null === $session = $this->repository->findByEntityId($basket->getId())) {
            return $this->createNewSession($basket);
        }

        if (null !== $session->getOrderId()) {
            throw new \DomainException(sprintf('Basket %s was already finalized.', $basket->getId()));
        }

        return $session;
    }

    private function createNewSession(BasketInterface $basket): BasketSessionInterface
    {
        $session = $this->repository->createNewSession($basket);
        $this->repository->persist($session);

        return $session;
    }

    private function saveConfirmation(BindBasketCommand $command, BasketSessionInterface $session, BasketBindingResponse $binding): BasketBindingResult
    {
        $confirmation = BindingConfirmation::fromLinkedBasketBindingResponse($binding, $command->getBrowserId());
        $session->setBindingConfirmation($confirmation);
        $this->repository->persist($session);

        return BasketBindingResult::success($session->getBasketId());
    }

    private function upsertBasket(BindBasketCommand $command, BasketSessionInterface $session, BasketBindingResponse $binding): BasketBindingResult
    {
        $basket = $this->builderFactory
            ->createRequestBuilder($command->getBasket())
            ->setBrowserId($command->getBrowserId())
            ->build();

        $response = $this->client->upsertBasket($session->getBasketId(), $basket);
        $confirmation = BindingConfirmation::fromTrustedBrowserUpsertBasketResponse($command->getBrowserId(), $binding, $response);

        $session->setBindingConfirmation($confirmation);
        $this->repository->persist($session);

        return BasketBindingResult::success($session->getBasketId());
    }

    private function initBindingProcess(BindBasketCommand $command, BasketSessionInterface $session): BasketBindingResult
    {
        if (null !== $session->getBindingConfirmation()) {
            $session->unbind();
            $this->repository->persist($session);
        }

        return null === $command->getPhoneNumber()
            ? $this->bindByDeepLink($session->getBasketId(), $command)
            : $this->bindByPhoneNumber($session->getBasketId(), $command);
    }

    private function bindByDeepLink(string $basketId, BindBasketCommand $command): BasketBindingResult
    {
        $request = BindingRequest::byDeepLink($command->getBrowser(), $command->getBindingPlace());
        $qrCode = $this->client->bindBasketsByDeepLink($basketId, $request);

        return BasketBindingResult::qrCode($basketId, $qrCode);
    }

    private function bindByPhoneNumber(string $basketId, BindBasketCommand $command): BasketBindingResult
    {
        $request = BindingRequest::byPhoneNumber($command->getBrowser(), $command->getPhoneNumber(), $command->getBindingPlace());
        $this->client->bindBasketsByPhoneNumber($basketId, $request);

        return BasketBindingResult::success($basketId);
    }
}

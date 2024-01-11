<?php

declare(strict_types=1);

namespace izi\prestashop\Handler;

use izi\prestashop\BasketApp\Basket\BasketsApiClientInterface;
use izi\prestashop\BasketApp\Basket\Response\BasketBindingResponse;
use izi\prestashop\BasketApp\Basket\Response\ClientDetails;
use izi\prestashop\Command\GetClientDetailsCommand;
use izi\prestashop\Entities\BasketSessionInterface;
use izi\prestashop\MerchantApi\Model\Basket\Request\BindingConfirmation;
use izi\prestashop\Repository\BasketSessionRepositoryInterface;

final class GetClientDetailsHandler implements GetClientDetailsHandlerInterface
{
    /**
     * @var \Context
     */
    private $context;

    /**
     * @var BasketSessionRepositoryInterface
     */
    private $repository;

    /**
     * @var BasketsApiClientInterface
     */
    private $client;

    public function __construct(\Context $context, BasketSessionRepositoryInterface $repository, BasketsApiClientInterface $client)
    {
        $this->context = $context;
        $this->repository = $repository;
        $this->client = $client;
    }

    public static function getHandledCommandClass(): string
    {
        return GetClientDetailsCommand::class;
    }

    public function __invoke(GetClientDetailsCommand $command): ?ClientDetails
    {
        if (null === $session = $this->repository->findByEntityId($command->getBasketId())) {
            return null;
        }

        $binding = $this->client->getBasketBinding($session->getBasketId());

        if ($binding->isBasketLinked()) {
            $this->context->cookie->inpostizi_basket_id = $session->getBasketId();
            $this->saveConfirmation($session, $binding);
        } elseif ($session->isBasketBound()) {
            $session->unbind();
            $this->repository->persist($session);
        }

        return $binding->getClientDetails();
    }

    private function saveConfirmation(BasketSessionInterface $session, BasketBindingResponse $binding): void
    {
        $confirmation = $session->getBindingConfirmation();

        if (null !== $confirmation && $confirmation->getInPostBasketId() === $binding->getInPostBasketId()) {
            return;
        }

        $confirmation = BindingConfirmation::fromLinkedBasketBindingResponse($binding);
        $session->setBindingConfirmation($confirmation);
        $this->repository->persist($session);
    }
}

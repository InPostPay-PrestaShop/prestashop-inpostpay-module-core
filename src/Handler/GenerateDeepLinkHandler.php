<?php

declare(strict_types=1);

namespace izi\prestashop\Handler;

use izi\prestashop\BasketApp\Basket\BasketsApiClientInterface;
use izi\prestashop\BasketApp\Basket\Response\BasketBindingResponse;
use izi\prestashop\Command\GenerateDeepLinkCommand;
use izi\prestashop\Entities\BasketSessionInterface;
use izi\prestashop\Environment\EnvironmentInterface;
use izi\prestashop\Handler\Result\DeepLink;
use izi\prestashop\MerchantApi\Model\Basket\Request\BindingConfirmation;
use izi\prestashop\MerchantApi\Model\Basket\Request\BindingStatus;
use izi\prestashop\Repository\BasketSessionRepositoryInterface;

final class GenerateDeepLinkHandler implements GenerateDeepLinkHandlerInterface
{
    /**
     * @var EnvironmentInterface
     */
    private $environment;

    /**
     * @var BasketSessionRepositoryInterface
     */
    private $repository;

    /**
     * @var BasketsApiClientInterface
     */
    private $client;

    public function __construct(EnvironmentInterface $environment, BasketSessionRepositoryInterface $repository, BasketsApiClientInterface $client)
    {
        $this->environment = $environment;
        $this->repository = $repository;
        $this->client = $client;
    }

    public static function getHandledCommandClass(): string
    {
        return GenerateDeepLinkCommand::class;
    }

    public function __invoke(GenerateDeepLinkCommand $command): DeepLink
    {
        if (null === $basketId = $this->getInPostBasketId($command)) {
            throw new \DomainException('Basket is not linked.');
        }

        $uri = $this->buildUri($basketId);

        return new DeepLink($uri);
    }

    private function getInPostBasketId(GenerateDeepLinkCommand $command): ?string
    {
        if (null === $session = $this->repository->findByEntityId($command->getBasketId())) {
            return null;
        }

        $confirmation = $session->getBindingConfirmation();

        if (null !== $confirmation && BindingStatus::Success() === $confirmation->getStatus()) {
            return $confirmation->getInPostBasketId();
        }

        $binding = $this->client->getBasketBinding($session->getBasketId());

        if (!$binding->isBasketLinked()) {
            return null;
        }

        $this->saveConfirmation($session, $binding);

        return $binding->getInPostBasketId();
    }

    private function buildUri(string $inPostBasketId): string
    {
        $query = http_build_query(['basket_id' => $inPostBasketId], '', '&', PHP_QUERY_RFC3986);

        return http_build_url($this->environment->getDeepLinkUri(), $query, HTTP_URL_JOIN_QUERY);
    }

    private function saveConfirmation(BasketSessionInterface $session, BasketBindingResponse $binding): void
    {
        $confirmation = BindingConfirmation::fromLinkedBasketBindingResponse($binding);
        $session->setBindingConfirmation($confirmation);
        $this->repository->persist($session);
    }
}

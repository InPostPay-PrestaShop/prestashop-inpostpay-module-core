<?php

declare(strict_types=1);

namespace izi\prestashop\Analytics\Handler;

use izi\prestashop\Analytics\BasketAnalytics;
use izi\prestashop\Analytics\BasketAnalyticsRepositoryInterface;
use izi\prestashop\Analytics\Command\UpdateCartAnalyticsCommand;
use izi\prestashop\Handler\CommandHandlerTrait;

final class UpdateCartAnalyticsHandler implements UpdateCartAnalyticsHandlerInterface
{
    use CommandHandlerTrait;

    /**
     * @var BasketAnalyticsRepositoryInterface
     */
    private $repository;

    public function __construct(BasketAnalyticsRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    private function getNewFieldValue(?string $oldKey, ?string $newKey): ?string
    {
        if (null === $oldKey || $oldKey !== $newKey) {
            return $newKey;
        }

        return $oldKey;
    }

    public function __invoke(UpdateCartAnalyticsCommand $command)
    {
        $currentBasketRepository = $this->repository->find($command->getCartId());

        if (null === $currentBasketRepository) {
            $basketAnalytics = new BasketAnalytics(
                $command->getCartId(),
                $command->getBasketAnalytics()->getGclid(),
                $command->getBasketAnalytics()->getFbclid(),
                $command->getBasketAnalytics()->getClientId()
            );

            $this->repository->save($basketAnalytics);

            return;
        }

        $basketAnalytics = new BasketAnalytics(
            $command->getCartId(),
            $this->getNewFieldValue($currentBasketRepository->getGclid(), $command->getBasketAnalytics()->getGclid()),
            $this->getNewFieldValue($currentBasketRepository->getFbclid(), $command->getBasketAnalytics()->getFbclid()),
            $this->getNewFieldValue($currentBasketRepository->getClientId(), $command->getBasketAnalytics()->getClientId())
        );

        $this->repository->save($basketAnalytics);
    }
}

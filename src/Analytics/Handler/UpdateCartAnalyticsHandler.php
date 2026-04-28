<?php

declare(strict_types=1);

namespace izi\prestashop\Analytics\Handler;

use izi\prestashop\Analytics\BasketAnalytics;
use izi\prestashop\Analytics\BasketAnalyticsRepositoryInterface;
use izi\prestashop\Analytics\Command\UpdateCartAnalyticsCommand;
use izi\prestashop\Handler\CommandHandlerTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class UpdateCartAnalyticsHandler implements UpdateCartAnalyticsHandlerInterface
{
    use CommandHandlerTrait;

    /**
     * @var BasketAnalyticsRepositoryInterface
     */
    private $repository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(BasketAnalyticsRepositoryInterface $repository, ?LoggerInterface $logger = null)
    {
        $this->repository = $repository;
        $this->logger = $logger ?? new NullLogger();
    }

    public function __invoke(UpdateCartAnalyticsCommand $command): void
    {
        $parameters = BasketAnalytics::doGetParameters($command->getBasketAnalytics());

        if (null === $analytics = $this->repository->find($command->getCartId())) {
            $analytics = BasketAnalytics::fromParameters($command->getCartId(), $parameters);
        } elseif (!$analytics instanceof BasketAnalytics) {
            $parameters = array_merge(BasketAnalytics::doGetParameters($analytics), $parameters);
            $analytics = BasketAnalytics::fromParameters($command->getCartId(), $parameters);
        } else {
            $this->updateParameters($analytics, $parameters);
        }

        $this->repository->save($analytics);
    }

    private function updateParameters(BasketAnalytics $analytics, array $parameters): void
    {
        foreach ($parameters as $name => $value) {
            if ($analytics->setParameter($name, $value)) {
                continue;
            }

            $this->logger->warning('Unknown analytics parameter "{name}".', [
                'name' => $name,
            ]);
        }
    }
}

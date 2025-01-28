<?php

declare(strict_types=1);

namespace izi\prestashop\Product\Price;

use izi\prestashop\Common\Price;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Service\ResetInterface;

final class ErrorHandlingLowestPriceProvider implements BatchLowestPriceProviderInterface
{
    /**
     * @var LowestPriceProviderInterface
     */
    private $provider;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LowestPriceProviderInterface $provider, LoggerInterface $logger)
    {
        $this->provider = $provider;
        $this->logger = $logger;
    }

    public function preparePrices(LowestPriceQuery ...$queries): void
    {
        if (!$this->provider instanceof LowestPriceProviderInterface) {
            return;
        }

        try {
            $this->provider->preparePrices(...$queries);
        } catch (\Throwable $e) {
            $this->logError($e, __METHOD__);
        }
    }

    public function getPrice(LowestPriceQuery $query): ?Price
    {
        try {
            return $this->provider->getPrice($query);
        } catch (\Throwable $e) {
            $this->logError($e, __METHOD__);

            return null;
        }
    }

    public function reset(): void
    {
        if (!$this->provider instanceof ResetInterface) {
            return;
        }

        $this->provider->reset();
    }

    private function logError(\Throwable $e, string $method): void
    {
        $this->logger->error('Lowest price provider "{class}::{method}()" error: {exception}', [
            'class' => get_class($this->provider),
            'method' => $method,
            'exception' => $e,
        ]);
    }
}

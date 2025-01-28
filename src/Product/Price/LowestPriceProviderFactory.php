<?php

declare(strict_types=1);

namespace izi\prestashop\Product\Price;

use izi\prestashop\Module\ModuleRepository;
use Psr\Log\LoggerInterface;

class LowestPriceProviderFactory
{
    /**
     * @var ModuleRepository
     */
    private $moduleRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(ModuleRepository $moduleRepository, LoggerInterface $logger)
    {
        $this->moduleRepository = $moduleRepository;
        $this->logger = $logger;
    }

    public function create(): LowestPriceProviderInterface
    {
        return $this->createX13Provider() ?? new NullLowestPriceProvider();
    }

    private function createX13Provider(): ?LowestPriceProviderInterface
    {
        $module = $this->moduleRepository->findByName('x13pricehistory');

        if (null === $module || !$module->active) {
            return null;
        }

        if (null === $priceProvider = X13PriceHistoryLowestPriceProvider::create($module)) {
            return null;
        }

        return $this->createErrorHandlingProvider($priceProvider);
    }

    private function createErrorHandlingProvider(LowestPriceProviderInterface $provider): ErrorHandlingLowestPriceProvider
    {
        return new ErrorHandlingLowestPriceProvider($provider, $this->logger);
    }
}

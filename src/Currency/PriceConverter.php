<?php

declare(strict_types=1);

namespace izi\prestashop\Currency;

use izi\prestashop\Configuration\PrestaShopConfiguration;
use izi\prestashop\ObjectModel\Repository\ObjectRepositoryInterface;

final class PriceConverter implements PriceConverterInterface
{
    /**
     * @var ObjectRepositoryInterface<\Currency>
     */
    private $repository;

    /**
     * @var PrestaShopConfiguration
     */
    private $configuration;

    /**
     * @param ObjectRepositoryInterface<\Currency> $repository
     */
    public function __construct(ObjectRepositoryInterface $repository, PrestaShopConfiguration $configuration)
    {
        $this->repository = $repository;
        $this->configuration = $configuration;
    }

    public function convert(float $amount, \Currency $target, ?\Currency $from = null): float
    {
        if (null === $from) {
            $from = $this->getCurrency();
        }

        if ((int) $target->id === (int) $from->id) {
            return $amount;
        }

        return \Tools::convertPriceFull($amount, $from, $target);
    }

    public function convertByIds(float $amount, int $targetCurrencyId, ?int $fromCurrencyId = null): float
    {
        if ($fromCurrencyId === $targetCurrencyId) {
            return $amount;
        }

        $from = $this->getCurrency($fromCurrencyId);
        $target = $this->getCurrency($targetCurrencyId);

        return $this->convert($amount, $target, $from);
    }

    private function getCurrency(?int $currencyId = null): \Currency
    {
        if (null === $currencyId) {
            $currencyId = $this->configuration->getDefaultCurrencyId();
        }

        if (null !== $currency = $this->repository->find($currencyId)) {
            return $currency;
        }

        throw new \RuntimeException(sprintf('Currency %d does not exist.', $currencyId));
    }
}

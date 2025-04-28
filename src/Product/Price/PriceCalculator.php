<?php

declare(strict_types=1);

namespace izi\prestashop\Product\Price;

use izi\prestashop\Builder\PriceFactory;
use izi\prestashop\Common\Currency;
use izi\prestashop\Common\Price;
use izi\prestashop\Configuration\PrestaShopConfiguration;
use izi\prestashop\ObjectModel\Repository\CurrencyRepository;
use izi\prestashop\ObjectModel\Repository\ObjectRepositoryInterface;

final class PriceCalculator implements PriceCalculatorInterface
{
    /**
     * @var PrestaShopConfiguration
     */
    private $configuration;

    /**
     * @var CurrencyRepository
     */
    private $currencyRepository;

    /**
     * @var ObjectRepositoryInterface
     */
    private $countryRepository;

    /**
     * @var array<int, int> country IDs by shop ID
     */
    private $countryIds = [];

    /**
     * @param CurrencyRepository $currencyRepository
     * @param ObjectRepositoryInterface<\Country> $countryRepository
     */
    public function __construct(PrestaShopConfiguration $configuration, ObjectRepositoryInterface $currencyRepository, ObjectRepositoryInterface $countryRepository)
    {
        $this->configuration = $configuration;
        $this->currencyRepository = $currencyRepository;
        $this->countryRepository = $countryRepository;
    }

    public function calculatePrice(PriceQuery $query): ?Price
    {
        $parameters = $this->getCalculationParameters(
            $query->getCurrency(),
            $query->getShopId()
        );

        $net = $this->getPrice($query, false, $parameters);
        $gross = $this->getPrice($query, true, $parameters);

        return PriceFactory::create($net, $gross);
    }

    public function getCalculationParameters(Currency $currency, ?int $shopId = null): CalculationParameters
    {
        $shopId = $shopId ?? $this->configuration->getDefaultShopId();

        $currencyId = $this->getCurrencyId($currency->value, $shopId);
        $countryId = $this->getCountryId($shopId);
        $groupId = $this->configuration->getAnonymousCustomerGroupId($shopId);

        return new CalculationParameters($shopId, $currencyId, $countryId, $groupId);
    }

    private function getPrice(PriceQuery $query, bool $withTax, CalculationParameters $parameters): float
    {
        return (float) \Product::priceCalculation(
            $query->getShopId(),
            $query->getProductId(),
            $query->getCombinationId(),
            $parameters->getCountryId(),
            0,
            '',
            $parameters->getCurrencyId(),
            $parameters->getCustomerGroupId(),
            1,
            $withTax,
            6,
            false,
            true,
            true,
            $specificPrice,
            true
        );
    }

    private function getCurrencyId(string $isoCode, int $shopId): int
    {
        $currency = $this->currencyRepository->findOneByIsoCode($isoCode, $shopId);

        if (null === $currency) {
            throw new \RuntimeException('Currency not found.');
        }

        return (int) $currency->id;
    }

    private function getCountryId(int $shopId): int
    {
        if (isset($this->countryIds[$shopId])) {
            return $this->countryIds[$shopId];
        }

        $country = $this->countryRepository->findOneBy([
            'iso_code' => 'PL',
            'id_shop' => $shopId,
        ], ['active' => 'DESC']);

        if (null !== $country) {
            return $this->countryIds[$shopId] = (int) $country->id;
        }

        return $this->countryIds[$shopId] = $this->configuration->getDefaultCountryId($shopId);
    }
}

<?php

namespace izi\prestashop\Product\Price;

use izi\prestashop\Builder\PriceFactory;
use izi\prestashop\Common\Price;

/**
 * Adapter for "x13pricehistory" module version ^1.5.0.
 *
 * @phpstan-type ProductId array{id_product: int, id_product_attribute?: int|null}
 * @phpstan-type PriceInfo array{
 *     id_product: int,
 *     id_product_attribute: int,
 *     is_default_combination: bool,
 *     reduction_type: "percentage"|"amount",
 *     product_price_amount: float,
 *     lowest_price: string,
 *     lowest_price_amount: float,
 *     product_price: string,
 *     real_discount_value: string,
 * }
 * @phpstan-type BatchLowestPriceProvider object{
 *     getPricesForProductList: callable(
 *         ProductId[] $productIds,
 *         int|null $shopId,
 *         int|null $currencyId,
 *         int|null $countryId,
 *         int|null $customerGroupId,
 *         bool $useTax,
 *     ): array<int, array<int, PriceInfo>>
 * }
 */
final class X13PriceHistoryLowestPriceProvider implements BatchLowestPriceProviderInterface
{
    /**
     * @var BatchLowestPriceProvider
     */
    private $priceProvider;

    /**
     * @var array<int, array<int, Price>>|null prices by product and combination ID
     */
    private $prices;

    /**
     * @param BatchLowestPriceProvider $priceProvider
     */
    public function __construct($priceProvider)
    {
        $this->priceProvider = $priceProvider;
    }

    /**
     * @param \X13PriceHistory|\Module $module
     */
    public static function create(\Module $module): ?self
    {
        if (!property_exists($module, 'batchLowestPriceProvider')) {
            return null;
        }

        /** @var \x13pricehistory\Providers\BatchLowestPriceProvider $priceProvider */
        $priceProvider = $module->batchLowestPriceProvider;

        if (!\is_callable([$priceProvider, 'getPricesForProductList'])) {
            return null;
        }

        return new self($priceProvider);
    }

    public function preparePrices(LowestPriceQuery ...$queries): void
    {
        if ([] === $queries) {
            $this->prices = [];

            return;
        }

        $this->prices = $this->doGetPrices(...$queries);
    }

    public function getPrice(LowestPriceQuery $query): ?Price
    {
        $prices = $this->prices ?? $this->doGetPrices($query);

        $productId = $query->getProductId();
        $combinationId = (int) $query->getCombinationId();

        return $prices[$productId][$combinationId] ?? null;
    }

    public function reset(): void
    {
        $this->prices = null;
    }

    /**
     * @return array<int, array<int, Price>> prices by product and combination ID
     */
    private function doGetPrices(LowestPriceQuery ...$queries): array
    {
        $groupedQueries = [];
        array_map(function ($query) use (&$groupedQueries) {
            $groupedQueries[$query->getShopId()][] = $query;
        }, $queries);

        $prices = [];
        foreach ($groupedQueries as $idShop => $shopQueries) {
            $productIds = array_map([$this, 'getProductId'], $shopQueries);
            $query = current($shopQueries);

            $prices += $this->getPricesForShop($idShop, $productIds, $query);
        }

        return $prices;
    }

    private function getPricesForShop(int $idShop, array $productIds, LowestPriceQuery $query)
    {
        $args = [
            $productIds,
            $idShop,
            $query->getCurrencyId(),
            $query->getCountryId(),
            $query->getCustomerGroupId(),
            false,
        ];

        if ([] === $netPrices = $this->priceProvider->getPricesForProductList(...$args)) {
            return [];
        }

        $args[5] = true;
        $grossPrices = $this->priceProvider->getPricesForProductList(...$args);

        $prices = [];

        foreach ($productIds as $product) {
            $productId = $product['id_product'];
            $combinationId = $product['id_product_attribute'];

            if (null === $netPrice = $netPrices[$productId][$combinationId] ?? $netPrices[$productId][0] ?? null) {
                continue;
            }

            if (null === $grossPrice = $grossPrices[$productId][$combinationId] ?? $grossPrices[$productId][0] ?? null) {
                continue;
            }

            if (!isset($netPrice['lowest_price_amount'], $grossPrice['lowest_price_amount'])) {
                continue;
            }

            $prices[$productId][$combinationId] = PriceFactory::create(
                (float) $netPrice['lowest_price_amount'],
                (float) $grossPrice['lowest_price_amount']
            );
        }

        return $prices;
    }

    private function getProductId(LowestPriceQuery $query): array
    {
        return [
            'id_product' => $query->getProductId(),
            'id_product_attribute' => (int) $query->getCombinationId(),
        ];
    }
}

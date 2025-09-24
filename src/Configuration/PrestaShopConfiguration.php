<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration;

class PrestaShopConfiguration
{
    public const DEFAULT_SHOP_ID = 'PS_SHOP_DEFAULT';
    public const DEFAULT_CURRENCY_ID = 'PS_CURRENCY_DEFAULT';
    public const DEFAULT_COUNTRY_ID = 'PS_COUNTRY_DEFAULT';
    public const DEFAULT_LANGUAGE_ID = 'PS_LANGUAGE_DEFAULT';
    public const TAX_ADDRESS_TYPE = 'PS_TAX_ADDRESS_TYPE';
    public const FREE_DELIVERY_MIN_AMOUNT = 'PS_SHIPPING_FREE_PRICE';
    public const SHIPPING_HANDLING_COST = 'PS_SHIPPING_HANDLING';
    public const ATTRIBUTES_SEPARATOR = 'PS_ATTRIBUTE_ANCHOR_SEPARATOR';
    public const ANONYMOUS_CUSTOMER_GROUP_ID = 'PS_UNIDENTIFIED_GROUP';
    public const GIFT_WRAPPING = 'PS_GIFT_WRAPPING';

    /**
     * @var LanguageAwareConfigurationInterface
     */
    private $configuration;

    public function __construct(LanguageAwareConfigurationInterface $configuration)
    {
        $this->configuration = $configuration;
    }

    public function getDefaultShopId(): int
    {
        return (int) $this->configuration->get(self::DEFAULT_SHOP_ID);
    }

    public function getDefaultCurrencyId(?int $shopId = null): int
    {
        return (int) $this->configuration->get(self::DEFAULT_CURRENCY_ID, $shopId);
    }

    public function getDefaultCountryId(?int $shopId = null): int
    {
        return (int) $this->configuration->get(self::DEFAULT_COUNTRY_ID, $shopId);
    }

    public function getDefaultLanguageId(?int $shopId = null): int
    {
        return (int) $this->configuration->get(self::DEFAULT_LANGUAGE_ID, $shopId);
    }

    public function getTaxAddressType(?int $shopId = null): string
    {
        $value = $this->configuration->get(self::TAX_ADDRESS_TYPE, $shopId);

        if (in_array($value, ['id_address_delivery', 'id_address_invoice'], true)) {
            return $value;
        }

        return 'id_address_delivery';
    }

    public function getFreeDeliveryMinAmount(?int $shopId = null): float
    {
        return (float) $this->configuration->get(self::FREE_DELIVERY_MIN_AMOUNT, $shopId);
    }

    public function getShippingHandlingCost(?int $shopId = null): float
    {
        return (float) $this->configuration->get(self::SHIPPING_HANDLING_COST, $shopId);
    }

    public function getAttributesSeparator(?int $shopId = null): string
    {
        return (string) $this->configuration->get(self::ATTRIBUTES_SEPARATOR, $shopId);
    }

    public function getAnonymousCustomerGroupId(?int $shopId = null): int
    {
        return (int) $this->configuration->get(self::ANONYMOUS_CUSTOMER_GROUP_ID, $shopId);
    }

    public function isGiftWrappingEnabled(?int $shopId = null): bool
    {
        return (bool) $this->configuration->get(self::GIFT_WRAPPING, $shopId);
    }
}

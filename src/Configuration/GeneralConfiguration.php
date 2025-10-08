<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration;

use izi\prestashop\Hook\Front\DisplayCheckoutSummaryTop;
use izi\prestashop\Hook\Front\DisplayPaymentReturn;
use izi\prestashop\Hook\Front\DisplayProductActions;

/**
 * @implements PersistentConfigurationInterface<GeneralConfigurationInterface&PromoCodesConfigurationInterface>
 */
final class GeneralConfiguration implements GeneralConfigurationInterface, PromoCodesConfigurationInterface, PersistentConfigurationInterface
{
    private const ENABLE_FOR_EVERYONE = 'INPOST_PAY_show_izi';
    private const MAX_SUGGESTED_PRODUCTS = 'INPOST_PAY_related_count';
    private const THANK_YOU_DISPLAY_HOOK = 'INPOST_PAY_THANK_YOU_DISPLAY';
    private const PRODUCT_CARD_DISPLAY_HOOK = 'INPOST_PAY_PRODUCT_CARD_DISPLAY_HOOK';
    private const CHECKOUT_BUTTON_DISPLAY_HOOK = 'INPOST_PAY_CHECKOUT_DISPLAY_HOOK';
    private const FULL_PAGE_CACHE_MODULE_IN_USE = 'INPOST_PAY_FULL_PAGE_CACHE_MODULE_IN_USE';
    private const SEND_ANALYTICS_DATA = 'INPOST_PAY_SEND_ANALYTICS_DATA';
    private const WIDGET_SPLIT_BOUND = 'INPOST_PAY_WIDGET_SPLIT_BOUND';
    private const DEFAULT_PROMOTION_DETAILS_PAGE_ID = 'INPOST_PAY_DEFAULT_PROMO_DETAILS_CMS_ID';

    /**
     * @var ShopAwareConfigurationInterface
     */
    private $configuration;

    public function __construct(ShopAwareConfigurationInterface $configuration)
    {
        $this->configuration = $configuration;
    }

    public function isEnabledForEveryone(): bool
    {
        return (bool) $this->configuration->get(self::ENABLE_FOR_EVERYONE);
    }

    public function getMaxSuggestedProducts(?int $shopId = null): ?int
    {
        $value = $this->configuration->get(self::MAX_SUGGESTED_PRODUCTS, $shopId);

        return null === $value ? $value : (int) $value;
    }

    public function getThankYouDisplayHook(?int $shopId = null): string
    {
        $hook = $this->configuration->get(self::THANK_YOU_DISPLAY_HOOK, $shopId);

        return $hook ?? DisplayPaymentReturn::HOOK_NAME;
    }

    public function getProductCardDisplayHook(?int $shopId = null): string
    {
        $hook = $this->configuration->get(self::PRODUCT_CARD_DISPLAY_HOOK, $shopId);

        return $hook ?? DisplayProductActions::HOOK_NAME;
    }

    public function getCheckoutButtonDisplayHook(?int $shopId = null): string
    {
        $hook = $this->configuration->get(self::CHECKOUT_BUTTON_DISPLAY_HOOK, $shopId);

        return $hook ?? DisplayCheckoutSummaryTop::HOOK_NAME;
    }

    public function isFullPageCacheModuleInUse(?int $shopId = null): bool
    {
        return (bool) $this->configuration->get(self::FULL_PAGE_CACHE_MODULE_IN_USE, $shopId);
    }

    public function isSendAnalyticsData(?int $shopId = null): bool
    {
        return (bool) $this->configuration->get(self::SEND_ANALYTICS_DATA, $shopId);
    }

    public function isWidgetSplitBoundEnabled(?int $shopId = null): bool
    {
        return (bool) $this->configuration->get(self::WIDGET_SPLIT_BOUND, $shopId);
    }

    public function getDefaultPromoDetailsPageId(?int $shopId = null): ?int
    {
        $value = $this->configuration->get(self::DEFAULT_PROMOTION_DETAILS_PAGE_ID, $shopId);

        return null === $value ? null : (int) $value;
    }

    /**
     * @return GeneralConfigurationInterface&PromoCodesConfigurationInterface
     */
    public function copy(): GeneralConfigurationInterface
    {
        $configuration = new DTO\GeneralConfiguration(
            $this->isEnabledForEveryone(),
            $this->getMaxSuggestedProducts(),
            $this->getThankYouDisplayHook(),
            $this->getProductCardDisplayHook(),
            $this->getCheckoutButtonDisplayHook(),
            $this->isFullPageCacheModuleInUse(),
            $this->isSendAnalyticsData(),
            $this->isWidgetSplitBoundEnabled()
        );

        return $configuration->setDefaultPromoDetailsPageId($this->getDefaultPromoDetailsPageId());
    }

    public function persist(GeneralConfigurationInterface $configuration): void
    {
        $this->configuration->set(self::ENABLE_FOR_EVERYONE, $configuration->isEnabledForEveryone());
        $this->configuration->set(self::MAX_SUGGESTED_PRODUCTS, $configuration->getMaxSuggestedProducts());
        $this->configuration->set(self::THANK_YOU_DISPLAY_HOOK, $configuration->getThankYouDisplayHook());
        $this->configuration->set(self::PRODUCT_CARD_DISPLAY_HOOK, $configuration->getProductCardDisplayHook());
        $this->configuration->set(self::CHECKOUT_BUTTON_DISPLAY_HOOK, $configuration->getCheckoutButtonDisplayHook());
        $this->configuration->set(self::FULL_PAGE_CACHE_MODULE_IN_USE, $configuration->isFullPageCacheModuleInUse());
        $this->configuration->set(self::SEND_ANALYTICS_DATA, $configuration->isSendAnalyticsData());
        $this->configuration->set(self::WIDGET_SPLIT_BOUND, $configuration->isWidgetSplitBoundEnabled());

        if ($configuration instanceof PromoCodesConfigurationInterface) {
            $this->configuration->set(self::DEFAULT_PROMOTION_DETAILS_PAGE_ID, $configuration->getDefaultPromoDetailsPageId());
        }
    }
}

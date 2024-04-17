<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration;

use izi\prestashop\Hook\Front\DisplayCheckoutSummaryTop;
use izi\prestashop\Hook\Front\DisplayProductActions;
use izi\prestashop\Hook\Front\DisplayProductAdditionalInfo;

final class GeneralConfiguration implements GeneralConfigurationInterface, PersistentConfigurationInterface
{
    private const ENABLE_FOR_EVERYONE = 'INPOST_PAY_show_izi';
    private const MAX_SUGGESTED_PRODUCTS = 'INPOST_PAY_related_count';
    private const THANK_YOU_DISPLAY_HOOK = 'INPOST_PAY_THANK_YOU_DISPLAY';
    private const PRODUCT_CARD_DISPLAY_HOOK = 'INPOST_PAY_PRODUCT_CARD_DISPLAY_HOOK';
    private const CHECKOUT_BUTTON_DISPLAY_HOOK = 'INPOST_PAY_PRODUCT_CARD_DISPLAY_HOOK';

    /**
     * @var ConfigurationInterface
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

    public function getThankYouDisplayHook(?int $shopId = null): ?string
    {
        return $this->configuration->get(self::THANK_YOU_DISPLAY_HOOK, $shopId);
    }

    public function getProductCardDisplayHook(?int $shopId = null): ?string
    {
        $hook = $this->configuration->get(self::PRODUCT_CARD_DISPLAY_HOOK, $shopId);

        if (null === $hook) {
            if (!DisplayProductActions::getVersionRange()->contains(_PS_VERSION_)) {
                $hook = DisplayProductAdditionalInfo::HOOK_NAME;
            } else {
                $hook = DisplayProductActions::HOOK_NAME;
            }
        }

        return $hook;
    }

    public function getCheckoutButtonDisplayHook(?int $shopId = null): ?string
    {
        $hook = $this->configuration->get(self::CHECKOUT_BUTTON_DISPLAY_HOOK, $shopId);

        if (null === $hook) {
            $hook = DisplayCheckoutSummaryTop::HOOK_NAME;
        }

        return $hook;
    }

    public function copy(): GeneralConfigurationInterface
    {
        return new DTO\GeneralConfiguration(
            $this->isEnabledForEveryone(),
            $this->getMaxSuggestedProducts(),
            $this->getThankYouDisplayHook(),
            $this->getProductCardDisplayHook(),
            $this->getCheckoutButtonDisplayHook()
        );
    }

    public function persist(GeneralConfigurationInterface $configuration): void
    {
        $this->configuration->set(self::ENABLE_FOR_EVERYONE, $configuration->isEnabledForEveryone());
        $this->configuration->set(self::MAX_SUGGESTED_PRODUCTS, $configuration->getMaxSuggestedProducts());
        $this->configuration->set(self::THANK_YOU_DISPLAY_HOOK, $configuration->getThankYouDisplayHook());
        $this->configuration->set(self::PRODUCT_CARD_DISPLAY_HOOK, $configuration->getProductCardDisplayHook());
        $this->configuration->set(self::CHECKOUT_BUTTON_DISPLAY_HOOK, $configuration->getCheckoutButtonDisplayHook());
    }
}

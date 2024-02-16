<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration;

final class GeneralConfiguration implements GeneralConfigurationInterface, PersistentConfigurationInterface
{
    private const ENABLE_FOR_EVERYONE = 'INPOST_PAY_show_izi';
    private const MAX_SUGGESTED_PRODUCTS = 'INPOST_PAY_related_count';
    private const THANK_YOU_DISPLAY_HOOK = 'INPOST_PAY_THANK_YOU_DISPLAY';

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

    public function getMaxSuggestedProducts(int $shopId = null): ?int
    {
        $value = $this->configuration->get(self::MAX_SUGGESTED_PRODUCTS, $shopId);

        return null === $value ? $value : (int) $value;
    }

    public function getThankYouDisplayHook(int $shopId = null): ?string
    {
        return $this->configuration->get(self::THANK_YOU_DISPLAY_HOOK, $shopId);
    }

    public function copy(): GeneralConfigurationInterface
    {
        return new DTO\GeneralConfiguration(
            $this->isEnabledForEveryone(),
            $this->getMaxSuggestedProducts(),
            $this->getThankYouDisplayHook()
        );
    }

    public function persist(GeneralConfigurationInterface $configuration): void
    {
        $this->configuration->set(self::ENABLE_FOR_EVERYONE, $configuration->isEnabledForEveryone());
        $this->configuration->set(self::MAX_SUGGESTED_PRODUCTS, $configuration->getMaxSuggestedProducts());
        $this->configuration->set(self::THANK_YOU_DISPLAY_HOOK, $configuration->getThankYouDisplayHook());
    }
}

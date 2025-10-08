<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration\DTO;

use izi\prestashop\Configuration\GeneralConfigurationInterface;
use izi\prestashop\Configuration\PromoCodesConfigurationInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class GeneralConfiguration implements GeneralConfigurationInterface, PromoCodesConfigurationInterface
{
    /**
     * @var bool
     */
    private $enabledForEveryone;

    /**
     * @var int|null
     *
     * @Assert\GreaterThanOrEqual(0)
     */
    private $maxSuggestedProducts;

    /**
     * @var string
     *
     * @Assert\NotBlank
     */
    private $thankYouDisplayHook;

    /**
     * @var string
     *
     * @Assert\NotBlank
     */
    private $productCardDisplayHook;

    /**
     * @var string
     *
     * @Assert\NotBlank
     */
    private $checkoutButtonDisplayHook;

    /**
     * @var bool
     */
    private $fullPageCacheModuleInUse;

    /**
     * @var bool
     */
    private $sendAnalyticsData;

    /**
     * @var bool
     */
    private $widgetSplitBoundEnabled;

    /**
     * @var int|null ID of {@see \CMS}
     *
     * @Assert\GreaterThan(0)
     */
    private $defaultPromoDetailsPageId;

    public function __construct(
        bool $enabledForEveryone = false,
        ?int $maxSuggestedProducts = null,
        ?string $thankYouDisplayHook = null,
        ?string $productCardDisplayHook = null,
        ?string $checkoutButtonDisplayHook = null,
        bool $fullPageCacheModuleInUse = false,
        bool $sendAnalyticsData = false,
        bool $widgetSplitBoundEnabled = false
    ) {
        $this->enabledForEveryone = $enabledForEveryone;
        $this->maxSuggestedProducts = $maxSuggestedProducts;
        $this->thankYouDisplayHook = $thankYouDisplayHook;
        $this->productCardDisplayHook = $productCardDisplayHook;
        $this->checkoutButtonDisplayHook = $checkoutButtonDisplayHook;
        $this->fullPageCacheModuleInUse = $fullPageCacheModuleInUse;
        $this->sendAnalyticsData = $sendAnalyticsData;
        $this->widgetSplitBoundEnabled = $widgetSplitBoundEnabled;
    }

    public function isEnabledForEveryone(): bool
    {
        return $this->enabledForEveryone;
    }

    public function setEnabledForEveryone(bool $enabledForEveryone): GeneralConfiguration
    {
        $this->enabledForEveryone = $enabledForEveryone;

        return $this;
    }

    public function getMaxSuggestedProducts(?int $shopId = null): ?int
    {
        return $this->maxSuggestedProducts;
    }

    public function setMaxSuggestedProducts(?int $maxSuggestedProducts): GeneralConfiguration
    {
        $this->maxSuggestedProducts = $maxSuggestedProducts;

        return $this;
    }

    public function getThankYouDisplayHook(?int $shopId = null): ?string
    {
        return $this->thankYouDisplayHook;
    }

    public function setThankYouDisplayHook(?string $thankYouDisplayHook): GeneralConfiguration
    {
        $this->thankYouDisplayHook = $thankYouDisplayHook;

        return $this;
    }

    public function getProductCardDisplayHook(?int $shopId = null): ?string
    {
        return $this->productCardDisplayHook;
    }

    public function setProductCardDisplayHook(?string $productCardDisplayHook): GeneralConfiguration
    {
        $this->productCardDisplayHook = $productCardDisplayHook;

        return $this;
    }

    public function getCheckoutButtonDisplayHook(?int $shopId = null): ?string
    {
        return $this->checkoutButtonDisplayHook;
    }

    public function setCheckoutButtonDisplayHook(?string $checkoutButtonDisplayHook): GeneralConfiguration
    {
        $this->checkoutButtonDisplayHook = $checkoutButtonDisplayHook;

        return $this;
    }

    public function isFullPageCacheModuleInUse(?int $shopId = null): bool
    {
        return $this->fullPageCacheModuleInUse;
    }

    public function setFullPageCacheModuleInUse(bool $fullPageCacheModuleInUse): GeneralConfiguration
    {
        $this->fullPageCacheModuleInUse = $fullPageCacheModuleInUse;

        return $this;
    }

    public function isSendAnalyticsData(?int $shopId = null): bool
    {
        return $this->sendAnalyticsData;
    }

    public function setSendAnalyticsData(bool $sendAnalyticsData): GeneralConfiguration
    {
        $this->sendAnalyticsData = $sendAnalyticsData;

        return $this;
    }

    public function getDefaultPromoDetailsPageId(?int $shopId = null): ?int
    {
        return $this->defaultPromoDetailsPageId;
    }

    /**
     * @param int|null $cmsId ID of {@see \CMS}
     *
     * @return $this
     */
    public function setDefaultPromoDetailsPageId(?int $cmsId): self
    {
        $this->defaultPromoDetailsPageId = $cmsId;

        return $this;
    }

    public function isWidgetSplitBoundEnabled(?int $shopId = null): bool
    {
        return $this->widgetSplitBoundEnabled;
    }

    public function setWidgetSplitBoundEnabled(bool $widgetSplitBoundEnabled): GeneralConfiguration
    {
        $this->widgetSplitBoundEnabled = $widgetSplitBoundEnabled;

        return $this;
    }
}

<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration\DTO;

use izi\prestashop\Configuration\GeneralConfigurationInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class GeneralConfiguration implements GeneralConfigurationInterface
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

    public function __construct(
        bool $enabledForEveryone = false,
        int $maxSuggestedProducts = null,
        string $thankYouDisplayHook = null,
        string $productCardDisplayHook = null
    )
    {
        $this->enabledForEveryone = $enabledForEveryone;
        $this->maxSuggestedProducts = $maxSuggestedProducts;
        $this->thankYouDisplayHook = $thankYouDisplayHook;
        $this->productCardDisplayHook = $productCardDisplayHook;
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

    public function getMaxSuggestedProducts(int $shopId = null): ?int
    {
        return $this->maxSuggestedProducts;
    }

    public function setMaxSuggestedProducts(?int $maxSuggestedProducts): GeneralConfiguration
    {
        $this->maxSuggestedProducts = $maxSuggestedProducts;

        return $this;
    }

    public function getThankYouDisplayHook(int $shopId = null): ?string
    {
        return $this->thankYouDisplayHook;
    }

    public function setThankYouDisplayHook(?string $thankYouDisplayHook): GeneralConfiguration
    {
        $this->thankYouDisplayHook = $thankYouDisplayHook;

        return $this;
    }

    public function getProductCardDisplayHook(int $shopId = null): ?string
    {
        return $this->productCardDisplayHook;
    }

    public function setProductCardDisplayHook(?string $productCardDisplayHook): GeneralConfiguration
    {
        $this->productCardDisplayHook = $productCardDisplayHook;

        return $this;
    }
}

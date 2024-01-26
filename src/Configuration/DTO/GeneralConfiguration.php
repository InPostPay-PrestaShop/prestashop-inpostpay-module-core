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

    public function __construct(bool $enabledForEveryone = false, int $maxSuggestedProducts = null)
    {
        $this->enabledForEveryone = $enabledForEveryone;
        $this->maxSuggestedProducts = $maxSuggestedProducts;
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
}

<?php

declare(strict_types=1);

namespace izi\prestashop\PromoCode;

class CartRuleOptions
{
    /**
     * @var int ID of {@see \CartRule}
     */
    private $cartRuleId;

    /**
     * @var bool
     */
    private $isOmnibus = false;

    /**
     * @var int|null ID of {@see \CMS}
     */
    private $promoDetailsPageId;

    public function __construct(int $cartRuleId)
    {
        if (0 >= $cartRuleId) {
            throw new \DomainException('Cart rule ID must be greater than 0.');
        }

        $this->cartRuleId = $cartRuleId;
    }

    public function getCartRuleId(): int
    {
        return $this->cartRuleId;
    }

    public function isOmnibus(): bool
    {
        return $this->isOmnibus;
    }

    public function setIsOmnibus(bool $isOmnibus): self
    {
        $this->isOmnibus = $isOmnibus;

        return $this;
    }

    /**
     * @return int|null ID of {@see \CMS}
     */
    public function getPromoDetailsPageId(): ?int
    {
        return $this->promoDetailsPageId;
    }

    /**
     * @param int|null $cmsId ID of {@see \CMS}
     */
    public function setPromoDetailsPageId(?int $cmsId): self
    {
        if (null !== $cmsId && 0 >= $cmsId) {
            throw new \DomainException('CMS page ID must be greater than 0.');
        }

        $this->promoDetailsPageId = $cmsId;

        return $this;
    }
}

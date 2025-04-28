<?php

declare(strict_types=1);

namespace izi\prestashop\Command\Config;

use izi\prestashop\Handler\Config\UpdateCartRuleOptionsHandler;
use izi\prestashop\PromoCode\CartRuleOptions;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @see UpdateCartRuleOptionsHandler
 */
final class UpdateCartRuleOptionsCommand
{
    /**
     * @var int
     *
     * @Assert\GreaterThan(0)
     */
    private $cartRuleId;

    /**
     * @var bool|null
     *
     * @Assert\NotNull()
     */
    private $omnibus;

    /**
     * @var int|null
     *
     * @Assert\GreaterThan(0)
     */
    private $promoDetailsPageId;

    public function __construct(int $cartRuleId, ?bool $isOmnibus = false)
    {
        $this->cartRuleId = $cartRuleId;
        $this->omnibus = $isOmnibus;
    }

    public static function for(CartRuleOptions $options): self
    {
        return (new self($options->getCartRuleId()))
            ->setOmnibus($options->isOmnibus())
            ->setPromoDetailsPageId($options->getPromoDetailsPageId());
    }

    public function getCartRuleId(): int
    {
        return $this->cartRuleId;
    }

    public function isOmnibus(): ?bool
    {
        return $this->omnibus;
    }

    public function setOmnibus(?bool $isOmnibus): self
    {
        $this->omnibus = $isOmnibus;

        return $this;
    }

    public function getPromoDetailsPageId(): ?int
    {
        return $this->promoDetailsPageId;
    }

    public function setPromoDetailsPageId(?int $cmsId): self
    {
        $this->promoDetailsPageId = $cmsId;

        return $this;
    }
}

<?php

declare(strict_types=1);

namespace izi\prestashop\Command\Config;

use izi\prestashop\Handler\Config\UpdateCartRuleOptionsHandler;
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

    public function __construct(int $cartRuleId, ?bool $isOmnibus = false)
    {
        $this->cartRuleId = $cartRuleId;
        $this->omnibus = $isOmnibus;
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
}

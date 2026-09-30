<?php

declare(strict_types=1);

namespace izi\prestashop\InPostDiscount;

final class CartRuleDiscount implements DiscountInterface
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var int
     */
    private $cartId;

    /**
     * @var string
     */
    private $type;

    /**
     * @var DiscountAmount
     */
    private $amount;

    /**
     * @var int
     */
    private $cartRuleId;

    public function __construct(int $cartId, string $type, DiscountAmount $amount, int $cartRuleId)
    {
        $this->cartId = $cartId;
        $this->type = $type;
        $this->amount = $amount;
        $this->cartRuleId = $cartRuleId;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCartId(): int
    {
        return $this->cartId;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getAmount(): DiscountAmount
    {
        return $this->amount;
    }

    public function getCartRuleId(): int
    {
        return $this->cartRuleId;
    }
}

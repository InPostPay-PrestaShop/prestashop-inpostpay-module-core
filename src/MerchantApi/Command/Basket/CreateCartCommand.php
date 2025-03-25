<?php

declare(strict_types=1);

namespace izi\prestashop\MerchantApi\Command\Basket;

final class CreateCartCommand
{
    /**
     * @var int|null
     */
    private $shopId;

    public function __construct(?int $shopId = null)
    {
        $this->shopId = $shopId;
    }

    public function getShopId(): ?int
    {
        return $this->shopId;
    }
}

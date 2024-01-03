<?php

declare(strict_types=1);

namespace izi\prestashop\MerchantApi\Command;

final class DeleteBasketBindingCommand
{
    /**
     * @var string
     */
    private $basketId;

    public function __construct(string $basketId)
    {
        $this->basketId = $basketId;
    }

    public function getBasketId(): string
    {
        return $this->basketId;
    }
}

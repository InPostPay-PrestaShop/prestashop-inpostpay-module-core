<?php

declare(strict_types=1);

namespace izi\prestashop\MerchantApi\Command;

use izi\prestashop\MerchantApi\Model\Basket\Request\BindingConfirmation;

final class ConfirmBasketBindingCommand
{
    /**
     * @var string
     */
    private $basketId;

    /**
     * @var BindingConfirmation
     */
    private $confirmation;

    public function __construct(string $basketId, BindingConfirmation $confirmation)
    {
        $this->basketId = $basketId;
        $this->confirmation = $confirmation;
    }

    public function getBasketId(): string
    {
        return $this->basketId;
    }

    public function getConfirmation(): BindingConfirmation
    {
        return $this->confirmation;
    }
}

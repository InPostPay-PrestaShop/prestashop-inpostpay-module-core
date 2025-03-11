<?php

declare(strict_types=1);

namespace izi\prestashop\BasketApp\Basket\Response;

final class UpdateBasketResponse implements \JsonSerializable
{
    /**
     * @var string
     */
    private $inpost_basket_id;

    public function __construct(string $inpost_basket_id)
    {
        $this->inpost_basket_id = $inpost_basket_id;
    }

    public function getInPostBasketId(): string
    {
        return $this->inpost_basket_id;
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}

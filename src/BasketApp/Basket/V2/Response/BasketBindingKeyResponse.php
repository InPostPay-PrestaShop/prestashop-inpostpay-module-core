<?php

declare(strict_types=1);

namespace izi\prestashop\BasketApp\Basket\V2\Response;

final class BasketBindingKeyResponse implements \JsonSerializable
{
    /**
     * @var string
     */
    private $basket_binding_api_key;

    public function __construct(string $basket_binding_api_key)
    {
        $this->basket_binding_api_key = $basket_binding_api_key;
    }

    public function getBindingKey(): string
    {
        return $this->basket_binding_api_key;
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}

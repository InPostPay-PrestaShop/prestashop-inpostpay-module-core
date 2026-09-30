<?php

declare(strict_types=1);

namespace izi\prestashop\Common\Order;

final class BasketAdditionalParameter implements \JsonSerializable
{
    /**
     * @var string
     */
    private $key;

    /**
     * @var string
     */
    private $value;

    public function __construct(string $key, string $value)
    {
        $this->key = $key;
        $this->value = $value;
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}

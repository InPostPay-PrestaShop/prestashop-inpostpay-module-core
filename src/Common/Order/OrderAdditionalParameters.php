<?php

declare(strict_types=1);

namespace izi\prestashop\Common\Order;

final class OrderAdditionalParameters implements \JsonSerializable
{
    /**
     * @var OrderAdditionalParameter[]
     */
    private $order_additional_parameters = [];

    /**
     * @param OrderAdditionalParameter[] $parameters
     */
    public function __construct(array $parameters = [])
    {
        foreach ($parameters as $parameter) {
            $this->addParameter($parameter);
        }
    }

    public function addParameter(OrderAdditionalParameter $parameter): void
    {
        $this->order_additional_parameters[] = $parameter;
    }

    /**
     * @return OrderAdditionalParameter[]
     */
    public function getParameters(): array
    {
        return $this->order_additional_parameters;
    }

    public function jsonSerialize(): array
    {
        return $this->order_additional_parameters;
    }
}

<?php

declare(strict_types=1);

namespace izi\prestashop\BasketApp\Payment\Response;

use izi\prestashop\Common\PaymentType;

/**
 * @implements \IteratorAggregate<PaymentType>
 */
final class AvailablePaymentOptions implements \JsonSerializable, \IteratorAggregate
{
    /**
     * @var PaymentType[]
     */
    private $payment_type;

    /**
     * @param PaymentType[] $payment_type
     */
    public function __construct(array $payment_type)
    {
        $this->payment_type = $payment_type;
    }

    /**
     * @return PaymentType[]
     */
    public function getPaymentTypes(): array
    {
        return $this->payment_type;
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }

    public function getIterator(): \Iterator
    {
        return new \ArrayIterator($this->payment_type);
    }
}

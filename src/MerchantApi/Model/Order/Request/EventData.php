<?php

declare(strict_types=1);

namespace izi\prestashop\MerchantApi\Model\Order\Request;

use izi\prestashop\Common\PaymentType;

final class EventData implements \JsonSerializable
{
    /**
     * @var PaymentStatus|null
     */
    private $payment_status;

    /**
     * @var OrderStatus|null
     */
    private $order_status;

    /**
     * @var string|null
     */
    private $payment_id;

    /**
     * @var string|null
     */
    private $payment_reference;

    /**
     * @var PaymentType|null
     */
    private $payment_type;

    public function __construct(?PaymentStatus $payment_status = null, ?OrderStatus $order_status = null, ?string $payment_id = null, ?string $payment_reference = null, ?PaymentType $payment_type = null)
    {
        $this->payment_status = $payment_status;
        $this->order_status = $order_status;
        $this->payment_id = $payment_id;
        $this->payment_reference = $payment_reference;
        $this->payment_type = $payment_type;
    }

    public function getPaymentStatus(): ?PaymentStatus
    {
        return $this->payment_status;
    }

    public function getOrderStatus(): ?OrderStatus
    {
        return $this->order_status;
    }

    public function getPaymentId(): ?string
    {
        return $this->payment_id;
    }

    public function getPaymentReference(): ?string
    {
        return $this->payment_reference;
    }

    public function getPaymentType(): ?PaymentType
    {
        return $this->payment_type;
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}

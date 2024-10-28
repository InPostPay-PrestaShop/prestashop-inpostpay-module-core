<?php

declare(strict_types=1);

namespace izi\prestashop\MerchantApi\Model\Order\Request;

use izi\prestashop\Common\Customer\InvoiceDetails;
use izi\prestashop\Common\Order\Consent;
use izi\prestashop\MerchantApi\Model\Order\Request\Delivery;

final class CreateOrderRequest implements \JsonSerializable
{
    /**
     * @var OrderDetails
     */
    private $order_details;

    /**
     * @var AccountInfo
     */
    private $account_info;

    /**
     * @var InvoiceDetails|null
     */
    private $invoice_details;

    /**
     * @var Delivery
     */
    private $delivery;

    /**
     * @var Consent[]
     */
    private $consents;

    /**
     * @param Consent[] $consents
     */
    public function __construct(OrderDetails $order_details, AccountInfo $account_info, Delivery $delivery, array $consents, ?InvoiceDetails $invoice_details = null)
    {
        $this->order_details = $order_details;
        $this->account_info = $account_info;
        $this->invoice_details = $invoice_details;
        $this->delivery = $delivery;
        $this->consents = $consents;
    }

    public function getOrderDetails(): OrderDetails
    {
        return $this->order_details;
    }

    public function getAccountInfo(): AccountInfo
    {
        return $this->account_info;
    }

    public function getInvoiceDetails(): ?InvoiceDetails
    {
        return $this->invoice_details;
    }

    public function getDelivery(): Delivery
    {
        return $this->delivery;
    }

    public function getConsents(): array
    {
        return $this->consents;
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}

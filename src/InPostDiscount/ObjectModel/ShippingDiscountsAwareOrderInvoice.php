<?php

declare(strict_types=1);

namespace izi\prestashop\InPostDiscount\ObjectModel;

use izi\prestashop\InPostDiscount\DiscountAmount;

/**
 * @internal
 */
class ShippingDiscountsAwareOrderInvoice extends \OrderInvoice
{
    private const CURRENCY_PRECISION = 2;

    /**
     * @var \OrderInvoice
     */
    private $invoice;

    /**
     * @var \Order
     */
    private $order;

    /**
     * @var DiscountAmount
     */
    private $discountsTotal;

    public function __construct(\OrderInvoice $invoice, \Order $order, DiscountAmount $discountsTotal)
    {
        parent::__construct();
        $this->setInvoice($invoice);
        $this->order = $order;
        $this->discountsTotal = $discountsTotal;
    }

    public function getOrder(): \Order
    {
        return $this->order;
    }

    /**
     * @param \Order|null $order
     *
     * @return array
     */
    public function getProductTaxesBreakdown($order = null): array
    {
        if ([] === $breakdown = $this->invoice->getProductTaxesBreakdown($order)) {
            return [];
        }

        if (0. !== $tax = $this->discountsTotal->getTax()) {
            \Tools::spreadAmount($tax, self::CURRENCY_PRECISION, $breakdown, 'total_amount');
        }

        \Tools::spreadAmount($this->discountsTotal->getNet(), self::CURRENCY_PRECISION, $breakdown, 'total_tax_excl');

        return $breakdown;
    }

    /**
     * @param \Order $order
     */
    public function getShippingTaxesBreakdown($order): array
    {
        if ([] === $breakdown = $this->invoice->getShippingTaxesBreakdown($order)) {
            return [];
        }

        $key = array_key_first($breakdown);
        $breakdown[$key]['total_amount'] = max(0., \Tools::ps_round($breakdown[$key]['total_amount'] - $this->discountsTotal->getTax(), self::CURRENCY_PRECISION, $order->round_mode));

        if (0. >= $breakdown[$key]['total_amount']) {
            unset($breakdown[$key]);
        } else {
            $breakdown[$key]['total_tax_excl'] = \Tools::ps_round($breakdown[$key]['total_tax_excl'] - $this->discountsTotal->getNet(), self::CURRENCY_PRECISION, $order->round_mode);
        }

        return $breakdown;
    }

    public function getProducts($products = false, $selected_products = false, $selected_qty = false)
    {
        return $this->invoice->getProducts($products, $selected_products, $selected_qty);
    }

    public function getEcoTaxTaxesBreakdown()
    {
        return $this->invoice->getEcoTaxTaxesBreakdown();
    }

    public function getWrappingTaxesBreakdown()
    {
        return $this->invoice->getWrappingTaxesBreakdown();
    }

    public function getInvoiceNumberFormatted($id_lang, $id_shop = null)
    {
        return $this->invoice->getInvoiceNumberFormatted($id_lang, $id_shop);
    }

    public function useOneAfterAnotherTaxComputationMethod()
    {
        return $this->invoice->useOneAfterAnotherTaxComputationMethod();
    }

    public function displayTaxBasesInProductTaxesBreakdown()
    {
        return $this->invoice->displayTaxBasesInProductTaxesBreakdown();
    }

    public function getOrderPaymentCollection()
    {
        return $this->invoice->getOrderPaymentCollection();
    }

    private function setInvoice(\OrderInvoice $invoice): void
    {
        foreach ($invoice as $property => $value) {
            $this->$property = $value;
        }

        $this->invoice = $invoice;
    }
}

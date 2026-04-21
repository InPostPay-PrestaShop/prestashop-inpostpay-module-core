<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Common;

use izi\prestashop\Hook\Exception\InvalidHookParamException;
use izi\prestashop\Hook\HookInterface;
use izi\prestashop\InPostDiscount\CartRule\Factory\InPostPlusCartRuleHandler;
use izi\prestashop\InPostDiscount\CartRuleDiscount;
use izi\prestashop\InPostDiscount\CartRuleDiscountRepository;
use izi\prestashop\InPostDiscount\DiscountAmount;
use izi\prestashop\InPostDiscount\DiscountRepositoryInterface;
use izi\prestashop\InPostDiscount\ObjectModel\ShippingDiscountsAwareOrderInvoice;

final class DisplayPDFInvoice implements HookInterface
{
    public const HOOK_NAME = 'displayPDFInvoice';

    /**
     * @var CartRuleDiscountRepository
     */
    private $discountRepository;

    /**
     * @param CartRuleDiscountRepository $discountRepository
     */
    public function __construct(DiscountRepositoryInterface $discountRepository)
    {
        $this->discountRepository = $discountRepository;
    }

    public static function getHookName(): string
    {
        return self::HOOK_NAME;
    }

    /**
     * @param array{object: \OrderInvoice} $parameters
     */
    public function execute(array $parameters): string
    {
        $invoice = $parameters['object'] ?? null;
        if (!$invoice instanceof \OrderInvoice) {
            throw InvalidHookParamException::unexpectedType('object', $invoice, \OrderInvoice::class);
        }

        $order = $invoice->getOrder();

        if ('inpostizi' !== $order->module || 0. >= $order->total_shipping_tax_excl) {
            return '';
        }

        $discountsTotal = $this->getShippingDiscountsTotal((int) $order->id_cart);

        if (0. === $discountsTotal->getTax()) {
            return '';
        }

        $template = $this->getPDFTemplate();
        if ($invoice !== $template->order_invoice && $template->order_invoice instanceof \OrderInvoice) {
            $invoice = $template->order_invoice;
        }

        $template->order_invoice = new ShippingDiscountsAwareOrderInvoice($invoice, $order, $discountsTotal);

        return '';
    }

    private function getShippingDiscountsTotal(int $cartId): DiscountAmount
    {
        $total = new DiscountAmount(0., 0.);

        if ([] === $discounts = $this->discountRepository->findByCartId($cartId)) {
            return $total;
        }

        return array_reduce($discounts, static function (DiscountAmount $total, CartRuleDiscount $discount) {
            if (InPostPlusCartRuleHandler::DISCOUNT_TYPE !== $discount->getType()) {
                return $total;
            }

            return $total->add($discount->getAmount());
        }, $total);
    }

    private function getPDFTemplate(): \HTMLTemplateInvoice
    {
        foreach (debug_backtrace(\DEBUG_BACKTRACE_PROVIDE_OBJECT, 10) as $frame) {
            $object = $frame['object'] ?? null;

            if (!$object instanceof \HTMLTemplateInvoice) {
                continue;
            }

            return $object;
        }

        throw new \RuntimeException(\sprintf('Could not find the "%s" object in the call stack.', \HTMLTemplateInvoice::class));
    }
}

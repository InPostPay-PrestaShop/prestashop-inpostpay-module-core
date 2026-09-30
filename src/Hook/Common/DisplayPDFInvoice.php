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
use izi\prestashop\ObjectModel\Repository\ObjectRepositoryInterface;

final class DisplayPDFInvoice implements HookInterface
{
    public const HOOK_NAME = 'displayPDFInvoice';

    /**
     * @var CartRuleDiscountRepository
     */
    private $discountRepository;

    /**
     * @var ObjectRepositoryInterface<\CartRule>
     */
    private $cartRuleRepository;

    /**
     * @param CartRuleDiscountRepository $discountRepository
     * @param ObjectRepositoryInterface<\CartRule> $cartRuleRepository
     */
    public function __construct(DiscountRepositoryInterface $discountRepository, ObjectRepositoryInterface $cartRuleRepository)
    {
        $this->discountRepository = $discountRepository;
        $this->cartRuleRepository = $cartRuleRepository;
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

        if (0. === $discountsTotal->getNet()) {
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

        return array_reduce($discounts, function (DiscountAmount $total, CartRuleDiscount $discount) {
            if (InPostPlusCartRuleHandler::DISCOUNT_TYPE !== $discount->getType() || $this->isFreeShipping($discount->getCartRuleId())) {
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

    private function isFreeShipping(int $cartRuleId): bool
    {
        if (null === $rule = $this->cartRuleRepository->find($cartRuleId)) {
            return false;
        }

        return (bool) $rule->free_shipping;
    }
}

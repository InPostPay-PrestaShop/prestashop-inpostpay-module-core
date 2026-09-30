<?php

declare(strict_types=1);

namespace izi\prestashop\InPostDiscount\EventListener;

use izi\prestashop\Event\OrderCartRuleEvent;
use izi\prestashop\Event\OrderEvent;
use izi\prestashop\InPostDiscount\CartRule\Factory\InPostPlusCartRuleHandler;
use izi\prestashop\InPostDiscount\CartRule\Util\FeatureHelper;
use izi\prestashop\InPostDiscount\CartRuleDiscount;
use izi\prestashop\InPostDiscount\Event\DiscountAppliedEvent;
use izi\prestashop\MerchantApi\Event\CreateOrderExceptionEvent;
use izi\prestashop\MerchantApi\Event\OrderCreatedEvent;
use izi\prestashop\ObjectModel\ObjectManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Corrects {@see \Order::$total_discounts_tax_excl} and {@see \Order::$total_paid_tax_excl} as well as
 * {@see \OrderCartRule::$value_tax_excl} if the shipping discount calculations were not handled by the
 * "actionApplyCartRule" hook. In such case, the net discount value is proportional to net product prices,
 * which may result in a different tax rate than the shipping tax.
 */
final class CorrectOrderDiscountTaxesListener implements EventSubscriberInterface
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var FeatureHelper
     */
    private $featureHelper;

    /**
     * @var \Order|null
     */
    private $order;

    /**
     * @var array<int, CartRuleDiscount> discounts by cart rule ID
     */
    private $discounts = [];

    public function __construct(ObjectManagerInterface $objectManager, FeatureHelper $featureHelper)
    {
        $this->objectManager = $objectManager;
        $this->featureHelper = $featureHelper;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            DiscountAppliedEvent::class => 'onDiscountApplied',
            OrderEvent::PERSISTED => 'onOrderPersisted',
            OrderCartRuleEvent::PRE_PERSIST => 'onPreOrderCartRulePersist',
            OrderCreatedEvent::class => 'onOrderCreated',
            CreateOrderExceptionEvent::class => 'onOrderException',
        ];
    }

    public function onDiscountApplied(DiscountAppliedEvent $event): void
    {
        $discount = $event->getDiscount();

        if (!$discount instanceof CartRuleDiscount) {
            return;
        }

        if (InPostPlusCartRuleHandler::DISCOUNT_TYPE !== $discount->getType()) {
            return;
        }

        $this->discounts[$discount->getCartRuleId()] = $discount;
    }

    public function onOrderPersisted(OrderEvent $event): void
    {
        $order = $event->getOrder();

        if ('inpostizi' !== $order->module) {
            return;
        }

        $this->order = $order;
    }

    public function onPreOrderCartRulePersist(OrderCartRuleEvent $event): void
    {
        if (null === $order = $this->order) {
            return;
        }

        $rule = $event->getRule();

        if (!isset($this->discounts[$cartRuleId = (int) $rule->id_cart_rule])) {
            return;
        }

        if ($this->featureHelper->isCustomCartRulesFeatureAvailable()) {
            return; // the net discount value from the "actionApplyCartRule" hook should be correct
        }

        $discount = $this->discounts[$cartRuleId];
        $deltaNet = $discount->getAmount()->getNet() - (float) $rule->value_tax_excl;

        if (abs($deltaNet) < 5e-7) {
            return;
        }

        $rule->value_tax_excl = $discount->getAmount()->getNet();
        $order->total_discounts_tax_excl = \Tools::ps_round($order->total_discounts_tax_excl + $deltaNet, 2, $order->round_mode);
        $order->total_paid_tax_excl = \Tools::ps_round($order->total_paid_tax_excl - $deltaNet, 2, $order->round_mode);

        $this->objectManager->save($order);
    }

    public function onOrderCreated(OrderCreatedEvent $event): void
    {
        $this->reset();
    }

    public function onOrderException(CreateOrderExceptionEvent $event): void
    {
        $this->reset();
    }

    private function reset(): void
    {
        $this->order = null;
        $this->discounts = [];
    }
}

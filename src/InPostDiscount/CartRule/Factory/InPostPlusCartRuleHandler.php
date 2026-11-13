<?php

declare(strict_types=1);

namespace izi\prestashop\InPostDiscount\CartRule\Factory;

use izi\prestashop\Configuration\PrestaShopConfiguration;
use izi\prestashop\InPostDiscount\CartRule\CustomRuleRepositoryInterface;
use izi\prestashop\InPostDiscount\CartRule\DiscountApplierInterface;
use izi\prestashop\InPostDiscount\CartRule\Util\CartRuleFactoryHelper;
use izi\prestashop\InPostDiscount\CartRule\Util\FeatureHelper;
use izi\prestashop\InPostDiscount\CartRuleDiscount;
use izi\prestashop\InPostDiscount\DiscountAmount;
use izi\prestashop\InPostDiscount\Exception\ZeroAmountException;
use izi\prestashop\MerchantApi\Model\Order\Request\InPostDiscount;
use izi\prestashop\ObjectModel\ObjectManagerInterface;
use PrestaShop\PrestaShop\Core\Cart\AmountImmutable;
use PrestaShop\PrestaShop\Core\Cart\CartRuleCalculator;
use PrestaShop\PrestaShop\Core\Cart\CartRuleData;

final class InPostPlusCartRuleHandler extends AbstractCartRuleFactory implements DiscountApplierInterface
{
    public const DISCOUNT_TYPE = 'INPOST_SUBSCRIPTION';
    private const FREE_SHIPPING_CUSTOM_CR_KEY = self::DISCOUNT_TYPE . '_FS';

    /**
     * @var FeatureHelper
     */
    private $featureHelper;

    /**
     * @var CartRuleFactoryHelper
     */
    private $helper;

    /**
     * @var bool
     */
    private $freeShipping = false;

    /**
     * @var DiscountAmount|null
     */
    private $discountAmount;

    public function __construct(ObjectManagerInterface $objectManager, PrestaShopConfiguration $configuration, FeatureHelper $featureHelper, CustomRuleRepositoryInterface $customRuleRepository)
    {
        parent::__construct($objectManager, $configuration, $featureHelper, $customRuleRepository);

        $this->featureHelper = $featureHelper;
        $this->helper = new CartRuleFactoryHelper($objectManager, $customRuleRepository);
    }

    public static function getDefaultDiscountType(): string
    {
        return self::DISCOUNT_TYPE;
    }

    public function create(\Cart $cart, InPostDiscount $discount): CartRuleDiscount
    {
        try {
            return $this->doCreate($cart, $discount);
        } finally {
            $this->discountAmount = null;
        }
    }

    public function apply(CartRuleCalculator $calculator, CartRuleData $data, CartRuleDiscount $discount, bool $withShipping): void
    {
        if (!$withShipping) {
            return;
        }

        $fees = $calculator->getCalculator()->getFees();

        if ($data->getCartRule()->free_shipping) {
            $discountValue = $fees->getInitialShippingFees();
            $fees->subDiscountValueShipping($discountValue);
        } else {
            $originalShippingCost = $fees->getFinalShippingFees();
            $fees->subDiscountValueShipping(new AmountImmutable(
                $discount->getAmount()->getGross(),
                $discount->getAmount()->getNet()
            ));

            $discountValue = $originalShippingCost->sub($fees->getFinalShippingFees());
        }

        $data->addDiscountApplied($discountValue);
    }

    protected function supports(string $type): bool
    {
        return self::DISCOUNT_TYPE === $type;
    }

    protected function calculateAmount(\Cart $cart, InPostDiscount $discount): DiscountAmount
    {
        if (isset($this->discountAmount)) {
            return $this->discountAmount;
        }

        if ([] !== $cart->getCartRules(\CartRule::FILTER_ACTION_SHIPPING, false)) {
            throw new ZeroAmountException('A free shipping rule is applied to the cart.');
        }

        if (0. >= $shippingCost = (float) $cart->getPackageShippingCost($cart->id_carrier)) {
            throw new ZeroAmountException('Cart shipping cost is 0.');
        }

        if ($shippingCost <= $gross = $discount->getDiscount()) {
            $gross = $shippingCost;
            $this->freeShipping = true;
        } else {
            $this->freeShipping = false;
        }

        $net = $this->getNetShippingAmount($cart, $gross);

        return $this->discountAmount = new DiscountAmount($net, $gross);
    }

    protected function getCartRuleName(string $type): string
    {
        return 'InPost+';
    }

    protected function configureSingleUseCartRule(\CartRule $cartRule, DiscountAmount $amount): void
    {
        if ($this->freeShipping) {
            $cartRule->free_shipping = true;
        } else {
            $cartRule->free_shipping = false;
            $cartRule->reduction_tax = true;
            $cartRule->reduction_amount = $amount->getGross();
        }
    }

    private function getNetShippingAmount(\Cart $cart, float $grossAmount): float
    {
        /** @var \Carrier $carrier */
        $carrier = $this->objectManager->find(\Carrier::class, (int) $cart->id_carrier);
        $address = $this->getTaxAddress($cart);
        $netAmount = $carrier->getTaxCalculator($address)->removeTaxes($grossAmount);

        return round($netAmount, 6);
    }

    private function doCreate(\Cart $cart, InPostDiscount $discount): CartRuleDiscount
    {
        $type = $discount->getType();
        if (!$this->supports($type) || !$this->featureHelper->isCustomCartRulesFeatureAvailable()) {
            return parent::create($cart, $discount);
        }

        $amount = $this->calculateAmount($cart, $discount);
        if (!$this->freeShipping) {
            return parent::create($cart, $discount);
        }

        $cartRule = $this->helper->findOrCreateCustom($type, $this->getCartRuleName($type), static function (\CartRule $cartRule): void {
            $cartRule->free_shipping = true;
        }, self::FREE_SHIPPING_CUSTOM_CR_KEY);

        return new CartRuleDiscount((int) $cart->id, $type, $amount, (int) $cartRule->id);
    }
}

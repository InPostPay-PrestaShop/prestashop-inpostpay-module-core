<?php

declare(strict_types=1);

namespace izi\prestashop\InPostDiscount\CartRule\Factory;

use izi\prestashop\Configuration\PrestaShopConfiguration;
use izi\prestashop\InPostDiscount\CartRule\CustomRuleRepositoryInterface;
use izi\prestashop\InPostDiscount\CartRule\Util\CartRuleFactoryHelper;
use izi\prestashop\InPostDiscount\CartRule\Util\FeatureHelper;
use izi\prestashop\InPostDiscount\CartRuleDiscount;
use izi\prestashop\InPostDiscount\DiscountAmount;
use izi\prestashop\InPostDiscount\Exception\UnsupportedTypeException;
use izi\prestashop\InPostDiscount\Exception\ZeroAmountException;
use izi\prestashop\MerchantApi\Model\Order\Request\InPostDiscount;
use izi\prestashop\ObjectModel\ObjectManagerInterface;

abstract class AbstractCartRuleFactory implements CartRuleFactoryInterface
{
    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var PrestaShopConfiguration
     */
    private $configuration;

    /**
     * @var FeatureHelper
     */
    private $featureHelper;

    /**
     * @var CartRuleFactoryHelper
     */
    private $helper;

    public function __construct(ObjectManagerInterface $objectManager, PrestaShopConfiguration $configuration, FeatureHelper $featureHelper, CustomRuleRepositoryInterface $customRuleRepository)
    {
        $this->objectManager = $objectManager;
        $this->configuration = $configuration;
        $this->featureHelper = $featureHelper;
        $this->helper = new CartRuleFactoryHelper($objectManager, $customRuleRepository);
    }

    public function create(\Cart $cart, InPostDiscount $discount): CartRuleDiscount
    {
        if (!$this->supports($type = $discount->getType())) {
            throw UnsupportedTypeException::create($type);
        }

        $amount = $this->calculateAmount($cart, $discount);

        if ($this->featureHelper->isCustomCartRulesFeatureAvailable()) {
            $cartRule = $this->findOrCreateCustomCartRule($type);
        } else {
            $cartRule = $this->createSingleUseCartRule($cart, $type, $amount);
        }

        return new CartRuleDiscount((int) $cart->id, $type, $amount, (int) $cartRule->id);
    }

    abstract protected function supports(string $type): bool;

    /**
     * @throws ZeroAmountException
     */
    abstract protected function calculateAmount(\Cart $cart, InPostDiscount $discount): DiscountAmount;

    abstract protected function getCartRuleName(string $type): string;

    abstract protected function configureSingleUseCartRule(\CartRule $cartRule, DiscountAmount $amount): void;

    protected function getTaxAddress(\Cart $cart): \Address
    {
        $taxAddressType = $this->configuration->getTaxAddressType();
        $addressId = (int) $cart->$taxAddressType;

        return $this->objectManager->find(\Address::class, $addressId);
    }

    protected function createSingleUseCartRule(\Cart $cart, string $type, DiscountAmount $amount): \CartRule
    {
        $cartRule = $this->createCartRule($type);
        $cartRule->id_customer = (int) $cart->id_customer;
        $cartRule->reduction_currency = (int) $cart->id_currency;
        $cartRule->quantity = $cartRule->quantity_per_user = 1;
        $cartRule->code = \sprintf('%s_%s', $type, strtoupper(bin2hex(random_bytes(8))));

        $this->configureSingleUseCartRule($cartRule, $amount);
        $this->objectManager->save($cartRule);

        return $cartRule;
    }

    private function findOrCreateCustomCartRule(string $type): \CartRule
    {
        return $this->helper->findOrCreateCustom($type, $this->getCartRuleName($type), static function (\CartRule $cartRule): void {
            /* @see \CartRuleCore::FILTER_ACTION_REDUCTION filter handling by {@see \Cart::getCartRules()} */
            $cartRule->reduction_amount = 1.; // the actual value will be calculated in hook
        });
    }

    private function createCartRule(string $type): \CartRule
    {
        return $this->helper->create($type, $this->getCartRuleName($type));
    }
}

<?php

declare(strict_types=1);

namespace izi\prestashop\InPostDiscount\CartRule\Factory;

use izi\prestashop\Configuration\PrestaShopConfiguration;
use izi\prestashop\InPostDiscount\CartRule\CustomRuleRepositoryInterface;
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
     * @var CustomRuleRepositoryInterface
     */
    private $customRuleRepository;

    public function __construct(ObjectManagerInterface $objectManager, PrestaShopConfiguration $configuration, FeatureHelper $featureHelper, CustomRuleRepositoryInterface $customRuleRepository)
    {
        $this->objectManager = $objectManager;
        $this->configuration = $configuration;
        $this->featureHelper = $featureHelper;
        $this->customRuleRepository = $customRuleRepository;
    }

    public function create($cart, $discount): CartRuleDiscount
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
        $cartRuleId = $this->customRuleRepository->getCartRuleId($type);

        if (null === $cartRuleId || null === $cartRule = $this->objectManager->find(\CartRule::class, $cartRuleId)) {
            return $this->createCustomCartRule($type);
        }

        if (!$cartRule->active) {
            $cartRule->active = true;
            $this->objectManager->save($cartRule);
        }

        return $cartRule;
    }

    private function createCustomCartRule(string $type): \CartRule
    {
        $cartRule = $this->createCartRule($type);
        $cartRule->code = $type;
        /* @see \CartRuleCore::FILTER_ACTION_REDUCTION filter handling by {@see \Cart::getCartRules()} */
        $cartRule->reduction_amount = 1.; // the actual value will be calculated in hook

        $metadata = $this->objectManager->getMetadata(\CartRule::class);
        if (!empty($metadata['fields']['quantity']['allow_null'])) {
            $cartRule->quantity = $cartRule->quantity_per_user = null; // unlimited quantity
        } else {
            $cartRule->quantity = $cartRule->quantity_per_user = (int) 2e32 - 1; // max unsigned 32-bit int
        }

        try {
            $this->objectManager->save($cartRule);
            $this->customRuleRepository->registerCartRule($type, (int) $cartRule->id);
        } catch (\Throwable $e) {
            try {
                $this->objectManager->remove($cartRule);
            } catch (\Throwable $ignored) {
                // ignore and rethrow the original exception
            }

            throw $e;
        }

        return $cartRule;
    }

    private function createCartRule(string $type): \CartRule
    {
        $cartRule = new \CartRule();

        $cartRule->name = [];
        foreach ($this->objectManager->getRepository(\Language::class)->findAll() as $language) {
            $cartRule->name[$language->id] = $this->getCartRuleName($type);
        }

        $cartRule->date_from = '2000-01-01 00:00:00'; // arbitrary past date
        $cartRule->date_to = '2050-01-01 00:00:00'; // arbitrary future date
        $cartRule->description = $type;
        $cartRule->partial_use = false;
        $cartRule->reduction_tax = true;
        $cartRule->highlight = false;
        $cartRule->active = true;

        return $cartRule;
    }
}

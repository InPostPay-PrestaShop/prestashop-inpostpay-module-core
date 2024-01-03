<?php

declare(strict_types=1);

namespace izi\prestashop;

use izi\prestashop\Common\Currency;
use izi\prestashop\ObjectModel\ObjectManagerInterface;
use izi\prestashop\ObjectModel\Repository\CurrencyRepository;

final class ContextManager
{
    /**
     * @var \Context
     */
    private $context;

    /**
     * @var ObjectManagerInterface
     */
    private $manager;

    private $stack = [];

    public function __construct(\Context $context, ObjectManagerInterface $manager)
    {
        $this->context = $context;
        $this->manager = $manager;
    }

    public function changeContext(\Cart $cart): void
    {
        $restorationPoint = [];

        try {
            $this->isContextCart($cart)
                ? $this->buildRestorationPointForContextCart($cart, $restorationPoint)
                : $this->buildRestorationPoint($cart, $restorationPoint);
        } finally {
            $this->stack[] = $restorationPoint;
        }
    }

    public function restoreContext(): void
    {
        if ([] === $this->stack) {
            return;
        }

        if ([] === $previousContext = array_pop($this->stack)) {
            return;
        }

        if (isset($previousContext['currency'])) {
            \Cache::clean('getPackageShippingCost_' . (int) $this->context->cart->id . '_*');
        }

        foreach ($previousContext as $name => $value) {
            $this->restoreContextProperty($name, $value);
        }
    }

    private function isContextCart(\Cart $cart): bool
    {
        if (!isset($this->context->cart)) {
            return false;
        }

        return (int) $this->context->cart->id === (int) $cart->id;
    }

    private function buildRestorationPointForContextCart(\Cart $cart, array &$restorationPoint): void
    {
        if (null === $previousCurrency = $this->changeCurrency($cart)) {
            return;
        }

        $contextCart = $this->context->cart;
        $this->context->cart = $cart;

        $restorationPoint = [
            'cart' => $contextCart,
            'currency' => $previousCurrency,
        ];
    }

    private function buildRestorationPoint(\Cart $cart, array &$restorationPoint): void
    {
        $restorationPoint['cart'] = $this->context->cart;
        $this->context->cart = $cart;

        if (null !== $shop = $this->changeShop($cart)) {
            $restorationPoint['shop'] = $shop;
        }

        if (null !== $currency = $this->changeCurrency($cart)) {
            $restorationPoint['currency'] = $currency;
        }

        if (null !== $language = $this->changeLanguage($cart)) {
            $restorationPoint['language'] = $language;
        }

        $restorationPoint['customer'] = $this->changeCustomer($cart);
    }

    private function restoreContextProperty(string $name, $value): void
    {
        if ('shop' === $name) {
            [$value, $type, $id] = $value;
            \Shop::setContext($type, $id);
        }

        $this->context->{$name} = $value;

        if ('language' === $name) {
            $this->context->getTranslator()->setLocale($this->context->language->locale);
        }
    }

    private function changeCurrency(\Cart $cart): ?\Currency
    {
        /** @var CurrencyRepository $repository */
        $repository = $this->manager->getRepository(\Currency::class);

        $cartCurrency = $repository->find((int) $cart->id_currency);

        if (null === $cartCurrency || null === Currency::tryFrom($cartCurrency->iso_code)) {
            $cartCurrency = $repository->findOneByIsoCode(Currency::getDefault()->value);

            if (null === $cartCurrency) {
                throw new \RuntimeException('Could not find suitable currency to switch to.');
            }

            $cart->id_currency = $cartCurrency->id;
        }

        $contextCurrency = $this->context->currency;

        if ($contextCurrency && (int) $contextCurrency->id === (int) $cartCurrency->id) {
            return null;
        }

        $this->context->currency = $cartCurrency;
        \Cache::clean('getPackageShippingCost_' . (int) $cart->id . '_*');

        return $contextCurrency;
    }

    private function changeShop(\Cart $cart): ?array
    {
        $type = \Shop::getContext();
        $contextShop = $this->context->shop;

        $shopId = (int) $cart->id_shop;

        if (\Shop::CONTEXT_SHOP === $type && (int) $contextShop->id === $shopId) {
            return null;
        }

        $restorationData = [
            $contextShop,
            $type,
            \Shop::CONTEXT_GROUP === $type ? \Shop::getContextShopGroupID() : \Shop::getContextShopID(),
        ];

        \Shop::setContext(\Shop::CONTEXT_SHOP, $shopId);
        $this->context->shop = $this->manager->getRepository(\Shop::class)->find($shopId);

        return $restorationData;
    }

    private function changeCustomer(\Cart $cart): ?\Customer
    {
        $contextCustomer = $this->context->customer;
        $customerId = (int) $cart->id_customer;

        if ($contextCustomer && (int) $contextCustomer->id === $customerId) {
            return $contextCustomer;
        }

        $customer = $this->manager->getRepository(\Customer::class)->find($customerId) ?? new \Customer();
        $this->context->customer = $customer;
        $cart->setTaxCalculationMethod();

        return $contextCustomer;
    }

    private function changeLanguage(\Cart $cart): ?\Language
    {
        $contextLanguage = $this->context->language;

        if ((int) $contextLanguage->id === $languageId = (int) $cart->id_lang) {
            return null;
        }

        $language = $this->manager->getRepository(\Language::class)->find($languageId);
        $this->context->language = $language;
        $this->context->getTranslator()->setLocale($this->context->language->locale);

        return $contextLanguage;
    }
}

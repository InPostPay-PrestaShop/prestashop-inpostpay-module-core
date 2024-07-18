<?php

declare(strict_types=1);

namespace izi\prestashop;

use izi\prestashop\Common\Currency;
use izi\prestashop\Configuration\Adapter\Configuration;
use izi\prestashop\Configuration\PrestaShopConfiguration;
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

    /**
     * @var PrestaShopConfiguration
     */
    private $configuration;

    private $stack = [];

    public function __construct(\Context $context, ObjectManagerInterface $manager, ?PrestaShopConfiguration $configuration = null)
    {
        $this->context = $context;
        $this->manager = $manager;
        $this->configuration = $configuration ?? new PrestaShopConfiguration(new Configuration());
    }

    public function getContext(): \Context
    {
        return $this->context;
    }

    /**
     * @param array{
     *     currency?: Currency,
     *     country?: array{
     *         delivery: string,
     *         invoice: string,
     *     },
     * } $options
     */
    public function changeContext(\Cart $cart, array $options = []): void
    {
        $restorationPoint = [];

        try {
            $this->isContextCart($cart)
                ? $this->buildRestorationPointForContextCart($cart, $options, $restorationPoint)
                : $this->buildRestorationPoint($cart, $options, $restorationPoint);
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

    private function buildRestorationPointForContextCart(\Cart $cart, array $options, array &$restorationPoint): void
    {
        if (null !== $country = $this->changeCountry($cart, $options['country'] ?? [])) {
            $restorationPoint['country'] = $country;
        }

        if (null === $currency = $this->changeCurrency($cart, $options['currency'] ?? null)) {
            return;
        }

        /* @see \Cart::$id_currency might have changed */
        $restorationPoint['cart'] = $this->context->cart;
        $restorationPoint['currency'] = $currency;

        $this->context->cart = $cart;
    }

    private function buildRestorationPoint(\Cart $cart, array $options, array &$restorationPoint): void
    {
        $restorationPoint['cart'] = $this->context->cart;
        $this->context->cart = $cart;

        if (null !== $shop = $this->changeShop($cart)) {
            $restorationPoint['shop'] = $shop;
        }

        if (null !== $currency = $this->changeCurrency($cart, $options['currency'] ?? null)) {
            $restorationPoint['currency'] = $currency;
        }

        if (null !== $language = $this->changeLanguage($cart)) {
            $restorationPoint['language'] = $language;
        }

        if (null !== $country = $this->changeCountry($cart, $options['country'] ?? [])) {
            $restorationPoint['country'] = $country;
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

    private function changeCurrency(\Cart $cart, ?Currency $targetCurrency): ?\Currency
    {
        /** @var CurrencyRepository $repository */
        $repository = $this->manager->getRepository(\Currency::class);

        $currency = $repository->find((int) $cart->id_currency);
        $currentCurrency = null === $currency ? null : Currency::tryFrom($currency->iso_code);

        if (
            null === $currentCurrency
            || null !== $targetCurrency && $currentCurrency !== $targetCurrency
        ) {
            $targetCurrency = $targetCurrency ?? Currency::getDefault();
            $currency = $repository->findOneByIsoCode($targetCurrency->value);

            if (null === $currency) {
                throw new \RuntimeException('Could not find suitable currency to switch to.');
            }

            $cart->id_currency = $currency->id;
        }

        $contextCurrency = $this->context->currency;

        if ($contextCurrency && (int) $contextCurrency->id === (int) $currency->id) {
            return null;
        }

        $this->context->currency = $currency;
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

    private function changeCountry(\Cart $cart, array $countryCodes): ?\Country
    {
        if (null === $country = $this->findTaxCalculationCountry($cart, $countryCodes)) {
            throw new \RuntimeException('Could not find suitable country to switch to.');
        }

        $contextCountry = $this->context->country;

        if ((int) $contextCountry->id === (int) $country->id) {
            return null;
        }

        $this->context->country = $country;

        return $contextCountry;
    }

    private function findTaxCalculationCountry(\Cart $cart, array $countryCodes): ?\Country
    {
        $shopId = (int) $cart->id_shop;
        $taxAddressType = $this->configuration->getTaxAddressType($shopId);

        if ('id_address_delivery' === $taxAddressType) {
            // as of writing this comment, only domestic delivery is available
            $isoCode = $countryCodes['delivery'] ?? 'PL';

            return $this->getCountryByIsoCode($isoCode, (int) $cart->id_lang);
        }

        if (isset($countryCodes['invoice'])) {
            return $this->getCountryByIsoCode($countryCodes['invoice'], (int) $cart->id_lang);
        }

        $taxAddressId = (int) $cart->id_address_invoice;
        $taxAddress = $this->manager->getRepository(\Address::class)->find($taxAddressId);

        if (null === $taxAddress || 0 >= $countryId = (int) $taxAddress->id_country) {
            $countryId = $this->configuration->getDefaultCountryId($shopId);
        }

        return $this->manager
            ->getRepository(\Country::class)
            ->find($countryId, (int) $cart->id_lang);
    }

    private function getCountryByIsoCode(string $isoCode, int $languageId): \Country
    {
        $country = $this->manager
            ->getRepository(\Country::class)
            ->findOneBy([
                'iso_code' => $isoCode,
                'id_lang' => $languageId,
            ], ['active' => 'DESC']);

        if (null !== $country) {
            return $country;
        }

        throw new \RuntimeException(sprintf('Country "%s" not found.', $isoCode)); // TODO specific exception
    }
}

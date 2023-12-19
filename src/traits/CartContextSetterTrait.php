<?php

namespace izi\prestashop\traits;

trait CartContextSetterTrait
{
    /**
     * @var \Context
     */
    private $context;

    private function setUpContext(\Cart $cart): void
    {
        if ($currencyId = \Currency::getIdByIsoCode('PLN')) {
            $cart->id_currency = $currencyId;
        }

        $this->context->cart = $cart;
        $this->context->shop = new \Shop($cart->id_shop);
        $this->context->customer = new \Customer($cart->id_customer);
        $this->context->cart->setTaxCalculationMethod();
        $this->context->currency = \Currency::getCurrencyInstance($cart->id_currency);
        $this->context->language = new \Language($cart->id_lang);

        $this->context->getTranslator()->setLocale($this->context->language->locale);

        \Shop::setContext(\Shop::CONTEXT_SHOP, $cart->id_shop);
    }
}

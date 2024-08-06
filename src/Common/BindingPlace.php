<?php

declare(strict_types=1);

namespace izi\prestashop\Common;

use izi\prestashop\Enum\StringEnum;
use izi\prestashop\Form\Type\GuiConfigurationType;
use izi\prestashop\Translation\LegacyTranslator;

/**
 * @method static self ProductCard()
 * @method static self BasketSummary()
 * @method static self BasketPopup()
 * @method static self OrderCreate()
 * @method static self ThankYouPage()
 * @method static self LoginPage()
 * @method static self RegisterFormPage()
 * @method static self CheckoutPage()
 * @method static self MiniCartPage()
 */
final class BindingPlace extends StringEnum
{
    private const PRODUCT_CARD = 'PRODUCT_CARD';
    private const BASKET_SUMMARY = 'BASKET_SUMMARY';
    private const BASKET_POPUP = 'BASKET_POPUP';
    private const ORDER_CREATE = 'ORDER_CREATE';
    private const THANK_YOU_PAGE = 'THANK_YOU_PAGE';
    private const LOGIN_PAGE = 'LOGIN_PAGE';
    private const REGISTER_FORM_PAGE = 'REGISTERFORM_PAGE';
    private const CHECKOUT_PAGE = 'CHECKOUT_PAGE';
    private const MINI_CART_PAGE = 'MINICART_PAGE';

    /**
     * @return self[]
     */
    public static function getBindingWidgetDisplayPlaces(): array
    {
        return array_filter(self::cases(), static function (self $bindingPlace): bool {
            return self::ThankYouPage() !== $bindingPlace;
        });
    }

    public function requiresExistingBasket(): bool
    {
        return BindingPlace::ProductCard() !== $this;
    }

    public function canDisplayBindingWidget(): bool
    {
        return $this !== self::ThankYouPage();
    }

    public function trans(LegacyTranslator $translator): string
    {
        switch ($this) {
            case self::ProductCard():
                return $translator->l('Product card', GuiConfigurationType::TRANSLATION_SOURCE);
            case self::BasketSummary():
                return $translator->l('Cart page', GuiConfigurationType::TRANSLATION_SOURCE);
            case self::BasketPopup():
                return $translator->l('Add to cart confirmation', 'bindingplace');
            case self::OrderCreate():
                return $translator->l('Payment method option selection', 'bindingplace');
            case self::LoginPage():
                return $translator->l('Login page', GuiConfigurationType::TRANSLATION_SOURCE);
            case self::RegisterFormPage():
                return $translator->l('Register page', GuiConfigurationType::TRANSLATION_SOURCE);
            case self::CheckoutPage():
                return $translator->l('Checkout page', GuiConfigurationType::TRANSLATION_SOURCE);
            case self::MiniCartPage():
                return $translator->l('Cart preview', GuiConfigurationType::TRANSLATION_SOURCE);
            case self::ThankYouPage():
                return $translator->l('"Thank you" page', 'bindingplace');
            default:
                throw new \LogicException('Unreachable statement.');
        }
    }
}

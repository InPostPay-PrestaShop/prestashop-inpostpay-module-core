<?php

declare(strict_types=1);

namespace izi\prestashop\Common;

use izi\prestashop\Enum\StringEnum;
use izi\prestashop\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

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
final class BindingPlace extends StringEnum implements TranslatableInterface
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
        return self::ProductCard() !== $this;
    }

    public function canDisplayBindingWidget(): bool
    {
        return self::ThankYouPage() !== $this;
    }

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        switch ($this) {
            case self::ProductCard():
                return $translator->trans('Product card', [], 'Modules.Inpostizi.Binding', $locale);
            case self::BasketSummary():
                return $translator->trans('Cart page', [], 'Modules.Inpostizi.Binding', $locale);
            case self::BasketPopup():
                return $translator->trans('Add to cart confirmation popup', [], 'Modules.Inpostizi.Binding', $locale);
            case self::OrderCreate():
                return $translator->trans('Payment method selection step', [], 'Modules.Inpostizi.Binding', $locale);
            case self::LoginPage():
                return $translator->trans('Login page', [], 'Modules.Inpostizi.Binding', $locale);
            case self::RegisterFormPage():
                return $translator->trans('Registration page', [], 'Modules.Inpostizi.Binding', $locale);
            case self::CheckoutPage():
                return $translator->trans('Checkout page', [], 'Modules.Inpostizi.Binding', $locale);
            case self::MiniCartPage():
                return $translator->trans('Cart preview', [], 'Modules.Inpostizi.Binding', $locale);
            case self::ThankYouPage():
                return $translator->trans('"Thank you" page', [], 'Modules.Inpostizi.Binding', $locale);
            default:
                throw new \LogicException('Unreachable statement.');
        }
    }
}

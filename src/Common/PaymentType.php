<?php

declare(strict_types=1);

namespace izi\prestashop\Common;

use izi\prestashop\Enum\NotAnEnum;
use izi\prestashop\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @todo: Cannot be modeled as an enum since InPost does not consider adding new values as a breaking change.
 *
 * @method static self Card()
 * @method static self CardToken()
 * @method static self GooglePay()
 * @method static self ApplePay()
 * @method static self BlikCode()
 * @method static self BlikToken()
 * @method static self PayByLink()
 * @method static self ShoppingLimit()
 * @method static self DeferredPayment()
 * @method static self CashOnDelivery()
 */
final class PaymentType extends NotAnEnum implements TranslatableInterface
{
    private const CARD = 'CARD';
    private const CARD_TOKEN = 'CARD_TOKEN';
    private const GOOGLE_PAY = 'GOOGLE_PAY';
    private const APPLE_PAY = 'APPLE_PAY';
    private const BLIK_CODE = 'BLIK_CODE';
    private const BLIK_TOKEN = 'BLIK_TOKEN';
    private const PAY_BY_LINK = 'PAY_BY_LINK';
    private const SHOPPING_LIMIT = 'SHOPPING_LIMIT';
    private const DEFERRED_PAYMENT = 'DEFERRED_PAYMENT';
    private const CASH_ON_DELIVERY = 'CASH_ON_DELIVERY';

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        switch ($this) {
            case self::Card():
                return $translator->trans('Credit card', [], 'Modules.Inpostizi.Payment', $locale);
            case self::CardToken():
                return $translator->trans('Remembered credit card', [], 'Modules.Inpostizi.Payment', $locale);
            case self::GooglePay():
                return $translator->trans('Google Pay', [], 'Modules.Inpostizi.Payment', $locale);
            case self::ApplePay():
                return $translator->trans('Apple Pay', [], 'Modules.Inpostizi.Payment', $locale);
            case self::BlikCode():
                return $translator->trans('BLIK code', [], 'Modules.Inpostizi.Payment', $locale);
            case self::BlikToken():
                return $translator->trans('BLIK without a code', [], 'Modules.Inpostizi.Payment', $locale);
            case self::PayByLink():
                return $translator->trans('Pay by Link', [], 'Modules.Inpostizi.Payment', $locale);
            case self::ShoppingLimit():
                return $translator->trans('Shopping limit', [], 'Modules.Inpostizi.Payment', $locale);
            case self::DeferredPayment():
                return $translator->trans('Deferred payment', [], 'Modules.Inpostizi.Payment', $locale);
            case self::CashOnDelivery():
                return $translator->trans('Cash on Delivery', [], 'Modules.Inpostizi.Payment', $locale);
            default:
                return $this->value;
        }
    }
}

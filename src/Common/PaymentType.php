<?php

declare(strict_types=1);

namespace izi\prestashop\Common;

use izi\prestashop\Enum\Enum;
use izi\prestashop\Enum\StringEnum;

/**
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
final class PaymentType extends StringEnum
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
    private const CARD_NT = 'CARD_NT';

    /**
     * @deprecated
     *
     * @return self[]
     */
    public static function getCarrierProvidedPaymentOptions(): array
    {
        @trigger_error(sprintf('Method "%s()" is deprecated without replacement.', __METHOD__), \E_USER_DEPRECATED);

        return [self::CashOnDelivery()];
    }

    /**
     * @deprecated
     *
     * @return self[]
     */
    public static function getBankProvidedPaymentOptions(): array
    {
        @trigger_error(sprintf('Method "%s()" is deprecated without replacement.', __METHOD__), \E_USER_DEPRECATED);

        $diff = array_udiff(self::cases(), self::getCarrierProvidedPaymentOptions(), [Enum::class, 'compareValues']);

        return array_values($diff);
    }

    /**
     * @deprecated
     *
     * @return self[]
     */
    public static function getAvailableByDefaultPaymentOptions(): array
    {
        @trigger_error(sprintf('Method "%s()" is deprecated without replacement.', __METHOD__), \E_USER_DEPRECATED);

        $filtered = array_filter(self::cases(), static function (self $type) {
            return $type !== self::DeferredPayment();
        });

        return array_values($filtered);
    }
}

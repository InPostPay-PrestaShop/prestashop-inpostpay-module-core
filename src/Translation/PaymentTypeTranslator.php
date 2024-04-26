<?php

declare(strict_types=1);

namespace izi\prestashop\Translation;

use izi\prestashop\Common\PaymentType;

final class PaymentTypeTranslator
{
    private const TRANSLATION_SOURCE = 'paymenttypetranslator';

    /**
     * @var LegacyTranslator
     */
    private $translator;

    public function __construct(LegacyTranslator $translator)
    {
        $this->translator = $translator;
    }

    public function getLabel(PaymentType $paymentType): string
    {
        switch ($paymentType) {
            case PaymentType::Card():
                return $this->translator->l('Credit card', self::TRANSLATION_SOURCE);
            case PaymentType::CardToken():
                return $this->translator->l('Remembered credit card', self::TRANSLATION_SOURCE);
            case PaymentType::GooglePay():
                return $this->translator->l('Google Pay', self::TRANSLATION_SOURCE);
            case PaymentType::ApplePay():
                return $this->translator->l('Apple Pay', self::TRANSLATION_SOURCE);
            case PaymentType::BlikCode():
                return $this->translator->l('BLIK code', self::TRANSLATION_SOURCE);
            case PaymentType::BlikToken():
                return $this->translator->l('Remembered BLIK account', self::TRANSLATION_SOURCE);
            case PaymentType::PayByLink():
                return $this->translator->l('Pay by Link', self::TRANSLATION_SOURCE);
            case PaymentType::ShoppingLimit():
                return $this->translator->l('Shopping limit', self::TRANSLATION_SOURCE);
            case PaymentType::DeferredPayment():
                return $this->translator->l('Deferred payment', self::TRANSLATION_SOURCE);
            case PaymentType::CashOnDelivery():
                return $this->translator->l('Cash on Delivery', self::TRANSLATION_SOURCE);
            default:
                return $paymentType->value;
        }
    }
}

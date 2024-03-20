<?php

declare(strict_types=1);

namespace izi\prestashop\Translation;

use izi\prestashop\Common\Delivery\ServiceCode;

final class ServiceNameTranslator
{
    private const TRANSLATION_SOURCE = 'servicenametranslator';

    /**
     * @var LegacyTranslator
     */
    private $translator;

    public function __construct(LegacyTranslator $translator)
    {
        $this->translator = $translator;
    }

    public function getName(ServiceCode $serviceCode): string
    {
        switch ($serviceCode) {
            case ServiceCode::Cod():
                return $this->translator->l('Cash on Delivery', self::TRANSLATION_SOURCE);
            case ServiceCode::Pww():
                return $this->translator->l('Weekend Delivery', self::TRANSLATION_SOURCE);
            default:
                return $serviceCode->value;
        }
    }
}

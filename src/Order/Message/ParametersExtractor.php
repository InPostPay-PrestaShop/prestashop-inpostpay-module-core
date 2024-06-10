<?php

declare(strict_types=1);

namespace izi\prestashop\Order\Message;

use izi\prestashop\Common\Delivery\ServiceCode;
use izi\prestashop\MerchantApi\Model\Order\Request\CreateOrderRequest;
use izi\prestashop\Translation\LegacyTranslator;

final class ParametersExtractor implements ParametersExtractorInterface, ParameterDescriptorInterface
{
    private const TRANSLATION_SOURCE = 'parametersextractor';

    /**
     * @var LegacyTranslator
     */
    private $translator;

    public function __construct(LegacyTranslator $translator)
    {
        $this->translator = $translator;
    }

    public function extract(CreateOrderRequest $request): array
    {
        return [
            'request' => $request,
            'order_comments' => $request->getOrderDetails()->getOrderComments(),
            'payment_type' => $request->getOrderDetails()->getPaymentType()->value,
            'delivery_type' => $request->getDelivery()->getType()->value,
            'courier_note' => $request->getDelivery()->getCourierNote(),
            'delivery_codes' => array_map(static function (ServiceCode $code): string {
                return $code->value;
            }, $deliveryCodes = $request->getDelivery()->getOptionalServiceCodes()),
            'is_cod' => in_array(ServiceCode::Cod(), $deliveryCodes, true),
            'is_pww' => in_array(ServiceCode::Pww(), $deliveryCodes, true),
            'delivery_point' => $request->getDelivery()->getPoint(),
        ];
    }

    public function getDescriptions(): array
    {
        return [
            'payment_type' => $this->translator->l('code of used payment method', self::TRANSLATION_SOURCE),
            'delivery_point' => $this->translator->l('selected APM identifier', self::TRANSLATION_SOURCE),
            'delivery_codes' => $this->translator->l('codes of selected optional services', self::TRANSLATION_SOURCE),
            'is_pww' => $this->translator->l('if Weekend Delivery option was selected', self::TRANSLATION_SOURCE),
            'is_cod' => $this->translator->l('if Cash on Delivery option was selected', self::TRANSLATION_SOURCE),
        ];
    }
}

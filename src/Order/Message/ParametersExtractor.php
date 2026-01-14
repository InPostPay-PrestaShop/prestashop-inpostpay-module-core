<?php

declare(strict_types=1);

namespace izi\prestashop\Order\Message;

use izi\prestashop\Common\Delivery\ServiceCode;
use izi\prestashop\MerchantApi\Model\Order\Request\CreateOrderRequest;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ParametersExtractor implements ParametersExtractorInterface, ParameterDescriptorInterface
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(TranslatorInterface $translator)
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
            'is_cod' => \in_array(ServiceCode::Cod(), $deliveryCodes, true),
            'is_pww' => \in_array(ServiceCode::Pww(), $deliveryCodes, true),
            'delivery_point' => $request->getDelivery()->getPoint(),
        ];
    }

    public function getDescriptions(): array
    {
        return [
            'payment_type' => $this->translator->trans('used payment method code', [], 'Modules.Inpostizi.Order'),
            'delivery_point' => $this->translator->trans('the selected APM\'s identifier', [], 'Modules.Inpostizi.Order'),
            'delivery_codes' => $this->translator->trans('codes of selected optional services', [], 'Modules.Inpostizi.Order'),
            'is_pww' => $this->translator->trans('if {option} option was selected', [
                '{option}' => $this->translator->trans('Weekend Delivery', [], 'Modules.Inpostizi.Delivery'),
            ], 'Modules.Inpostizi.Order'),
            'is_cod' => $this->translator->trans('if {option} option was selected', [
                '{option}' => $this->translator->trans('Cash on Delivery', [], 'Modules.Inpostizi.Payment'),
            ], 'Modules.Inpostizi.Order'),
        ];
    }
}

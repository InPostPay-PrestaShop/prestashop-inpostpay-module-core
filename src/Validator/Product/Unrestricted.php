<?php

namespace izi\prestashop\Validator\Product;

use izi\prestashop\Common\Delivery\DeliveryType;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\InvalidArgumentException;

final class Unrestricted extends Constraint
{
    public const ORDER_DISALLOWED_ERROR = 'inpost_izi_order_disallowed';
    public const DELIVERY_DISALLOWED_ERROR = 'inpost_izi_delivery_disallowed';

    public $message = 'Product is restricted';

    /**
     * @var int|null
     */
    public $shopId;

    /**
     * @var DeliveryType|null
     */
    public $deliveryType;

    /**
     * @var bool if true, general restrictions will not be checked if delivery type is not specified
     */
    public $strict = false;

    public function __construct($options = null)
    {
        parent::__construct($options);

        if (null !== $this->deliveryType && !$this->deliveryType instanceof DeliveryType) {
            throw new InvalidArgumentException(\sprintf('Expected option "deliveryType" of type "%s|null", "%s" given.', DeliveryType::class, get_debug_type($this->deliveryType)));
        }
    }

    public function getDefaultOption(): string
    {
        return 'shopId';
    }
}

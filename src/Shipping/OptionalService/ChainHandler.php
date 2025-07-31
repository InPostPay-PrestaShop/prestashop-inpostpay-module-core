<?php

declare(strict_types=1);

namespace izi\prestashop\Shipping\OptionalService;

use izi\prestashop\Common\Delivery\DeliveryType;

final class ChainHandler implements OptionalServiceHandlerInterface
{
    /**
     * @var iterable<OptionalServiceHandlerInterface>
     */
    private $handlers;

    /**
     * @param iterable<OptionalServiceHandlerInterface> $handlers
     */
    public function __construct(iterable $handlers)
    {
        $this->handlers = $handlers;
    }

    public function supports(string $serviceCode): bool
    {
        foreach ($this->handlers as $handler) {
            if ($handler->supports($serviceCode)) {
                return true;
            }
        }

        return false;
    }

    public function handle(\Cart $cart, string $serviceCode, DeliveryType $deliveryType, bool $selected): void
    {
        foreach ($this->handlers as $handler) {
            if ($handler->supports($serviceCode)) {
                $handler->handle($cart, $serviceCode, $deliveryType, $selected);

                return;
            }
        }
    }
}

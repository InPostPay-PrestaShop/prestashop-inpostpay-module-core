<?php

namespace izi\prestashop\traits;

trait OrderStatusDescriberTrait
{
    private function getStatusDescription(\Order $order): string
    {
        $orderStateId = (int) $order->current_state;
        $statusDescription = \Configuration::get(sprintf('INPOST_PAY_OS_DESCRIPTION_%d', $orderStateId), $order->id_lang, null, $order->id_shop);

        return false !== $statusDescription && '' !== $statusDescription
            ? $statusDescription
            : (new \OrderState($orderStateId, $order->id_lang))->name;
    }
}

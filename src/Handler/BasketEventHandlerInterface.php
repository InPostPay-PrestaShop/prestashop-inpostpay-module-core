<?php

namespace izi\prestashop\Handler;

use izi\item\BasketNotice;

interface BasketEventHandlerInterface
{
    /**
     * @param object $event
     */
    public function handle(\Cart $cart, $event): ?BasketNotice;
}

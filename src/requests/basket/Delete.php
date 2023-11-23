<?php

namespace izi\prestashop\requests\basket;

use izi\prestashop\CartSession;
use izi\prestashop\Logger;
use izi\prestashop\rest\SignatureVerification;

class Delete
{
    public function handleRequest($basketId)
    {
        $signature = new SignatureVerification();
        $signature->check();
        Logger::response('200', "BASKET DROP FROM BA FOR {$basketId}");
        \izi\BasketIdentification::drop();
        CartSession::deleteByCartId($basketId);
        die(json_encode(['success' => true]));
    }
}

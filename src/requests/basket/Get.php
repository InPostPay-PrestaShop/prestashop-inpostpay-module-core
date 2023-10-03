<?php
namespace izi\prestashop\requests\basket;

use izi\prestashop\rest\SignatureVerification;

class Get {
    public function handleRequest($basketId) {
        $signature = new SignatureVerification();
        $signature->check();
        return \izi\prestashop\PrestashopBasket::getBasket(new \Cart($basketId))->encode();
    }
}

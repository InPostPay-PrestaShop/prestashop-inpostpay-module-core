<?php

namespace izi\prestashop\requests\merchant;

use izi\BasketIdentification;
use izi\prestashop\InpostIziPayPrestashop;
use izi\Storage;

class OrderConfirmation extends \izi\prestashop\requests\EventStream
{
    public function send()
    {
        $start = time();
        $id = Storage::findSession(BasketIdentification::INPOSTIZI_BASKET_ID);
        session_write_close();
        $response = [];
        $this->sendHelloMessage();
        while (empty($response)) {
            if (connection_aborted() || time() - $start > 10) {
                exit();
            }
            $response = InpostIziPayPrestashop::getInstance()->getController()->orderGetInterval($id);
        }
        $this->sendEventMessage('message', $response);
        exit();
    }
}

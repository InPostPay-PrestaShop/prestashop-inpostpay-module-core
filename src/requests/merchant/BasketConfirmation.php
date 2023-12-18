<?php

namespace izi\prestashop\requests\merchant;

use izi\BasketIdentification;
use izi\prestashop\InpostIziPayPrestashop;

class BasketConfirmation extends \izi\prestashop\requests\EventStream
{
    public function send()
    {
        $this->sseHeaders();

        $start = time();
        $id = BasketIdentification::get();
        session_write_close();
        $this->sendHelloMessage();
        for ($i = 0; $i < 1000; ++$i) {
            if (connection_aborted() || time() - $start > 10) {
                exit();
            }
            $response = InpostIziPayPrestashop::getInstance()->getController()->basketBindingGetInterval($id, 1);
            if (!empty($response)) {
                $this->sendEventMessage('message', $response);
                exit();
            }
        }
    }
}

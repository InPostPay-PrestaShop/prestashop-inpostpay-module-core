<?php

namespace izi\prestashop\requests\merchant;

use izi\BasketIdentification;
use izi\prestashop\CartSession;
use izi\prestashop\InpostIziPayPrestashop;
use izi\Storage;

class OrderConfirmation extends \izi\prestashop\requests\EventStream
{
    private $context;

    public function __construct(\Context $context)
    {
        $this->context = $context;
    }

    public function send()
    {
        $basketId = Storage::findSession(BasketIdentification::INPOSTIZI_BASKET_ID);

        if (null !== $customerId = $this->getCustomerIdByBasketId($basketId)) {
            $this->updateCustomer($customerId);
        }

        $this->sseHeaders();

        $start = time();
        session_write_close();
        $response = [];
        $this->sendHelloMessage();
        while (empty($response)) {
            if (connection_aborted() || time() - $start > 10) {
                exit();
            }
            $response = InpostIziPayPrestashop::getInstance()->getController()->orderGetInterval($basketId);
        }

        if ($this->needsRetry($response, $basketId)) {
            $this->retry(300);
        } else {
            CartSession::setRedirectedById($basketId, true);
            $this->sendEventMessage('message', $response);
        }

        exit();
    }

    private function needsRetry(array $response, string $basketId): bool
    {
        if ('redirect' !== $response['action']) {
            return false;
        }

        if (\Validate::isLoadedObject($this->context->customer)) {
            return false;
        }

        $customerId = $this->getCustomerIdByBasketId($basketId);

        return (new \Customer($customerId))->is_guest;
    }

    private function updateCustomer(int $customerId): void
    {
        if ((int) $this->context->customer->id === $customerId) {
            return;
        }

        $customer = new \Customer($customerId);

        if (!$customer->is_guest) {
            return;
        }

        $this->context->updateCustomer($customer);
    }

    private function getCustomerIdByBasketId(string $basketId): ?int
    {
        $session = CartSession::getByBasketId($basketId);

        if (null === $session) {
            throw new \RuntimeException('Session does not exist.');
        }

        if (!$session->order_id) {
            return null;
        }

        $order = new \Order($session->order_id);

        return (int) $order->id_customer;
    }
}

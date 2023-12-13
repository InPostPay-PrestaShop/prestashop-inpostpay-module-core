<?php

namespace izi\prestashop\Controller;

use izi\BasketIdentification;
use izi\BindingProvider;
use izi\InPostIzi;
use izi\prestashop\CartSession;
use izi\prestashop\models\InpostiziBasketSession;
use izi\prestashop\PrestashopBasket;
use izi\Storage;
use Symfony\Component\HttpFoundation\JsonResponse;

class MerchantController
{
    private $application;
    private $context;

    public function __construct(InPostIzi $application = null, \Context $context = null)
    {
        $this->application = $application ?? \izi\prestashop\InpostIziPayPrestashop::getInstance();
        $this->context = $context ?? \Context::getContext();
    }

    public function getLink(): JsonResponse
    {
        $binding = BindingProvider::getBinding();

        if (!isset($binding->inpost_basket_id)) {
            return new JsonResponse([
                'link' => '',
                'inpost_basket_id' => '',
            ]);
        }

        $inpost_basket_id = $binding->inpost_basket_id;
        $link = \izi\InPostIzi::getLinkUrl() . '?basket_id=' . $inpost_basket_id;

        return new JsonResponse([
            'link' => $link,
            'inpost_basket_id' => $inpost_basket_id,
        ]);
    }

    public function bindCart(string $prefix = null, string $number = null): JsonResponse
    {
        \izi\prestashop\CartSession::storeCurrent();
        CartSession::forceBasketStore();
        $browserId = Storage::findSession('BrowserId');
        if (!$browserId && isset($_COOKIE['BrowserId'])) {
            $browserId = $_COOKIE['BrowserId'];
        }
        if ($browserId) {
            \izi\prestashop\Logger::log('MAM BROWSER ID, WYSYŁAM KOSZYK');
            Storage::eraseSession('binding_get');
            $binding = BindingProvider::getBinding(true);
            if ($binding && isset($binding->browser_trusted) && $binding->browser_trusted) {
                \izi\prestashop\Logger::log('pre basket send');

                $basket = InPostIzi::getCartSessionClass()::getBasketCacheById(BasketIdentification::get());
                if (!$basket) {
                    $this->application->basketPut(false, true);
                }
                $response = \izi\prestashop\InpostIziPayPrestashop::getInstance()->getController()->basketBindingPost();
                $binding = BindingProvider::getBinding(true);
                foreach ($binding->client_details as $innerKey => $innerData) {
                    $binding->$innerKey = $innerData;
                }
                CartSession::setConfirmationToCart(\izi\BasketIdentification::get(), json_encode($binding));
                \izi\prestashop\Logger::log('post basket send and binding is ' . json_encode($binding));

                return new JsonResponse($response);
            }
        }
        \izi\prestashop\Logger::log('NIE MAM PRZEGLĄDARKI I :(');
        \izi\prestashop\Logger::log('Prefix to: ' . print_r($prefix, true));
        \izi\prestashop\Logger::log('Numer telefonu to: ' . print_r($number, true));
        $response = \izi\prestashop\InpostIziPayPrestashop::getInstance()->getController()->basketBindingPost($prefix, $number);

        return new JsonResponse($response);
    }

    public function checkOrderConfirmation()
    {
        /** @var InpostiziBasketSession $session */
        $session = CartSession::getObjectById(BasketIdentification::get());

        if ($session && $session->order_id) {
            $this->updateCustomer($session->order_id);
        }

        (new \izi\prestashop\requests\merchant\OrderConfirmation())->send();
    }

    public function checkBindingConfirmation()
    {
        (new \izi\prestashop\requests\merchant\BasketConfirmation())->send();
    }

    public function deleteBinding(): JsonResponse
    {
        $response = $this->application->getController()->basketBindingDelete();
        \izi\BasketIdentification::drop();
        $cart = $this->context->cart;
        $basketId = \izi\BasketIdentification::get();
        $data = PrestashopBasket::createForCart($cart, $basketId)->encode();
        $jsonResponse = json_encode(json_decode($data));
        CartSession::storeCurrent();
        CartSession::setBasketCacheById($basketId, $jsonResponse);
        \izi\prestashop\Logger::log("Saved {$basketId} for {$jsonResponse}");

        return new JsonResponse($response);
    }

    private function updateCustomer(int $orderId): void
    {
        $order = new \Order($orderId);
        if (!\Validate::isLoadedObject($order)) {
            return;
        }

        if ((int) $this->context->customer->id === (int) $order->id_customer) {
            return;
        }

        $customer = new \Customer($order->id_customer);
        if (!\Validate::isLoadedObject($customer) || !$customer->is_guest) {
            return;
        }

        $this->context->updateCustomer($customer);
    }
}

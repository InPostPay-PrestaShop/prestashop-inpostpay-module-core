<?php

namespace izi\prestashop\Controller\Api;

use izi\prestashop\CartSession;
use izi\prestashop\PrestashopOrder;
use izi\prestashop\rest\Exception\OrderNotFoundException;
use izi\prestashop\rest\order\Create;
use izi\prestashop\traits\OrderStatusDescriberTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class OrderController extends ApiController
{
    use OrderStatusDescriberTrait;

    public function create(Request $request): JsonResponse
    {
        $data = $this->decodeRequest($request);

        $handler = new Create();
        $orderId = $handler->handleRequest($data);
        $order = $this->getOrderById($orderId);

        $response = PrestashopOrder::getOrder($order, $data->order_details->basket_id);

        return new JsonResponse($response, 201);
    }

    public function get(int $orderId): JsonResponse
    {
        $order = $this->getOrderById($orderId);

        $basketId = CartSession::getBasketIdByCartId($order->id_cart);
        $response = PrestashopOrder::getOrder($order, $basketId);

        return new JsonResponse($response);
    }

    public function update(int $orderId, Request $request): JsonResponse
    {
        $order = $this->getOrderById($orderId);

        if ('inpostizi' !== $order->module) {
            return new JsonResponse([
                'order_status' => 'ORDER_COMPLETED',
            ]);
        }

        $data = $this->decodeRequest($request);
        $this->updateOrderStatus($order, $data);

        return new JsonResponse([
            'order_merchant_status_description' => $this->getStatusDescription($order),
        ]);
    }

    private function getOrderById(int $orderId): \Order
    {
        $order = new \Order($orderId);

        if (!\Validate::isLoadedObject($order)) {
            throw OrderNotFoundException::create();
        }

        return $order;
    }

    private function updateOrderStatus(\Order $order, $data): void
    {
        if ('AUTHORIZED' !== $data->event_data->payment_status) {
            return;
        }

        $statusId = (int) \Configuration::get('INPOST_PAY_authorized_payment', null, null, $order->id_shop);
        if (0 >= $statusId || $statusId === (int) $order->current_state) {
            return;
        }

        $order->setCurrentState($statusId);

        \izi\prestashop\Logger::log(sprintf('Updated order #%d status to #%d.', $order->id, $statusId));
    }
}

<?php

declare(strict_types=1);

namespace izi\prestashop\Controller\Api;

use izi\prestashop\CartSession;
use izi\prestashop\Common\Order\MerchantOrderStatusData;
use izi\prestashop\MerchantApi\Command\UpdateOrderCommand;
use izi\prestashop\MerchantApi\Exception\MalformedRequestException;
use izi\prestashop\MerchantApi\Model\Order\Request\OrderEvent;
use izi\prestashop\PrestashopOrder;
use izi\prestashop\MerchantApi\Exception\OrderNotFoundException;
use izi\prestashop\rest\order\Create;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class OrderController extends AbstractApiController
{
    public function create(Request $request): JsonResponse
    {
        $data = $this->_decodeRequest($request);

        $handler = new Create();
        $orderId = $handler->handleRequest($data);
        $order = $this->getOrderById($orderId);

        $response = PrestashopOrder::getOrder($order, $data->order_details->basket_id);

        return new JsonResponse($response, 201);
    }

    public function get(string $orderId): JsonResponse
    {
        $order = $this->getOrderById((int) $orderId);

        $basketId = CartSession::getBasketIdByCartId((int) $order->id_cart);
        $response = PrestashopOrder::getOrder($order, $basketId);

        return new JsonResponse($response);
    }

    public function update(string $orderId, Request $request): JsonResponse
    {
        $event = $this->decodeRequest($request, OrderEvent::class);
        $command = new UpdateOrderCommand($orderId, $event);

        /** @var MerchantOrderStatusData $orderStatus */
        $orderStatus = $this->bus->handle($command);

        return new JsonResponse($orderStatus);
    }

    private function getOrderById(int $orderId): \Order
    {
        $order = new \Order($orderId);

        if (!\Validate::isLoadedObject($order)) {
            throw OrderNotFoundException::create();
        }

        return $order;
    }

    private function _decodeRequest(Request $request)
    {
        $data = json_decode($request->getContent(), false);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw MalformedRequestException::create();
        }

        return $data;
    }
}

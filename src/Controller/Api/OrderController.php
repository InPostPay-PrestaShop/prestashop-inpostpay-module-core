<?php

declare(strict_types=1);

namespace izi\prestashop\Controller\Api;

use izi\prestashop\BasketApp\BasketAppClientInterface;
use izi\prestashop\MerchantApi\Command\CreateOrderCommand;
use izi\prestashop\MerchantApi\Command\GetOrderCommand;
use izi\prestashop\MerchantApi\Command\UpdateOrderCommand;
use izi\prestashop\MerchantApi\Model\Order\Request\CreateOrderRequest;
use izi\prestashop\MerchantApi\Model\Order\Request\OrderEvent;
use izi\prestashop\MerchantApi\Model\Order\Response\Order;
use izi\prestashop\MerchantApi\Model\Order\Response\OrderStatusData;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class OrderController extends AbstractApiController
{
    public function create(Request $request): JsonResponse
    {
        $data = $this->decodeRequest($request, CreateOrderRequest::class);
        $command = new CreateOrderCommand($data);

        /** @var Order $order */
        $order = $this->bus->handle($command);

        return $this->orderResponse($order, 201);
    }

    public function get(string $orderId): JsonResponse
    {
        /** @var Order $order */
        $order = $this->bus->handle(new GetOrderCommand($orderId));

        return $this->orderResponse($order);
    }

    public function update(string $orderId, Request $request): JsonResponse
    {
        $event = $this->decodeRequest($request, OrderEvent::class);
        $command = new UpdateOrderCommand($orderId, $event);

        /** @var OrderStatusData $orderStatus */
        $orderStatus = $this->bus->handle($command);

        return new JsonResponse($orderStatus);
    }

    private function orderResponse(Order $order, int $status = 200): JsonResponse
    {
        $data = $this->serializer->serialize($order, 'json', [
            'datetime_format' => BasketAppClientInterface::DATETIME_FORMAT,
            'datetime_timezone' => BasketAppClientInterface::DATETIME_ZONE,
        ]);

        return JsonResponse::create(null, $status)->setContent($data);
    }
}

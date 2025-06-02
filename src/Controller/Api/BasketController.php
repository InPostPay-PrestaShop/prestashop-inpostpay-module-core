<?php

declare(strict_types=1);

namespace izi\prestashop\Controller\Api;

use izi\prestashop\BasketApp\BasketAppClientInterface;
use izi\prestashop\MerchantApi\Command\AddProductToBasketCommand;
use izi\prestashop\MerchantApi\Command\ConfirmBasketBindingCommand;
use izi\prestashop\MerchantApi\Command\DeleteBasketBindingCommand;
use izi\prestashop\MerchantApi\Command\GetBasketCommand;
use izi\prestashop\MerchantApi\Command\UpdateBasketCommand;
use izi\prestashop\MerchantApi\Model\Basket\Request\BasketEvent;
use izi\prestashop\MerchantApi\Model\Basket\Request\BasketId;
use izi\prestashop\MerchantApi\Model\Basket\Request\BindingConfirmation;
use izi\prestashop\MerchantApi\Model\Basket\Response\Basket;
use izi\prestashop\MerchantApi\Model\Basket\Response\IdentifiableBasket;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class BasketController extends AbstractApiController
{
    public function get(string $basketId): JsonResponse
    {
        /** @var Basket $basket */
        $basket = $this->bus->handle(new GetBasketCommand($basketId));

        return $this->basketResponse($basket);
    }

    public function confirm(string $basketId, Request $request): JsonResponse
    {
        $confirmation = $this->decodeRequest($request, BindingConfirmation::class);
        $command = new ConfirmBasketBindingCommand($basketId, $confirmation);

        /** @var Basket $basket */
        $basket = $this->bus->handle($command);

        return $this->basketResponse($basket);
    }

    public function update(string $basketId, Request $request): JsonResponse
    {
        $event = $this->decodeRequest($request, BasketEvent::class);
        $command = new UpdateBasketCommand($basketId, $event);

        /** @var Basket $basket */
        $basket = $this->bus->handle($command);

        return $this->basketResponse($basket);
    }

    public function deleteBinding(string $basketId): JsonResponse
    {
        $this->bus->handle(new DeleteBasketBindingCommand($basketId));

        return JsonResponse::create()->setContent(null);
    }

    public function addProduct(string $productId, Request $request): JsonResponse
    {
        $basketId = $this->decodeRequest($request, BasketId::class);
        $command = new AddProductToBasketCommand($productId, $basketId);

        /** @var IdentifiableBasket $basket */
        $basket = $this->bus->handle($command);

        return $this->basketResponse($basket);
    }

    /**
     * @param Basket|IdentifiableBasket $basket
     */
    private function basketResponse($basket): JsonResponse
    {
        $data = $this->serializer->serialize($basket, 'json', [
            'datetime_format' => BasketAppClientInterface::DATETIME_FORMAT,
            'datetime_timezone' => BasketAppClientInterface::DATETIME_ZONE,
        ]);

        return JsonResponse::create()->setContent($data);
    }
}

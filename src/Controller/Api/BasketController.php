<?php

namespace izi\prestashop\Controller\Api;

use izi\item\Basket;
use izi\item\BasketNotice;
use izi\prestashop\CartSession;
use izi\prestashop\Handler\Factory\BasketEventHandlerFactory;
use izi\prestashop\PrestashopBasket;
use izi\prestashop\rest\Exception\BasketNotFoundException;
use izi\prestashop\traits\CartContextSetterTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class BasketController extends ApiController
{
    use CartContextSetterTrait;

    public function __construct(\Context $context = null)
    {
        $this->context = $context ?? \Context::getContext();
    }

    public function get(string $basketId): JsonResponse
    {
        $basket = $this->getBasketById($basketId);
        CartSession::setBasketCacheById($basketId, json_encode($basket));

        return new JsonResponse($basket);
    }

    public function confirm(string $basketId, Request $request): JsonResponse
    {
        $basket = $this->getBasketById($basketId);

        $data = $request->getContent();
        CartSession::setConfirmationToCart($basketId, $data);

        return new JsonResponse($basket);
    }

    public function update(string $basketId, Request $request): JsonResponse
    {
        $cart = $this->getCartByBasketId($basketId);

        $event = $this->decodeRequest($request);

        $handler = BasketEventHandlerFactory::create($this->context, $event);
        $notice = $handler->handle($cart, $event);

        if (null === $notice || BasketNotice::TYPE_ERROR !== $notice->getType()) {
            \CartRule::autoRemoveFromCart($this->context);
            \CartRule::autoAddToCart($this->context);
        }

        $basket = PrestashopBasket::createForCart($cart, $basketId);
        if (null !== $notice) {
            $basket->setNotice($notice);
        }

        CartSession::setBasketCacheById($basketId, json_encode($basket));
        CartSession::setBasketCouponsById($basketId, 1);

        return new JsonResponse($basket);
    }

    public function deleteBinding(string $basketId): JsonResponse
    {
        CartSession::deleteByBasketId($basketId);

        return new JsonResponse();
    }

    private function getBasketById(string $basketId): Basket
    {
        $cart = $this->getCartByBasketId($basketId);

        return PrestashopBasket::createForCart($cart, $basketId);
    }

    private function getCartByBasketId(string $basketId): \Cart
    {
        $cartId = CartSession::getCartIdByBasketId($basketId);

        if (!$cartId || !\Validate::isLoadedObject($cart = new \Cart($cartId))) {
            throw BasketNotFoundException::create();
        }

        $this->setUpContext($cart);

        return $cart;
    }
}

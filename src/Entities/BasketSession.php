<?php

declare(strict_types=1);

namespace izi\prestashop\Entities;

use izi\prestashop\MerchantApi\Model\Basket\Request\BasketEvent;
use izi\prestashop\MerchantApi\Model\Basket\Request\BindingConfirmation;
use izi\prestashop\MerchantApi\Model\Basket\Request\BindingStatus;
use izi\prestashop\MerchantApi\Model\Order\Request\CreateOrderRequest;
use izi\prestashop\ObjectModel\Entity\InPostIziBasketSession;
use izi\prestashop\Uuid\Uuid;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @implements SwitchableBasketSessionInterface<Cart>
 */
final class BasketSession implements SwitchableBasketSessionInterface
{
    /**
     * @var InPostIziBasketSession
     */
    private $model;

    /**
     * @var BasketInterface<\Cart>
     */
    private $cart;

    /**
     * @var BindingConfirmation|null
     */
    private $confirmation;

    /**
     * @var CreateOrderRequest|null
     */
    private $orderRequest;

    /**
     * @var callable(): BindingConfirmation|null
     */
    private $confirmationFactory;

    /**
     * @var callable(): CreateOrderRequest|null
     */
    private $orderRequestFactory;

    private $basketSwitched = false;

    private function __construct(BasketInterface $cart, InPostIziBasketSession $model)
    {
        $this->cart = $cart;
        $this->model = $model;
    }

    /**
     * @param BasketInterface<\Cart> $basket
     * @param class-string<InPostIziBasketSession> $modelClass
     */
    public static function new(BasketInterface $basket, Uuid $uuid, string $modelClass = InPostIziBasketSession::class): self
    {
        $model = new $modelClass();
        $model->session_id = (int) $basket->getId();
        $model->cart_id = (string) $uuid;

        return new self($basket, $model);
    }

    /**
     * @param BasketInterface<\Cart> $basket
     */
    public static function existing(InPostIziBasketSession $model, BasketInterface $basket, SerializerInterface $serializer): self
    {
        $session = new self($basket, $model);

        $session->confirmationFactory = \Closure::bind(function () use ($serializer) {
            if (null === $this->model->confirmation_response) {
                return null;
            }

            $value = base64_decode($this->model->confirmation_response);

            return $serializer->deserialize($value, BindingConfirmation::class, 'json');
        }, $session);

        $session->orderRequestFactory = \Closure::bind(function () use ($serializer) {
            if (null === $this->model->order_details) {
                return null;
            }

            $value = base64_decode($this->model->order_details);

            return $serializer->deserialize($value, CreateOrderRequest::class, 'json');
        }, $session);

        return $session;
    }

    public function getBasketId(): string
    {
        return $this->model->cart_id;
    }

    public function isBasketBound(): bool
    {
        $confirmation = $this->getBindingConfirmation();

        return null !== $confirmation && BindingStatus::Success() === $confirmation->getStatus();
    }

    public function getBindingConfirmation(): ?BindingConfirmation
    {
        if (null === $this->confirmation && null !== $this->confirmationFactory) {
            $this->confirmation = ($this->confirmationFactory)();
        }

        return $this->confirmation;
    }

    public function setBindingConfirmation(BindingConfirmation $confirmation): void
    {
        $this->confirmation = $confirmation;
        $this->model->confirmation_response = base64_encode(json_encode($confirmation));
    }

    public function unbind(): void
    {
        $this->confirmation = null;
        $this->model->confirmation_response = null;
    }

    public function updatedBy(BasketEvent $event): void
    {
        $this->model->coupons = true;
    }

    public function wasUpdated(): bool
    {
        $updated = (bool) $this->model->coupons;
        $this->model->coupons = false;

        return $updated;
    }

    public function getOrderId(): ?string
    {
        if (0 >= $orderId = (int) $this->model->order_id) {
            return null;
        }

        return (string) $orderId;
    }

    public function getOrderConfirmationUrl(): ?string
    {
        return $this->model->redirect_url;
    }

    public function finalize(string $orderId, string $orderConfirmationUrl, CreateOrderRequest $request): void
    {
        $this->orderRequest = $request;

        $this->model->order_id = (int) $orderId;
        $this->model->redirect_url = $orderConfirmationUrl;
        $this->model->order_details = base64_encode(json_encode($request));
    }

    public function wasUserRedirected(): bool
    {
        return $this->model->redirected;
    }

    public function redirect(): void
    {
        $this->model->redirected = true;
    }

    /**
     * @return BasketInterface<\Cart>
     */
    public function getBasket(): BasketInterface
    {
        return $this->cart;
    }

    public function getBindingApiKey(): ?string
    {
        return $this->model->binding_api_key;
    }

    public function setBindingApiKey(?string $key): void
    {
        $this->model->binding_api_key = $key;
    }

    /**
     * @param BasketInterface<\Cart> $basket
     */
    public function switchBasket(BasketInterface $basket): void
    {
        if (null !== $this->model->order_id) {
            throw new \DomainException('Cannot switch basket for finalized order.');
        }

        $this->cart = $basket;
        $this->model->session_id = (int) $basket->getId();
        $this->basketSwitched = true;
    }

    public function wasBasketSwitched(): bool
    {
        return $this->basketSwitched;
    }

    /**
     * @internal
     */
    public function getModel(): InPostIziBasketSession
    {
        return $this->model;
    }

    /**
     * @internal
     */
    public function getOrderRequest(): ?CreateOrderRequest
    {
        if (null === $this->orderRequest && null !== $this->orderRequestFactory) {
            $this->orderRequest = ($this->orderRequestFactory)();
        }

        return $this->orderRequest;
    }
}

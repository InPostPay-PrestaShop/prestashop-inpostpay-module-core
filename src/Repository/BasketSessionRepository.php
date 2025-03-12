<?php

declare(strict_types=1);

namespace izi\prestashop\Repository;

use izi\prestashop\Entities\BasketInterface;
use izi\prestashop\Entities\BasketSession;
use izi\prestashop\Entities\BasketSessionInterface;
use izi\prestashop\Entities\CartProxy;
use izi\prestashop\MerchantApi\Model\Order\Request\CreateOrderRequest;
use izi\prestashop\ObjectModel\Entity\InPostIziBasketSession;
use izi\prestashop\ObjectModel\ObjectManagerInterface;
use izi\prestashop\Uuid\Uuid;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @implements BasketSessionRepositoryInterface<BasketSession>
 */
final class BasketSessionRepository implements BasketSessionRepositoryInterface, OrderDataRepositoryInterface
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var ObjectManagerInterface
     */
    private $manager;

    private $sessionsByCartId = [];
    private $sessionsByBasketId = [];
    private $sessionsByOrderId = [];

    public function __construct(SerializerInterface $serializer, ObjectManagerInterface $manager)
    {
        $this->serializer = $serializer;
        $this->manager = $manager;
    }

    public function findByBasketId(string $basketId): ?BasketSessionInterface
    {
        if (isset($this->sessionsByBasketId[$basketId])) {
            return $this->sessionsByBasketId[$basketId];
        }

        $model = $this->manager
            ->getRepository(InPostIziBasketSession::class)
            ->findOneBy(['cart_id' => $basketId]);

        if (null === $model) {
            return null;
        }

        return $this->createExistingSession($model);
    }

    public function findByEntityId($id): ?BasketSessionInterface
    {
        if (0 >= $cartId = (int) $id) {
            return null;
        }

        if (isset($this->sessionsByCartId[$cartId])) {
            return $this->sessionsByCartId[$cartId];
        }

        $model = $this->manager
            ->getRepository(InPostIziBasketSession::class)
            ->findOneBy(['session_id' => $cartId]);

        if (null === $model) {
            return null;
        }

        return $this->createExistingSession($model);
    }

    public function findByOrderId(string $id): ?BasketSessionInterface
    {
        if (0 >= $orderId = (int) $id) {
            return null;
        }

        if (isset($this->sessionsByOrderId[$orderId])) {
            return $this->sessionsByOrderId[$orderId];
        }

        $model = $this->manager
            ->getRepository(InPostIziBasketSession::class)
            ->findOneBy(['order_id' => $orderId]);

        if (null === $model) {
            return null;
        }

        return $this->createExistingSession($model);
    }

    public function createNewSession(BasketInterface $basket): BasketSessionInterface
    {
        return BasketSession::new($basket, Uuid::v4());
    }

    public function persist(BasketSessionInterface $session): void
    {
        if (!$session instanceof BasketSession) {
            throw new \InvalidArgumentException(sprintf('Expected an instance of %s, %s given', BasketSession::class, get_class($session)));
        }

        $model = $session->getModel();
        $this->doPersist($model);

        if (isset($this->sessionsByBasketId[$model->cart_id]) && $session->wasBasketSwitched()) {
            $cartId = array_search($session, $this->sessionsByCartId, true);
            unset($this->sessionsByCartId[$cartId]);
        }

        $this->sessionsByBasketId[$model->cart_id] = $session;
        $this->sessionsByCartId[$model->session_id] = $session;
        if ($model->order_id) {
            $this->sessionsByOrderId[$model->order_id] = $session;
        }
    }

    public function refresh(BasketSessionInterface $session): void
    {
        $session->unbind();
        $model = $session->getModel();
        $this->manager->refresh($model);
    }

    public function resetBindingKeysCache(): void
    {
        $this->manager->getConnection()->update(InPostIziBasketSession::TABLE_NAME, [
            'binding_api_key' => null,
        ], ['order_id' => null]);
    }

    public function getOrderData(string $orderId): ?CreateOrderRequest
    {
        if (null === $session = $this->findByOrderId($orderId)) {
            return null;
        }

        return $session->getOrderRequest();
    }

    private function doPersist(InPostIziBasketSession $model): void
    {
        try {
            $this->manager->save($model);

            return;
        } catch (\PrestaShopDatabaseException $e) {
            if (null !== $model->id || !in_array((int) $e->getCode(), [1062, 1557, 1569, 1586], true)) {
                throw $e;
            }
        }

        // unique constraint violation, try to create with a different UUID
        $model->cart_id = (string) Uuid::v4();
        $this->doPersist($model);
    }

    private function createExistingSession(InPostIziBasketSession $model): BasketSessionInterface
    {
        $cart = new CartProxy((int) $model->session_id, $this->manager);
        $session = BasketSession::existing($model, $cart, $this->serializer);

        $this->sessionsByBasketId[$model->cart_id] = $session;
        $this->sessionsByCartId[$model->session_id] = $session;
        if ($model->order_id) {
            $this->sessionsByOrderId[$model->order_id] = $session;
        }

        return $session;
    }
}

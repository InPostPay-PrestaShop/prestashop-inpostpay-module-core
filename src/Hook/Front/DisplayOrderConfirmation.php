<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Front;

use izi\prestashop\Hook\HookInterface;
use izi\prestashop\Repository\BasketSessionRepositoryInterface;

final class DisplayOrderConfirmation implements HookInterface
{
    public const HOOK_NAME = 'displayOrderConfirmation';

    /**
     * @var BasketSessionRepositoryInterface
     */
    private $repository;

    /**
     * @var \Context
     */
    private $context;

    public function __construct(BasketSessionRepositoryInterface $repository, \Context $context)
    {
        $this->repository = $repository;
        $this->context = $context;
    }

    public static function getHookName(): string
    {
        return self::HOOK_NAME;
    }

    /**
     * @param array{order: \Order} $parameters
     */
    public function execute(array $parameters): string
    {
        $order = $parameters['order'] ?? null;

        if (!$order instanceof \Order) {
            throw new \InvalidArgumentException(sprintf('Parameter "order" expected to be an instance of "%s", "%s" given.', \Order::class, is_object($order) ? get_class($order) : gettype($order)));
        }

        if (null === $session = $this->repository->findByEntityId((int) $order->id_cart)) {
            return '';
        }

        if ($session->getBasketId() === $this->context->cookie->inpostizi_basket_id) {
            unset($this->context->cookie->inpostizi_basket_id);
        }

        return '';
    }
}

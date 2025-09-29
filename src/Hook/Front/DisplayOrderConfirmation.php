<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Front;

use izi\prestashop\Configuration\GeneralConfigurationInterface;
use izi\prestashop\Hook\Exception\InvalidHookParamException;
use izi\prestashop\Hook\HookInterface;
use izi\prestashop\Repository\BasketSessionRepositoryInterface;

final class DisplayOrderConfirmation implements HookInterface
{
    use ThankYouWidgetRendererTrait;

    public const HOOK_NAME = 'displayOrderConfirmation';

    /**
     * @var BasketSessionRepositoryInterface
     */
    private $repository;

    /**
     * @var \Context
     */
    private $context;

    public function __construct(BasketSessionRepositoryInterface $repository, \Context $context, \PaymentModule $paymentModule, GeneralConfigurationInterface $configuration)
    {
        $this->repository = $repository;
        $this->context = $context;
        $this->paymentModule = $paymentModule;
        $this->configuration = $configuration;
    }

    public static function getHookName(): string
    {
        return self::HOOK_NAME;
    }

    /**
     * @param \Order $order
     *
     * @return void
     */
    private function removeSavedBasketId(\Order $order): void
    {
        if (null === $session = $this->repository->findByEntityId((int) $order->id_cart)) {
            return;
        }

        if ($session->getBasketId() === $this->context->cookie->inpostizi_basket_id) {
            unset($this->context->cookie->inpostizi_basket_id);
        }
    }

    /**
     * @param array{order?: \Order} $parameters
     */
    public function execute(array $parameters): string
    {
        $order = $parameters['order'] ?? null;

        if (!$order instanceof \Order) {
            throw InvalidHookParamException::unexpectedType('order', $order, \Order::class);
        }

        $this->removeSavedBasketId($order);

        if ($this->shouldBeRendered(self::HOOK_NAME, $order)) {
            return $this->renderWidgetBlock();
        }

        return '';
    }
}

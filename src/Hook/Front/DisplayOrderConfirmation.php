<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Front;

use izi\prestashop\Cart\Storage\BasketIdStorageInterface;
use izi\prestashop\Cart\Storage\ChainBasketIdStorage;
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
     * @var BasketIdStorageInterface
     */
    private $basketIdStorage;

    /**
     * @param BasketIdStorageInterface $basketIdStorage
     */
    public function __construct(BasketSessionRepositoryInterface $repository, $basketIdStorage, \PaymentModule $paymentModule, GeneralConfigurationInterface $configuration)
    {
        $this->repository = $repository;
        $this->paymentModule = $paymentModule;
        $this->configuration = $configuration;

        if ($basketIdStorage instanceof \Context) {
            @trigger_error(\sprintf('Passing an instance of "Context" as the second argument to "%s()" is deprecated since version 2.7.0 / 3.3.0. Provide a "%s" instead.', __METHOD__, BasketIdStorageInterface::class), \E_USER_DEPRECATED);
            $basketIdStorage = ChainBasketIdStorage::createDefault($basketIdStorage);
        }

        if (!$basketIdStorage instanceof BasketIdStorageInterface) {
            throw new \InvalidArgumentException(\sprintf('Expected $basketIdStorage to be an instance of "%s", got "%s".', BasketIdStorageInterface::class, get_debug_type($basketIdStorage)));
        }

        $this->basketIdStorage = $basketIdStorage;
    }

    public static function getHookName(): string
    {
        return self::HOOK_NAME;
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

    /**
     * @param \Order $order
     */
    private function removeSavedBasketId(\Order $order): void
    {
        if (null === $session = $this->repository->findByEntityId((int) $order->id_cart)) {
            return;
        }

        if ($session->getBasketId() === $this->basketIdStorage->getBasketId()) {
            $this->basketIdStorage->setBasketId(null);
        }
    }
}

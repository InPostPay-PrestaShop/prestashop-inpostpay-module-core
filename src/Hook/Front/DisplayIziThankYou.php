<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Front;

use izi\prestashop\Configuration\GeneralConfigurationInterface;
use izi\prestashop\Hook\Exception\InvalidHookParamException;
use izi\prestashop\Hook\HookInterface;
use izi\prestashop\ObjectModel\Repository\ObjectRepositoryInterface;
use PrestaShop\PrestaShop\Adapter\Presenter\Order\OrderLazyArray;

final class DisplayIziThankYou implements HookInterface
{
    use ThankYouWidgetRendererTrait;

    public const HOOK_NAME = 'displayIziThankYou';

    /**
     * @var ObjectRepositoryInterface<\Order>|null
     */
    private $orderRepository;

    /**
     * @param ObjectRepositoryInterface<\Order>|null $orderRepository
     */
    public function __construct(\PaymentModule $paymentModule, GeneralConfigurationInterface $configuration, ?ObjectRepositoryInterface $orderRepository = null)
    {
        $this->paymentModule = $paymentModule;
        $this->configuration = $configuration;
        $this->orderRepository = $orderRepository;
    }

    public static function getHookName(): string
    {
        return self::HOOK_NAME;
    }

    /**
     * @param array{order?: OrderLazyArray|array} $parameters
     *
     * @return string
     */
    public function execute(array $parameters): string
    {
        $order = $parameters['order'] ?? null;

        if (!is_array($order) && !$order instanceof \ArrayAccess) {
            throw InvalidHookParamException::unexpectedType('order', $order, 'array|ArrayAccess');
        }

        if (null === $moduleName = $this->getPaymentModuleName($order)) {
            throw new InvalidHookParamException('Expected offset "details[module]" in parameter "order".');
        }

        if (
            $this->paymentModule->name !== $moduleName
            || !$this->shouldDisplayHook(self::HOOK_NAME)
        ) {
            return '';
        }

        return $this->renderWidgetBlock();
    }

    /**
     * @param array|\ArrayAccess $data
     */
    private function getPaymentModuleName($order): ?string
    {
        if (isset($order['details']['module'])) {
            return $order['details']['module'];
        }

        if (0 >= $orderId = (int) ($order['details']['id'] ?? 0)) {
            return null;
        }

        if (null === $model = $this->getOrder($orderId)) {
            return null;
        }

        return $model->module;
    }

    private function getOrder(int $id): ?\Order
    {
        if (isset($this->orderRepository)) {
            return $this->orderRepository->find($id);
        }

        $order = new \Order($id);

        return \Validate::isLoadedObject($order) ? $order : null;
    }
}

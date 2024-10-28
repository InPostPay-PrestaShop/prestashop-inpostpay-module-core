<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Front;

use izi\prestashop\Configuration\GeneralConfigurationInterface;
use izi\prestashop\Hook\HookInterface;
use PrestaShop\PrestaShop\Adapter\Presenter\Order\OrderDetailLazyArray;
use PrestaShop\PrestaShop\Adapter\Presenter\Order\OrderLazyArray;

final class DisplayIziThankYou implements HookInterface
{
    use ThankYouWidgetRendererTrait;

    public const HOOK_NAME = 'displayIziThankYou';

    /**
     * @var \PaymentModule
     */
    private $paymentModule;

    public function __construct(\PaymentModule $paymentModule, GeneralConfigurationInterface $configuration)
    {
        $this->paymentModule = $paymentModule;
        $this->configuration = $configuration;
    }

    public static function getHookName(): string
    {
        return self::HOOK_NAME;
    }

    /**
     * @param array{order?: OrderLazyArray} $parameters
     *
     * @return string
     */
    public function execute(array $parameters): string
    {
        $order = $parameters['order'] ?? null;

        $orderObj = $this->getOrderObject($order);

        if (null === $orderObj) {
            throw new \InvalidArgumentException('Order object is required.');
        }

        if (!\Validate::isLoadedObject($orderObj)) {
            throw new \InvalidArgumentException(sprintf('Order with id "%s" not found.', $orderObj->id));
        }

        if ($this->shouldBeRendered(self::HOOK_NAME, $orderObj)) {
            return $this->renderWidgetBlock();
        }

        return '';
    }

    /**
     * @param $presentedOrder OrderLazyArray|array
     *
     * @return \Order|null
     */
    private function getOrderObject($presentedOrder): ?\Order
    {
        $orderId = null;

        if ($presentedOrder instanceof OrderLazyArray) {
            /** @var OrderDetailLazyArray $orderDetails */
            $orderDetails = $presentedOrder->getDetails();

            $orderId = $orderDetails->getId();
        } elseif (!empty($presentedOrder['details']['id'])) {
            $orderId = $presentedOrder['details']['id'];
        }

        if (null === $orderId) {
            return null;
        }

        return new \Order($orderId);
    }
}

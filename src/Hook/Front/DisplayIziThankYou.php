<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Front;

use izi\prestashop\Configuration\ThankYouWidgetConfigurationInterface;
use izi\prestashop\Hook\HookInterface;
use PrestaShop\PrestaShop\Adapter\Presenter\Order\OrderLazyArray;
use PrestaShop\PrestaShop\Adapter\Presenter\Order\OrderDetailLazyArray;

final class DisplayIziThankYou implements HookInterface
{
    use ThankYouWidgetRendererTrait;

    public const HOOK_NAME = 'displayIziThankYou';

    /**
     * @var \PaymentModule
     */
    private $paymentModule;

    /**
     * @var \ThankYouWidgetConfigurationInterface
     */
    private $configuration;

    public function __construct(
        \PaymentModule $paymentModule,
        ThankYouWidgetConfigurationInterface $configuration
    )
    {
        $this->paymentModule = $paymentModule;
        $this->configuration = $configuration;
    }

    public static function getHookName(): string
    {
        return self::HOOK_NAME;
    }

    /**
     * @param array{order: \OrderLazyArray} $parameters
     * @return string
     */
    public function execute(array $parameters): string
    {
        $order = $parameters['order'] ?? null;

        if (!$order instanceof OrderLazyArray) {
            throw new \InvalidArgumentException(sprintf('Parameter "order" expected to be an instance of "%s", "%s" given.', OrderLazyArray::class, is_object($order) ? get_class($order) : gettype($order)));
        }

        /** @var OrderDetailLazyArray $orderDetails */
        $orderDetails = $order->getDetails();

        $orderId = $orderDetails->getId();
        $orderObj = new \Order($orderId);

        if (!\Validate::isLoadedObject($orderObj)) {
            throw new \InvalidArgumentException(sprintf('Order with id "%s" not found.', $orderId));
        }

        if ($this->shouldBeRendered(self::HOOK_NAME, $orderObj)) {
            return $this->renderWidgetBlock();
        }

        return '';
    }
}

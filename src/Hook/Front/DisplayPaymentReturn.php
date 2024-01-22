<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Front;

use izi\prestashop\Hook\HookInterface;
use izi\prestashop\Configuration\ThankYouWidgetConfigurationInterface;

final class DisplayPaymentReturn implements HookInterface
{
    use ThankYouWidgetRendererTrait;

    public const HOOK_NAME = 'displayPaymentReturn';

    /**
     * @var \PaymentModule
     */
    private $paymentModule;

    /**
     * @var ThankYouWidgetConfigurationInterface
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
     * @param array{order: \Order} $parameters
     */
    public function execute(array $parameters): string
    {
        $order = $parameters['order'] ?? null;

        if (!$order instanceof \Order) {
            throw new \InvalidArgumentException(sprintf('Parameter "cart" expected to be an instance of "%s", "%s" given.', \Order::class, is_object($order) ? get_class($order) : gettype($order)));
        }

        if ($this->shouldBeRendered(self::HOOK_NAME, $order)) {
            return $this->renderWidgetBlock();
        }

        return '';
    }
}

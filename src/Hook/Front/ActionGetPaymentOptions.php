<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Front;

use izi\prestashop\Common\BindingPlace;
use izi\prestashop\Configuration\GuiConfigurationInterface;
use izi\prestashop\Hook\Exception\InvalidHookParamException;
use izi\prestashop\Hook\HookInterface;
use izi\prestashop\Payment\PaymentCurrencyChecker;
use izi\prestashop\View\Templating\RendererInterface;
use PrestaShop\PrestaShop\Core\Module\WidgetInterface;
use PrestaShop\PrestaShop\Core\Payment\PaymentOption;
use Symfony\Component\HttpFoundation\Request;

final class ActionGetPaymentOptions implements HookInterface
{
    public const HOOK_NAME = 'paymentOptions';

    /**
     * @var \PaymentModule&WidgetInterface
     */
    private $paymentModule;

    /**
     * @var PaymentCurrencyChecker
     */
    private $currencyChecker;

    /**
     * @var GuiConfigurationInterface
     */
    private $configuration;

    /**
     * @var RendererInterface
     */
    private $renderer;

    /**
     * @param \PaymentModule&WidgetInterface $paymentModule
     */
    public function __construct(\PaymentModule $paymentModule, PaymentCurrencyChecker $currencyChecker, GuiConfigurationInterface $configuration, RendererInterface $renderer)
    {
        $this->paymentModule = $paymentModule;
        $this->currencyChecker = $currencyChecker;
        $this->configuration = $configuration;
        $this->renderer = $renderer;
    }

    public static function getHookName(): string
    {
        return self::HOOK_NAME;
    }

    /**
     * @param array{cart?: \Cart, request?: Request} $parameters
     *
     * @return PaymentOption[]
     */
    public function execute(array $parameters): array
    {
        $cart = $parameters['cart'] ?? null;

        if (!$cart instanceof \Cart) {
            throw InvalidHookParamException::unexpectedType('cart', $cart, \Cart::class);
        }

        if (0 >= (int) $cart->id || !$this->currencyChecker->check($this->paymentModule, (int) $cart->id_currency)) {
            return [];
        }

        $widget = $this->paymentModule->renderWidget(self::HOOK_NAME, [
            'config' => $this->configuration->getDisplayConfiguration(BindingPlace::OrderCreate()),
            'request' => $parameters['request'] ?? null,
        ]);

        if ('' === $widget) {
            return [];
        }

        $paymentOption = $this->createPaymentOption($widget);

        return [$paymentOption];
    }

    private function createPaymentOption(string $widget): PaymentOption
    {
        $additionalInfo = $this->renderer->render('module:inpostizi/views/templates/front/buttonWidget.tpl', [
            'widget' => $widget,
            'styles' => [],
        ]);

        return (new PaymentOption())
            ->setModuleName($this->paymentModule->name)
            ->setCallToActionText($this->paymentModule->getTranslator()->trans('Pay with InPost Pay', [], 'Modules.Inpostizi.Checkout'))
            ->setBinary(true) // does it do anything?
            ->setAdditionalInformation($additionalInfo);
    }
}

<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Front;

use izi\prestashop\Common\BindingPlace;
use izi\prestashop\Configuration\GeneralConfigurationInterface;
use izi\prestashop\Configuration\GuiConfigurationInterface;
use izi\prestashop\Hook\HookInterface;
use izi\prestashop\View\Templating\RendererInterface;
use PrestaShop\PrestaShop\Core\Module\WidgetInterface;
use Symfony\Component\HttpFoundation\Request;

final class DisplayCheckoutSummaryTop implements HookInterface
{
    use ButtonWidgetRendererTrait;

    public const HOOK_NAME = 'displayCheckoutSummaryTop';

    /**
     * @var RendererInterface
     */
    private $renderer;

    /**
     * @var GeneralConfigurationInterface
     */
    private $generalConfiguration;

    public function __construct(GuiConfigurationInterface $configuration, GeneralConfigurationInterface $generalConfiguration, WidgetInterface $module, RendererInterface $renderer)
    {
        $this->generalConfiguration = $generalConfiguration;
        $this->configuration = $configuration;
        $this->module = $module;
        $this->renderer = $renderer;
    }

    public static function getHookName(): string
    {
        return self::HOOK_NAME;
    }

    /**
     * @param array{request?: Request} $parameters
     */
    public function execute(array $parameters): string
    {
        $binding = BindingPlace::CheckoutPage();

        if (
            self::HOOK_NAME !== $this->generalConfiguration->getCheckoutButtonDisplayHook()
            || '' === $widget = $this->renderWidget($binding, $parameters, self::HOOK_NAME)
        ) {
            return '';
        }

        return $this->renderer->render('module:inpostizi/views/templates/hook/buttonWidget.tpl', [
            'widget' => $widget,
            'styles' => $this->getHtmlStyles($binding),
        ]);
    }
}

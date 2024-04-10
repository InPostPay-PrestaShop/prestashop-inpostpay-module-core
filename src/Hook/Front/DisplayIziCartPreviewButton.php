<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Front;

use izi\prestashop\Common\BindingPlace;
use izi\prestashop\Configuration\GuiConfigurationInterface;
use izi\prestashop\Hook\HookInterface;
use izi\prestashop\View\Templating\RendererInterface;
use PrestaShop\PrestaShop\Core\Module\WidgetInterface;
use Symfony\Component\HttpFoundation\Request;

final class DisplayIziCartPreviewButton implements HookInterface
{
    use ButtonWidgetRendererTrait;

    public const HOOK_NAME = 'displayIziCartPreviewButton';

    /**
     * @var RendererInterface
     */
    private $renderer;

    public function __construct(GuiConfigurationInterface $configuration, WidgetInterface $module, RendererInterface $renderer)
    {
        $this->configuration = $configuration;
        $this->module = $module;
        $this->renderer = $renderer;
    }

    public static function getHookName(): string
    {
        return self::HOOK_NAME;
    }

    /**
     * @param array{request: Request} $parameters
     */
    public function execute(array $parameters): string
    {
        $binding = BindingPlace::MiniCartPage();

        if ('' === $widget = $this->renderWidget($binding, $parameters, self::HOOK_NAME)) {
            return '';
        }

        return $this->renderer->render('module:inpostizi/views/templates/hook/buttonWidget.tpl', [
            'widget' => $widget,
            'styles' => $this->getHtmlStyles($binding),
        ]);
    }
}

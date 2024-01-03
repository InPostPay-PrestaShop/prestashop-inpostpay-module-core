<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Front;

use izi\prestashop\Configuration\WidgetConfigurationInterface;
use izi\prestashop\Hook\HookInterface;
use izi\prestashop\View\Templating\RendererInterface;
use PrestaShop\PrestaShop\Core\Module\WidgetInterface;
use Symfony\Component\HttpFoundation\Request;

final class DisplayShoppingCartFooter implements HookInterface
{
    use CartWidgetRendererTrait;

    public const HOOK_NAME = 'displayShoppingCartFooter';

    /**
     * @var RendererInterface
     */
    private $renderer;

    public function __construct(WidgetConfigurationInterface $configuration, WidgetInterface $module, RendererInterface $renderer)
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
        if ('' === $widget = $this->renderWidget($parameters, self::HOOK_NAME)) {
            return '';
        }

        return $this->renderer->render('module:inpostizi/views/templates/hook/mymodule.tpl', [
            'widget' => $widget,
            'styles' => $this->getHtmlStyles(),
        ]);
    }
}

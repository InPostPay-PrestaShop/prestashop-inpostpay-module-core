<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Front;

use izi\prestashop\Configuration\GeneralConfigurationInterface;
use izi\prestashop\Configuration\GuiConfigurationInterface;
use izi\prestashop\Hook\HookInterface;
use izi\prestashop\Repository\BasketSessionRepositoryInterface;
use izi\prestashop\View\Templating\RendererInterface;
use PrestaShop\PrestaShop\Adapter\Presenter\Product\ProductLazyArray;
use PrestaShop\PrestaShop\Core\Module\WidgetInterface;
use Symfony\Component\HttpFoundation\Request;

final class DisplayIziProductButton implements HookInterface
{
    use ProductWidgetRendererTrait;

    public const HOOK_NAME = 'displayIziProductButton';

    /**
     * @var RendererInterface
     */
    private $renderer;

    public function __construct(GuiConfigurationInterface $configuration, GeneralConfigurationInterface $generalConfiguration, WidgetInterface $module, RendererInterface $renderer, \Context $context, BasketSessionRepositoryInterface $basketSessionRepository)
    {
        $this->configuration = $configuration;
        $this->generalConfiguration = $generalConfiguration;
        $this->module = $module;
        $this->renderer = $renderer;
        $this->context = $context;
        $this->basketSessionRepository = $basketSessionRepository;
    }

    public static function getHookName(): string
    {
        return self::HOOK_NAME;
    }

    /**
     * @param array{product?: ProductLazyArray, request?: Request} $parameters
     */
    public function execute(array $parameters): string
    {
        $product = $this->getProduct($parameters);

        if (self::HOOK_NAME !== $this->generalConfiguration->getProductCardDisplayHook()) {
            return '';
        }

        if (!$this->isWidgetDisplayed($product)) {
            return '';
        }

        return $this->renderer->render('module:inpostizi/views/templates/front/productButtonWidget.tpl', [
            'widget' => $this->renderWidget($product, $parameters, self::HOOK_NAME),
            'styles' => $this->getHtmlStyles(),
            'hookName' => self::HOOK_NAME,
            'idProduct' => $product['id_product'],
        ]);
    }
}

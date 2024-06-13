<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Front;

use izi\prestashop\Configuration\GeneralConfigurationInterface;
use izi\prestashop\Configuration\GuiConfigurationInterface;
use izi\prestashop\Hook\PrestaShopVersionAwareHookInterface;
use izi\prestashop\Hook\VersionRange;
use izi\prestashop\Repository\BasketSessionRepositoryInterface;
use izi\prestashop\View\Templating\RendererInterface;
use PrestaShop\PrestaShop\Adapter\Presenter\Product\ProductLazyArray;
use PrestaShop\PrestaShop\Core\Module\WidgetInterface;
use Symfony\Component\HttpFoundation\Request;

final class DisplayProductActions implements PrestaShopVersionAwareHookInterface
{
    use ProductWidgetRendererTrait;

    public const HOOK_NAME = 'displayProductActions';

    /**
     * @var RendererInterface
     */
    private $renderer;

    public function __construct(
        GuiConfigurationInterface $configuration,
        GeneralConfigurationInterface $generalConfiguration,
        WidgetInterface $module,
        RendererInterface $renderer,
        \Context $context,
        BasketSessionRepositoryInterface $basketSessionRepository
    ) {
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

    public static function getVersionRange(): VersionRange
    {
        return new VersionRange('1.7.6');
    }

    /**
     * @param array{product?: ProductLazyArray, request?: Request} $parameters
     */
    public function execute(array $parameters): string
    {
        $product = $parameters['product'] ?? null;

        if (!isset($product['id_product']) || !is_numeric($product['id_product'])) {
            throw new \InvalidArgumentException(sprintf('Parameter "product" expected to be an instance of "%s", "%s" given.', ProductLazyArray::class, get_debug_type($product)));
        }

        if (self::HOOK_NAME !== $this->generalConfiguration->getProductCardDisplayHook()) {
            return '';
        }

        $refresh = $this->generalConfiguration->isFullPageCacheModuleInUse();

        return $this->renderer->render('module:inpostizi/views/templates/hook/productButtonWidget.tpl', [
            'widget' => $refresh ? '' : $this->renderWidget($product, $parameters, self::HOOK_NAME),
            'refresh' => $refresh,
            'styles' => $this->getHtmlStyles(),
            'hookName' => self::HOOK_NAME,
            'idProduct' => $product['id_product'],
        ]);
    }
}

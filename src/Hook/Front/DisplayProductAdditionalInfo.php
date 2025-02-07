<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Front;

use izi\prestashop\Configuration\ApiConfigurationInterface;
use izi\prestashop\Configuration\GeneralConfigurationInterface;
use izi\prestashop\Configuration\GuiConfigurationInterface;
use izi\prestashop\Hook\AliasedHookInterface;
use izi\prestashop\Hook\VersionRange;
use izi\prestashop\Repository\BasketSessionRepositoryInterface;
use izi\prestashop\View\Templating\RendererInterface;
use PrestaShop\PrestaShop\Adapter\Presenter\Product\ProductLazyArray;
use PrestaShop\PrestaShop\Core\Module\WidgetInterface;
use Symfony\Component\HttpFoundation\Request;

final class DisplayProductAdditionalInfo implements AliasedHookInterface
{
    use ProductWidgetRendererTrait;

    public const HOOK_NAME = 'displayProductAdditionalInfo';
    private const HOOK_ALIAS = 'displayProductButtons';

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
        BasketSessionRepositoryInterface $basketSessionRepository,
        ?ApiConfigurationInterface $apiConfiguration = null
    ) {
        $this->configuration = $configuration;
        $this->generalConfiguration = $generalConfiguration;
        $this->module = $module;
        $this->renderer = $renderer;
        $this->context = $context;
        $this->basketSessionRepository = $basketSessionRepository;
        $this->apiConfiguration = $apiConfiguration;
    }

    public static function getHookName(): string
    {
        return self::HOOK_NAME;
    }

    public static function getAliases(): array
    {
        return [
            self::HOOK_ALIAS => new VersionRange('1.7.0', '1.7.1'),
            self::HOOK_NAME => new VersionRange('1.7.1', '1.7.6'),
        ];
    }

    /**
     * @param array{product?: ProductLazyArray|array, request?: Request} $parameters
     */
    public function execute(array $parameters): string
    {
        $product = $parameters['product'] ?? null;

        if (!isset($product['id_product']) || !is_numeric($product['id_product'])) {
            throw new \InvalidArgumentException(sprintf('Expected parameter "product" to be an array or an instance of "%s", "%s" given.', ProductLazyArray::class, get_debug_type($product)));
        }

        if (self::HOOK_NAME !== $this->generalConfiguration->getProductCardDisplayHook()) {
            return '';
        }

        $refresh = $this->shouldRenderCacheableHookContent($parameters['request'] ?? null);

        return $this->renderer->render('module:inpostizi/views/templates/hook/productButtonWidget.tpl', [
            'widget' => $refresh ? '' : $this->renderWidget($product, $parameters, self::HOOK_NAME),
            'refresh' => $refresh,
            'styles' => $this->getHtmlStyles(),
            'hookName' => self::HOOK_NAME,
            'idProduct' => $product['id_product'],
        ]);
    }
}

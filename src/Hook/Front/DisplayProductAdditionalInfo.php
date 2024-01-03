<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Front;

use izi\prestashop\Configuration\WidgetConfigurationInterface;
use izi\prestashop\Hook\AliasedHookInterface;
use izi\prestashop\Hook\VersionRange;
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

    public static function getAliases(): array
    {
        return [
            self::HOOK_ALIAS => new VersionRange('1.7.0', '1.7.1'),
            self::HOOK_NAME => new VersionRange('1.7.1', '1.7.6'),
        ];
    }

    /**
     * @param array{product: ProductLazyArray|array, request: Request} $parameters
     */
    public function execute(array $parameters): string
    {
        $product = $parameters['product'] ?? null;

        if (!isset($product['id_product']) || !is_numeric($product['id_product'])) {
            throw new \InvalidArgumentException(sprintf('Parameter "product" expected to be an array or an instance of "%s", "%s" given.', ProductLazyArray::class, is_object($product) ? get_class($product) : gettype($product)));
        }

        if ('' === $widget = $this->renderWidget((int) $product['id_product'], $parameters, self::HOOK_NAME)) {
            return '';
        }

        return $this->renderer->render('module:inpostizi/views/templates/hook/mymodule.tpl', [
            'widget' => $widget,
            'styles' => [],
        ]);
    }
}

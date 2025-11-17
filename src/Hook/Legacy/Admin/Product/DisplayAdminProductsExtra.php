<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Legacy\Admin\Product;

use izi\prestashop\Hook\Exception\InvalidHookParamException;
use izi\prestashop\Hook\PrestaShopVersionAwareHookInterface;
use izi\prestashop\Hook\VersionRange;

final class DisplayAdminProductsExtra implements PrestaShopVersionAwareHookInterface
{
    public const HOOK_NAME = 'displayAdminProductsExtra';

    /**
     * @var ProductOptionsFormRenderer
     */
    private $renderer;

    public function __construct(ProductOptionsFormRenderer $renderer)
    {
        $this->renderer = $renderer;
    }

    public static function getHookName(): string
    {
        return self::HOOK_NAME;
    }

    public static function getVersionRange(): VersionRange
    {
        return new VersionRange(null, '1.7.2');
    }

    /**
     * @param array{id_product: int} $parameters
     */
    public function execute(array $parameters): string
    {
        $productId = $parameters['id_product'] ?? null;

        if (!is_int($productId) && (!is_string($productId) || !ctype_digit($productId))) {
            throw InvalidHookParamException::unexpectedType('id_product', $productId, 'int');
        }

        return $this->renderer->render((int) $productId, 'module:inpostizi/views/templates/hook/legacy/admin/product/options_form_tab.tpl', [
            'label' => false,
        ]);
    }
}

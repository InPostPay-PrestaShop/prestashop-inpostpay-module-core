<?php

declare(strict_types=1);

namespace izi\prestashop\Product;

use izi\prestashop\Enum\StringEnum;
use izi\prestashop\Translation\LegacyTranslator;
use izi\prestashop\Translation\TranslatableInterface;
use PrestaShop\PrestaShop\Adapter\Presenter\Product\ProductLazyArray;

/**
 * @method static self Standard()
 * @method static self Combination()
 * @method static self Customizable()
 * @method static self Pack()
 * @method static self Virtual()
 */
final class ProductType extends StringEnum implements TranslatableInterface
{
    private const STANDARD = 'standard';
    private const COMBINATION = 'combination';
    private const CUSTOMIZABLE = 'customizable';
    private const PACK = 'pack';
    private const VIRTUAL = 'virtual';

    public function trans(LegacyTranslator $translator): string
    {
        switch ($this) {
            case self::Standard():
                return $translator->l('Standard products', 'producttype');
            case self::Combination():
                return $translator->l('Products with combinations', 'producttype');
            case self::Customizable():
                return $translator->l('Customizable products', 'producttype');
            case self::Pack():
                return $translator->l('Packs of products', 'producttype');
            case self::Virtual():
                return $translator->l('Virtual products', 'producttype');
            default:
                throw new \LogicException('Unreachable statement.');
        }
    }

    /**
     * @param ProductLazyArray|array $product
     */
    public static function fromProductData($product): self
    {
        if (!is_array($product) && !$product instanceof \ArrayAccess) {
            throw new \InvalidArgumentException(sprintf('Expected $product to be an array or an instance of "%s", "%s" given.', ProductLazyArray::class, get_debug_type($product)));
        }

        if ($product['is_virtual'] ?? false) {
            return self::Virtual();
        }

        if ($product['pack'] ?? false) {
            return self::Pack();
        }

        if (0 < (int) ($product['id_product_attribute'] ?? 0)) {
            return self::Combination();
        }

        return self::Standard();
    }
}

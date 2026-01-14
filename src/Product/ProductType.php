<?php

declare(strict_types=1);

namespace izi\prestashop\Product;

use izi\prestashop\Enum\StringEnum;
use izi\prestashop\Translation\TranslatableInterface;
use PrestaShop\PrestaShop\Adapter\Presenter\Product\ProductLazyArray;
use Symfony\Contracts\Translation\TranslatorInterface;

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

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        switch ($this) {
            case self::Standard():
                return $translator->trans('Standard products', [], 'Modules.Inpostizi.Product', $locale);
            case self::Combination():
                return $translator->trans('Products with combinations', [], 'Modules.Inpostizi.Product', $locale);
            case self::Customizable():
                return $translator->trans('Customizable products', [], 'Modules.Inpostizi.Product', $locale);
            case self::Pack():
                return $translator->trans('Packs of products', [], 'Modules.Inpostizi.Product', $locale);
            case self::Virtual():
                return $translator->trans('Virtual products', [], 'Modules.Inpostizi.Product', $locale);
            default:
                throw new \LogicException('Unreachable statement.');
        }
    }

    /**
     * @param ProductLazyArray|array $product
     */
    public static function fromProductData($product): self
    {
        if (!\is_array($product) && !$product instanceof \ArrayAccess) {
            throw new \InvalidArgumentException(\sprintf('Expected $product to be an array or an instance of "%s", "%s" given.', ProductLazyArray::class, get_debug_type($product)));
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

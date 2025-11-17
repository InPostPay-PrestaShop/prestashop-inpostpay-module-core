<?php

declare(strict_types=1);

namespace izi\prestashop\View\Widget;

use izi\prestashop\Enum\StringEnum;
use izi\prestashop\Translation\LegacyTranslator;
use izi\prestashop\Translation\TranslatableInterface;

/**
 * @method static self ExtraSmall()
 * @method static self Small()
 * @method static self Medium()
 * @method static self Large()
 * @method static self ExtraLarge()
 */
final class Size extends StringEnum implements TranslatableInterface
{
    private const EXTRA_SMALL = 'size-xs';
    private const SMALL = 'size-sm';
    private const MEDIUM = 'size-md';
    private const LARGE = 'size-lg';
    private const EXTRA_LARGE = 'size-xl';

    public function trans(LegacyTranslator $translator): string
    {
        switch ($this) {
            case self::ExtraSmall():
                return $translator->l('Extra small', 'size');
            case self::Small():
                return $translator->l('Small', 'size');
            case self::Medium():
                return $translator->l('Medium', 'size');
            case self::Large():
                return $translator->l('Large', 'size');
            case self::ExtraLarge():
                return $translator->l('Extra large', 'size');
            default:
                throw new \LogicException('Unreachable statement.');
        }
    }
}

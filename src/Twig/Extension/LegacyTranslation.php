<?php

declare(strict_types=1);

namespace izi\prestashop\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class LegacyTranslation extends AbstractExtension
{
    private $module;

    public function __construct(\Module $module)
    {
        $this->module = $module;
    }

    public function getFilters()
    {
        return [
            new TwigFilter('legacy_trans', [$this, 'legacyTrans'], [
                'is_safe' => ['html'],
            ]),
        ];
    }

    public function legacyTrans($value)
    {
        return $this->module->l($value, 'admin_template_translations');
    }
}

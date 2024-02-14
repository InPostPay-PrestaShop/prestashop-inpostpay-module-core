<?php

declare(strict_types=1);

namespace izi\prestashop\Twig\Extension;

use izi\prestashop\Translation\LegacyTranslator;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class LegacyTranslationExtension extends AbstractExtension
{
    /**
     * @var LegacyTranslator
     */
    private $translator;

    public function __construct(LegacyTranslator $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @return TwigFilter[]
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('legacy_trans', [$this, 'legacyTrans'], [
                'is_safe' => ['html'],
            ]),
        ];
    }

    public function legacyTrans(string $value): string
    {
        return $this->translator->l($value, 'admin_template_translations');
    }
}

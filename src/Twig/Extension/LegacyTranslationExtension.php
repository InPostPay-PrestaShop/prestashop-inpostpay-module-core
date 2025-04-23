<?php

declare(strict_types=1);

namespace izi\prestashop\Twig\Extension;

use izi\prestashop\Translation\LegacyTranslator;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

if (!class_exists(AbstractExtension::class)) {
    class_alias(\Twig_Extension::class, AbstractExtension::class);
    class_alias(\Twig_SimpleFilter::class, TwigFilter::class);
}

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

    public function legacyTrans(string $value, ?string $domain = null): string
    {
        return $this->translator->l($value, $domain ?? 'admin_template_translations');
    }
}

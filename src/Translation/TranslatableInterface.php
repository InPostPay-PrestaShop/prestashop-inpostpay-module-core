<?php

declare(strict_types=1);

namespace izi\prestashop\Translation;

interface TranslatableInterface
{
    public function trans(LegacyTranslator $translator): string;
}

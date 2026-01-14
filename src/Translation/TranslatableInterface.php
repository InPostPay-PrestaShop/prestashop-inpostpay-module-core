<?php

declare(strict_types=1);

namespace izi\prestashop\Translation;

use Symfony\Contracts\Translation\TranslatableInterface as ContractsTranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

if (interface_exists(ContractsTranslatableInterface::class)) {
    interface TranslatableInterface extends ContractsTranslatableInterface
    {
    }
} else {
    interface TranslatableInterface
    {
        public function trans(TranslatorInterface $translator, ?string $locale = null): string;
    }
}

<?php

declare(strict_types=1);

namespace izi\prestashop\Translation;

final class LegacyTranslator
{
    /**
     * @var string
     */
    private $moduleName;

    public function __construct(string $moduleName)
    {
        $this->moduleName = $moduleName;
    }

    public function l(string $source, string $domain = null): string
    {
        return \Translate::getModuleTranslation(
            $this->moduleName,
            $source,
            $domain ?? $this->moduleName
        );
    }
}

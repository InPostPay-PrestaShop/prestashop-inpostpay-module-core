<?php

declare(strict_types=1);

namespace izi\prestashop\Translation\Message;

final class ImportTranslationsCommand
{
    /**
     * @var string
     */
    private $directory;

    /**
     * @param string $directory path to a directory containing XLF files grouped by locale
     */
    public function __construct(string $directory)
    {
        $this->directory = $directory;
    }

    public function getDirectory(): string
    {
        return $this->directory;
    }
}

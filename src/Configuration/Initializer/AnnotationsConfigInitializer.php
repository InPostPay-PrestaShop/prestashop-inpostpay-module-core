<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration\Initializer;

use Doctrine\Common\Annotations\AnnotationReader;

final class AnnotationsConfigInitializer implements ConfigurationInitializerInterface
{
    /**
     * Ignored by default since doctrine/annotations 1.9.0. PS before version 1.7.8 used a lower version of the package.
     */
    private const IGNORED_NAMES = [
        'template',
        'implements',
        'extends',
        'readonly',
    ];

    /**
     * @var string[]
     */
    private $ignoredNames;

    /**
     * @param string[] $ignoredNames tag names that should be ignored when parsing annotations
     */
    public function __construct(array $ignoredNames = [])
    {
        $this->ignoredNames = array_merge($ignoredNames, self::IGNORED_NAMES);
    }

    public function init(): void
    {
        foreach ($this->ignoredNames as $name) {
            AnnotationReader::addGlobalIgnoredName($name);
        }
    }
}

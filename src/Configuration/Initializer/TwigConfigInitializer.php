<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration\Initializer;

use Twig\Environment;
use Twig\Extension\ExtensionInterface;

if (!class_exists(Environment::class)) {
    class_alias(\Twig_Environment::class, Environment::class);
}

/**
 * @internal
 */
final class TwigConfigInitializer implements ConfigurationInitializerInterface
{
    /**
     * @var Environment
     */
    private $twig;

    /**
     * @var iterable<ExtensionInterface>
     */
    private $extensions;

    /**
     * @param iterable<ExtensionInterface> $extensions
     */
    public function __construct(Environment $twig, iterable $extensions)
    {
        $this->extensions = $extensions;
        $this->twig = $twig;
    }

    public function init(): void
    {
        foreach ($this->extensions as $extension) {
            $this->twig->addExtension($extension);
        }
    }
}

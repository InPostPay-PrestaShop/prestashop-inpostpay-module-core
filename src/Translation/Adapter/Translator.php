<?php

declare(strict_types=1);

namespace izi\prestashop\Translation\Adapter;

use Symfony\Component\Translation\TranslatorInterface as LegacyTranslatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

if (!interface_exists(LegacyTranslatorInterface::class)) {
    throw new \LogicException(\sprintf('You cannot use the "%s" because the "symfony/translation" package already fulfills the "%s" contract. Use "%s" directly instead.', Translator::class, TranslatorInterface::class, \Symfony\Component\Translation\Translator::class));
}

final class Translator implements TranslatorInterface
{
    /**
     * @var LegacyTranslatorInterface
     */
    private $translator;

    public function __construct(LegacyTranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritDoc}
     *
     * @param array<string, mixed> $parameters
     */
    public function trans($id, array $parameters = [], $domain = null, $locale = null): string
    {
        return $this->translator->trans($id, $parameters, $domain, $locale);
    }

    public function getLocale(): string
    {
        return $this->translator->getLocale();
    }

    /**
     * Forwards calls to the inner translator.
     *
     * @return mixed
     */
    public function __call(string $name, array $arguments)
    {
        return $this->translator->$name(...$arguments);
    }
}

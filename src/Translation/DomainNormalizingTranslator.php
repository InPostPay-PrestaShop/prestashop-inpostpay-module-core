<?php

declare(strict_types=1);

namespace izi\prestashop\Translation;

use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Normalizes PS translation domains on PS < 1.7.6 config page.
 *
 * @internal
 */
final class DomainNormalizingTranslator implements TranslatorInterface, TranslatorBagInterface
{
    /**
     * @var TranslatorInterface&TranslatorBagInterface
     */
    private $translator;

    /**
     * @param TranslatorInterface&TranslatorBagInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function getCatalogue($locale = null): MessageCatalogue
    {
        return $this->translator->getCatalogue($locale);
    }

    public function trans($id, array $parameters = [], $domain = null, $locale = null): string
    {
        $domain = $this->normalizeDomain($domain);

        return $this->translator->trans($id, $parameters, $domain, $locale);
    }

    public function transChoice($id, $number, array $parameters = [], $domain = null, $locale = null): string
    {
        $domain = $this->normalizeDomain($domain);

        return $this->translator->transChoice($id, $number, $parameters, $domain, $locale);
    }

    public function setLocale($locale): void
    {
        $this->translator->setLocale($locale);
    }

    public function getLocale(): string
    {
        return $this->translator->getLocale();
    }

    private function normalizeDomain(?string $domain): ?string
    {
        if (null === $domain) {
            return null;
        }

        return str_replace('.', '', $domain);
    }
}

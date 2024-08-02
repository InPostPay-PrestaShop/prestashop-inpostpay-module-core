<?php

declare(strict_types=1);

namespace izi\prestashop\Validator\Consent;

use izi\prestashop\Configuration\DTO\Consent;
use izi\prestashop\Translation\LegacyTranslator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class DescriptionUsesIdPlaceholdersValidator extends ConstraintValidator
{
    private const TRANSLATION_SOURCE = 'descriptionusesidplaceholdersvalidator';

    /**
     * @var LegacyTranslator
     */
    private $translator;

    public function __construct(LegacyTranslator $translator)
    {
        $this->translator = $translator;
    }

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof DescriptionUsesIdPlaceholders) {
            throw new UnexpectedTypeException($constraint, DescriptionUsesIdPlaceholders::class);
        }

        if (null === $value) {
            return;
        }

        if (!$value instanceof Consent) {
            throw new UnexpectedTypeException($value, Consent::class);
        }

        if ([] === $placeholders = $this->collectIdPlaceholders($value)) {
            return;
        }

        $hasAdditionalLinks = [] !== $value->getAdditionalLinks();

        foreach ($value->getDescriptions() as $languageId => $description) {
            if ('' === $description = (string) $description) {
                continue;
            }

            $this->validateDescription($description, $languageId, $placeholders, $hasAdditionalLinks);
        }
    }

    private function validateDescription(string $description, int $languageId, array $placeholders, bool $hasAdditionalLinks): void
    {
        $duplicated = [];

        foreach ($placeholders as $id => $placeholder) {
            $count = substr_count($description, $placeholder);

            $description = strtr($description, [
                $placeholder => '',
            ]);

            if (0 === $count) {
                continue;
            }

            unset($placeholders[$id]);

            if (1 === $count) {
                continue;
            }

            $duplicated[] = $placeholder;
        }

        if ([] !== $placeholders && $hasAdditionalLinks) {
            $this->context
                ->buildViolation(sprintf($this->translator->l('Unused ID placeholders: %s.', self::TRANSLATION_SOURCE), sprintf('"%s"', implode('", "', $placeholders))))
                ->atPath('descriptions[' . $languageId . ']')
                ->addViolation();
        } elseif ([] !== $duplicated) {
            $this->context
                ->buildViolation(sprintf($this->translator->l('Duplicated ID placeholders: %s.', self::TRANSLATION_SOURCE), sprintf('"%s"', implode('", "', $duplicated))))
                ->atPath('descriptions[' . $languageId . ']')
                ->addViolation();
        }
    }

    /**
     * @return array<string, string> placeholders by link ID
     */
    private function collectIdPlaceholders(Consent $consent): array
    {
        $ids = [];

        if ('' !== $id = (string) $consent->getId()) {
            $ids[$id] = '#' . $id;
        }

        foreach ($consent->getAdditionalLinks() as $link) {
            if ('' === $id = (string) $link->getId()) {
                continue;
            }

            $ids[$id] = '#' . $id;
        }

        usort($ids, static function ($a, $b) {
            return strlen($b) - strlen($a);
        });

        return $ids;
    }
}

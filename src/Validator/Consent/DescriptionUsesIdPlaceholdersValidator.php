<?php

declare(strict_types=1);

namespace izi\prestashop\Validator\Consent;

use izi\prestashop\Common\Basket\ConsentLink;
use izi\prestashop\Configuration\DTO\Consent;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class DescriptionUsesIdPlaceholdersValidator extends ConstraintValidator
{
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

    /**
     * @param string[] $placeholders
     */
    private function validateDescription(string $description, int $languageId, array $placeholders, bool $hasAdditionalLinks): void
    {
        $duplicated = [];

        foreach ($placeholders as $key => $placeholder) {
            $count = substr_count($description, $placeholder);

            $description = strtr($description, [
                $placeholder => '',
            ]);

            if (0 === $count) {
                continue;
            }

            unset($placeholders[$key]);

            if (1 === $count) {
                continue;
            }

            $duplicated[] = $placeholder;
        }

        if ([] !== $placeholders && $hasAdditionalLinks) {
            $this->context
                ->buildViolation('Unused ID placeholders: "{{ placeholders}}".')
                ->setTranslationDomain('Modules.Inpostizi.Validators')
                ->setParameter('{{ placeholders }}', implode('", "', $placeholders))
                ->atPath('descriptions[' . $languageId . ']')
                ->addViolation();
        } elseif ([] !== $duplicated) {
            $this->context
                ->buildViolation('Duplicated ID placeholders: "{{ placeholders }}".')
                ->setTranslationDomain('Modules.Inpostizi.Validators')
                ->setParameter('{{ placeholders }}', implode('", "', $duplicated))
                ->atPath('descriptions[' . $languageId . ']')
                ->addViolation();
        }
    }

    /**
     * @return string[]
     */
    private function collectIdPlaceholders(Consent $consent): array
    {
        $ids = [];

        if ('' !== $id = (string) $consent->getId()) {
            $ids[$id] = ConsentLink::PLACEHOLDER_PREFIX . $id;
        }

        foreach ($consent->getAdditionalLinks() as $link) {
            if ('' === $id = (string) $link->getId()) {
                continue;
            }

            $ids[$id] = ConsentLink::PLACEHOLDER_PREFIX . $id;
        }

        usort($ids, static function ($a, $b) {
            return \strlen($b) - \strlen($a);
        });

        return $ids;
    }
}

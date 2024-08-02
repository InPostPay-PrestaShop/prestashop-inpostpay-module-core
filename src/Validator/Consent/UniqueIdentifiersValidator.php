<?php

declare(strict_types=1);

namespace izi\prestashop\Validator\Consent;

use izi\prestashop\Configuration\DTO\Consent;
use izi\prestashop\Configuration\DTO\ConsentLink;
use izi\prestashop\Translation\LegacyTranslator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class UniqueIdentifiersValidator extends ConstraintValidator
{
    private const TRANSLATION_SOURCE = 'uniqueidentifiersvalidator';

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
        if (!$constraint instanceof UniqueIdentifiers) {
            throw new UnexpectedTypeException($constraint, UniqueIdentifiers::class);
        }

        if (null === $value) {
            return;
        }

        if (!$value instanceof Consent) {
            throw new UnexpectedTypeException($value, Consent::class);
        }

        if (null === $id = $value->getId()) {
            return;
        }

        if ([] === $linkIds = $this->collectLinkIds($value)) {
            return;
        }

        foreach ($linkIds as $linkId) {
            if ($linkId !== $id) {
                continue;
            }

            $this->context
                ->buildViolation($this->translator->l('Identifier should be unique.', self::TRANSLATION_SOURCE))
                ->atPath('link.id')
                ->addViolation();

            return;
        }
    }

    /**
     * @return string[]
     */
    private function collectLinkIds(Consent $consent): array
    {
        return array_map([ConsentLink::class, 'normalize'], $consent->getAdditionalLinks());
    }
}

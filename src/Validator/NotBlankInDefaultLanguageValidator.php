<?php

declare(strict_types=1);

namespace izi\prestashop\Validator;

use izi\prestashop\Configuration\ConfigurationInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class NotBlankInDefaultLanguageValidator extends ConstraintValidator
{
    private const DEFAULT_LANGUAGE_ID_CONFIG_KEY = 'PS_LANG_DEFAULT';

    /**
     * @var ConfigurationInterface
     */
    private $configuration;

    public function __construct(ConfigurationInterface $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof NotBlankInDefaultLanguage) {
            throw new UnexpectedTypeException($constraint, NotBlankInDefaultLanguage::class);
        }

        if (null === $value) {
            return;
        }

        if (!is_array($value)) {
            throw new UnexpectedTypeException($value, 'array');
        }

        $defaultLanguageId = (int) $this->configuration->get(self::DEFAULT_LANGUAGE_ID_CONFIG_KEY);

        if (!isset($value[$defaultLanguageId]) || '' === $value[$defaultLanguageId]) {
            $this->context->buildViolation($constraint->message)
                ->setTranslationDomain('Admin.Notifications.Error')
                ->setParameter('%field_name%', $constraint->fieldName)
                ->addViolation();
        }
    }
}

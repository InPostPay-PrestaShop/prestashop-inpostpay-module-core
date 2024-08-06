<?php

declare(strict_types=1);

namespace izi\prestashop\Validator;

use Psr\Container\ContainerInterface;
use Symfony\Component\Validator\ConstraintValidatorFactoryInterface;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\ContainerConstraintValidatorFactory;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @internal
 */
final class ValidatorFactory
{
    /**
     * @param ContainerInterface $container service locator of {@see ConstraintValidatorInterface} by their class names
     */
    public static function create(ContainerInterface $container): ValidatorInterface
    {
        $constraintValidatorFactory = self::createConstraintValidatorFactory($container);

        return Validation::createValidatorBuilder()
            ->setConstraintValidatorFactory($constraintValidatorFactory)
            ->getValidator();
    }

    private static function createConstraintValidatorFactory(ContainerInterface $container): ConstraintValidatorFactoryInterface
    {
        return class_exists(ContainerConstraintValidatorFactory::class)
            ? new ContainerConstraintValidatorFactory($container)
            : new ConstraintValidatorFactory($container);
    }
}

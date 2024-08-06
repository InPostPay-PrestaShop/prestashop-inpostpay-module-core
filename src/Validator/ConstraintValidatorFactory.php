<?php

declare(strict_types=1);

namespace izi\prestashop\Validator;

use Psr\Container\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidatorFactoryInterface;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\ValidatorException;

/**
 * PSR-container-compatible polyfill for Sf 2.8.
 *
 * @author Kris Wallsmith <kris@symfony.com>
 *
 * @internal
 */
final class ConstraintValidatorFactory implements ConstraintValidatorFactoryInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var array<string, ConstraintValidatorInterface>
     */
    private $validators = [];

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getInstance(Constraint $constraint): ConstraintValidatorInterface
    {
        $name = $constraint->validatedBy();

        return $this->validators[$name] ?? $this->validators[$name] = $this->create($name);
    }

    private function create(string $name): ConstraintValidatorInterface
    {
        $serviceId = strtolower($name); // Sf 2.8 normalizes all service ids by converting them to lowercase

        if ($this->container->has($serviceId)) {
            $validator = $this->container->get($serviceId);
        } else {
            if (!class_exists($name)) {
                throw new ValidatorException(sprintf('Constraint validator "%s" does not exist or is not enabled. Check the "validatedBy" method in your constraint class "%s".', $name, get_class($constraint)));
            }

            $validator = new $name();
        }

        if (!$validator instanceof ConstraintValidatorInterface) {
            throw new UnexpectedTypeException($validator, ConstraintValidatorInterface::class);
        }

        return $validator;
    }
}

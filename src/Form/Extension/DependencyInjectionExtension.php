<?php

declare(strict_types=1);

namespace izi\prestashop\Form\Extension;

use Psr\Container\ContainerInterface;
use Symfony\Component\Form\Exception\InvalidArgumentException;
use Symfony\Component\Form\FormExtensionInterface;
use Symfony\Component\Form\FormTypeExtensionInterface;
use Symfony\Component\Form\FormTypeGuesserInterface;
use Symfony\Component\Form\FormTypeInterface;

/**
 * PSR-container-compatible extension for Sf 2.8.
 *
 * @internal
 */
final class DependencyInjectionExtension implements FormExtensionInterface
{
    /**
     * @var ContainerInterface
     */
    private $typeContainer;

    /**
     * @var array<string, FormTypeExtensionInterface[]>
     */
    private $typeExtensions;

    /**
     * @param array<string, FormTypeExtensionInterface[]> $typeExtensions type extensions by type name
     */
    public function __construct(ContainerInterface $typeContainer, array $typeExtensions = [])
    {
        $this->typeContainer = $typeContainer;
        $this->typeExtensions = $typeExtensions;
    }

    /**
     * @param string $name
     */
    public function getType($name): FormTypeInterface
    {
        $serviceId = $this->getFormTypeServiceId($name);

        if (!$this->typeContainer->has($serviceId)) {
            throw new InvalidArgumentException(sprintf('The field type "%s" is not registered in the service container.', $name));
        }

        return $this->typeContainer->get($serviceId);
    }

    /**
     * @param string $name
     */
    public function hasType($name): bool
    {
        return $this->typeContainer->has($this->getFormTypeServiceId($name));
    }

    /**
     * @param string $name
     *
     * @return FormTypeExtensionInterface[]
     */
    public function getTypeExtensions($name): array
    {
        return $this->typeExtensions[$name] ?? [];
    }

    /**
     * @param string $name
     */
    public function hasTypeExtensions($name): bool
    {
        return isset($this->typeExtensions[$name]);
    }

    public function getTypeGuesser(): ?FormTypeGuesserInterface
    {
        return null;
    }

    private function getFormTypeServiceId(string $name): string
    {
        return strtolower($name); // Sf 2.8 normalizes all service ids by converting them to lowercase
    }
}

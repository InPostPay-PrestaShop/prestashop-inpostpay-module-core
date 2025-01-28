<?php

declare(strict_types=1);

namespace izi\prestashop\Form;

use izi\prestashop\Form\Extension\DependencyInjectionExtension as DependencyInjectionExtensionPolyfill;
use Psr\Container\ContainerInterface;
use Symfony\Component\Form\Extension\Csrf\CsrfExtension;
use Symfony\Component\Form\Extension\DependencyInjection\DependencyInjectionExtension;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormExtensionInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\FormTypeExtensionInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @internal
 */
final class FormFactoryFactory
{
    /**
     * @var ValidatorInterface
     */
    private $validator;

    public function __construct(?ValidatorInterface $validator = null)
    {
        $this->validator = $validator ?? Validation::createValidator();
    }

    /**
     * @param ContainerInterface $container service locator of {@see FormTypeInterface} by their class names
     * @param array<string, FormTypeExtensionInterface[]> $typeExtensions type extensions by type name
     */
    public function create(ContainerInterface $container, array $typeExtensions = []): FormFactoryInterface
    {
        return Forms::createFormFactoryBuilder()
            ->addExtension(new ValidatorExtension($this->validator))
            ->addExtension(new CsrfExtension(new CsrfTokenManager()))
            ->addExtension(new HttpFoundationExtension())
            ->addExtension($this->createDependencyInjectionExtension($container, $typeExtensions))
            ->getFormFactory();
    }

    private function createDependencyInjectionExtension(ContainerInterface $container, array $typeExtensions): FormExtensionInterface
    {
        if (\Tools::version_compare(_PS_VERSION_, '1.7.4')) {
            return new DependencyInjectionExtensionPolyfill($container, $typeExtensions);
        }

        return new DependencyInjectionExtension($container, $typeExtensions, []);
    }
}

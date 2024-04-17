<?php

declare(strict_types=1);

namespace izi\prestashop\HttpKernel;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides service controller arguments on Sf 2.8.
 */
final class ServiceParamConverter implements ParamConverterInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function apply(Request $request, ParamConverter $configuration): bool
    {
        $value = $this->container->get($configuration->getClass());
        $request->attributes->set($configuration->getName(), $value);

        return true;
    }

    public function supports(ParamConverter $configuration): bool
    {
        if (null === $class = $configuration->getClass()) {
            return false;
        }

        return $this->container->has($class);
    }
}

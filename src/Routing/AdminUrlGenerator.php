<?php

declare(strict_types=1);

namespace izi\prestashop\Routing;

use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;

/**
 * @internal
 */
final class AdminUrlGenerator implements UrlGeneratorInterface
{
    /**
     * @var UrlGeneratorInterface
     */
    private $generator;

    /**
     * @var UrlGeneratorInterface|null
     */
    private $psGenerator;

    public function __construct(UrlGeneratorInterface $generator)
    {
        $this->generator = $generator;
    }

    public function setContext(RequestContext $context): void
    {
        $this->generator->setContext($context);
    }

    public function getContext(): RequestContext
    {
        return $this->generator->getContext();
    }

    public function generate($name, $parameters = [], $referenceType = self::ABSOLUTE_PATH): string
    {
        try {
            return $this->generator->generate($name, $parameters, $referenceType);
        } catch (RouteNotFoundException $e) {
            if (null === $generator = $this->getPrestaShopGenerator()) {
                throw $e;
            }

            return $generator->generate($name, $parameters, $referenceType);
        }
    }

    private function getPrestaShopGenerator(): ?UrlGeneratorInterface
    {
        if (isset($this->psGenerator)) {
            return $this->psGenerator;
        }

        global $kernel;

        if (!$kernel instanceof KernelInterface) {
            return null;
        }

        try {
            return $this->psGenerator = $kernel->getContainer()->get('router');
        } catch (ServiceNotFoundException $e) {
            return null;
        }
    }
}

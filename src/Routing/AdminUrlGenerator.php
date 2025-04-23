<?php

declare(strict_types=1);

namespace izi\prestashop\Routing;

use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

/**
 * Decorator used on PS < 1.7.6 config pages to keep legacy PS controller query params & to delegate generating default
 * PrestaShop routes' urls to the PS application kernel's router.
 *
 * @internal
 */
final class AdminUrlGenerator implements RouterInterface
{
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var UrlGeneratorInterface|null
     */
    private $psGenerator;

    public function __construct(RouterInterface $router, RequestStack $requestStack = null)
    {
        $this->router = $router;
        $this->requestStack = $requestStack ?? new RequestStack();
    }

    public function setContext(RequestContext $context): void
    {
        $this->router->setContext($context);
    }

    public function getContext(): RequestContext
    {
        return $this->router->getContext();
    }

    /**
     * @param string $name
     * @param array $parameters
     * @param int $referenceType
     */
    public function generate($name, $parameters = [], $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH): string
    {
        $originalParameters = $parameters;

        if (null !== $request = $this->requestStack->getCurrentRequest()) {
            $parameters += $request->query->all();
        }

        try {
            return $this->router->generate($name, $parameters, $referenceType);
        } catch (RouteNotFoundException $e) {
            if (null === $generator = $this->getPrestaShopGenerator()) {
                throw $e;
            }

            return $generator->generate($name, $originalParameters, $referenceType);
        }
    }

    /**
     * @param string $pathinfo
     */
    public function match($pathinfo): array
    {
        return $this->router->match($pathinfo);
    }

    public function getRouteCollection(): RouteCollection
    {
        return $this->router->getRouteCollection();
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

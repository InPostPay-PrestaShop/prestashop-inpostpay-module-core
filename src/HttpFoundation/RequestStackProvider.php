<?php

declare(strict_types=1);

namespace izi\prestashop\HttpFoundation;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @internal
 */
final class RequestStackProvider
{
    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getRequestStack(): RequestStack
    {
        return $this->getFrameworkStack() ?? $this->createRequestStack();
    }

    private function getFrameworkStack(): ?RequestStack
    {
        if (!$this->container->has('request_stack')) {
            return null;
        }

        $stack = $this->container->get('request_stack');

        if (null === $request = $stack->getCurrentRequest()) {
            // the original request has already been removed from the stack
            return null;
        }

        if (!$request->hasSession()) {
            $this->setSession($request);
        }

        return $stack;
    }

    private function createRequestStack(): RequestStack
    {
        $stack = new RequestStack();
        if ('cli' !== \PHP_SAPI) {
            $request = $this->createRequest();
            $stack->push($request);
        }

        return $stack;
    }

    private function createRequest(): Request
    {
        $request = Request::createFromGlobals();
        $this->setSession($request);

        return $request;
    }

    private function setSession(Request $request): void
    {
        if (!$this->container->has('session')) {
            return;
        }

        $request->setSession($this->container->get('session'));
    }
}

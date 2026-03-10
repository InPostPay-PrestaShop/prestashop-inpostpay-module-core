<?php

declare(strict_types=1);

namespace izi\prestashop\HttpFoundation;

use PrestaShop\PrestaShop\Adapter\SymfonyContainer;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * @internal
 */
final class RequestStackProvider
{
    /**
     * @var ContainerInterface|null
     */
    private $container;

    public function __construct(?ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public static function create(): self
    {
        $container = self::getKernelContainer();

        return new self($container);
    }

    public function getRequestStack(): RequestStack
    {
        return $this->getFrameworkStack() ?? $this->createRequestStack();
    }

    private function getFrameworkStack(): ?RequestStack
    {
        if (null === $this->container || !$this->container->has('request_stack')) {
            return null;
        }

        $stack = $this->container->get('request_stack');

        if (null === $stack->getCurrentRequest()) {
            // the original request has already been removed from the stack
            return null;
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
        try {
            return Request::createFromGlobals();
        } catch (FileNotFoundException $e) {
            // some of the uploaded files have already been moved
        }

        try {
            $files = $_FILES;
            $_FILES = [];

            return Request::createFromGlobals();
        } finally {
            $_FILES = $files;
        }
    }

    /**
     * @return ContainerInterface|null
     */
    private static function getKernelContainer()
    {
        if (class_exists(SymfonyContainer::class)) {
            return SymfonyContainer::getInstance();
        }

        global $kernel;

        if (!$kernel instanceof KernelInterface) {
            return null;
        }

        return $kernel->getContainer();
    }
}

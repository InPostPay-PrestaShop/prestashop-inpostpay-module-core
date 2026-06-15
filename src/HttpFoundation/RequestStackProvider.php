<?php

declare(strict_types=1);

namespace izi\prestashop\HttpFoundation;

use izi\prestashop\HttpFoundation\Session\LazySession;
use PrestaShop\PrestaShop\Adapter\SymfonyContainer;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;
use Symfony\Component\HttpFoundation\Session\Storage\PhpBridgeSessionStorage;
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

    /**
     * @var \Context
     */
    private $context;

    /**
     * @var SessionInterface|null
     */
    private $contextSession;

    public function __construct(?ContainerInterface $container, \Context $context)
    {
        $this->container = $container;
        $this->context = $context;
    }

    public static function create(): self
    {
        $container = self::getKernelContainer();

        return new self($container, \Context::getContext());
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

    private function setSession(Request $request): void
    {
        if (null !== $this->container && $this->container->has('session')) {
            $request->setSession($this->container->get('session'));

            return;
        }

        if (\PHP_SESSION_DISABLED === $status = session_status()) {
            return;
        }

        if (
            \PHP_SESSION_ACTIVE !== $status
            && isset($this->context->session)
            && $this->context->session->isStarted()
        ) {
            // the session created by the core is in unusable state due to starting the session with a bridge storage
            // when the actual session has not been started yet
            $this->contextSession = $this->context->session;
            $this->context->session->save();
        }

        if (method_exists($request, 'setSessionFactory')) {
            $request->setSessionFactory(function () {
                static $session;

                return $session ?? $session = $this->getContextSession();
            });
        } else {
            $request->setSession(new LazySession(function () {
                static $session;

                return $session ?? $session = $this->getContextSession();
            }));
        }
    }

    private function getContextSession(): SessionInterface
    {
        register_shutdown_function(function () {
            if (!$this->context->session->isStarted()) {
                return;
            }

            $this->context->session->save();
        });

        $active = \PHP_SESSION_ACTIVE === session_status();

        if (!isset($this->context->session)) {
            $this->context->session = new Session($active ? new PhpBridgeSessionStorage() : new NativeSessionStorage());
        } elseif (!$active && $this->contextSession === $this->context->session) {
            $this->context->session = new Session(new NativeSessionStorage());
        }

        return $this->context->session;
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

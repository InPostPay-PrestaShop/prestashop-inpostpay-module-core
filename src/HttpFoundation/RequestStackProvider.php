<?php

declare(strict_types=1);

namespace izi\prestashop\HttpFoundation;

use izi\prestashop\HttpFoundation\Session\LazySession;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;
use Symfony\Component\HttpFoundation\Session\Storage\PhpBridgeSessionStorage;

/**
 * @internal
 */
final class RequestStackProvider
{
    /**
     * @var ContainerInterface
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

    public function __construct(ContainerInterface $container, \Context $context)
    {
        $this->container = $container;
        $this->context = $context;
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
        if ($this->container->has('session')) {
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
}

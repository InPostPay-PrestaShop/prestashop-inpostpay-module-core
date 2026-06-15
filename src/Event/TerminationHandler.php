<?php

declare(strict_types=1);

namespace izi\prestashop\Event;

use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface as SymfonyEventDispatcherInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Dispatches {@see TerminateEvent} on request/response cycle termination or script shutdown.
 *
 * @internal
 */
final class TerminationHandler
{
    /**
     * @var \Context
     */
    private $context;

    /**
     * @var SymfonyEventDispatcherInterface|null
     */
    private $eventDispatcher;

    /**
     * @var EventDispatcherInterface[]
     */
    private $dispatchers = [];

    /**
     * @var bool
     */
    private $terminated = false;

    /**
     * @param SymfonyEventDispatcherInterface|null $eventDispatcher kernel event dispatcher
     */
    public function __construct(\Context $context, ?SymfonyEventDispatcherInterface $eventDispatcher = null)
    {
        $this->context = $context;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function register(EventDispatcherInterface $dispatcher): void
    {
        if ([] === $this->dispatchers) {
            $this->registerTerminateListener();
            $this->registerShutdownFunction();
        }

        $this->dispatchers[] = $dispatcher;
    }

    private function dispatchEvents(): void
    {
        $this->terminated = true;
        foreach ($this->dispatchers as $dispatcher) {
            $dispatcher->dispatch(new TerminateEvent());
        }
    }

    private function registerTerminateListener(): void
    {
        if (null === $this->eventDispatcher) {
            return;
        }

        $this->eventDispatcher->addListener('cli' === \PHP_SAPI ? ConsoleEvents::TERMINATE : KernelEvents::TERMINATE, function () {
            $this->dispatchEvents();
        });
    }

    private function registerShutdownFunction(): void
    {
        register_shutdown_function(function () {
            if ($this->terminated) {
                return;
            }

            $session = $this->context->session ?? null;

            if (!$session instanceof SessionInterface) {
                @session_write_close();
            } elseif ($session->isStarted()) {
                $session->save();
            }

            if (isset($this->context->cookie)) {
                $this->context->cookie->write();
            }

            if (\function_exists('fastcgi_finish_request')) {
                fastcgi_finish_request();
            }

            $this->dispatchEvents();
        });
    }
}

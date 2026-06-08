<?php

declare(strict_types=1);

namespace izi\prestashop\HttpFoundation\Session;

use Symfony\Component\HttpFoundation\Session\SessionBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Storage\MetadataBag;

/**
 * @internal
 */
final class LazySession implements SessionInterface
{
    /**
     * @var callable
     */
    private $factory;

    /**
     * @var SessionInterface|null
     */
    private $session;

    /**
     * @param callable(): SessionInterface $factory
     */
    public function __construct(callable $factory)
    {
        $this->factory = $factory;
    }

    public function start(): bool
    {
        return $this->getSession()->start();
    }

    public function getId(): string
    {
        return $this->getSession()->getId();
    }

    /**
     * @param string $id
     */
    public function setId($id): void
    {
        $this->getSession()->setId($id);
    }

    public function getName(): string
    {
        return $this->getSession()->getName();
    }

    /**
     * @param string $name
     */
    public function setName($name): void
    {
        $this->getSession()->setName($name);
    }

    /**
     * @param int|null $lifetime
     */
    public function invalidate($lifetime = null): bool
    {
        return $this->getSession()->invalidate($lifetime);
    }

    /**
     * @param bool $destroy
     * @param int|null $lifetime
     */
    public function migrate($destroy = false, $lifetime = null): bool
    {
        return $this->getSession()->migrate($destroy, $lifetime);
    }

    public function save(): void
    {
        $this->getSession()->save();
    }

    /**
     * @param string $name
     */
    public function has($name): bool
    {
        return $this->getSession()->has($name);
    }

    /**
     * @param string $name
     * @param mixed $default
     *
     * @return mixed
     */
    public function get($name, $default = null)
    {
        return $this->getSession()->get($name, $default);
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function set($name, $value): void
    {
        $this->getSession()->set($name, $value);
    }

    public function all(): array
    {
        return $this->getSession()->all();
    }

    public function replace(array $attributes): void
    {
        $this->getSession()->replace($attributes);
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function remove($name)
    {
        return $this->getSession()->remove($name);
    }

    public function clear(): void
    {
        $this->getSession()->clear();
    }

    public function isStarted(): bool
    {
        return $this->getSession()->isStarted();
    }

    public function registerBag(SessionBagInterface $bag): void
    {
        $this->getSession()->registerBag($bag);
    }

    /**
     * @param string $name
     */
    public function getBag($name): SessionBagInterface
    {
        return $this->getSession()->getBag($name);
    }

    public function getMetadataBag(): MetadataBag
    {
        return $this->getSession()->getMetadataBag();
    }

    /**
     * @return mixed
     */
    public function __call(string $name, array $arguments)
    {
        return $this->getSession()->$name(...$arguments);
    }

    private function getSession(): SessionInterface
    {
        return $this->session ?? $this->session = ($this->factory)();
    }
}

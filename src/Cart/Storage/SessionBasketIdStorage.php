<?php

declare(strict_types=1);

namespace izi\prestashop\Cart\Storage;

use izi\prestashop\Cart\Storage\Exception\StorageUnavailableException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

final class SessionBasketIdStorage implements BasketIdStorageInterface
{
    private const ATTRIBUTE_NAME = 'inpostizi/basket_id';

    /**
     * @var RequestStack
     */
    private $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function getBasketId(): ?string
    {
        return $this->getSession()->get(self::ATTRIBUTE_NAME);
    }

    public function setBasketId(?string $basketId): void
    {
        $session = $this->getSession();
        if (null === $basketId) {
            $session->remove(self::ATTRIBUTE_NAME);
        } else {
            $session->set(self::ATTRIBUTE_NAME, $basketId);
        }
    }

    private function getSession(): SessionInterface
    {
        if (null === $request = $this->requestStack->getCurrentRequest()) {
            throw new StorageUnavailableException('The request stack is empty.');
        }

        if (!$request->hasSession()) {
            throw new StorageUnavailableException('Session is not available.');
        }

        $session = $request->getSession();
        if (!$session->isStarted()) {
            $session->start();
        }

        return $session;
    }
}

<?php

declare(strict_types=1);

namespace izi\prestashop\Analytics\Cookie\Executor;

use izi\prestashop\Analytics\Cookie\CookiePersisterInterface;
use Symfony\Component\HttpFoundation\Request;

class CookiePersisterExecutor implements CookiePersisterInterface
{
    /**
     * @var iterable<CookiePersisterInterface>
     */
    private $persisters;

    /**
     * @param iterable<CookiePersisterInterface> $persisters
     */
    public function __construct(iterable $persisters)
    {
        $this->persisters = $persisters;
    }

    public function persist(Request $request): void
    {
        foreach ($this->persisters as $persister) {
            $persister->persist($request);
        }
    }
}

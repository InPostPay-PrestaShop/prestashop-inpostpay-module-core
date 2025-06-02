<?php

declare(strict_types=1);

namespace izi\prestashop\Analytics\Cookie\Executor;

use izi\prestashop\Analytics\Cookie\CookieEraserInterface;
use Symfony\Component\HttpFoundation\Request;

class CookieEraseExecutor implements CookieEraserInterface
{
    /**
     * @var iterable<CookieEraserInterface>
     */
    private $erasers;

    /**
     * @param iterable<CookieEraserInterface> $erasers
     */
    public function __construct(iterable $erasers)
    {
        $this->erasers = $erasers;
    }

    public function erase(Request $request): void
    {
        foreach ($this->erasers as $eraser) {
            $eraser->erase($request);
        }
    }
}

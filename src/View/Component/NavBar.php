<?php

declare(strict_types=1);

namespace izi\prestashop\View\Component;

use Twig\Environment;
use Twig\Markup;

/**
 * @implements \IteratorAggregate<\Stringable>
 */
final class NavBar implements \Stringable, \IteratorAggregate
{
    /**
     * @var Environment
     */
    private $twig;

    /**
     * @var iterable<NavItem>
     */
    private $items;

    /**
     * @var string|null
     */
    private $content;

    /**
     * @param iterable<NavItem> $items
     */
    public function __construct(Environment $twig, iterable $items)
    {
        $this->twig = $twig;
        $this->items = $items;
    }

    /**
     * @return \Traversable<\Stringable>
     */
    public function getIterator(): \Traversable
    {
        yield new Markup((string) $this, $this->twig->getCharset());
    }

    public function __toString(): string
    {
        return $this->content ?? $this->content = $this->render();
    }

    private function render(): string
    {
        return $this->twig->render('@Modules/inpostizi/views/templates/admin/config/nav.html.twig', [
            'items' => $this->items,
        ]);
    }
}

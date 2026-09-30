<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Event;

abstract class AbstractRenderEvent extends AbstractHookEvent
{
    /**
     * @var string
     */
    private $content = '';

    final public function getContent(): string
    {
        return $this->content;
    }

    final public function appendContent(string $content): void
    {
        $this->content .= $content;
    }

    final public function setContent(string $content): void
    {
        $this->content = $content;
    }
}

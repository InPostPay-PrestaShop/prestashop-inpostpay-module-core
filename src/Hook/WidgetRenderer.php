<?php

declare(strict_types=1);

namespace izi\prestashop\Hook;

use izi\prestashop\Configuration\WidgetConfigurationInterface;
use izi\prestashop\View\Templating\RendererInterface;
use izi\prestashop\View\Widget\Configuration;
use Symfony\Component\HttpFoundation\Request;

final class WidgetRenderer
{
    /**
     * @var \Context
     */
    private $context;

    /**
     * @var WidgetConfigurationInterface
     */
    private $configuration;

    /**
     * @var RendererInterface
     */
    private $renderer;

    public function __construct(\Context $context, WidgetConfigurationInterface $configuration, RendererInterface $renderer)
    {
        $this->context = $context;
        $this->configuration = $configuration;
        $this->renderer = $renderer;
    }

    public function render(Configuration $configuration, Request $request): string
    {
        if ($this->shouldWidgetBeHiddenForUser($request)) {
            return '';
        }

        return $this->renderer->render('module:inpostizi/views/templates/hook/widget.tpl', [
            'attributes' => $configuration,
        ]);
    }

    private function shouldWidgetBeHiddenForUser(Request $request): bool
    {
        if ($this->configuration->isVisibleToEveryone()) {
            return false;
        }

        if (!empty($this->context->cookie->izi_show)) {
            return false;
        }

        if ('true' !== $request->query->get('showIzi')) {
            return true;
        }

        $this->context->cookie->izi_show = true;

        return false;
    }
}

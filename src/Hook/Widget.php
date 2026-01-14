<?php

declare(strict_types=1);

namespace izi\prestashop\Hook;

use izi\prestashop\Hook\Exception\InvalidArgumentException;
use izi\prestashop\View\Templating\RendererInterface;
use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

final class Widget implements WidgetInterface
{
    public const DEFAULT_TEMPLATE = 'module:inpostizi/views/templates/front/widget.tpl';

    /**
     * @var RendererInterface
     */
    private $renderer;

    /**
     * @var WidgetParametersProviderInterface
     */
    private $parametersProvider;

    /**
     * @var string
     */
    private $template;

    public function __construct(RendererInterface $renderer, WidgetParametersProviderInterface $parametersProvider, string $template = self::DEFAULT_TEMPLATE)
    {
        $this->renderer = $renderer;
        $this->parametersProvider = $parametersProvider;
        $this->template = $template;
    }

    /**
     * @param string|null $hookName
     */
    public function renderWidget($hookName, array $configuration): string
    {
        if ([] === $parameters = $this->getWidgetVariables($hookName, $configuration)) {
            return '';
        }

        return $this->renderer->render($this->template, $parameters);
    }

    /**
     * @param string|null $hookName
     */
    public function getWidgetVariables($hookName, array $configuration): array
    {
        $this->assertIsValidHookName($hookName);

        return $this->parametersProvider->getParameters($hookName, $configuration);
    }

    private function assertIsValidHookName($hookName): void
    {
        if (null === $hookName || \is_string($hookName)) {
            return;
        }

        throw new InvalidArgumentException(\sprintf('Expected hook name to be a string or null, "%s" given', get_debug_type($hookName)));
    }
}

<?php

declare(strict_types=1);

namespace izi\prestashop\Hook;

use izi\prestashop\Security\AuthorizationChecker;
use izi\prestashop\Security\Voter\BindingWidgetVoter;
use izi\prestashop\View\Templating\RendererInterface;
use izi\prestashop\View\Widget\Configuration;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class WidgetRenderer
{
    /**
     * @var RendererInterface
     */
    private $renderer;

    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * @param RendererInterface $renderer
     * @param AuthorizationCheckerInterface $authorizationChecker
     */
    public function __construct(/* \Context $context, GeneralConfigurationInterface $configuration, */ $renderer, $authorizationChecker)
    {
        $arguments = func_get_args();
        if (isset($arguments[2]) && $arguments[2] instanceof RendererInterface) {
            @trigger_error(sprintf('Passing $context and $configuration as arguments for "%s::__construct()" is deprecated.', self::class), E_USER_DEPRECATED);
            $renderer = $arguments[2];
            $authorizationChecker = AuthorizationChecker::create([new BindingWidgetVoter($arguments[0], $arguments[1])]);
        }

        if (!$renderer instanceof RendererInterface) {
            throw new \InvalidArgumentException(sprintf('Expected $renderer to be instance of "%s", "%s" given', RendererInterface::class, get_debug_type($renderer)));
        }

        if (!$authorizationChecker instanceof AuthorizationCheckerInterface) {
            throw new \InvalidArgumentException(sprintf('Expected $authorizationChecker to be instance of "%s", "%s" given', AuthorizationCheckerInterface::class, get_debug_type($authorizationChecker)));
        }

        $this->renderer = $renderer;
        $this->authorizationChecker = $authorizationChecker;
    }

    public function render(Configuration $configuration, Request $request): string
    {
        if (!$this->authorizationChecker->isGranted(BindingWidgetVoter::VIEW, $request)) {
            return '';
        }

        return $this->renderer->render('module:inpostizi/views/templates/hook/widget.tpl', [
            'attributes' => $configuration,
        ]);
    }
}

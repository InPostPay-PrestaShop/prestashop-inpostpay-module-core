<?php

declare(strict_types=1);

namespace izi\prestashop\Security\Voter;

use izi\prestashop\Configuration\GeneralConfigurationInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class BindingWidgetVoter extends Voter
{
    public const VIEW = 'inpost_izi_widget_view';

    /**
     * @var GeneralConfigurationInterface
     */
    private $configuration;

    /**
     * @var \Context
     */
    private $context;

    public function __construct(GeneralConfigurationInterface $configuration, \Context $context)
    {
        $this->configuration = $configuration;
        $this->context = $context;
    }

    /**
     * @param string $attribute
     */
    protected function supports($attribute, $subject): bool
    {
        return self::VIEW === $attribute && $subject instanceof Request;
    }

    /**
     * @param string $attribute
     * @param Request $subject
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        if (!$subject instanceof Request) {
            throw new \InvalidArgumentException('Expected an instance of "%s", "%s" given.', Request::class, get_debug_type($subject));
        }

        if ($this->configuration->isEnabledForEveryone()) {
            return true;
        }

        if (isset($this->context->cookie->izi_show) && $this->context->cookie->izi_show) {
            return true;
        }

        if ('true' === $subject->query->get('showIzi')) {
            $this->context->cookie->izi_show = true;

            return true;
        }

        return $this->context->controller instanceof \ProductControllerCore
            && $this->configuration->isFullPageCacheModuleInUse();
    }
}

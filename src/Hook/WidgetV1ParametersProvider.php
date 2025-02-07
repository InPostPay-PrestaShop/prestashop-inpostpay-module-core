<?php

declare(strict_types=1);

namespace izi\prestashop\Hook;

use izi\prestashop\Configuration\Adapter\Configuration;
use izi\prestashop\Configuration\GeneralConfiguration;
use izi\prestashop\Security\AuthorizationChecker;
use izi\prestashop\Security\Voter\BindingWidgetVoter;
use izi\prestashop\View\Widget\WidgetConfigurationResolverInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * @internal
 *
 * @deprecated
 */
final class WidgetV1ParametersProvider implements WidgetParametersProviderInterface
{
    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * @var WidgetConfigurationResolverInterface
     */
    private $resolver;

    /**
     * @param WidgetConfigurationResolverInterface $resolver
     */
    public function __construct(AuthorizationCheckerInterface $authorizationChecker, $resolver)
    {
        $this->authorizationChecker = $authorizationChecker;
        $this->resolver = $resolver;
    }

    /**
     * @param WidgetConfigurationResolverInterface $resolver
     */
    public static function create($resolver, \Context $context): self
    {
        $generalConfig = new GeneralConfiguration(new Configuration());
        $authChecker = AuthorizationChecker::create([new BindingWidgetVoter($generalConfig, $context)]);

        return new self($authChecker, $resolver);
    }

    public function getParameters(?string $hookName, array $parameters): array
    {
        if (!$this->authorizationChecker->isGranted(BindingWidgetVoter::VIEW, $parameters['request'])) {
            return [];
        }

        if (null === $configuration = $this->resolver->resolve($parameters)) {
            return [];
        }

        return ['attributes' => $configuration];
    }
}

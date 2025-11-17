<?php

declare(strict_types=1);

namespace izi\prestashop\Hook;

use izi\prestashop\Common\BindingPlace;
use izi\prestashop\Configuration\ApiConfigurationInterface;
use izi\prestashop\Hook\Exception\InvalidHookParamException;
use izi\prestashop\Repository\BasketSessionRepositoryInterface;
use izi\prestashop\Security\Voter\BindingWidgetVoter;
use izi\prestashop\Validator\Cart\Bindable;
use izi\prestashop\View\Widget\WidgetConfigurationResolverInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class WidgetParametersProvider implements WidgetParametersProviderInterface
{
    /**
     * @var ApiConfigurationInterface
     */
    private $configuration;

    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * @var WidgetConfigurationResolverInterface
     */
    private $resolver;

    /**
     * @var BasketSessionRepositoryInterface
     */
    private $repository;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    public function __construct(ApiConfigurationInterface $configuration, AuthorizationCheckerInterface $authorizationChecker, WidgetConfigurationResolverInterface $resolver, BasketSessionRepositoryInterface $repository, ValidatorInterface $validator)
    {
        $this->configuration = $configuration;
        $this->authorizationChecker = $authorizationChecker;
        $this->resolver = $resolver;
        $this->repository = $repository;
        $this->validator = $validator;
    }

    public function getParameters(?string $hookName, array $parameters): array
    {
        if (!$this->hasRequiredConfiguration()) {
            return [];
        }

        $request = $parameters['request'] ?? null;

        if (!$request instanceof Request) {
            throw InvalidHookParamException::unexpectedType('request', $request, Request::class);
        }

        if (!$this->authorizationChecker->isGranted(BindingWidgetVoter::VIEW, $request)) {
            return [];
        }

        $cart = $parameters['cart'] ?? null;

        if (!$cart instanceof \Cart) {
            throw InvalidHookParamException::unexpectedType('cart', $cart, \Cart::class);
        }

        $widgetConfiguration = $this->resolver->resolve($parameters);

        if (!$this->isBindable($cart, $widgetConfiguration->getBindingPlace())) {
            return [];
        }

        return [
            'attributes' => $widgetConfiguration,
        ];
    }

    private function hasRequiredConfiguration(): bool
    {
        return null !== $this->configuration->getClientCredentials() && null !== $this->configuration->getMerchantClientId();
    }

    private function isBindable(\Cart $cart, BindingPlace $bindingPlace): bool
    {
        $session = $this->repository->findByEntityId((int) $cart->id);

        if (null !== $session && $session->isBasketBound()) {
            return true;
        }

        $violations = $this->validator->validate($cart, new Bindable($bindingPlace));

        return 0 === count($violations);
    }
}

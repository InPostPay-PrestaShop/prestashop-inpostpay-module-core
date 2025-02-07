<?php

declare(strict_types=1);

namespace izi\prestashop\Hook;

use izi\prestashop\Common\BindingPlace;
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

    public function __construct(AuthorizationCheckerInterface $authorizationChecker, WidgetConfigurationResolverInterface $resolver, BasketSessionRepositoryInterface $repository, ValidatorInterface $validator)
    {
        $this->authorizationChecker = $authorizationChecker;
        $this->resolver = $resolver;
        $this->repository = $repository;
        $this->validator = $validator;
    }

    public function getParameters(?string $hookName, array $parameters): array
    {
        $request = $parameters['request'] ?? null;

        if (!$request instanceof Request) {
            throw new \InvalidArgumentException(sprintf('Parameter "request" expected to be an instance of "%s", "%s" given.', Request::class, get_debug_type($request)));
        }

        if (!$this->authorizationChecker->isGranted(BindingWidgetVoter::VIEW, $request)) {
            return [];
        }

        if (null === $configuration = $this->resolver->resolve($parameters)) {
            @trigger_error(sprintf('Returning null in "%s::resolve() is deprecated.', get_class($this->resolver)), E_USER_DEPRECATED);

            return [];
        }

        $cart = $parameters['cart'] ?? null;

        if (!$cart instanceof \Cart) {
            throw new \InvalidArgumentException(sprintf('Parameter "cart" expected to be an instance of "%s", "%s" given.', \Cart::class, get_debug_type($cart)));
        }

        if (!$this->isBindable($cart, $configuration->getBindingPlace())) {
            return [];
        }

        return [
            'attributes' => $configuration,
        ];
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

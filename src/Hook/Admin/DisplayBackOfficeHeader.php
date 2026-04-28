<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Admin;

use izi\prestashop\Command\Config\UpdateCartRuleOptionsCommand;
use izi\prestashop\Form\Type\CartRuleOptionsType;
use izi\prestashop\Hook\HookInterface;
use izi\prestashop\PromoCode\CartRuleOptionsRepositoryInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Twig\Environment;

final class DisplayBackOfficeHeader implements HookInterface
{
    public const HOOK_NAME = 'displayBackOfficeHeader';

    /**
     * @var \Context
     */
    private $context;

    /**
     * @var CartRuleOptionsRepositoryInterface
     */
    private $repository;

    /**
     * @var FormFactoryInterface
     */
    private $formFactory;

    /**
     * @var Environment
     */
    private $twig;

    public function __construct(\Context $context, CartRuleOptionsRepositoryInterface $repository, FormFactoryInterface $formFactory, Environment $twig)
    {
        $this->context = $context;
        $this->repository = $repository;
        $this->formFactory = $formFactory;
        $this->twig = $twig;
    }

    public static function getHookName(): string
    {
        return self::HOOK_NAME;
    }

    /**
     * @param array{request?: Request} $parameters
     */
    public function execute(array $parameters): string
    {
        if (!$this->context->controller instanceof \AdminCartRulesControllerCore) {
            return '';
        }

        $request = $parameters['request'] ?? null;

        if (!$request instanceof Request) {
            return '';
        }

        if (!$request->query->has('addcart_rule') && !$request->query->has('updatecart_rule')) {
            return '';
        }

        $form = $this->formFactory->create(CartRuleOptionsType::class, $this->getInitialFormData($request));
        $form->handleRequest($request);

        return $this->twig->render('@Modules/inpostizi/views/templates/hook/admin/cart_rule_form.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @return array|UpdateCartRuleOptionsCommand
     */
    private function getInitialFormData(Request $request)
    {
        if (0 >= $cartRuleId = (int) $request->get('id_cart_rule')) {
            return [
                'omnibus' => false,
            ];
        }

        return $this->createUpdateOptionsCommand($cartRuleId);
    }

    private function createUpdateOptionsCommand(int $cartRuleId): UpdateCartRuleOptionsCommand
    {
        if (null === $options = $this->repository->find($cartRuleId)) {
            return new UpdateCartRuleOptionsCommand($cartRuleId);
        }

        return UpdateCartRuleOptionsCommand::for($options);
    }
}

<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Admin;

use izi\prestashop\Command\Config\UpdateCartRuleOptionsCommand;
use izi\prestashop\Form\Type\CartRuleOptionsType;
use izi\prestashop\Hook\HookInterface;
use izi\prestashop\Repository\CartRuleRepositoryInterface;
use izi\prestashop\View\Templating\RendererInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;

final class DisplayBackOfficeHeader implements HookInterface
{
    public const HOOK_NAME = 'displayBackOfficeHeader';

    /**
     * @var \Context
     */
    private $context;

    /**
     * @var CartRuleRepositoryInterface
     */
    private $repository;

    /**
     * @var FormFactoryInterface
     */
    private $formFactory;

    /**
     * @var RendererInterface
     */
    private $renderer;

    public function __construct(\Context $context, CartRuleRepositoryInterface $repository, FormFactoryInterface $formFactory, RendererInterface $renderer)
    {
        $this->context = $context;
        $this->repository = $repository;
        $this->formFactory = $formFactory;
        $this->renderer = $renderer;
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

        $form = $this->formFactory->create(CartRuleOptionsType::class, $this->getInitialFormData($request), [
            'csrf_protection' => false,
        ]);
        $form->handleRequest($request);

        return $this->renderer->render('module:inpostizi/views/templates/hook/admin/cart_rule_form.tpl', [
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

        $isOmnibus = $this->repository->isOmnibus($cartRuleId);

        return new UpdateCartRuleOptionsCommand($cartRuleId, $isOmnibus);
    }
}

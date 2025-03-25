<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Admin;

use izi\prestashop\Command\Config\UpdateCartRuleOptionsCommand;
use izi\prestashop\Form\Type\CartRuleOptionsType;
use izi\prestashop\Hook\HookInterface;
use izi\prestashop\PromoCode\CartRuleOptionsRepository;
use izi\prestashop\PromoCode\CartRuleOptionsRepositoryInterface;
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
     * @var CartRuleOptionsRepositoryInterface
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

    /**
     * @var CartRuleRepositoryInterface|null
     */
    private $originalRepository;

    /**
     * @param CartRuleOptionsRepositoryInterface|CartRuleRepositoryInterface $repository
     */
    public function __construct(\Context $context, CartRuleRepositoryInterface $repository, FormFactoryInterface $formFactory, RendererInterface $renderer)
    {
        $this->context = $context;
        $this->formFactory = $formFactory;
        $this->renderer = $renderer;

        if (!$repository instanceof CartRuleOptionsRepositoryInterface) {
            @trigger_error(sprintf('Passing a $repository that does not implement "%s" to "%s()" is deprecated since 2.1.0.', CartRuleOptionsRepositoryInterface::class, __METHOD__), E_USER_DEPRECATED);

            $this->repository = CartRuleOptionsRepository::create();
            $this->originalRepository = $repository;
        } else {
            $this->repository = $repository;
        }
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

        $command = $this->createUpdateOptionsCommand($cartRuleId);

        if (null !== $this->originalRepository) {
            $command->setOmnibus($this->originalRepository->isOmnibus($cartRuleId));
        }

        return $command;
    }

    private function createUpdateOptionsCommand(int $cartRuleId): UpdateCartRuleOptionsCommand
    {
        if (null === $options = $this->repository->find($cartRuleId)) {
            return new UpdateCartRuleOptionsCommand($cartRuleId);
        }

        return UpdateCartRuleOptionsCommand::for($options);
    }
}

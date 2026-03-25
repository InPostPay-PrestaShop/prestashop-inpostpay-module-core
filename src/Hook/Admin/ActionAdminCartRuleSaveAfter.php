<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Admin;

use izi\prestashop\Command\Config\UpdateCartRuleOptionsCommand;
use izi\prestashop\CommandBusInterface;
use izi\prestashop\Form\Type\CartRuleOptionsType;
use izi\prestashop\Hook\Exception\InvalidHookParamException;
use izi\prestashop\Hook\HookInterface;
use izi\prestashop\Hook\Legacy\ControllerHelper;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;

final class ActionAdminCartRuleSaveAfter implements HookInterface
{
    public const HOOK_NAME = 'actionAdminCartRulesControllerSaveAfter';

    /**
     * @var FormFactoryInterface
     */
    private $formFactory;

    /**
     * @var CommandBusInterface
     */
    private $bus;

    public function __construct(FormFactoryInterface $formFactory, CommandBusInterface $bus)
    {
        $this->formFactory = $formFactory;
        $this->bus = $bus;
    }

    public static function getHookName(): string
    {
        return self::HOOK_NAME;
    }

    /**
     * @param array{return: \CartRule|false, controller: \AdminCartRulesControllerCore, request?: Request} $parameters
     */
    public function execute(array $parameters): void
    {
        $request = $parameters['request'] ?? null;

        if (!$request instanceof Request) {
            return;
        }

        if (false === $cartRule = $parameters['return'] ?? null) {
            return;
        }

        if (!$cartRule instanceof \CartRule) {
            throw InvalidHookParamException::unexpectedType('return', $cartRule, \CartRule::class . '|false');
        }

        $form = $this->formFactory->create(CartRuleOptionsType::class, new UpdateCartRuleOptionsCommand((int) $cartRule->id));
        $form->handleRequest($request);

        if (!$form->isSubmitted()) {
            return;
        }

        if (!$form->isValid()) {
            $controller = $parameters['controller'] ?? null;

            if (!$controller instanceof \AdminControllerCore) {
                throw InvalidHookParamException::unexpectedType('controller', $controller, \AdminControllerCore::class);
            }

            // todo: store errors in session and redirect back to the cart rule edit page?
            ControllerHelper::setFormErrors($controller, $form);
        }

        $this->bus->handle($form->getData());
    }
}

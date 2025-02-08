<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Admin;

use izi\prestashop\Command\Config\UpdateCartRuleOptionsCommand;
use izi\prestashop\CommandBusInterface;
use izi\prestashop\Form\Type\CartRuleOptionsType;
use izi\prestashop\Hook\HookInterface;
use izi\prestashop\Module\Exception\PrestaShopModuleErrorException;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
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
     * @param array{return: false|\CartRule, controller: \AdminCartRulesControllerCore, request?: Request} $parameters
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
            throw new \InvalidArgumentException(sprintf('Expected parameter "return" to be false or an instance of %s, "%s" given.', \CartRule::class, get_debug_type($cartRule)));
        }

        $form = $this->formFactory->create(CartRuleOptionsType::class, new UpdateCartRuleOptionsCommand((int) $cartRule->id), [
            'csrf_protection' => false,
        ]);
        $form->handleRequest($request);

        if (!$form->isSubmitted()) {
            return;
        }

        if ($form->isValid()) {
            $this->bus->handle($form->getData());

            return;
        }

        $controller = $parameters['controller'] ?? null;

        if (!$controller instanceof \AdminControllerCore) {
            throw new \InvalidArgumentException(sprintf('Expected parameter "controller" to be an instance of %s, "%s" given.', \AdminControllerCore::class, get_debug_type($controller)));
        }

        $this->setControllerErrors($controller, $form);
    }

    /**
     * @see \AdminControllerCore::postProcess()
     *
     * @todo: store errors in session and redirect back to the cart rule edit page?
     */
    private function setControllerErrors(\AdminControllerCore $controller, FormInterface $form): void
    {
        $errors = $this->getFormErrors($form);
        $error = array_pop($errors);
        $controller->errors = array_merge($controller->errors, $errors);
        $controller->setRedirectAfter(null);

        throw new PrestaShopModuleErrorException($error);
    }

    /**
     * @return string[]
     */
    private function getFormErrors(FormInterface $form): array
    {
        $errors = [];

        foreach ($form->getErrors(true) as $error) {
            if (null === $origin = $error->getOrigin()) {
                $errors[] = $error->getMessage();
            } else {
                $label = $origin->getConfig()->getOption('label') ?? $origin->getName();
                $errors[] = sprintf('%s: %s', $label, $error->getMessage());
            }
        }

        return $errors;
    }
}

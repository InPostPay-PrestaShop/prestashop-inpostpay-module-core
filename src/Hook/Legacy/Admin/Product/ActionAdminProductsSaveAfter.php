<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Legacy\Admin\Product;

use izi\prestashop\CommandBusInterface;
use izi\prestashop\Hook\Admin\Product\ActionAfterUpdateProductFormHandler;
use izi\prestashop\Hook\Exception\InvalidHookParamException;
use izi\prestashop\Hook\Legacy\ControllerHelper;
use izi\prestashop\Hook\PrestaShopVersionAwareHookInterface;
use izi\prestashop\Hook\VersionRange;
use izi\prestashop\Module\Exception\PrestaShopModuleErrorException;
use izi\prestashop\ProductOptions\Form\ProductOptionsType;
use izi\prestashop\ProductOptions\Message\UpdateProductOptionsCommand;
use izi\prestashop\Translation\LegacyTranslator;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;

/* IGNORE_THIS_FILE_FOR_TRANSLATION */
final class ActionAdminProductsSaveAfter implements PrestaShopVersionAwareHookInterface
{
    public const HOOK_NAME = 'actionAdminProductsControllerSaveAfter';

    /**
     * @var FormFactoryInterface
     */
    private $formFactory;

    /**
     * @var CommandBusInterface
     */
    private $bus;

    /**
     * @var LegacyTranslator
     */
    private $translator;

    /**
     * @var bool
     */
    private $debug;

    public function __construct(FormFactoryInterface $formFactory, CommandBusInterface $bus, LegacyTranslator $translator, bool $debug = false)
    {
        $this->formFactory = $formFactory;
        $this->bus = $bus;
        $this->translator = $translator;
        $this->debug = $debug;
    }

    public static function getHookName(): string
    {
        return self::HOOK_NAME;
    }

    public static function getVersionRange(): VersionRange
    {
        return new VersionRange(null, '9.0.0');
    }

    /**
     * @param array{return: \Product|false, controller: \AdminProductsControllerCore, request?: Request} $parameters
     */
    public function execute(array $parameters): void
    {
        $request = $parameters['request'] ?? null;

        if (!$request instanceof Request) {
            return;
        }

        if (false === $product = $parameters['return'] ?? null) {
            return;
        }

        if (!$product instanceof \Product) {
            throw InvalidHookParamException::unexpectedType('return', $product, \Product::class . '|false');
        }

        $form = $this->formFactory->create(ProductOptionsType::class, new UpdateProductOptionsCommand((int) $product->id), [
            'csrf_protection' => false,
        ]);
        $form->handleRequest($request);

        if (!$form->isSubmitted()) {
            return;
        }

        if (!$form->isValid()) {
            $controller = $parameters['controller'] ?? null;

            if (!$controller instanceof \AdminControllerCore) {
                throw InvalidHookParamException::unexpectedType('controller', $controller, \AdminControllerCore::class);
            }

            ControllerHelper::setFormErrors($controller, $form);
        }

        try {
            $this->bus->handle($form->getData());
        } catch (\Exception $e) {
            if ($this->debug) {
                throw $e;
            }

            throw new PrestaShopModuleErrorException($this->translator->l('An error occurred while updating InPost Pay options.', strtolower(ActionAfterUpdateProductFormHandler::HOOK_NAME)), 0, $e);
        }
    }
}

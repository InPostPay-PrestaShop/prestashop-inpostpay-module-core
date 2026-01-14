<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Legacy\Admin\Product;

use izi\prestashop\Hook\Exception\InvalidHookParamException;
use izi\prestashop\Hook\PrestaShopVersionAwareHookInterface;
use izi\prestashop\Hook\VersionRange;
use izi\prestashop\ProductOptions\Form\ProductOptionsType;
use izi\prestashop\ProductOptions\Message\UpdateProductOptionsCommand;
use izi\prestashop\ProductOptions\ProductOptionsRepositoryInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Twig\Environment;

final class DisplayAdminProductsOptionsStepBottom implements PrestaShopVersionAwareHookInterface
{
    public const HOOK_NAME = 'displayAdminProductsOptionsStepBottom';

    /**
     * @var ProductOptionsRepositoryInterface
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

    public function __construct(ProductOptionsRepositoryInterface $repository, FormFactoryInterface $formFactory, Environment $twig)
    {
        $this->repository = $repository;
        $this->formFactory = $formFactory;
        $this->twig = $twig;
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
     * @param array{id_product: int} $parameters
     */
    public function execute(array $parameters): string
    {
        $productId = $parameters['id_product'] ?? null;

        if (!\is_int($productId) && (!\is_string($productId) || !ctype_digit($productId))) {
            throw InvalidHookParamException::unexpectedType('id_product', $productId, 'int');
        }

        $command = $this->createOptionsUpdateCommand((int) $productId);
        $form = $this->formFactory->create(ProductOptionsType::class, $command, [
            'csrf_protection' => false,
        ]);

        return $this->twig->render('@Modules/inpostizi/views/templates/hook/legacy/admin/product/options_form.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    private function createOptionsUpdateCommand(int $productId): UpdateProductOptionsCommand
    {
        if (null !== $options = $this->repository->find($productId)) {
            return UpdateProductOptionsCommand::for($options);
        }

        return new UpdateProductOptionsCommand($productId);
    }
}

<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Admin\Product;

use izi\prestashop\Hook\Exception\InvalidHookParamException;
use izi\prestashop\Hook\PrestaShopVersionAwareHookInterface;
use izi\prestashop\Hook\VersionRange;
use izi\prestashop\ProductOptions\Form\ProductOptionsType;
use izi\prestashop\ProductOptions\Message\UpdateProductOptionsCommand;
use izi\prestashop\ProductOptions\ProductOptionsRepositoryInterface;
use Symfony\Component\Form\FormBuilderInterface;

final class ActionProductFormBuilderModifier implements PrestaShopVersionAwareHookInterface
{
    public const HOOK_NAME = 'actionProductFormBuilderModifier';

    public const OPTIONS_FORM_NAME = 'inpostizi_options';

    /**
     * @var ProductOptionsRepositoryInterface
     */
    private $repository;

    public function __construct(ProductOptionsRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public static function getHookName(): string
    {
        return self::HOOK_NAME;
    }

    public static function getVersionRange(): VersionRange
    {
        return new VersionRange('8.0.0');
    }

    /**
     * @param array{form_builder: FormBuilderInterface, id: int} $parameters
     */
    public function execute(array $parameters): void
    {
        $formBuilder = $parameters['form_builder'] ?? null;

        if (!$formBuilder instanceof FormBuilderInterface) {
            throw InvalidHookParamException::unexpectedType('form_builder', $formBuilder, FormBuilderInterface::class);
        }

        if (!\is_int($productId = $parameters['id'] ?? null)) {
            throw InvalidHookParamException::unexpectedType('id', $productId, 'int');
        }

        $command = $this->createOptionsUpdateCommand($productId);

        $formBuilder->get('options')->add(self::OPTIONS_FORM_NAME, ProductOptionsType::class, [
            'data' => $command,
            'label_tag_name' => 'h3',
            'attr' => [
                'class' => 'col-lg-4 p-0',
            ],
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

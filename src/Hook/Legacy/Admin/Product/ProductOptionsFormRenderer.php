<?php

declare(strict_types=1);

namespace izi\prestashop\Hook\Legacy\Admin\Product;

use izi\prestashop\ProductOptions\Form\ProductOptionsType;
use izi\prestashop\ProductOptions\Message\UpdateProductOptionsCommand;
use izi\prestashop\ProductOptions\ProductOptionsRepositoryInterface;
use izi\prestashop\View\Templating\RendererInterface;
use Symfony\Component\Form\FormFactoryInterface;

final class ProductOptionsFormRenderer
{
    /**
     * @var ProductOptionsRepositoryInterface
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

    public function __construct(ProductOptionsRepositoryInterface $repository, FormFactoryInterface $formFactory, RendererInterface $renderer)
    {
        $this->repository = $repository;
        $this->formFactory = $formFactory;
        $this->renderer = $renderer;
    }

    public function render(int $productId, string $template, array $options = []): string
    {
        $options['csrf_protection'] = false;

        $command = $this->createOptionsUpdateCommand((int) $productId);
        $form = $this->formFactory->create(ProductOptionsType::class, $command, $options);

        return $this->renderer->render($template, [
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

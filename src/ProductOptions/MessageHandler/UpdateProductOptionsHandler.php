<?php

declare(strict_types=1);

namespace izi\prestashop\ProductOptions\MessageHandler;

use izi\prestashop\Handler\CommandHandlerTrait;
use izi\prestashop\ProductOptions\Message\UpdateProductOptionsCommand;
use izi\prestashop\ProductOptions\ProductOptions;
use izi\prestashop\ProductOptions\ProductOptionsRepositoryInterface;

final class UpdateProductOptionsHandler implements UpdateProductOptionsHandlerInterface
{
    use CommandHandlerTrait;

    /**
     * @var ProductOptionsRepositoryInterface
     */
    private $repository;

    public function __construct(ProductOptionsRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function __invoke(UpdateProductOptionsCommand $command): void
    {
        $options = $this->repository->find($command->getProductId());

        if (null === $options && null === $command->getImageGalleryType()) {
            return;
        }

        if (null === $options) {
            $this->addOptions($command);
        } else {
            $this->updateOptions($options, $command);
        }
    }

    private function addOptions(UpdateProductOptionsCommand $command): void
    {
        $options = new ProductOptions($command->getProductId());
        $this->applyChanges($options, $command);
        $this->repository->add($options);
    }

    private function updateOptions(ProductOptions $options, UpdateProductOptionsCommand $command): void
    {
        $this->applyChanges($options, $command);
        $this->repository->update($options);
    }

    private function applyChanges(ProductOptions $options, UpdateProductOptionsCommand $command): void
    {
        $options->setImageGalleryType($command->getImageGalleryType());
    }
}

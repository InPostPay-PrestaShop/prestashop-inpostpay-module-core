<?php

declare(strict_types=1);

namespace izi\prestashop\Handler\Config;

use izi\prestashop\Command\Config\UpdateCartRuleOptionsCommand;
use izi\prestashop\Handler\CommandHandlerTrait;
use izi\prestashop\PromoCode\CartRuleOptions;
use izi\prestashop\PromoCode\CartRuleOptionsRepositoryInterface;

final class UpdateCartRuleOptionsHandler implements UpdateCartRuleOptionsHandlerInterface
{
    use CommandHandlerTrait;

    /**
     * @var CartRuleOptionsRepositoryInterface
     */
    private $repository;

    public function __construct(CartRuleOptionsRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function __invoke(UpdateCartRuleOptionsCommand $command): void
    {
        $options = $this->repository->find($cartRuleId = $command->getCartRuleId());

        if (null === $options) {
            $this->addOptions($command);
        } else {
            $this->updateOptions($options, $command);
        }
    }

    private function addOptions(UpdateCartRuleOptionsCommand $command): void
    {
        $options = new CartRuleOptions($command->getCartRuleId());
        $this->applyChanges($options, $command);
        $this->repository->add($options);
    }

    private function updateOptions(CartRuleOptions $options, UpdateCartRuleOptionsCommand $command): void
    {
        $this->applyChanges($options, $command);
        $this->repository->update($options);
    }

    private function applyChanges(CartRuleOptions $options, UpdateCartRuleOptionsCommand $command): void
    {
        if (null !== $isOmnibus = $command->isOmnibus()) {
            $options->setIsOmnibus($isOmnibus);
        }

        $options->setPromoDetailsPageId($command->getPromoDetailsPageId());
    }
}

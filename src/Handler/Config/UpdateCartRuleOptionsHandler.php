<?php

declare(strict_types=1);

namespace izi\prestashop\Handler\Config;

use izi\prestashop\Command\Config\UpdateCartRuleOptionsCommand;
use izi\prestashop\Handler\CommandHandlerTrait;
use izi\prestashop\Repository\CartRuleRepositoryInterface;

final class UpdateCartRuleOptionsHandler implements UpdateCartRuleOptionsHandlerInterface
{
    use CommandHandlerTrait;

    /**
     * @var CartRuleRepositoryInterface
     */
    private $repository;

    public function __construct(CartRuleRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function __invoke(UpdateCartRuleOptionsCommand $command): void
    {
        if (null === $isOmnibus = $command->isOmnibus()) {
            return;
        }

        $this->repository->setOmnibus(
            $command->getCartRuleId(),
            $isOmnibus
        );
    }
}

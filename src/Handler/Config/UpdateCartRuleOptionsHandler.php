<?php

declare(strict_types=1);

namespace izi\prestashop\Handler\Config;

use izi\prestashop\Command\Config\UpdateCartRuleOptionsCommand;
use izi\prestashop\Handler\CommandHandlerTrait;
use izi\prestashop\PromoCode\CartRuleOptions;
use izi\prestashop\PromoCode\CartRuleOptionsRepository;
use izi\prestashop\PromoCode\CartRuleOptionsRepositoryInterface;
use izi\prestashop\Repository\CartRuleRepositoryInterface;

final class UpdateCartRuleOptionsHandler implements UpdateCartRuleOptionsHandlerInterface
{
    use CommandHandlerTrait;

    /**
     * @var CartRuleOptionsRepositoryInterface
     */
    private $repository;

    /**
     * @var CartRuleRepositoryInterface|null
     */
    private $originalRepository;

    /**
     * @param CartRuleOptionsRepositoryInterface|CartRuleRepositoryInterface $repository
     */
    public function __construct(CartRuleRepositoryInterface $repository)
    {
        if (!$repository instanceof CartRuleOptionsRepositoryInterface) {
            @trigger_error(sprintf('Passing a $repository that does not implement "%s" to "%s()" is deprecated since 2.1.0.', CartRuleOptionsRepositoryInterface::class, __METHOD__), E_USER_DEPRECATED);

            $this->repository = CartRuleOptionsRepository::create();
            $this->originalRepository = $repository;
        } else {
            $this->repository = $repository;
        }
    }

    public function __invoke(UpdateCartRuleOptionsCommand $command): void
    {
        $options = $this->repository->find($cartRuleId = $command->getCartRuleId());

        if (null === $options) {
            $this->addOptions($command);
        } else {
            $this->updateOptions($options, $command);
        }

        if (null !== $this->originalRepository && null !== $isOmnibus = $command->isOmnibus()) {
            $this->originalRepository->setOmnibus($cartRuleId, $isOmnibus);
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

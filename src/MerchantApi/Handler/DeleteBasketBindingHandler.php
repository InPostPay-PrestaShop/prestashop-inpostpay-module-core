<?php

declare(strict_types=1);

namespace izi\prestashop\MerchantApi\Handler;

use izi\prestashop\MerchantApi\Command\DeleteBasketBindingCommand;
use izi\prestashop\Repository\BasketSessionRepositoryInterface;

final class DeleteBasketBindingHandler implements DeleteBasketBindingHandlerInterface
{
    /**
     * @var BasketSessionRepositoryInterface
     */
    private $repository;

    public function __construct(BasketSessionRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function __invoke(DeleteBasketBindingCommand $command)
    {
        if (null === $session = $this->repository->findByBasketId($command->getBasketId())) {
            return;
        }

        $session->unbind();
        $this->repository->persist($session);
    }
}

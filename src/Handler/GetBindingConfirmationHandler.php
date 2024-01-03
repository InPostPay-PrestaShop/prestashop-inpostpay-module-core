<?php

declare(strict_types=1);

namespace izi\prestashop\Handler;

use izi\prestashop\Command\GetBindingConfirmationCommand;
use izi\prestashop\Entities\BasketSessionInterface;
use izi\prestashop\Handler\Result\BindingConfirmationStream;
use izi\prestashop\Http\Response\ServerSentEvent;
use izi\prestashop\MerchantApi\Model\Basket\Request\BindingConfirmation;
use izi\prestashop\Repository\BasketSessionRepositoryInterface;

final class GetBindingConfirmationHandler implements GetBindingConfirmationHandlerInterface
{
    /**
     * @var BasketSessionRepositoryInterface
     */
    private $repository;

    public function __construct(BasketSessionRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function __invoke(GetBindingConfirmationCommand $command): BindingConfirmationStream
    {
        $session = null === ($basketId = $command->getBasketId())
            ? null
            : $this->repository->findByEntityId($basketId);

        return new BindingConfirmationStream(
            $session ? $session->getBasketId() : null,
            $this->createEventStream($session)
        );
    }

    /**
     * @return \Generator<ServerSentEvent<BindingConfirmation>>
     */
    private function createEventStream(?BasketSessionInterface $session): \Generator
    {
        yield ServerSentEvent::builder()
            ->setComment('start')
            ->build();

        if (null === $session || null === $confirmation = $session->getBindingConfirmation()) {
            yield ServerSentEvent::builder()
                ->setRetry(2000)
                ->build();

            return;
        }

        yield ServerSentEvent::builder()
            ->setData($confirmation)
            ->build();
    }
}

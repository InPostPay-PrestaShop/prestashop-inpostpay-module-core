<?php

declare(strict_types=1);

namespace izi\prestashop\MerchantApi\Handler\Order;

use izi\prestashop\Configuration\OrdersConfigurationInterface;
use izi\prestashop\Handler\CommandHandlerTrait;
use izi\prestashop\MerchantApi\Command\Order\UpdateCartMessageCommand;
use izi\prestashop\MerchantApi\Model\Order\Request\CreateOrderRequest;
use izi\prestashop\ObjectModel\ObjectManagerInterface;
use izi\prestashop\Order\Message\MessageFormatterInterface;

final class UpdateCartMessageHandler implements UpdateCartMessageHandlerInterface
{
    use CommandHandlerTrait;

    /**
     * @var ObjectManagerInterface
     */
    private $manager;

    /**
     * @var OrdersConfigurationInterface
     */
    private $configuration;

    /**
     * @var MessageFormatterInterface
     */
    private $formatter;

    public function __construct(ObjectManagerInterface $manager, OrdersConfigurationInterface $configuration, MessageFormatterInterface $formatter)
    {
        $this->manager = $manager;
        $this->configuration = $configuration;
        $this->formatter = $formatter;
    }

    public function __invoke(UpdateCartMessageCommand $command): void
    {
        $cart = $command->getCart();

        if (0 >= $cartId = (int) $cart->id) {
            return;
        }

        $message = $this->findOrCreateMessage($cartId);
        $message->message = $this->formatMessage((int) $cart->id_shop, $command->getRequest());

        if ('' === $message->message) {
            $this->manager->remove($message);

            return;
        }

        $this->manager->save($message);
    }

    private function formatMessage(int $shopId, CreateOrderRequest $request): string
    {
        $message = $this->configuration->getMessageFormat($shopId);

        return $this->formatter->format($message, $request);
    }

    private function findOrCreateMessage(int $cartId): \Message
    {
        $message = $this->manager->getRepository(\Message::class)->findOneBy([
            'id_cart' => $cartId,
        ]);

        if (null !== $message) {
            return $message;
        }

        $message = new \Message();
        $message->id_cart = $cartId;

        return $message;
    }
}

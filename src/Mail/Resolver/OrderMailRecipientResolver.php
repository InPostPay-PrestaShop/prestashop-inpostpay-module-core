<?php

namespace izi\prestashop\Mail\Resolver;

use izi\prestashop\Mail\Dto\MailRecipient;
use izi\prestashop\MerchantApi\Model\Order\Request\CreateOrderRequest;
use izi\prestashop\ObjectModel\Repository\ObjectRepositoryInterface;
use izi\prestashop\Repository\OrderDataRepositoryInterface;

class OrderMailRecipientResolver
{
    /**
     * @var OrderDataRepositoryInterface
     */
    private $orderDataRepository;

    /**
     * @var ObjectRepositoryInterface<\Order>
     */
    private $orderRepository;

    /**
     * @var string[]
     */
    private $emailList;

    /**
     * @var CreateOrderRequest|null
     */
    private $orderData;

    /**
     * @var string[]
     */
    private $bccList;

    /**
     * @var \Customer
     */
    private $customer;

    /**
     * @param ObjectRepositoryInterface<\Order> $orderRepository
     */
    public function __construct(
        OrderDataRepositoryInterface $orderDataRepository,
        ObjectRepositoryInterface $orderRepository
    ) {
        $this->orderDataRepository = $orderDataRepository;
        $this->orderRepository = $orderRepository;
    }

    public function resolve(string $idOrder, array $emails, array $bcc): MailRecipient
    {
        $this->emailList = $emails;
        $this->bccList = $bcc;
        $this->createCustomer($idOrder);
        if (!$this->resolveIsInpostOrder($idOrder) || !$this->resolveIsCustomerMail()) {
            return new MailRecipient($this->emailList, $this->bccList);
        }

        return $this->resolveRecipientMail();
    }

    private function resolveIsInpostOrder(string $idOrder): bool
    {
        $this->orderData = $this->orderDataRepository->getOrderData($idOrder);

        return !empty($this->orderData);
    }

    private function resolveRecipientMail(): MailRecipient
    {
        if (null === $this->orderData->getDelivery()->getEmail()) {
            return new MailRecipient($this->emailList, $this->bccList);
        }
        $this->addDeliveryMailToSend();

        return new MailRecipient($this->emailList, $this->bccList);
    }

    private function createCustomer(string $idOrder): void
    {
        if (null === $order = $this->orderRepository->find((int) $idOrder)) {
            throw new \RuntimeException('Order does not exist.');
        }
        $this->customer = $order->getCustomer();
    }

    private function resolveIsCustomerMail(): bool
    {
        return in_array($this->customer->email, $this->emailList);
    }

    private function addDeliveryMailToSend()
    {
        if ($this->customer->email === $this->orderData->getAccountInfo()->getEmail()) {
            $key = array_search($this->customer->email, $this->emailList);
            if (false !== $key) {
                $this->emailList[$key] = $this->orderData->getDelivery()->getEmail();
            }
        } else {
            $this->bccList[] = $this->orderData->getDelivery()->getEmail();
        }
    }
}

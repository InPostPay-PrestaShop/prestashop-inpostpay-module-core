<?php

declare(strict_types=1);

namespace izi\prestashop\Validator;

use izi\prestashop\Common\Currency;
use izi\prestashop\Common\Customer\InvoiceDetails;
use izi\prestashop\Common\Customer\LegalForm;
use izi\prestashop\Common\Delivery\DeliveryType;
use izi\prestashop\Common\Order\Consent;
use izi\prestashop\Common\PaymentType;
use izi\prestashop\Common\PhoneNumber;
use izi\prestashop\Common\Price;
use izi\prestashop\MerchantApi\Model\Order\Request\AccountInfo;
use izi\prestashop\MerchantApi\Model\Order\Request\ClientAddress;
use izi\prestashop\MerchantApi\Model\Order\Request\CreateOrderRequest;
use izi\prestashop\MerchantApi\Model\Order\Request\Delivery;
use izi\prestashop\MerchantApi\Model\Order\Request\DeliveryAddress;
use izi\prestashop\MerchantApi\Model\Order\Request\OrderDetails;
use izi\prestashop\Order\Message\MessageFormatterInterface;
use izi\prestashop\Uuid\Uuid;
use Symfony\Component\ExpressionLanguage\SyntaxError;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class ProcessableMessageFormatValidator extends ConstraintValidator
{
    /**
     * @var MessageFormatterInterface
     */
    private $formatter;

    public function __construct(MessageFormatterInterface $formatter)
    {
        $this->formatter = $formatter;
    }

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof ProcessableMessageFormat) {
            throw new UnexpectedTypeException($constraint, ProcessableMessageFormat::class);
        }

        if (null === $value) {
            return;
        }

        if (!\is_string($value)) {
            throw new UnexpectedTypeException($value, 'string');
        }

        try {
            foreach ($this->getExampleRequests() as $request) {
                $this->formatter->format($value, $request);
            }
        } catch (SyntaxError|\LogicException $e) {
            $this->context
                ->buildViolation('Invalid message format. {{ error }}')
                ->setParameter('{{ error }}', $e->getMessage())
                ->setTranslationDomain('Modules.Inpostizi.Validators')
                ->addViolation();

            return;
        }
    }

    private function getExampleRequests(): \Generator
    {
        $phoneNumber = new PhoneNumber('+48', '123456789');
        $orderDetails = new OrderDetails((string) Uuid::v4(), Currency::Pln(), new Price(0., 0., 0.), PaymentType::Card(), 'comment');
        $accountInfo = new AccountInfo('firstname', 'lastname', $phoneNumber, 'test@example.com', new ClientAddress('PL', 'address', 'city', '12-123'));
        $consents = [new Consent('1', '1', true)];
        $invoiceDetails = new InvoiceDetails(LegalForm::Company(), 'PL', 'city', 'street', '1', '12-123', 'PL', '1234567890', 'company');

        $deliveryType = DeliveryType::Apm();

        yield new CreateOrderRequest(
            $orderDetails,
            $accountInfo,
            new Delivery($deliveryType, $deliveryType->getAvailableServiceCodes(), 'test@example.com', $phoneNumber, 'APM123'),
            $consents,
            $invoiceDetails
        );

        $deliveryType = DeliveryType::Courier();
        $deliveryAddress = new DeliveryAddress('full name', 'PL', 'address', 'city', '12-123');

        yield new CreateOrderRequest(
            $orderDetails,
            $accountInfo,
            new Delivery($deliveryType, $deliveryType->getAvailableServiceCodes(), 'test@example.com', $phoneNumber, null, $deliveryAddress, 'note'),
            $consents,
            $invoiceDetails
        );
    }
}

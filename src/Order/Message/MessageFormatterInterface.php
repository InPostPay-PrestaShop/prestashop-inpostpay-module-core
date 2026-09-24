<?php

declare(strict_types=1);

namespace izi\prestashop\Order\Message;

use izi\prestashop\MerchantApi\Model\Order\Request\CreateOrderRequest;

interface MessageFormatterInterface
{
    public const DEFAULT_FORMAT = <<<FORMAT
{order_comments}

Unikalny maskowany adres e-mail: {delivery_email}
FORMAT;

    public function format(string $message, CreateOrderRequest $request): string;
}

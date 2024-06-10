<?php

declare(strict_types=1);

namespace izi\prestashop\Order\Message;

use izi\prestashop\MerchantApi\Model\Order\Request\CreateOrderRequest;
use izi\prestashop\Order\Message\Processor\ProcessorInterface;

final class MessageFormatter implements MessageFormatterInterface
{
    /**
     * @var ParametersExtractorInterface
     */
    private $parametersExtractor;

    /**
     * @var iterable<ProcessorInterface>
     */
    private $processors;

    /**
     * @param iterable<ProcessorInterface> $processors
     */
    public function __construct(ParametersExtractorInterface $parametersExtractor, iterable $processors)
    {
        $this->parametersExtractor = $parametersExtractor;
        $this->processors = $processors;
    }

    public function format(string $message, CreateOrderRequest $request): string
    {
        $parameters = $this->parametersExtractor->extract($request);
        $formatted = $this
            ->processMessage(new Message($message, $parameters))
            ->getMessage();

        return trim($formatted);
    }

    private function processMessage(Message $message): Message
    {
        foreach ($this->processors as $processor) {
            $message = $processor($message);
        }

        return $message;
    }
}

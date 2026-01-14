<?php

declare(strict_types=1);

namespace izi\prestashop\Order\Message\Processor;

use izi\prestashop\Enum\Enum;
use izi\prestashop\Order\Message\Message;

final class ParameterReplacementProcessor implements ProcessorInterface
{
    /**
     * @var string
     */
    private $dateFormat;

    public function __construct(?string $dateFormat = null)
    {
        $this->dateFormat = $dateFormat ?? \DateTime::ATOM;
    }

    public function __invoke(Message $message): Message
    {
        $originalMessage = $message->getMessage();

        if (!str_contains($originalMessage, '{')) {
            return $message;
        }

        $replacements = [];
        $parameters = $message->getParameters();

        foreach ($parameters as $name => $value) {
            $placeholder = \sprintf('{%s}', $name);
            if (!str_contains($originalMessage, $placeholder)) {
                continue;
            }

            $replacements[$placeholder] = $this->formatReplacement($value);
        }

        return new Message(strtr($originalMessage, $replacements), $parameters);
    }

    private function formatReplacement($value): string
    {
        if (\is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (null === $value || \is_scalar($value)) {
            return (string) $value;
        }

        if ($value instanceof Enum) {
            return (string) $value->value;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format($this->dateFormat);
        }

        if ($value instanceof \JsonSerializable) {
            return json_encode($value);
        }

        if (\is_array($value)) {
            return $this->formatArray($value);
        }

        return \sprintf('[%s]', get_debug_type($value));
    }

    private function formatArray(array $value): string
    {
        if (\is_int(key($value)) || false === $json = json_encode($value)) {
            $formatted = array_map([$this, 'formatReplacement'], $value);

            return \sprintf('[%s]', implode(', ', $formatted));
        }

        return $json;
    }
}

<?php

declare(strict_types=1);

namespace izi\prestashop\Order\Message\Processor;

use izi\prestashop\Order\Message\ExpressionLanguage;
use izi\prestashop\Order\Message\Message;

final class ExpressionLanguageProcessor implements ProcessorInterface
{
    /**
     * @var ExpressionLanguage
     */
    private $expressionLanguage;

    public function __construct(?ExpressionLanguage $expressionLanguage = null)
    {
        $this->expressionLanguage = $expressionLanguage ?? new ExpressionLanguage();
    }

    public function __invoke(Message $message): Message
    {
        if (!str_contains($result = $message->getMessage(), '{{')) {
            return $message;
        }

        if (!preg_match_all('/\{\{\s*(.*)?\s*}}/', $result, $matches, PREG_SET_ORDER)) {
            return $message;
        }

        $newParameters = $parameters = $message->getParameters();

        foreach ($matches as $i => $match) {
            $key = sprintf('expression_%d', $i);
            $result = str_replace($match[0], sprintf('{%s}', $key), $result);
            $newParameters[$key] = $this->expressionLanguage->evaluate($match[1], $parameters);
        }

        return new Message($result, $newParameters);
    }
}

<?php

declare(strict_types=1);

namespace izi\prestashop\Order\Message\Processor;

use izi\prestashop\Order\Message\ExpressionLanguage;
use izi\prestashop\Order\Message\Message;

final class ConditionalBlockProcessor implements ProcessorInterface
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
        if (!str_contains($originalMessage = $message->getMessage(), '{% if ')) {
            return $message;
        }

        $parameters = $message->getParameters();

        $result = '';
        $currentBlock = [];
        $expression = null;

        foreach (preg_split('/\r\n?|\n/', $originalMessage) as $line) {
            if (preg_match('/^\{% if (.*) %}\s*$/', $line, $matches)) {
                if ([] !== $currentBlock) {
                    throw new \LogicException('Nested conditional blocks are not supported.');
                }

                $expression = $matches[1];
            } elseif (preg_match('/^\{% endif %}\s*$/', $line)) {
                if (null === $expression) {
                    throw new \LogicException('Syntax error: unexpected "endif".');
                }

                if ($this->expressionLanguage->evaluate($expression, $parameters)) {
                    $result .= implode(\PHP_EOL, $currentBlock) . \PHP_EOL;
                }

                $currentBlock = [];
                $expression = null;
            } elseif (null !== $expression) {
                $currentBlock[] = $line;
            } else {
                $result .= $line . \PHP_EOL;
            }
        }

        if ([] !== $currentBlock) {
            throw new \LogicException('Syntax error: unclosed "if" block.');
        }

        return new Message($result, $parameters);
    }
}

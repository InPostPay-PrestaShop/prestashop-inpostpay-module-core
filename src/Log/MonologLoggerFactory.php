<?php

declare(strict_types=1);

namespace izi\prestashop\Log;

use izi\prestashop\Log\Handler\HandlerFactoryInterface;
use Monolog\Formatter\JsonFormatter;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\HandlerInterface;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;
use Psr\Log\LoggerInterface;

final class MonologLoggerFactory implements LoggerFactoryInterface
{
    private static $decoratePsrProcessor;

    /**
     * @param iterable<HandlerFactoryInterface> $factories
     */
    private $factories;

    /**
     * @var array<string, HandlerInterface>
     */
    private $handlers = [];

    /**
     * @param iterable<HandlerFactoryInterface> $factories
     */
    public function __construct(iterable $factories)
    {
        $this->factories = $factories;
    }

    public function create(string $name, array $options): LoggerInterface
    {
        $handler = $this->getHandler($name, $options);

        return new Logger($name, [$handler]);
    }

    private function getHandler(string $name, array $options): HandlerInterface
    {
        return $this->handlers[$name] ?? ($this->handlers[$name] = $this->createHandler($options));
    }

    private function createHandler(array $options): HandlerInterface
    {
        if (!isset($options['type']) || !\is_string($options['type'])) {
            throw new \InvalidArgumentException('A required "type" option is missing.');
        }

        $handler = $this
            ->getHandlerFactory($options['type'])
            ->create($this->filterHandlerOptions($options));

        if (\is_array($options['channels'] ?? null)) {
            foreach ($options['channels'] as $channel) {
                $this->handlers[$channel] = $handler;
            }
        }

        if (null === ($options['process_psr_3_messages']['enabled'] ?? null)) {
            $options['process_psr_3_messages']['enabled'] = !isset($options['handler']) && empty($options['members']);
        }

        if ($options['process_psr_3_messages']['enabled']) {
            $this->processPsrMessages($handler, $options['process_psr_3_messages']);
        }

        if ($options['include_stacktraces'] ?? false) {
            $this->includeStacktraces($handler);
        }

        return $handler;
    }

    private function filterHandlerOptions(array $options): array
    {
        unset($options['type'], $options['channels'], $options['include_stacktraces'], $options['process_psr_3_messages']);

        return $options;
    }

    private function getHandlerFactory(string $type): HandlerFactoryInterface
    {
        foreach ($this->factories as $factory) {
            if ($factory->supports($type)) {
                return $factory;
            }
        }

        throw new \RuntimeException(\sprintf('No log handler factory registered for type "%s".', $type));
    }

    private function includeStacktraces(HandlerInterface $handler): void
    {
        $formatter = $handler->getFormatter();
        if ($formatter instanceof LineFormatter || $formatter instanceof JsonFormatter) {
            $formatter->includeStacktraces();
        }
    }

    private function processPsrMessages(HandlerInterface $handler, array $options): void
    {
        $removeContextFields = $options['remove_used_context_fields'] ?? false;
        $processor = new PsrLogMessageProcessor($options['date_format'] ?? null, $removeContextFields);

        if ($removeContextFields && self::shouldDecoratePsrProcessor()) {
            $processor = new UsedContextFieldsRemovingProcessor($processor);
        }

        $handler->pushProcessor($processor);
    }

    private static function shouldDecoratePsrProcessor(): bool
    {
        if (isset(self::$decoratePsrProcessor)) {
            return self::$decoratePsrProcessor;
        }

        $class = new \ReflectionClass(PsrLogMessageProcessor::class);

        if (null === $constructor = $class->getConstructor()) {
            return self::$decoratePsrProcessor = true;
        }

        return self::$decoratePsrProcessor = 2 > \count($constructor->getParameters());
    }
}

/**
 * @internal
 */
final class UsedContextFieldsRemovingProcessor
{
    /**
     * @var callable
     */
    private $processor;

    public function __construct(callable $processor)
    {
        $this->processor = $processor;
    }

    public function __invoke(array $record): array
    {
        if (false === strpos($record['message'], '{')) {
            return ($this->processor)($record);
        }

        $toRemove = [];

        foreach ($record['context'] as $key => $val) {
            $placeholder = '{' . $key . '}';
            if (false === strpos($record['message'], $placeholder)) {
                continue;
            }

            $toRemove[] = $key;
        }

        $record = ($this->processor)($record);

        foreach ($toRemove as $key) {
            unset($record['context'][$key]);
        }

        return $record;
    }
}

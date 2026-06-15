<?php

declare(strict_types=1);

namespace izi\prestashop\Serializer;

use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;

trait SafeDeserializerTrait
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var LoggerInterface|null
     */
    private $logger;

    /**
     * @template T
     *
     * @param class-string<T> $class
     *
     * @return T|null
     */
    private function deserialize(string $value, string $class, string $format = 'json', array $context = [])
    {
        try {
            return $this->serializer->deserialize($value, $class, $format, $context);
        } catch (ExceptionInterface $e) {
            if (null !== $this->logger) {
                $this->logger->error('Failed to deserialize {format} data into "{class}".', [
                    'format' => $format,
                    'class' => $class,
                    'data' => $value,
                    'exception' => \sprintf('%s: %s at %s:%d', \get_class($e), $e->getMessage(), $e->getFile(), $e->getLine()),
                ]);
            }

            return null;
        }
    }
}

<?php

declare(strict_types=1);

namespace izi\prestashop\Serializer;

use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;

trait SafeDeserializerTrait
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

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
            return null;
        }
    }
}

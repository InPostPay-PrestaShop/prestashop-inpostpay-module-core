<?php

namespace izi\prestashop\Serializer\Normalizer;

use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Exception\LogicException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Polyfill for Sf 2.8
 *
 * @internal
 *
 * @author Fred Cox <mcfedr@gmail.com>
 * @see \Symfony\Component\Serializer\Normalizer\JsonSerializableNormalizer
 */
class JsonSerializableNormalizer implements NormalizerInterface, SerializerAwareInterface
{
    private $serializer;

    public function setSerializer(SerializerInterface $serializer): void
    {
        $this->serializer = $serializer;
    }

    public function normalize($object, $format = null, array $context = [])
    {
        if (!$object instanceof \JsonSerializable) {
            throw new InvalidArgumentException(sprintf('The object must implement "%s".', \JsonSerializable::class));
        }

        if (!$this->serializer instanceof NormalizerInterface) {
            throw new LogicException('Cannot normalize object because injected serializer is not a normalizer.');
        }

        return $this->serializer->normalize($object->jsonSerialize(), $format, $context);
    }

    public function supportsNormalization($data, $format = null): bool
    {
        return $data instanceof \JsonSerializable;
    }
}

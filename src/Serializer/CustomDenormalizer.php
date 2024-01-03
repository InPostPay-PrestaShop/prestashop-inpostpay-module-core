<?php

declare(strict_types=1);

namespace izi\prestashop\Serializer;

use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class CustomDenormalizer implements DenormalizerInterface
{
    /**
     * @template T of DenormalizableInterface
     *
     * @param class-string<T> $type
     *
     * @return T|null
     */
    public function denormalize($data, $type, $format = null, array $context = [])
    {
        if (null === $data) {
            return null;
        }

        if (!is_array($data)) {
            throw new InvalidArgumentException(sprintf('Data expected to be an array, "%s" given.', is_object($data) ? get_class($data) : gettype($data)));
        }

        if (!is_subclass_of($type, DenormalizableInterface::class)) {
            throw new UnexpectedValueException(sprintf('Unsupported type: "%s".', $type));
        }

        return $type::denormalize($data);
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return is_subclass_of($type, DenormalizableInterface::class);
    }
}

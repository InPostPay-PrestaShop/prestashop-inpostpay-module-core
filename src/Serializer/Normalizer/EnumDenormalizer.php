<?php

declare(strict_types=1);

namespace izi\prestashop\Serializer\Normalizer;

use izi\prestashop\Enum\Enum;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class EnumDenormalizer implements DenormalizerInterface
{
    /**
     * @template T of Enum
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

        if (!is_scalar($data)) {
            throw new InvalidArgumentException(sprintf('Data expected to be scalar, "%s" given.', get_class($data)));
        }

        if (!is_subclass_of($type, Enum::class)) {
            throw new InvalidArgumentException(sprintf('Unsupported type: "%s".', $type));
        }

        return $type::tryFrom($data);
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return is_subclass_of($type, Enum::class);
    }
}

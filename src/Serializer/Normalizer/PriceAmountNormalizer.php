<?php

declare(strict_types=1);

namespace izi\prestashop\Serializer\Normalizer;

use izi\prestashop\Common\PriceAmount;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class PriceAmountNormalizer implements NormalizerInterface
{
    public function normalize($object, $format = null, array $context = []): string
    {
        if (!$object instanceof PriceAmount) {
            throw new InvalidArgumentException(sprintf('Expected object to be an instance of "%s", "%s" given.', PriceAmount::class, get_debug_type($object)));
        }

        if ('json' !== $format) {
            throw new UnexpectedValueException(sprintf('Expected format to be "json", "%s" given.', is_string($format) ? $format : get_debug_type($format)));
        }

        return number_format($object->jsonSerialize(), 2, '.', '');
    }

    public function supportsNormalization($data, $format = null): bool
    {
        return $data instanceof PriceAmount && 'json' === $format;
    }
}

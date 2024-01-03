<?php

declare(strict_types=1);

namespace izi\prestashop\Serializer;

use izi\prestashop\Common\Price;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class PriceNormalizer implements NormalizerInterface
{
    public function normalize($object, $format = null, array $context = []): array
    {
        if (!$object instanceof Price) {
            throw new InvalidArgumentException('Expected object to be an instance of "%s", "%s" given.', Price::class, is_object($object) ? get_class($object) : gettype($object));
        }

        if ('json' !== $format) {
            throw new UnexpectedValueException('Expected format to be "json", "%s" given.', Price::class, $format ?? 'NULL');
        }

        return array_map(static function (float $amount) {
            return number_format($amount, 2, '.', '');
        }, $object->jsonSerialize());
    }

    public function supportsNormalization($data, $format = null): bool
    {
        return $data instanceof Price && 'json' === $format;
    }
}

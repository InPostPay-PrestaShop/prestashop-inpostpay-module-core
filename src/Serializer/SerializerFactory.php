<?php

declare(strict_types=1);

namespace izi\prestashop\Serializer;

use izi\prestashop\Serializer\DateTimeNormalizer as DateTimeNormalizerPolyfill;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\JsonSerializableNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

final class SerializerFactory
{
    public static function create(): SerializerInterface
    {
        $normalizers = self::createNormalizers();
        $encoders = [new JsonEncoder()];

        return new Serializer($normalizers, $encoders);
    }

    private static function createNormalizers(): array
    {
        return [
            new PriceNormalizer(),
            new EnumDenormalizer(),
            new CustomDenormalizer(),
            class_exists(DateTimeNormalizer::class) ? new DateTimeNormalizer() : new DateTimeNormalizerPolyfill(),
            new JsonSerializableNormalizer(), // TODO Sf 2.8?
            new ArrayDenormalizer(),
            self::createObjectNormalizer(),
        ];
    }

    private static function createObjectNormalizer(): ObjectNormalizer
    {
        $extractor = new PropertyInfoExtractor([], [new ReflectionExtractor(), new PhpDocExtractor()]);

        return new ObjectNormalizer(null, null, null, $extractor); // TODO Sf 2.8
    }
}

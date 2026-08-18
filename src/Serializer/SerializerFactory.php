<?php

declare(strict_types=1);

namespace izi\prestashop\Serializer;

use izi\prestashop\Serializer\Normalizer\BasketAppPaginationPageDenormalizer;
use izi\prestashop\Serializer\Normalizer\CustomDenormalizer;
use izi\prestashop\Serializer\Normalizer\EnumDenormalizer;
use izi\prestashop\Serializer\Normalizer\PriceAmountNormalizer;
use izi\prestashop\Serializer\Normalizer\PriceNormalizer;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\CustomNormalizer;
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
            new PriceAmountNormalizer(),
            new EnumDenormalizer(),
            new CustomDenormalizer(),
            new CustomNormalizer(),
            new DateTimeNormalizer(),
            new JsonSerializableNormalizer(),
            new ArrayDenormalizer(),
            new BasketAppPaginationPageDenormalizer(),
            self::createObjectNormalizer(),
        ];
    }

    private static function createObjectNormalizer(): ObjectNormalizer
    {
        $typeExtractor = new PropertyInfoExtractor([], [
            self::createPhpDocTypeExtractor(),
            new ReflectionExtractor(),
        ]);

        return new ObjectNormalizer(null, null, null, $typeExtractor);
    }

    private static function createPhpDocTypeExtractor(): PropertyTypeExtractorInterface
    {
        try {
            return new PhpDocExtractor();
        } catch (\Exception $e) {
            return new PropertyDocBlockTypeExtractor();
        }
    }
}

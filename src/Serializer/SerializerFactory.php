<?php

declare(strict_types=1);

namespace izi\prestashop\Serializer;

use izi\prestashop\Serializer\Normalizer\CustomDenormalizer;
use izi\prestashop\Serializer\Normalizer\DateTimeNormalizer as DateTimeNormalizerPolyfill;
use izi\prestashop\Serializer\Normalizer\EnumDenormalizer;
use izi\prestashop\Serializer\Normalizer\JsonSerializableNormalizer as JsonSerializableNormalizerPolyfill;
use izi\prestashop\Serializer\Normalizer\ObjectNormalizer as CustomObjectNormalizer;
use izi\prestashop\Serializer\Normalizer\PriceNormalizer;
use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\FileReflector;
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
            new EnumDenormalizer(),
            new CustomDenormalizer(),
            new CustomNormalizer(),
            class_exists(DateTimeNormalizer::class) ? new DateTimeNormalizer() : new DateTimeNormalizerPolyfill(),
            class_exists(JsonSerializableNormalizer::class) ? new JsonSerializableNormalizer() : new JsonSerializableNormalizerPolyfill(),
            new ArrayDenormalizer(),
            self::createObjectNormalizer(),
        ];
    }

    private static function createObjectNormalizer(): ObjectNormalizer
    {
        $typeExtractor = self::createTypeExtractor();

        $class = new \ReflectionClass(ObjectNormalizer::class);
        $params = $class->getConstructor()->getParameters();

        return 3 < count($params) && \Tools::version_compare(_PS_VERSION_, '1.7.5.1', '>=')
            ? new ObjectNormalizer(null, null, null, $typeExtractor)
            : new CustomObjectNormalizer(null, null, null, $typeExtractor);
    }

    private static function createTypeExtractor(): PropertyTypeExtractorInterface
    {
        $typeExtractors = [];

        if (
            class_exists(DocBlock::class)
            && (\Tools::version_compare(_PS_VERSION_, '1.7.4', '>=') || class_exists(FileReflector::class))
        ) {
            $typeExtractors[] = new PhpDocExtractor();
        } else {
            $typeExtractors[] = new PropertyDocBlockTypeExtractor();
        }

        $typeExtractors[] = new ReflectionExtractor();

        return new PropertyInfoExtractor([], $typeExtractors);
    }
}

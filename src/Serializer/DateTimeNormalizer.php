<?php

declare(strict_types=1);

namespace izi\prestashop\Serializer;

use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Polyfill for Sf 2.8
 *
 * @internal
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @see \Symfony\Component\Serializer\Normalizer\DateTimeNormalizer
 */
class DateTimeNormalizer implements NormalizerInterface, DenormalizerInterface
{
    public const FORMAT_KEY = 'datetime_format';
    public const TIMEZONE_KEY = 'datetime_timezone';

    private const DEFAULT_FORMAT = \DateTime::RFC3339;

    private const SUPPORTED_TYPES = [
        \DateTimeInterface::class => true,
        \DateTimeImmutable::class => true,
        \DateTime::class => true,
    ];

    public function normalize($object, $format = null, array $context = []): string
    {
        if (!$object instanceof \DateTimeInterface) {
            throw new InvalidArgumentException('The object must implement the "\DateTimeInterface".');
        }

        $dateTimeFormat = $context[self::FORMAT_KEY] ?? self::DEFAULT_FORMAT;
        $timezone = $this->getTimezone($context);

        if (null !== $timezone) {
            $object = clone $object;
            $object = $object->setTimezone($timezone);
        }

        return $object->format($dateTimeFormat);
    }

    public function supportsNormalization($data, $format = null): bool
    {
        return $data instanceof \DateTimeInterface;
    }

    public function denormalize($data, $type, $format = null, array $context = []): \DateTimeInterface
    {
        $dateTimeFormat = $context[self::FORMAT_KEY] ?? null;
        $timezone = $this->getTimezone($context);

        if (null === $data || is_string($data) && '' === trim($data)) {
            throw new UnexpectedValueException('The data is either an empty string or null, you should pass a string that can be parsed with the passed format or a valid DateTime string.');
        }

        $data = (string) $data;

        if (null !== $dateTimeFormat) {
            return $this->doDenormalize($data, $type, $dateTimeFormat, $timezone);
        }

        $object = \DateTime::class === $type
            ? \DateTime::createFromFormat(self::DEFAULT_FORMAT, $data, $timezone)
            : \DateTimeImmutable::createFromFormat(self::DEFAULT_FORMAT, $data, $timezone);

        if (false !== $object) {
            return $object;
        }

        try {
            return \DateTime::class === $type ? new \DateTime($data, $timezone) : new \DateTimeImmutable($data, $timezone);
        } catch (\Exception $e) {
            throw new UnexpectedValueException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return isset(self::SUPPORTED_TYPES[$type]);
    }

    private function doDenormalize(string $data, string $type, string $dateTimeFormat, ?\DateTimeZone $timezone): \DateTimeInterface
    {
        $object = \DateTime::class === $type
            ? \DateTime::createFromFormat($dateTimeFormat, $data, $timezone)
            : \DateTimeImmutable::createFromFormat($dateTimeFormat, $data, $timezone);

        if (false !== $object) {
            return $object;
        }

        $dateTimeErrors = \DateTime::class === $type ? \DateTime::getLastErrors() : \DateTimeImmutable::getLastErrors();

        throw new UnexpectedValueException(sprintf('Parsing datetime string "%s" using format "%s" resulted in %d errors: ', $data, $dateTimeFormat, $dateTimeErrors['error_count']) . "\n" . implode("\n", $this->formatDateTimeErrors($dateTimeErrors['errors'])));
    }

    /**
     * @return string[]
     */
    private function formatDateTimeErrors(array $errors): array
    {
        $formattedErrors = [];

        foreach ($errors as $pos => $message) {
            $formattedErrors[] = sprintf('at position %d: %s', $pos, $message);
        }

        return $formattedErrors;
    }

    private function getTimezone(array $context): ?\DateTimeZone
    {
        $dateTimeZone = $context[self::TIMEZONE_KEY] ?? null;

        if (null === $dateTimeZone) {
            return null;
        }

        return $dateTimeZone instanceof \DateTimeZone ? $dateTimeZone : new \DateTimeZone($dateTimeZone);
    }
}

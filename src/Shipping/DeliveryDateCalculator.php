<?php

declare(strict_types=1);

namespace izi\prestashop\Shipping;

use izi\prestashop\Common\Delivery\DeliveryType;
use izi\prestashop\Configuration\ShippingConfigurationInterface;
use Psr\Clock\ClockInterface;
use Symfony\Component\OptionsResolver\Exception\ExceptionInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class DeliveryDateCalculator implements DeliveryDateCalculatorInterface
{
    public const DEFAULT_DELIVERY_TIME_HOURS = 48;

    /**
     * @var ShippingConfigurationInterface
     */
    private $configuration;

    /**
     * @var ClockInterface
     */
    private $clock;

    /**
     * @var array{
     *     timezone: \DateTimeZone|null,
     *     working_hours_start: string,
     *     working_hours_end: string,
     * }
     */
    private $options;

    /**
     *  The options are read in the given timezone, which is what decides how the working hours are interpreted.
     *
     *  Available options:
     *  - working_hours_start: DateTimeInterface|string - datetime object or time string;
     *                                                    if given as an object, its timezone is not adjusted
     *  - working_hours_end: DateTimeInterface|string - same as "working_hours_start"; must be a later time
     *  - timezone: DateTimeZone|string|null - string will be interpreted as a timezone name;
     *                                         if null, the calculation will use the timezone set by the given clock
     *
     * @param array<string, mixed> $options
     *
     * @throws ExceptionInterface if the given options are invalid
     */
    public function __construct(ShippingConfigurationInterface $configuration, ClockInterface $clock, array $options = [])
    {
        $this->configuration = $configuration;
        $this->clock = $clock;
        $this->options = $this->resolveOptions($options);
    }

    public function calculate(\Cart $cart, DeliveryType $deliveryType): \DateTimeImmutable
    {
        if (DeliveryType::Digital() === $deliveryType) {
            return $this->calculateDigitalDeliveryDate();
        }

        $datetime = $this->clock->now();
        if (null !== $timezone = $this->options['timezone']) {
            $datetime = $datetime->setTimezone($timezone);
        }

        $datetime = $this->moveIntoWorkingHours($datetime);
        $hours = $this->configuration->getShippingOptions($deliveryType)->getEstimatedDeliveryTime() ?? self::DEFAULT_DELIVERY_TIME_HOURS;

        while ($hours >= 24) {
            $datetime = $this->skipWeekend($datetime->modify('+1 day'));
            $hours -= 24;
        }

        if ($hours > 0) {
            $datetime = $this->skipWeekend($datetime->modify(\sprintf('+%d hours', $hours)));
            $datetime = $this->moveIntoWorkingHours($datetime);
        }

        return $this->getNextFullHour($datetime);
    }

    private function calculateDigitalDeliveryDate(): \DateTimeImmutable
    {
        $datetime = $this->clock->now()->modify('+1 minute');

        return $datetime->setTime((int) $datetime->format('G'), (int) $datetime->format('i'));
    }

    private function moveIntoWorkingHours(\DateTimeImmutable $datetime): \DateTimeImmutable
    {
        if ($this->options['working_hours_end'] < $time = $datetime->format('H:i:s')) {
            $datetime = $datetime->modify('+1 day');
        } elseif ($time >= $this->options['working_hours_start'] && !$this->isWeekend($datetime)) {
            return $datetime;
        }

        return $this
            ->skipWeekend($datetime)
            ->setTime(...$this->parseTime($this->options['working_hours_start']));
    }

    private function skipWeekend(\DateTimeImmutable $datetime): \DateTimeImmutable
    {
        if (!$this->isWeekend($datetime)) {
            return $datetime;
        }

        return $datetime->modify(\sprintf('+%d days', 8 - (int) $datetime->format('N')));
    }

    private function isWeekend(\DateTimeImmutable $datetime): bool
    {
        return $datetime->format('N') >= 6;
    }

    private function getNextFullHour(\DateTimeImmutable $datetime): \DateTimeImmutable
    {
        $datetime = $datetime->modify('+1 hour');

        return $datetime->setTime((int) $datetime->format('G'), 0);
    }

    /**
     * @return array{0: int, 1: int} hours and minutes
     */
    private function parseTime(string $time): array
    {
        return array_map('intval', explode(':', $time, 2));
    }

    /**
     * @throws ExceptionInterface
     */
    private function resolveOptions(array $options): array
    {
        return (new OptionsResolver())
            ->setDefaults([
                'timezone' => 'Europe/Warsaw',
                'working_hours_start' => '09:00',
                'working_hours_end' => '17:00',
            ])
            ->setAllowedTypes('timezone', [\DateTimeZone::class, 'string', 'null'])
            ->setAllowedTypes('working_hours_start', [\DateTimeInterface::class, 'string'])
            ->setAllowedTypes('working_hours_end', [\DateTimeInterface::class, 'string'])
            ->setAllowedValues('working_hours_start', static function ($value) {
                return self::isTime($value);
            })
            ->setAllowedValues('working_hours_end', static function ($value) {
                return self::isTime($value);
            })
            ->setNormalizer('timezone', static function (Options $options, $value): ?\DateTimeZone {
                if (null === $value || $value instanceof \DateTimeZone) {
                    return $value;
                }

                try {
                    return new \DateTimeZone($value);
                } catch (\Exception $e) {
                    throw new InvalidOptionsException(\sprintf('The option "timezone" must be a timezone name, got "%s".', $value), 0, $e);
                }
            })
            ->setNormalizer('working_hours_start', static function (Options $options, $value): string {
                return self::normalizeTime($value);
            })
            ->setNormalizer('working_hours_end', static function (Options $options, $value): string {
                $value = self::normalizeTime($value);
                if ($value > $options['working_hours_start']) {
                    return $value;
                }

                throw new InvalidOptionsException('The option "working_hours_end" must be later than "working_hours_start".');
            })
            ->resolve($options);
    }

    /**
     * @param \DateTimeInterface|string $value
     */
    private static function isTime($value): bool
    {
        if ($value instanceof \DateTimeInterface) {
            return true;
        }

        return (bool) preg_match('/^(?:[01]?\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/', $value);
    }

    /**
     * @param \DateTimeInterface|string $value
     */
    private static function normalizeTime($value): string
    {
        if (\is_string($value)) {
            $value = new \DateTime($value);
        }

        return $value->format('H:i:s');
    }
}

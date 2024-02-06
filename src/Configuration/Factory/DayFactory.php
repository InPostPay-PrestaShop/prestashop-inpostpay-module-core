<?php

declare(strict_types=1);

namespace izi\prestashop\Configuration\Factory;

use izi\prestashop\Configuration\DTO\Day;

final class DayFactory implements DayFactoryInterface
{
    private const TRANSLATION_SOURCE = 'dayfactory';

    private $module;

    public function __construct(\Module $module)
    {
        $this->module = $module;
    }

    public function create(int $id): Day
    {
        if (!in_array($id, Day::DAYS)) {
            throw new \InvalidArgumentException('Invalid day id provided.');
        }

        return new Day($id, $this->getDayName($id));
    }

    private function getDayName(int $id): string
    {
        switch ($id) {
            case Day::MONDAY:
                return $this->module->l('Poniedziałek', self::TRANSLATION_SOURCE);
            case Day::TUESDAY:
                return $this->module->l('Wtorek', self::TRANSLATION_SOURCE);
            case Day::WEDNESDAY:
                return $this->module->l('Środa', self::TRANSLATION_SOURCE);
            case Day::THURSDAY:
                return $this->module->l('Czwartek', self::TRANSLATION_SOURCE);
            case Day::FRIDAY:
                return $this->module->l('Piątek', self::TRANSLATION_SOURCE);
            case Day::SATURDAY:
                return $this->module->l('Sobota', self::TRANSLATION_SOURCE);
            case Day::SUNDAY:
                return $this->module->l('Niedziela', self::TRANSLATION_SOURCE);
            default:
                return '';
        }
    }
}


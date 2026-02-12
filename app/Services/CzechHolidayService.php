<?php

namespace App\Services;

use Carbon\Carbon;

class CzechHolidayService
{
    /**
     * Vrátí všechny české státní svátky pro daný rok
     */
    public static function getHolidays(int $year): array
    {
        $holidays = [
            // Pevné svátky
            Carbon::create($year, 1, 1)->format('Y-m-d') => 'Nový rok / Den obnovy samostatného českého státu',
            Carbon::create($year, 5, 1)->format('Y-m-d') => 'Svátek práce',
            Carbon::create($year, 5, 8)->format('Y-m-d') => 'Den vítězství',
            Carbon::create($year, 7, 5)->format('Y-m-d') => 'Den slovanských věrozvěstů Cyrila a Metoděje',
            Carbon::create($year, 7, 6)->format('Y-m-d') => 'Den upálení mistra Jana Husa',
            Carbon::create($year, 9, 28)->format('Y-m-d') => 'Den české státnosti',
            Carbon::create($year, 10, 28)->format('Y-m-d') => 'Den vzniku samostatného československého státu',
            Carbon::create($year, 11, 17)->format('Y-m-d') => 'Den boje za svobodu a demokracii',
            Carbon::create($year, 12, 24)->format('Y-m-d') => 'Štědrý den',
            Carbon::create($year, 12, 25)->format('Y-m-d') => '1. svátek vánoční',
            Carbon::create($year, 12, 26)->format('Y-m-d') => '2. svátek vánoční',
        ];

        // Pohyblivé svátky
        $easterDate = self::getEasterDate($year);
        $holidays[$easterDate->format('Y-m-d')] = 'Velikonoční neděle';
        $holidays[$easterDate->copy()->addDay()->format('Y-m-d')] = 'Velikonoční pondělí';

        return $holidays;
    }

    /**
     * Vypočítá datum Velikonoc pro daný rok
     */
    private static function getEasterDate(int $year): Carbon
    {
        $days = easter_days($year);
        return Carbon::create($year, 3, 21)->addDays($days);
    }

    /**
     * Zkontroluje, zda je daný den státní svátek
     */
    public static function isHoliday(Carbon $date): bool
    {
        $holidays = self::getHolidays($date->year);
        return isset($holidays[$date->format('Y-m-d')]);
    }

    /**
     * Vrátí název svátku, pokud existuje
     */
    public static function getHolidayName(Carbon $date): ?string
    {
        $holidays = self::getHolidays($date->year);
        return $holidays[$date->format('Y-m-d')] ?? null;
    }

    /**
     * Vrátí pouze pracovní dny (svátky na víkendech se nezobrazují)
     */
    public static function getWorkdayHolidays(int $year): array
    {
        $holidays = self::getHolidays($year);
        $workdayHolidays = [];

        foreach ($holidays as $dateStr => $name) {
            $date = Carbon::parse($dateStr);
            if ($date->isWeekday()) {
                $workdayHolidays[$dateStr] = $name;
            }
        }

        return $workdayHolidays;
    }
}

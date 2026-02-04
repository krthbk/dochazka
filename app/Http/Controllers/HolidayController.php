<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class HolidayController extends Controller
{
    public function cz(Request $request)
    {
        $validated = $request->validate([
            'start' => ['required', 'date'],
            'end'   => ['required', 'date'],
        ]);

        $start = Carbon::parse($validated['start'])->startOfDay();
        $end   = Carbon::parse($validated['end'])->endOfDay();

        $fixed = config('holidays.cz_fixed', []);
        $years = range($start->year, $end->year);

        $events = [];

        // helper: přidá background + label pro dané datum (1 den)
        $pushHoliday = function (Carbon $date, string $title, string $idPrefix) use (&$events, $start, $end) {
            if (!$date->betweenIncluded($start, $end)) return;

            $ymd = $date->toDateString();
            $endYmd = $date->copy()->addDay()->toDateString(); // FullCalendar end = exclusive

            $events[] = [
                'id' => "{$idPrefix}-bg-{$ymd}",
                'start' => $ymd,
                'end' => $endYmd,
                'allDay' => true,
                'display' => 'background',
                'backgroundColor' => '#e5e7eb',
                'borderColor' => '#e5e7eb',
            ];

            $events[] = [
                'id' => "{$idPrefix}-label-{$ymd}",
                'title' => $title,
                'start' => $ymd,
                'end' => $endYmd,
                'allDay' => true,
                'editable' => false,
                'classNames' => ['holiday-label'],
            ];
        };

        foreach ($years as $year) {
            // 1) FIXNÍ SVÁTKY z configu
            foreach ($fixed as $h) {
                $date = Carbon::create($year, $h['month'], $h['day'])->startOfDay();
                $pushHoliday($date, $h['title'], "holiday-fixed-{$year}-{$h['month']}-{$h['day']}");
            }

            // 2) POHYBLIVÉ: VELIKONOCE (bez timezone driftu)
            // easter_days = počet dnů po 21. březnu
            $easterSunday = Carbon::create($year, 3, 21)->startOfDay()->addDays(easter_days($year));

            $goodFriday   = $easterSunday->copy()->subDays(2);
            $easterMonday = $easterSunday->copy()->addDay();

            $pushHoliday($goodFriday, 'Velký pátek', "holiday-easter-goodfriday-{$year}");
            $pushHoliday($easterMonday, 'Velikonoční pondělí', "holiday-easter-monday-{$year}");
        }

        return response()->json($events);
    }
}

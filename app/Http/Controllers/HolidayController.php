<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class HolidayController extends Controller
{
    public function cz(Request $request)
    {
        $start = Carbon::parse($request->query('start'))->startOfDay();
        $end   = Carbon::parse($request->query('end'))->endOfDay();

        $fixed = config('holidays.cz_fixed', []);
        $years = range($start->year, $end->year);

        $events = [];

        foreach ($years as $year) {
            foreach ($fixed as $h) {
                $date = Carbon::create($year, $h['month'], $h['day'])->startOfDay();

                if ($date->betweenIncluded($start, $end)) {

                    // background (šedé pozadí)
                    $events[] = [
                        'id' => "holiday-bg-{$year}-{$h['month']}-{$h['day']}",
                        'start' => $date->toDateString(),
                        'allDay' => true,
                        'display' => 'background',
                        'backgroundColor' => '#e5e7eb',
                        'borderColor' => '#e5e7eb',
                    ];

                    // label (text uprostřed)
                    $events[] = [
                        'id' => "holiday-label-{$year}-{$h['month']}-{$h['day']}",
                        'title' => $h['title'],
                        'start' => $date->toDateString(),
                        'allDay' => true,
                        'editable' => false,
                        'classNames' => ['holiday-label'],
                    ];
                }
            }
        }

        return response()->json($events);
    }
}

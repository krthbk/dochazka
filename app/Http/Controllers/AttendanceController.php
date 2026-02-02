<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\TeamMember;
use App\Models\Attendance;

class AttendanceController extends Controller
{
    // ✅ renderuje welcome.blade.php
    public function index()
    {
        $members = TeamMember::orderBy('name')->get();
        return view('welcome', compact('members'));
    }

    // ✅ ukládání záznamu
    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id'   => ['required', 'integer', 'exists:team_members,id'],
            'from_date' => ['required', 'date'],
            'to_date'   => ['required', 'date', 'after_or_equal:from_date'],
            'activity'  => ['required', 'string', 'max:255'],
        ]);

        $row = Attendance::create([
            'team_member_id' => $data['user_id'],
            'from_date'      => $data['from_date'],
            'to_date'        => $data['to_date'],
            'activity'       => $data['activity'],
        ]);

        // ✅ když posíláš fetch, vrať JSON
        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'id' => $row->id]);
        }

        // fallback (kdyby se posílalo klasickým POSTem)
        return back()->with('status', 'Uloženo');
    }

    // ✅ všechny záznamy pro kalendář
    public function events(Request $request)
    {
        $start = Carbon::parse($request->query('start'))->startOfDay();
        $end   = Carbon::parse($request->query('end'))->endOfDay();

        $rows = Attendance::query()
            ->with('member:id,name')
            ->whereDate('to_date', '>=', $start)
            ->whereDate('from_date', '<=', $end)
            ->get();

        $events = $rows->map(function ($a) {
            return [
                'id' => (string) $a->id,
                'title' => $a->activity,
                'start' => $a->from_date,
                // end exclusive:
                'end' => Carbon::parse($a->to_date)->addDay()->toDateString(),
                'allDay' => true,
                'extendedProps' => [
                    'memberId' => $a->team_member_id,
                    'memberName' => $a->member?->name ?? '',
                ],
            ];
        });

        return response()->json($events);
    }
}
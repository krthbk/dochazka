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
            return response()->json(['ok' => true, 'id' => (string) $row->id]);
        }

        return back()->with('status', 'Uloženo');
    }

    // ✅ EDITACE záznamu (PATCH /attendance/{attendance})
    public function update(Request $request, Attendance $attendance)
    {
        $data = $request->validate([
            // user_id nechávám volitelný – můžeš i měnit komu to patří (když chceš)
            'user_id'   => ['sometimes', 'integer', 'exists:team_members,id'],
            'from_date' => ['required', 'date'],
            'to_date'   => ['required', 'date', 'after_or_equal:from_date'],
            'activity'  => ['required', 'string', 'max:255'],
        ]);

        $attendance->update([
            'team_member_id' => $data['user_id'] ?? $attendance->team_member_id,
            'from_date'      => $data['from_date'],
            'to_date'        => $data['to_date'],
            'activity'       => $data['activity'],
        ]);

        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'id' => (string) $attendance->id]);
        }

        return back()->with('status', 'Upraveno');
    }

    // ✅ SMAZÁNÍ záznamu (DELETE /attendance/{attendance})
    public function destroy(Request $request, Attendance $attendance)
    {
        $attendance->delete();

        if ($request->wantsJson()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('status', 'Smazáno');
    }

    // ✅ záznamy pro kalendář (+ filtr podle člověka)
    public function events(Request $request)
    {
        $start = Carbon::parse($request->query('start'))->startOfDay();
        $end   = Carbon::parse($request->query('end'))->endOfDay();

        $query = Attendance::query()
            ->with('member:id,name')
            ->whereDate('to_date', '>=', $start)
            ->whereDate('from_date', '<=', $end);

        // ✅ FILTR: když přijde member_id, vrať jen jeho záznamy
        if ($request->filled('member_id')) {
            $query->where('team_member_id', (int) $request->query('member_id'));
        }

        $rows = $query->get();

        $events = $rows->map(function ($a) {
            return [
                'id' => (string) $a->id,
                'title' => $a->activity,
                'start' => Carbon::parse($a->from_date)->toDateString(),
                'end' => Carbon::parse($a->to_date)->addDay()->toDateString(), // exclusive
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

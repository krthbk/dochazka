<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\TeamMember;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

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
            'team_member_id' => ['required', 'integer', 'exists:team_members,id'],
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
            'activity' => ['required', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
        ]);

        $row = Attendance::create([
            'team_member_id' => (int) $data['team_member_id'],
            'from_date' => $data['from_date'],
            'to_date' => $data['to_date'],
            'activity' => $data['activity'],
            'note' => $data['note'] ?? null,
        ]);

        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'id' => (string) $row->id]);
        }

        return back()->with('status', 'Uloženo');
    }

    // ✅ EDITACE záznamu (PATCH /attendance/{attendance})
    public function update(Request $request, Attendance $attendance)
    {
        $data = $request->validate([
            // team_member_id nechávám volitelný – můžeš i měnit komu to patří
            'team_member_id' => ['sometimes', 'integer', 'exists:team_members,id'],
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date', 'after_or_equal:from_date'],
            'activity' => ['required', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
        ]);

        $attendance->update([
            'team_member_id' => isset($data['team_member_id'])
                ? (int) $data['team_member_id']
                : $attendance->team_member_id,
            'from_date' => $data['from_date'],
            'to_date' => $data['to_date'],
            'activity' => $data['activity'],
            'note' => $data['note'] ?? null,
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
        $validated = $request->validate([
            'start' => ['required', 'date'],
            'end' => ['required', 'date'],
            'member_id' => ['nullable', 'integer', 'exists:team_members,id'],
        ]);

        $start = Carbon::parse($validated['start'])->startOfDay();
        $end = Carbon::parse($validated['end'])->endOfDay();

        $query = Attendance::query()
            ->with('member:id,name')
            ->whereDate('to_date', '>=', $start)
            ->whereDate('from_date', '<=', $end);

        if (!empty($validated['member_id'])) {
            $query->where('team_member_id', (int) $validated['member_id']);
        }

        $rows = $query->get();

        $events = $rows->map(function ($a) {
            return [
                'id' => (string) $a->id,
                'title' => $a->activity,
                'start' => Carbon::parse($a->from_date)->toDateString(),
                // FullCalendar bere end jako exclusive -> +1 den
                'end' => Carbon::parse($a->to_date)->addDay()->toDateString(),
                'allDay' => true,
                'extendedProps' => [
                    'memberId' => $a->team_member_id,
                    'memberName' => $a->member?->name ?? '',
                    'note' => $a->note ?? '',
                ],
            ];
        });

        return response()->json($events);
    }
}

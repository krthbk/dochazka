<?php

namespace App\Livewire;

use App\Models\Activity;
use App\Models\User;
use App\Services\CzechHolidayService;
use Carbon\Carbon;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class MonthlyCalendar extends Component
{
    public $year;
    public $month;
    public $selectedUserId = 'all';
    public $selectedLocation = 'all';
    public $showActivityModal = false;
    public $showDayModal = false;
    public $selectedDate;
    public $selectedDayActivities = [];
    
    // Activity form
    public $activityId = null;
    public $activityName = '';
    public $activityNote = '';
    public $activityStartDate = '';
    public $activityEndDate = '';
    public $activityUserId;

    protected $rules = [
        'activityName' => 'required|string|max:255',
        'activityNote' => 'nullable|string',
        'activityStartDate' => 'required|date',
        'activityEndDate' => 'required|date|after_or_equal:activityStartDate',
        'activityUserId' => 'required|exists:users,id',
    ];

    protected $messages = [
        'activityName.required' => 'Název aktivity je povinný.',
        'activityStartDate.required' => 'Datum od je povinné.',
        'activityEndDate.required' => 'Datum do je povinné.',
        'activityEndDate.after_or_equal' => 'Datum do musí být stejné nebo pozdější než datum od.',
        'activityUserId.required' => 'Uživatel je povinný.',
    ];

    public function mount()
    {
        $this->year = now()->year;
        $this->month = now()->month;
        
        // Defaultní filtry podle uživatele
        if (Auth::user()->is_supervisor) {
            $this->selectedUserId = 'all';
        } else {
            $this->selectedUserId = Auth::id();
        }
    }

    public function previousMonth()
    {
        $date = Carbon::create($this->year, $this->month, 1)->subMonth();
        $this->year = $date->year;
        $this->month = $date->month;
    }

    public function nextMonth()
    {
        $date = Carbon::create($this->year, $this->month, 1)->addMonth();
        $this->year = $date->year;
        $this->month = $date->month;
    }

    public function openActivityModal($date, $userId = null)
    {
        $carbonDate = Carbon::parse($date);
        
        // Kontrola, zda není svátek
        if (CzechHolidayService::isHoliday($carbonDate)) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Do státních svátků nelze vkládat aktivity.'
            ]);
            return;
        }

        $this->resetActivityForm();
        $this->activityStartDate = $date;
        $this->activityEndDate = $date;
        $this->activityUserId = $userId ?? Auth::id();
        $this->showActivityModal = true;
    }

    public function editActivity($activityId)
    {
        $activity = Activity::findOrFail($activityId);
        
        if (!Auth::user()->canEdit($activity)) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Nemáte oprávnění upravovat tuto aktivitu.'
            ]);
            return;
        }

        $this->activityId = $activity->id;
        $this->activityName = $activity->name;
        $this->activityNote = $activity->note;
        $this->activityStartDate = $activity->start_date->format('Y-m-d');
        $this->activityEndDate = $activity->end_date->format('Y-m-d');
        $this->activityUserId = $activity->user_id;
        $this->showActivityModal = true;
    }

    public function saveActivity()
    {
        $this->validate();

        $activity = $this->activityId 
            ? Activity::findOrFail($this->activityId) 
            : new Activity();

        // Kontrola oprávnění
        if ($activity->exists && !Auth::user()->canEdit($activity)) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Nemáte oprávnění upravovat tuto aktivitu.'
            ]);
            return;
        }

        // Pro nové nebo při změně uživatele - kontrola, zda uživatel vytváří aktivitu pro sebe
        if (!Auth::user()->is_supervisor && $this->activityUserId != Auth::id()) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Můžete vytvářet aktivity pouze pro sebe.'
            ]);
            return;
        }

        $activity->fill([
            'user_id' => $this->activityUserId,
            'name' => $this->activityName,
            'note' => $this->activityNote,
            'start_date' => $this->activityStartDate,
            'end_date' => $this->activityEndDate,
        ]);

        // Kontrola překryvů
        if ($activity->hasOverlap()) {
            $this->addError('activityStartDate', 'Aktivita se překrývá s jinou aktivitou tohoto uživatele.');
            return;
        }

        $activity->save();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => $this->activityId ? 'Aktivita upravena.' : 'Aktivita vytvořena.'
        ]);

        $this->closeActivityModal();
    }

    public function deleteActivity($activityId)
    {
        $activity = Activity::findOrFail($activityId);
        
        if (!Auth::user()->canDelete($activity)) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Nemáte oprávnění smazat tuto aktivitu.'
            ]);
            return;
        }

        $activity->delete();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Aktivita smazána.'
        ]);

        $this->closeActivityModal();
        $this->closeDayModal();
    }

    public function openDayModal($date)
    {
        $this->selectedDate = $date;
        $carbonDate = Carbon::parse($date);
        
        $query = Activity::with('user')
            ->where(function ($q) use ($carbonDate) {
                $q->where('start_date', '<=', $carbonDate)
                  ->where('end_date', '>=', $carbonDate);
            });

        // Aplikuj filtry
        if ($this->selectedUserId !== 'all') {
            $query->where('user_id', $this->selectedUserId);
        }

        if ($this->selectedLocation !== 'all') {
            $query->whereHas('user', function ($q) {
                $q->where('location', $this->selectedLocation);
            });
        }

        $this->selectedDayActivities = $query->get();
        $this->showDayModal = true;
    }

    public function closeDayModal()
    {
        $this->showDayModal = false;
        $this->selectedDate = null;
        $this->selectedDayActivities = [];
    }

    private function resetActivityForm()
    {
        $this->activityId = null;
        $this->activityName = '';
        $this->activityNote = '';
        $this->activityStartDate = '';
        $this->activityEndDate = '';
        $this->activityUserId = Auth::id();
        $this->resetErrorBag();
    }

    public function closeActivityModal()
    {
        $this->showActivityModal = false;
        $this->resetActivityForm();
    }

    public function render()
    {
        // Získej první den měsíce
        $firstDay = Carbon::create($this->year, $this->month, 1);
        $lastDay = $firstDay->copy()->endOfMonth();

        // Získej všechny pracovní dny v měsíci
        $workingDays = [];
        $current = $firstDay->copy();
        
        while ($current <= $lastDay) {
            if ($current->isWeekday()) {
                $workingDays[] = $current->copy();
            }
            $current->addDay();
        }

        // Získej aktivity pro aktuální měsíc
        $query = Activity::with('user')
            ->where(function ($q) use ($firstDay, $lastDay) {
                $q->whereBetween('start_date', [$firstDay, $lastDay])
                  ->orWhereBetween('end_date', [$firstDay, $lastDay])
                  ->orWhere(function ($q) use ($firstDay, $lastDay) {
                      $q->where('start_date', '<=', $firstDay)
                        ->where('end_date', '>=', $lastDay);
                  });
            });

        // Aplikuj filtry
        if ($this->selectedUserId !== 'all') {
            $query->where('user_id', $this->selectedUserId);
        }

        if ($this->selectedLocation !== 'all') {
            $query->whereHas('user', function ($q) {
                $q->where('location', $this->selectedLocation);
            });
        }

        $activities = $query->get();

        // Seskup aktivity podle dnů
        $activitiesByDay = [];
        foreach ($activities as $activity) {
            foreach ($activity->getWorkingDays() as $day) {
                $dateKey = $day->format('Y-m-d');
                if (!isset($activitiesByDay[$dateKey])) {
                    $activitiesByDay[$dateKey] = [];
                }
                $activitiesByDay[$dateKey][] = $activity;
            }
        }

        return view('livewire.monthly-calendar', [
            'workingDays' => $workingDays,
            'activitiesByDay' => $activitiesByDay,
            'holidays' => CzechHolidayService::getWorkdayHolidays($this->year),
            'users' => User::orderBy('name')->get(),
            'locations' => ['Ostrava', 'Ústí nad Labem', 'Nadřízená'],
        ]);
    }
}

<div class="max-w-[1600px] mx-auto p-6">
    {{-- Header s navigací a filtry --}}
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Docházka</h1>
            <div class="flex items-center gap-4">
                <button wire:click="previousMonth" class="p-2 hover:bg-gray-100 rounded-lg transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </button>
                <h2 class="text-xl font-semibold text-gray-800 min-w-[200px] text-center">
                    {{ \Carbon\Carbon::create($year, $month, 1)->locale('cs')->isoFormat('MMMM YYYY') }}
                </h2>
                <button wire:click="nextMonth" class="p-2 hover:bg-gray-100 rounded-lg transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Filtry --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Uživatel</label>
                <select wire:model.live="selectedUserId" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
                    <option value="all">Všichni</option>
                    @if(!auth()->user()->is_supervisor)
                        <option value="{{ auth()->id() }}">Jen já</option>
                    @endif
                    @foreach($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Lokalita</label>
                <select wire:model.live="selectedLocation" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
                    <option value="all">Všechny</option>
                    @foreach($locations as $location)
                        <option value="{{ $location }}">{{ $location }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    {{-- Kalendář --}}
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        {{-- Hlavička kalendáře - dny v týdnu --}}
        <div class="grid grid-cols-5 bg-gray-50 border-b">
            @foreach(['Pondělí', 'Úterý', 'Středa', 'Čtvrtek', 'Pátek'] as $day)
                <div class="p-3 text-center font-semibold text-gray-700 border-r last:border-r-0">
                    {{ $day }}
                </div>
            @endforeach
        </div>

        {{-- Dny v měsíci --}}
        <div class="grid grid-cols-5">
            @php
                $currentWeekday = 1; // Pondělí
                $previousMonth = \Carbon\Carbon::create($year, $month, 1)->subMonth();
            @endphp

            @foreach($workingDays as $day)
                @php
                    $dayOfWeek = $day->dayOfWeekIso; // 1 = Po, 5 = Pá
                    $dateKey = $day->format('Y-m-d');
                    $isHoliday = isset($holidays[$dateKey]);
                    $dayActivities = $activitiesByDay[$dateKey] ?? [];
                    
                    // Přidej prázdné buňky pro dny před prvním dnem měsíce
                    if ($day->day === 1) {
                        $emptyDays = $dayOfWeek - 1;
                        for ($i = 0; $i < $emptyDays; $i++) {
                            echo '<div class="border-r border-b min-h-[120px] bg-gray-50"></div>';
                        }
                    }
                @endphp

                <div class="border-r border-b min-h-[120px] {{ $isHoliday ? 'bg-gray-100' : 'hover:bg-gray-50' }} transition relative group"
                     @if(!$isHoliday)
                     x-data="{ showAddButton: false }"
                     @mouseenter="showAddButton = true"
                     @mouseleave="showAddButton = false"
                     @endif>
                    
                    {{-- Datum --}}
                    <div class="p-2 flex items-center justify-between">
                        <span class="text-sm font-medium {{ $isHoliday ? 'text-gray-500' : 'text-gray-900' }}">
                            {{ $day->day }}
                        </span>
                        
                        @if(!$isHoliday && $selectedUserId !== 'all')
                            <button 
                                x-show="showAddButton"
                                x-transition
                                wire:click="openActivityModal('{{ $dateKey }}', {{ $selectedUserId }})"
                                class="text-blue-600 hover:text-blue-700 p-1 rounded hover:bg-blue-50"
                                title="Přidat aktivitu">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                            </button>
                        @endif
                    </div>

                    {{-- Svátek --}}
                    @if($isHoliday)
                        <div class="px-2 pb-2">
                            <p class="text-xs text-gray-600 italic">{{ $holidays[$dateKey] }}</p>
                        </div>
                    @else
                        {{-- Aktivity --}}
                        <div class="px-2 pb-2 space-y-1">
                            @foreach($dayActivities->take(3) as $activity)
                                <div 
                                    wire:click="editActivity({{ $activity->id }})"
                                    class="text-xs px-2 py-1 rounded cursor-pointer hover:opacity-80 transition text-white"
                                    style="background-color: {{ $activity->user->color }}"
                                    title="{{ $activity->note ? $activity->note : '' }}"
                                    x-data="{ showTooltip: false }"
                                    @mouseenter="showTooltip = true"
                                    @mouseleave="showTooltip = false">
                                    
                                    <div class="font-medium truncate">{{ $activity->user->name }}</div>
                                    <div class="truncate opacity-90">{{ $activity->name }}</div>
                                    
                                    {{-- Tooltip s poznámkou --}}
                                    @if($activity->note)
                                        <div 
                                            x-show="showTooltip"
                                            x-transition
                                            class="absolute z-50 bg-gray-900 text-white text-xs rounded-lg px-3 py-2 mt-1 shadow-lg max-w-xs"
                                            style="left: 50%; transform: translateX(-50%);">
                                            {{ $activity->note }}
                                        </div>
                                    @endif
                                </div>
                            @endforeach

                            {{-- Tlačítko "+ další" --}}
                            @if($dayActivities->count() > 3)
                                <button 
                                    wire:click="openDayModal('{{ $dateKey }}')"
                                    class="text-xs text-blue-600 hover:text-blue-700 font-medium px-2 py-1 hover:bg-blue-50 rounded w-full text-left">
                                    + {{ $dayActivities->count() - 3 }} další
                                </button>
                            @endif
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    {{-- Modal pro vytvoření/úpravu aktivity --}}
    @if($showActivityModal)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" wire:click.self="closeActivityModal">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
                <div class="p-6">
                    <h3 class="text-xl font-bold text-gray-900 mb-4">
                        {{ $activityId ? 'Upravit aktivitu' : 'Nová aktivita' }}
                    </h3>

                    <form wire:submit="saveActivity" class="space-y-4">
                        {{-- Uživatel (pouze pro Janu) --}}
                        @if(auth()->user()->is_supervisor)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Uživatel *</label>
                                <select wire:model="activityUserId" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                                    @endforeach
                                </select>
                                @error('activityUserId') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                        @endif

                        {{-- Název aktivity --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Název aktivity *</label>
                            <input type="text" wire:model="activityName" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200" placeholder="např. Dovolená, Home office...">
                            @error('activityName') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        {{-- Datum od --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Datum od *</label>
                            <input type="date" wire:model="activityStartDate" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
                            @error('activityStartDate') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        {{-- Datum do --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Datum do *</label>
                            <input type="date" wire:model="activityEndDate" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
                            @error('activityEndDate') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        {{-- Poznámka --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Poznámka</label>
                            <textarea wire:model="activityNote" rows="3" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200" placeholder="Volitelná poznámka..."></textarea>
                            @error('activityNote') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        {{-- Tlačítka --}}
                        <div class="flex gap-3 pt-4">
                            <button type="submit" class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition font-medium">
                                {{ $activityId ? 'Uložit změny' : 'Vytvořit' }}
                            </button>
                            <button type="button" wire:click="closeActivityModal" class="flex-1 bg-gray-200 text-gray-800 px-4 py-2 rounded-lg hover:bg-gray-300 transition font-medium">
                                Zrušit
                            </button>
                        </div>

                        {{-- Smazat (jen při editaci) --}}
                        @if($activityId)
                            <button 
                                type="button" 
                                wire:click="deleteActivity({{ $activityId }})"
                                wire:confirm="Opravdu chcete smazat tuto aktivitu?"
                                class="w-full text-red-600 hover:text-red-700 font-medium py-2 hover:bg-red-50 rounded-lg transition">
                                Smazat aktivitu
                            </button>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal pro zobrazení všech aktivit v den --}}
    @if($showDayModal)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" wire:click.self="closeDayModal">
            <div class="bg-white rounded-lg shadow-xl max-w-lg w-full mx-4 max-h-[80vh] overflow-y-auto">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-bold text-gray-900">
                            Aktivity {{ \Carbon\Carbon::parse($selectedDate)->locale('cs')->isoFormat('D. MMMM YYYY') }}
                        </h3>
                        <button wire:click="closeDayModal" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <div class="space-y-3">
                        @forelse($selectedDayActivities as $activity)
                            <div 
                                wire:click="editActivity({{ $activity->id }})"
                                class="p-4 rounded-lg cursor-pointer hover:opacity-90 transition text-white"
                                style="background-color: {{ $activity->user->color }}">
                                <div class="font-bold text-lg">{{ $activity->user->name }}</div>
                                <div class="font-medium mt-1">{{ $activity->name }}</div>
                                @if($activity->note)
                                    <div class="mt-2 text-sm opacity-90">{{ $activity->note }}</div>
                                @endif
                                <div class="mt-2 text-xs opacity-75">
                                    {{ $activity->start_date->format('d.m.Y') }} - {{ $activity->end_date->format('d.m.Y') }}
                                </div>
                            </div>
                        @empty
                            <p class="text-gray-500 text-center py-8">Žádné aktivity</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

{{-- Alpine.js pro tooltips --}}
@script
<script>
    // Livewire listener pro notifikace
    $wire.on('notify', (event) => {
        const data = event[0];
        alert(data.message); // Můžeš nahradit toast knihovnou
    });
</script>
@endscript

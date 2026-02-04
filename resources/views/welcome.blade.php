<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- CSRF pro fetch --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Docházka</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-gray-100 font-sans antialiased">
<header class="max-w-5xl mx-auto px-4 pt-8">
    <h1 class="text-2xl font-semibold">Docházka</h1>
    <p class="text-gray-600">
        Vyber člena týmu a klikni do kalendáře pro zápis.
        Klik na existující záznam = editace.
    </p>
</header>

<main class="max-w-5xl mx-auto px-4 py-6 space-y-4">

    {{-- Ovládání --}}
    <section>
        <label for="user_id" class="block text-sm font-medium text-gray-700">
            Zapsat za
        </label>

        <select
            id="user_id"
            name="user_id"
            class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-3 py-2"
        >
            <option value="">— vyber člena týmu —</option>
            @foreach ($members as $member)
                <option value="{{ $member->id }}">{{ $member->name }}</option>
            @endforeach
        </select>

        <p class="text-xs text-gray-500 mt-1">
            Tip: výběr člena zároveň filtruje kalendář.
        </p>
    </section>

    {{-- Kalendář --}}
    <section>
        <div id="calendar" class="bg-white rounded-2xl shadow p-3"></div>
    </section>

    {{-- Dialog --}}
    <dialog id="attendanceDialog" class="rounded-2xl p-0 w-full max-w-lg">
        <form
            id="attendanceForm"
            method="post"
            action="{{ route('attendance.store') }}"
            class="p-6 space-y-4"
        >
            @csrf

            {{-- hidden values --}}
            <input type="hidden" id="user_id_hidden" name="user_id">
            <input type="hidden" id="attendance_id" value="">
            <input type="hidden" id="_method" name="_method" value="POST">

            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-xl font-semibold">Zapsat aktivitu</h2>
                    <p id="dialogHint" class="text-xs text-gray-500 mt-1">
                        Vytváříš nový záznam.
                    </p>
                </div>

                <button
                    type="button"
                    id="cancelDialog"
                    class="px-3 py-2 rounded-xl border border-gray-300 bg-white text-sm"
                >
                    Zavřít
                </button>
            </div>

            <input type="hidden" id="from_date" name="from_date">
            <input type="hidden" id="to_date" name="to_date">

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Od</label>
                    <input
                        id="from_date_view"
                        type="date"
                        class="mt-1 w-full rounded-xl border border-gray-300 bg-gray-50 px-3 py-2"
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Do</label>
                    <input
                        id="to_date_view"
                        type="date"
                        class="mt-1 w-full rounded-xl border border-gray-300 bg-gray-50 px-3 py-2"
                    >
                </div>
            </div>

            {{-- Aktivita --}}
            <div>
                <label for="activity" class="block text-sm font-medium text-gray-700">
                    Aktivita
                </label>
                <input
                    id="activity"
                    name="activity"
                    type="text"
                    required
                    placeholder="např. home office, školení, vývoj…"
                    class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-3 py-2"
                >
            </div>

            {{-- ✅ POZNÁMKA --}}
            <div>
                <label for="note" class="block text-sm font-medium text-gray-700">
                    Poznámka (volitelné)
                </label>
                <textarea
                    id="note"
                    name="note"
                    rows="3"
                    placeholder="Detail k aktivitě – zobrazí se při najetí myší na záznam"
                    class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm resize-none"
                ></textarea>
            </div>

            <div class="flex justify-between items-center gap-2 pt-2">
                <button
                    type="button"
                    id="deleteAttendance"
                    class="px-4 py-2 rounded-xl border border-red-200 bg-red-50 text-red-700 font-semibold hidden"
                >
                    Smazat
                </button>

                <button
                    type="submit"
                    class="px-4 py-2 rounded-xl bg-blue-600 text-white font-semibold"
                >
                    Uložit
                </button>
            </div>

            <p class="text-xs text-gray-500">
                Poznámka se nezobrazuje v kalendáři – jen při detailu / hoveru.
            </p>
        </form>
    </dialog>

</main>
</body>
</html>

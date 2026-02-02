<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- ✅ pro fetch POST --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Docházka</title>

    <!-- FullCalendar CSS (CDN) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.20/index.global.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fullcalendar/daygrid@6.1.20/index.global.min.css">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Styles / Scripts -->
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <style>
            /* fallback tailwind (nechávám jak to máš) */
            /* ! tailwindcss v3.4.17 | MIT License | https://tailwindcss.com */
            *,:before,:after{--tw-border-spacing-x: 0;--tw-border-spacing-y: 0;--tw-translate-x: 0;--tw-translate-y: 0;--tw-rotate: 0;--tw-skew-x: 0;--tw-skew-y: 0;--tw-scale-x: 1;--tw-scale-y: 1;--tw-pan-x: ;--tw-pan-y: ;--tw-pinch-zoom: ;--tw-scroll-snap-strictness: proximity;--tw-gradient-from-position: ;--tw-gradient-via-position: ;--tw-gradient-to-position: ;--tw-ordinal: ;--tw-slashed-zero: ;--tw-numeric-figure: ;--tw-numeric-spacing: ;--tw-numeric-fraction: ;--tw-ring-inset: ;--tw-ring-offset-width: 0px;--tw-ring-offset-color: #fff;--tw-ring-color: rgb(59 130 246 / .5);--tw-ring-offset-shadow: 0 0 #0000;--tw-ring-shadow: 0 0 #0000;--tw-shadow: 0 0 #0000;--tw-shadow-colored: 0 0 #0000;--tw-blur: ;--tw-brightness: ;--tw-contrast: ;--tw-grayscale: ;--tw-hue-rotate: ;--tw-invert: ;--tw-saturate: ;--tw-sepia: ;--tw-drop-shadow: ;--tw-backdrop-blur: ;--tw-backdrop-brightness: ;--tw-backdrop-contrast: ;--tw-backdrop-grayscale: ;--tw-backdrop-hue-rotate: ;--tw-backdrop-invert: ;--tw-backdrop-opacity: ;--tw-backdrop-saturate: ;--tw-backdrop-sepia: ;--tw-contain-size: ;--tw-contain-layout: ;--tw-contain-paint: ;--tw-contain-style: }::backdrop{--tw-border-spacing-x: 0;--tw-border-spacing-y: 0;--tw-translate-x: 0;--tw-translate-y: 0;--tw-rotate: 0;--tw-skew-x: 0;--tw-skew-y: 0;--tw-scale-x: 1;--tw-scale-y: 1;--tw-pan-x: ;--tw-pan-y: ;--tw-pinch-zoom: ;--tw-scroll-snap-strictness: proximity;--tw-gradient-from-position: ;--tw-gradient-via-position: ;--tw-gradient-to-position: ;--tw-ordinal: ;--tw-slashed-zero: ;--tw-numeric-figure: ;--tw-numeric-spacing: ;--tw-numeric-fraction: ;--tw-ring-inset: ;--tw-ring-offset-width: 0px;--tw-ring-offset-color: #fff;--tw-ring-color: rgb(59 130 246 / .5);--tw-ring-offset-shadow: 0 0 #0000;--tw-ring-shadow: 0 0 #0000;--tw-shadow: 0 0 #0000;--tw-shadow-colored: 0 0 #0000;--tw-blur: ;--tw-brightness: ;--tw-contrast: ;--tw-grayscale: ;--tw-hue-rotate: ;--tw-invert: ;--tw-saturate: ;--tw-sepia: ;--tw-drop-shadow: ;--tw-backdrop-blur: ;--tw-backdrop-brightness: ;--tw-backdrop-contrast: ;--tw-backdrop-grayscale: ;--tw-backdrop-hue-rotate: ;--tw-backdrop-invert: ;--tw-backdrop-opacity: ;--tw-backdrop-saturate: ;--tw-backdrop-sepia: ;--tw-contain-size: ;--tw-contain-layout: ;--tw-contain-paint: ;--tw-contain-style: }*,:before,:after{box-sizing:border-box;border-width:0;border-style:solid;border-color:#e5e7eb}:before,:after{--tw-content: ""}html,:host{line-height:1.5;-webkit-text-size-adjust:100%;-moz-tab-size:4;-o-tab-size:4;tab-size:4;font-family:Figtree,ui-sans-serif,system-ui,sans-serif,"Apple Color Emoji","Segoe UI Emoji",Segoe UI Symbol,"Noto Color Emoji";font-feature-settings:normal;font-variation-settings:normal;-webkit-tap-highlight-color:transparent}body{margin:0;line-height:inherit}
        </style>
    @endif
</head>

<body class="bg-gray-100 font-sans antialiased">
<header class="max-w-5xl mx-auto px-4 pt-8">
    <h1 class="text-2xl font-semibold">Docházka</h1>
    <p class="text-gray-600">Vyber člena týmu a klikni do kalendáře pro zápis.</p>
</header>

<main class="max-w-5xl mx-auto px-4 py-6 space-y-4">
    <section aria-label="Ovládání">
        <label for="user_id" class="block text-sm font-medium text-gray-700">Zapsat za</label>

        {{-- ✅ name přidán --}}
        <select id="user_id" name="user_id"
                class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-3 py-2">
            <option value="">— vyber člena týmu —</option>
            @foreach ($members as $member)
                <option value="{{ $member->id }}">{{ $member->name }}</option>
            @endforeach
        </select>
    </section>

    <section aria-label="Kalendář">
        <div id="calendar" class="bg-white rounded-2xl shadow p-3"></div>
    </section>

    <!-- Modal / panel pro zápis -->
    <dialog id="attendanceDialog" class="rounded-2xl p-0 w-full max-w-lg">
        <form id="attendanceForm"
              method="post"
              action="{{ route('attendance.store') }}"
              class="p-6 space-y-4">
            @csrf

            {{-- ✅ protože select je mimo form --}}
            <input type="hidden" id="user_id_hidden" name="user_id">

            <h2 class="text-xl font-semibold">Zapsat aktivitu</h2>

            <input type="hidden" id="from_date" name="from_date">
            <input type="hidden" id="to_date" name="to_date">

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Od</label>
                    <input id="from_date_view" type="date"
                           class="mt-1 w-full rounded-xl border border-gray-300 bg-gray-50 px-3 py-2">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Do</label>
                    <input id="to_date_view" type="date"
                           class="mt-1 w-full rounded-xl border border-gray-300 bg-gray-50 px-3 py-2">
                </div>
            </div>

            <div>
                <label for="activity" class="block text-sm font-medium text-gray-700">Aktivita</label>
                <input id="activity" name="activity" type="text" required
                       placeholder="např. home office, školení, vývoj, meetingy…"
                       class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-3 py-2">
            </div>

            <div class="flex justify-end gap-2">
                <button type="button" id="cancelDialog"
                        class="px-4 py-2 rounded-xl border border-gray-300 bg-white">
                    Zrušit
                </button>
                <button type="submit"
                        class="px-4 py-2 rounded-xl bg-blue-600 text-white font-semibold">
                    Uložit
                </button>
            </div>
        </form>
    </dialog>
</main>
</body>
</html>
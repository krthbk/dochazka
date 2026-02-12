<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Docházka') }}</title>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-gray-100 min-h-screen">
    {{-- Navigace --}}
    <nav class="bg-white shadow-sm">
        <div class="max-w-[1600px] mx-auto px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <h1 class="text-xl font-bold text-gray-900">Docházka</h1>
                    @auth
                        <span class="text-sm text-gray-600">
                            Přihlášen jako: <strong>{{ auth()->user()->name }}</strong>
                            @if(auth()->user()->is_supervisor)
                                <span class="ml-2 px-2 py-1 bg-red-100 text-red-800 text-xs rounded-full">Nadřízená</span>
                            @endif
                        </span>
                    @endauth
                </div>
                
                @auth
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-sm text-gray-600 hover:text-gray-900">
                            Odhlásit se
                        </button>
                    </form>
                @endauth
            </div>
        </div>
    </nav>

    {{-- Obsah --}}
    <main class="py-6">
        {{ $slot }}
    </main>

    @livewireScripts
</body>
</html>

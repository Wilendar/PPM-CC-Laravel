<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel użytkownika - PPM</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style> body { background: #0b0f19; } </style>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body class="min-h-screen text-gray-200">
    <div class="max-w-4xl mx-auto px-6 py-16">
        <div class="bg-gray-900/70 backdrop-filter backdrop-blur rounded-xl border border-gray-700 p-8 shadow-xl">
            <h1 class="text-2xl font-semibold mb-2">Witaj w PPM</h1>

            @auth
                <div class="mb-6 space-y-1">
                    <p class="text-lg text-white font-medium">{{ auth()->user()->name }}</p>
                    <p class="text-gray-400">{{ auth()->user()->email }}</p>
                    @if(auth()->user()->roles->isNotEmpty())
                        <p class="text-gray-500 text-sm">
                            Rola: <span class="text-gray-300">{{ auth()->user()->roles->pluck('name')->implode(', ') }}</span>
                        </p>
                    @endif
                </div>

                @if(auth()->user()->roles->isNotEmpty())
                    <a href="{{ url('/admin') }}" class="inline-flex items-center px-4 py-2 rounded-md bg-blue-600 hover:bg-blue-700 text-white transition">
                        @if(auth()->user()->hasRole('Admin'))
                            Przejdz do Panelu Administratora
                        @else
                            Przejdz do Panelu
                        @endif
                    </a>
                @else
                    <p class="text-gray-400">Nie posiadasz przypisanej roli. Skontaktuj sie z administratorem systemu.</p>
                @endif
            @else
                <p class="text-gray-400 mb-4">Nie jestes zalogowany.</p>
                <a href="{{ route('login') }}" class="inline-flex items-center px-4 py-2 rounded-md bg-blue-600 hover:bg-blue-700 text-white transition">
                    Zaloguj sie
                </a>
            @endauth

            <div class="mt-8 flex items-center gap-4">
                <a href="{{ url('/') }}" class="text-sm text-gray-400 hover:text-gray-200">Powrot na strone glowna</a>
                @auth
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="text-sm text-red-400 hover:text-red-300">Wyloguj sie</button>
                    </form>
                @endauth
            </div>
        </div>
    </div>
</body>
</html>

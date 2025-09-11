<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel użytkownika</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style> body { background: #0b0f19; } </style>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- Prosty fallback widok dla użytkowników nie-admin -->
</head>
<body class="min-h-screen text-gray-200">
    <div class="max-w-4xl mx-auto px-6 py-16">
        <div class="bg-gray-900/70 backdrop-filter backdrop-blur rounded-xl border border-gray-700 p-8 shadow-xl">
            <h1 class="text-2xl font-semibold mb-2">Witaj w PPM</h1>
            <p class="text-gray-400 mb-6">Zalogowano pomyślnie.</p>

            @if(auth()->check() && auth()->user()->hasRole('Admin'))
                <a href="{{ url('/admin') }}" class="inline-flex items-center px-4 py-2 rounded-md bg-blue-600 hover:bg-blue-700 text-white">Przejdź do Panelu Administratora</a>
            @else
                <p class="text-gray-400">Nie posiadasz uprawnień administratora. Skontaktuj się z administratorem systemu, jeśli to błąd.</p>
            @endif

            <div class="mt-8">
                <a href="{{ url('/') }}" class="text-sm text-gray-400 hover:text-gray-200">Powrót na stronę główną</a>
            </div>
        </div>
    </div>
</body>
</html>


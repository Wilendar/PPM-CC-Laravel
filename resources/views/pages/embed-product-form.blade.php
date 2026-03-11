<!DOCTYPE html>
<html lang="pl" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Dodaj nowy produkt - PPM Management</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Livewire Styles -->
    @livewireStyles
</head>
<body class="bg-gray-50 dark:bg-gray-900">

    <!-- Simple Header -->
    <header class="bg-white dark:bg-gray-800 shadow">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center space-x-4">
                    <a href="{{ route('admin.dashboard') }}" class="text-xl font-bold text-gray-900 dark:text-white">
                        PPM Admin
                    </a>
                    <span class="text-gray-400">→</span>
                    <a href="{{ route('admin.products.index') }}" class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">
                        Produkty
                    </a>
                    <span class="text-gray-400">→</span>
                    <span class="text-gray-900 dark:text-white">Dodaj nowy</span>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main>
        <livewire:products.management.product-form />
    </main>

    <!-- Livewire Scripts -->
    @livewireScripts

</body>
</html>


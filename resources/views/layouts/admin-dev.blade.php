<!DOCTYPE html>
<html lang="pl" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Panel - PPM Management')</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- REMOVED: Alpine.js CDN - using only Livewire Alpine to avoid conflicts -->
    <!-- <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script> -->
    
    <!-- Livewire Styles -->
    @livewireStyles
    
    <style>
        [x-cloak] { display: none !important; }
        
        /* CRITICAL: Dropdown z-index fix */
        .dropdown-fix { 
            z-index: 99999 !important; 
            position: absolute !important;
        }
        
        /* Prevent backdrop filter isolation issues */
        .backdrop-blur-xl, .backdrop-blur-sm {
            isolation: auto !important;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 text-white">
    
    <!-- DEV MODE HEADER -->
    <div class="bg-orange-600 text-white text-center p-2 text-sm font-bold">
        🚧 DEVELOPMENT MODE - Authentication Disabled 🚧
    </div>
    
    {{-- Flash Messages --}}
    <x-flash-messages />

    <!-- Main Content -->
    <div class="min-h-screen">
        {{ $slot }}
    </div>
    
    <!-- Livewire Scripts -->
    @livewireScripts
    
    <!-- Additional Scripts -->
    @stack('scripts')
    
</body>
</html>
<!DOCTYPE html>
<html lang="pl" x-data="{ darkMode: $persist(false), sidebarOpen: false }" :class="{ 'dark': darkMode }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>{{ $title ?? 'PPM - Prestashop Product Manager' }}</title>
    
    {{-- Meta Description --}}
    <meta name="description" content="System zarządzania produktami PPM dla MPP TRADE - Panel użytkownika">
    
    {{-- Favicon --}}
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    
    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    {{-- Styles --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        
        /* Custom PPM Brand Colors */
        :root {
            --ppm-primary: #2563eb;
            --ppm-primary-dark: #1d4ed8;
            --ppm-secondary: #059669;
            --ppm-secondary-dark: #047857;
            --ppm-accent: #dc2626;
            --ppm-accent-dark: #b91c1c;
        }
        
        /* Sidebar animations */
        .sidebar-enter {
            transform: translateX(-100%);
        }
        
        .sidebar-enter-active {
            transform: translateX(0);
            transition: transform 0.3s ease-in-out;
        }
        
        .sidebar-exit {
            transform: translateX(0);
        }
        
        .sidebar-exit-active {
            transform: translateX(-100%);
            transition: transform 0.3s ease-in-out;
        }
        
        /* Loading states */
        .loading-fade {
            animation: fadeInOut 1.5s ease-in-out infinite;
        }
        
        @keyframes fadeInOut {
            0%, 100% { opacity: 0.4; }
            50% { opacity: 1; }
        }
        
        /* Role-based color schemes */
        .role-admin { --role-color: #dc2626; --role-bg: #fef2f2; }
        .role-manager { --role-color: #ea580c; --role-bg: #fff7ed; }
        .role-editor { --role-color: #059669; --role-bg: #f0fdfa; }
        .role-warehouseman { --role-color: #2563eb; --role-bg: #eff6ff; }
        .role-salesperson { --role-color: #7c3aed; --role-bg: #f5f3ff; }
        .role-claims { --role-color: #0891b2; --role-bg: #f0f9ff; }
        .role-user { --role-color: #6b7280; --role-bg: #f9fafb; }
        
        /* Dark mode enhancements */
        .dark .role-admin { --role-bg: rgba(220, 38, 38, 0.1); }
        .dark .role-manager { --role-bg: rgba(234, 88, 12, 0.1); }
        .dark .role-editor { --role-bg: rgba(5, 150, 105, 0.1); }
        .dark .role-warehouseman { --role-bg: rgba(37, 99, 235, 0.1); }
        .dark .role-salesperson { --role-bg: rgba(124, 58, 237, 0.1); }
        .dark .role-claims { --role-bg: rgba(8, 145, 178, 0.1); }
        .dark .role-user { --role-bg: rgba(107, 114, 128, 0.1); }
    </style>
</head>
<body class="bg-gray-100 dark:bg-gray-900 font-sans antialiased">
    <div class="min-h-screen flex">
        {{-- Sidebar --}}
        <aside class="fixed inset-y-0 left-0 z-50 w-64 bg-gray-800 shadow-lg transform transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-0"
               :class="{ 'translate-x-0': sidebarOpen, '-translate-x-full': !sidebarOpen }">
            
            {{-- Logo --}}
            <div class="flex items-center justify-center h-16 px-4 bg-blue-600 dark:bg-blue-700">
                <div class="flex items-center">
                    <h1 class="text-xl font-bold text-white">PPM</h1>
                    <span class="ml-2 text-xs text-blue-200">v1.0</span>
                </div>
            </div>
            
            {{-- User Info --}}
            @auth
            <div class="p-4 border-b border-gray-700">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        @if(Auth::user()->avatar)
                            <img src="{{ Storage::url(Auth::user()->avatar) }}" 
                                 alt="{{ Auth::user()->first_name }}" 
                                 class="h-10 w-10 rounded-full object-cover">
                        @else
                            <div class="h-10 w-10 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center">
                                <span class="text-sm font-medium text-gray-300">
                                    {{ substr(Auth::user()->first_name, 0, 1) }}{{ substr(Auth::user()->last_name, 0, 1) }}
                                </span>
                            </div>
                        @endif
                    </div>
                    <div class="ml-3 min-w-0 flex-1">
                        <p class="text-sm font-medium text-white truncate">
                            {{ Auth::user()->first_name }} {{ Auth::user()->last_name }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                            {{ Auth::user()->getRoleNames()->first() ?? 'User' }}
                        </p>
                    </div>
                </div>
            </div>
            @endauth
            
            {{-- Navigation --}}
            <nav class="mt-4 px-2">
                @include('layouts.navigation')
            </nav>
        </aside>
        
        {{-- Mobile sidebar overlay --}}
        <div class="fixed inset-0 z-40 lg:hidden" 
             x-show="sidebarOpen" 
             x-transition:enter="transition-opacity ease-linear duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
            <div class="absolute inset-0 bg-gray-600 opacity-75" 
                 @click="sidebarOpen = false"></div>
        </div>
        
        {{-- Main Content --}}
        <div class="flex-1 flex flex-col lg:pl-64">
            {{-- Top Header --}}
            <header class="bg-gray-800 shadow-sm">
                <div class="px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between items-center py-4">
                        {{-- Mobile menu button --}}
                        <button @click="sidebarOpen = !sidebarOpen" 
                                class="lg:hidden -ml-0.5 -mt-0.5 h-12 w-12 inline-flex items-center justify-center rounded-md text-gray-500 hover:text-white focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500">
                            <span class="sr-only">Open sidebar</span>
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                            </svg>
                        </button>
                        
                        {{-- Page title --}}
                        <div class="flex-1 min-w-0 lg:ml-0 ml-4">
                            <h2 class="text-2xl font-bold leading-7 text-white sm:text-3xl sm:truncate">
                                @yield('page-title', 'Dashboard')
                            </h2>
                        </div>
                        
                        {{-- Header actions --}}
                        <div class="flex items-center space-x-4">
                            {{-- Theme toggle --}}
                            <button @click="darkMode = !darkMode" 
                                    class="p-2 rounded-md text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <svg x-show="!darkMode" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                                </svg>
                                <svg x-show="darkMode" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                </svg>
                            </button>
                            
                            {{-- Notifications --}}
                            <button class="p-2 rounded-md text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 relative">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM4 19h10v-1a3 3 0 00-3-3H7a3 3 0 00-3 3v1zM12 3a4 4 0 00-4 4v4l-2 2h12l-2-2V7a4 4 0 00-4-4z"></path>
                                </svg>
                                {{-- Notification dot --}}
                                <span class="absolute top-0 right-0 block h-2 w-2 rounded-full bg-red-400 ring-2 ring-white"></span>
                            </button>
                            
                            {{-- User menu dropdown --}}
                            @include('layouts.user-menu')
                        </div>
                    </div>
                </div>
            </header>
            
            {{-- Main content area --}}
            <main class="flex-1">
                {{-- Page content --}}
                @yield('content')
                {{ $slot ?? '' }}
            </main>
        </div>
    </div>

    {{-- Flash Messages - MUST be direct child of body for correct z-index stacking --}}
    @include('components.flash-messages')
    
    {{-- Scripts --}}
    @livewireScripts
    
    {{-- Alpine.js is loaded via Vite bundle in resources/js/app.js --}}
    {{-- Removed CDN version to prevent multiple instances conflict --}}
    
    {{-- Custom Scripts --}}
    <script>
        // Session management
        let sessionWarningShown = false;
        let sessionTimeout = {{ config('session.lifetime') * 60 * 1000 }};
        
        function showSessionWarning() {
            if (!sessionWarningShown) {
                sessionWarningShown = true;
                
                if (confirm('Twoja sesja wygaśnie za 5 minut. Czy chcesz kontynuować?')) {
                    fetch('/ping', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Content-Type': 'application/json',
                        }
                    }).then(() => {
                        sessionWarningShown = false;
                        resetSessionTimer();
                    }).catch(() => {
                        window.location.reload();
                    });
                } else {
                    window.location.href = '/logout';
                }
            }
        }
        
        function resetSessionTimer() {
            setTimeout(showSessionWarning, sessionTimeout - 300000); // 5 minutes before expiry
        }
        
        // Start session timer
        resetSessionTimer();
        
        // Activity detection
        let activityEvents = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart'];
        let activityTimer;
        
        function resetActivityTimer() {
            clearTimeout(activityTimer);
            activityTimer = setTimeout(() => {
                if (confirm('Długi okres nieaktywności. Czy chcesz kontynuować sesję?')) {
                    resetActivityTimer();
                } else {
                    window.location.href = '/logout';
                }
            }, 1800000); // 30 minutes
        }
        
        activityEvents.forEach(event => {
            document.addEventListener(event, resetActivityTimer, true);
        });
        
        resetActivityTimer();
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl/Cmd + K - Quick search
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                // Focus search input if exists
                const searchInput = document.querySelector('input[type="search"]');
                if (searchInput) {
                    searchInput.focus();
                }
            }
            
            // Esc - Close modals/dropdowns
            if (e.key === 'Escape') {
                // Close sidebar on mobile
                Alpine.store('app').sidebarOpen = false;
            }
        });
        
        // Performance monitoring
        window.addEventListener('load', function() {
            // Log page load time
            const loadTime = performance.timing.loadEventEnd - performance.timing.navigationStart;
            if (loadTime > 3000) {
                console.warn('Page load time exceeded 3 seconds:', loadTime + 'ms');
            }
        });
    </script>
    
    @stack('scripts')
</body>
</html>
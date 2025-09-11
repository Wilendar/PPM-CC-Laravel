<!DOCTYPE html>
<html lang="pl" x-data="{ darkMode: $persist(false) }" :class="{ 'dark': darkMode }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>@yield('title', $title ?? 'PPM - Prestashop Product Manager')</title>
    
    {{-- Meta Description --}}
    <meta name="description" content="System zarządzania produktami PPM - Logowanie do aplikacji enterprise dla MPP TRADE">
    
    {{-- Favicon --}}
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    
    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    {{-- Tailwind CSS --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        'sans': ['Inter', 'sans-serif'],
                    },
                    colors: {
                        'ppm': {
                            'primary': '#e0ac7e',
                            'primary-dark': '#d1975a',
                        }
                    }
                }
            }
        }
    </script>
    
    {{-- Alpine.js --}}
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    @livewireStyles
    
    {{-- Additional head content --}}
    @stack('head')
    
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
        
        /* Loading animations */
        .loading-fade {
            animation: fadeInOut 1.5s ease-in-out infinite;
        }
        
        @keyframes fadeInOut {
            0%, 100% { opacity: 0.4; }
            50% { opacity: 1; }
        }
        
        /* Auth form enhancements */
        .auth-input:focus {
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        /* Accessibility improvements */
        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }
        
        /* Dark mode auth styling */
        .dark .auth-bg {
            background: linear-gradient(135deg, #1f2937 0%, #111827 100%);
        }
        
        .dark .auth-card {
            background: rgba(31, 41, 55, 0.8);
            border: 1px solid rgba(75, 85, 99, 0.2);
        }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-900 min-h-screen auth-bg">
    {{-- Theme Toggle --}}
    <div class="absolute top-4 right-4 z-20">
        <button 
            @click="darkMode = !darkMode" 
            class="p-2 rounded-lg bg-white dark:bg-gray-800 shadow-md hover:shadow-lg transition-shadow duration-200"
            :class="darkMode ? 'text-yellow-400' : 'text-gray-600'"
            title="Przełącz motyw"
        >
            <svg x-show="!darkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
            </svg>
            <svg x-show="darkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
            </svg>
        </button>
    </div>

    {{-- Main Content --}}
    <main class="relative">
        @yield('content')
        {{ $slot ?? '' }}
    </main>
    
    {{-- Footer --}}
    <footer class="absolute bottom-0 left-0 right-0 p-6">
        <div class="text-center">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                © {{ date('Y') }} MPP TRADE. Wszelkie prawa zastrzeżone.
            </p>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                PPM v1.0 - Prestashop Product Manager Enterprise
            </p>
        </div>
    </footer>
    
    {{-- Flash Messages --}}
    @if(session('success'))
        <div x-data="{ show: true }" 
             x-show="show" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-90"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-300"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-90"
             x-init="setTimeout(() => show = false, 5000)"
             class="fixed top-4 left-4 right-4 sm:left-auto sm:right-4 sm:w-96 z-50">
            <div class="bg-green-50 border-l-4 border-green-400 p-4 rounded-md shadow-lg">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3 flex-1">
                        <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                    </div>
                    <div class="ml-auto pl-3">
                        <button @click="show = false" class="text-green-400 hover:text-green-600">
                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div x-data="{ show: true }" 
             x-show="show" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-90"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-300"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-90"
             x-init="setTimeout(() => show = false, 8000)"
             class="fixed top-4 left-4 right-4 sm:left-auto sm:right-4 sm:w-96 z-50">
            <div class="bg-red-50 border-l-4 border-red-400 p-4 rounded-md shadow-lg">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3 flex-1">
                        <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
                    </div>
                    <div class="ml-auto pl-3">
                        <button @click="show = false" class="text-red-400 hover:text-red-600">
                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
    
    {{-- Scripts --}}
    @livewireScripts
    
    {{-- Session management --}}
    <script>
        // Session timeout warning
        let sessionWarningShown = false;
        let sessionTimeout = {{ config('session.lifetime') * 60 * 1000 }}; // Convert to milliseconds
        
        function showSessionWarning() {
            if (!sessionWarningShown) {
                sessionWarningShown = true;
                
                if (confirm('Twoja sesja wygaśnie za 5 minut. Czy chcesz kontynuować?')) {
                    // Ping server to extend session
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
                // User inactive for 30 minutes
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
        
        // Initialize activity timer
        resetActivityTimer();
    </script>
    
    {{-- PWA Support --}}
    <script>
        // Service Worker registration for offline support
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('/sw.js')
                    .then(function(registration) {
                        console.log('SW registered: ', registration);
                    })
                    .catch(function(registrationError) {
                        console.log('SW registration failed: ', registrationError);
                    });
            });
        }
    </script>
</body>
</html>
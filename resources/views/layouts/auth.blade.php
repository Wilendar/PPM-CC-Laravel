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

    {{-- Vite Assets (includes Tailwind CSS compiled + Alpine.js via Livewire) --}}
    @vite([
        'resources/css/app.css',
        'resources/js/app.js'
    ])

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
            class="p-2 rounded-lg bg-gray-800 shadow-md hover:shadow-lg transition-shadow duration-200"
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
    
    {{-- Flash Messages - unified component --}}
    <x-flash-messages />
    
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
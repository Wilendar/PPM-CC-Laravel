<!DOCTYPE html>
<html lang="pl" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Test UI - PPM')</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Font Awesome CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">

    <!-- Alpine.js -->
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

    <style>
        [x-cloak] { display: none !important; }

        /* ULTRA-AGGRESSIVE DROPDOWN Z-INDEX FIXES */
        .dropdown-fix {
            z-index: 999999 !important;
            position: fixed !important; /* Changed from absolute to fixed */
        }

        /* Force any dropdown to be on top */
        .dropdown-menu,
        [x-show="open"],
        .dropdown-content {
            z-index: 999999 !important;
            position: fixed !important;
            isolation: isolate !important;
        }

        /* Prevent stacking context isolation */
        .backdrop-blur-xl, .backdrop-blur-sm, .backdrop-blur {
            isolation: auto !important;
        }

        /* Reset any transform that creates stacking context */
        .transform {
            isolation: auto !important;
        }

        /* Override any competing z-index */
        * {
            z-index: auto !important;
        }

        /* Except our dropdowns */
        .dropdown-fix,
        .dropdown-menu,
        [x-show="open"] {
            z-index: 999999 !important;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-900 via-gray-800 to-black text-white min-h-screen">

    <!-- Animated Background Elements -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none z-0">
        <div class="absolute -top-32 -right-32 w-64 h-64 rounded-full blur-3xl animate-pulse" style="background: radial-gradient(circle, rgba(224, 172, 126, 0.1), rgba(209, 151, 90, 0.05));"></div>
        <div class="absolute -bottom-32 -left-32 w-64 h-64 rounded-full blur-3xl animate-pulse" style="background: radial-gradient(circle, rgba(209, 151, 90, 0.1), rgba(224, 172, 126, 0.05)); animation-delay: 2s;"></div>
    </div>

    <!-- Test Header -->
    <div class="relative z-10">
        <div class="backdrop-blur-xl shadow-2xl" style="background: linear-gradient(135deg, rgba(31, 41, 55, 0.95), rgba(17, 24, 39, 0.95)); border-bottom: 1px solid rgba(224, 172, 126, 0.3);">
            <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <div class="flex items-center">
                        <div class="relative w-10 h-10 rounded-xl flex items-center justify-center shadow-lg" style="background: linear-gradient(45deg, #e0ac7e, #d1975a);">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h1 class="text-lg font-bold tracking-tight" style="color: #e0ac7e;">@yield('breadcrumb', 'TEST UI')</h1>
                        </div>
                    </div>

                    <div class="text-sm text-gray-400">
                        PPM Enterprise - Test Mode
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <main class="relative z-10">
            @yield('content')
        </main>
    </div>

    @stack('scripts')
</body>
</html>
<!DOCTYPE html>
<html lang="pl" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PPM - Prestashop Product Manager</title>
    
    <meta name="description" content="PPM - System zarządzania produktami klasy enterprise dla MPP TRADE. Multi-store management, integracje ERP, zaawansowana analityka.">
    <meta name="theme-color" content="#2563eb">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS -->
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
    
    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <style>
        body { font-family: 'Inter', sans-serif; }
        
        .bg-grid-pattern {
            background-image: 
                linear-gradient(rgba(255, 255, 255, 0.05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.05) 1px, transparent 1px);
            background-size: 50px 50px;
        }
        
        .shadow-3xl {
            box-shadow: 0 35px 60px -12px rgba(0, 0, 0, 0.25);
        }
        
        .backdrop-blur-md {
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }
        
        @media (max-width: 640px) {
            .bg-grid-pattern { background-size: 30px 30px; }
        }
    </style>
</head>
<body class="bg-gray-900 text-white overflow-x-hidden">
    <!-- Theme Toggle -->
    <div class="absolute top-4 right-4 z-20">
        <button 
            onclick="toggleDarkMode()" 
            id="theme-toggle"
            class="p-2 rounded-lg bg-white/10 backdrop-blur-md shadow-md hover:shadow-lg transition-shadow duration-200 text-white hover:text-yellow-400"
            title="Przełącz motyw"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
            </svg>
        </button>
    </div>

    <div class="relative min-h-screen overflow-hidden">
        <!-- Background Gradient -->
        <div class="absolute inset-0 bg-gradient-to-br from-gray-900 via-gray-800 to-black"></div>
        
        <!-- Geometric Background Elements -->
        <div class="absolute inset-0">
            <div class="absolute top-10 left-10 w-72 h-72 bg-white/5 rounded-full blur-3xl"></div>
            <div class="absolute bottom-10 right-10 w-96 h-96 bg-blue-300/10 rounded-full blur-3xl"></div>
            <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-64 h-64 bg-indigo-300/5 rounded-full blur-2xl"></div>
        </div>
        
        <!-- Grid Pattern -->
        <div class="absolute inset-0 bg-grid-pattern opacity-10"></div>
        
        <!-- Main Content -->
        <div class="relative z-10 flex flex-col justify-center min-h-screen px-4 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-4xl text-center">
                
                <!-- Logo/Brand Section -->
                <div class="mb-8" x-data="{ loaded: false }" x-init="setTimeout(() => loaded = true, 200)">
                    <div 
                        x-show="loaded" 
                        x-transition:enter="transition ease-out duration-1000"
                        x-transition:enter-start="opacity-0 scale-90 -translate-y-4"
                        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                        class="mb-6"
                    >
                        <img src="https://mm.mpptrade.pl/images/logo/mpptrade/dark-bg/color/mpp-trade-logo-color.png" 
                             alt="MPP TRADE Logo" 
                             style="width: 100%; max-width: 900px; height: 200px; object-fit: cover; margin: 0 auto 30px;"
                             onerror="this.style.display='none'"
                        >
                    </div>
                    
                    
                    <div 
                        x-show="loaded"
                        x-transition:enter="transition ease-out duration-1000 delay-300"
                        x-transition:enter-start="opacity-0 translate-y-4"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        class="text-lg sm:text-xl font-semibold mb-8"
                        style="color: #e0ac7e;"
                    >
                        /// TWORZYMY PASJE /// DOSTARCZAMY EMOCJE ///
                    </div>
                    <h2 class="text-lg sm:text-xl text-gray-300 mb-6">
                        Prestashop Product Manager
                    </h2>
                </div>
                
                <!-- Hero Description -->
                <div 
                    x-data="{ loaded: false }" 
                    x-init="setTimeout(() => loaded = true, 800)"
                    x-show="loaded"
                    x-transition:enter="transition ease-out duration-1000"
                    x-transition:enter-start="opacity-0 translate-y-6"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    class="mb-12 space-y-6"
                >
                    <p class="text-xl sm:text-2xl text-gray-300 max-w-3xl mx-auto leading-relaxed">
                        System zarządzania produktami klasy enterprise dla 
                        <span class="font-semibold text-white">MPP TRADE</span>
                    </p>
                    
                    <div class="text-lg text-gray-400 max-w-2xl mx-auto space-y-2">
                        <p>Centralny hub do zarządzania produktami na wielu sklepach Prestashop jednocześnie</p>
                        <p class="text-base text-gray-500">Import • Export • Synchronizacja • Analityka</p>
                    </div>
                </div>
                
                <!-- Features Grid -->
                <div 
                    x-data="{ loaded: false }" 
                    x-init="setTimeout(() => loaded = true, 1200)"
                    x-show="loaded"
                    x-transition:enter="transition ease-out duration-1000"
                    x-transition:enter-start="opacity-0 translate-y-8"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    class="mb-12"
                >
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-4xl mx-auto">
                        <!-- Multi-Store Management -->
                        <div class="bg-gray-800/50 backdrop-blur-md rounded-xl p-6 border border-gray-700/30 hover:bg-gray-700/50 transition-all duration-300">
                            <div class="w-12 h-12 rounded-lg flex items-center justify-center mb-4 mx-auto" style="background-color: rgba(224, 172, 126, 0.2);">
                                <svg class="w-6 h-6" fill="none" stroke="#e0ac7e" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                          d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-white mb-2">Multi-Store</h3>
                            <p class="text-gray-400 text-sm">Zarządzanie wieloma sklepami z jednego miejsca</p>
                        </div>
                        
                        <!-- Enterprise Integration -->
                        <div class="bg-gray-800/50 backdrop-blur-md rounded-xl p-6 border border-gray-700/30 hover:bg-gray-700/50 transition-all duration-300">
                            <div class="w-12 h-12 rounded-lg flex items-center justify-center mb-4 mx-auto" style="background-color: rgba(224, 172, 126, 0.2);">
                                <svg class="w-6 h-6" fill="none" stroke="#e0ac7e" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                          d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-white mb-2">Integracje ERP</h3>
                            <p class="text-gray-400 text-sm">Baselinker, Subiekt GT, Microsoft Dynamics</p>
                        </div>
                        
                        <!-- Advanced Analytics -->
                        <div class="bg-gray-800/50 backdrop-blur-md rounded-xl p-6 border border-gray-700/30 hover:bg-gray-700/50 transition-all duration-300">
                            <div class="w-12 h-12 rounded-lg flex items-center justify-center mb-4 mx-auto" style="background-color: rgba(224, 172, 126, 0.2);">
                                <svg class="w-6 h-6" fill="none" stroke="#e0ac7e" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                          d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-white mb-2">Analityka</h3>
                            <p class="text-gray-400 text-sm">Zaawansowane raporty i business intelligence</p>
                        </div>
                    </div>
                </div>
                
                <!-- CTA Section -->
                <div 
                    x-data="{ loaded: false }" 
                    x-init="setTimeout(() => loaded = true, 1600)"
                    x-show="loaded"
                    x-transition:enter="transition ease-out duration-1000"
                    x-transition:enter-start="opacity-0 translate-y-8"
                    x-transition:enter-end="opacity-100 translate-y-0"
                >
                    <a 
                        href="/login" 
                        class="inline-flex items-center px-8 py-4 font-semibold rounded-full 
                               hover:scale-105 focus:outline-none focus:ring-4 focus:ring-orange-500/50 
                               transition-all duration-300 shadow-2xl hover:shadow-3xl"
                        style="background: linear-gradient(45deg, #e0ac7e, #d1975a); color: white;"
                    >
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                        </svg>
                        Zaloguj się do systemu
                    </a>
                    
                    <p class="mt-6 text-gray-400 text-sm">
                        Bezpieczne logowanie przez Google Workspace lub Microsoft Entra ID
                    </p>
                </div>
            </div>
            
        </div>
    </div>
    
    <!-- Footer -->
    <footer class="absolute bottom-0 left-0 right-0 p-6 z-10">
        <div class="text-center">
            <p class="text-sm text-gray-500">
                © 2025 MPP TRADE. Wszelkie prawa zastrzeżone.
            </p>
            <p class="text-xs text-gray-600 mt-1">
                PPM v1.0 - Prestashop Product Manager Enterprise
            </p>
        </div>
    </footer>

    <script>
        
        // Dark mode toggle (placeholder - currently always dark)
        function toggleDarkMode() {
            // Placeholder for future light mode implementation
            console.log('Dark mode toggle clicked');
        }
        
        // Smooth scrolling for any anchor links
        document.addEventListener('DOMContentLoaded', function() {
            const links = document.querySelectorAll('a[href^="#"]');
            links.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({ behavior: 'smooth' });
                    }
                });
            });
        });
        
        // Preload login page
        const loginLink = document.querySelector('a[href*="login"]');
        if (loginLink) {
            const preloadLink = document.createElement('link');
            preloadLink.rel = 'prefetch';
            preloadLink.href = loginLink.href;
            document.head.appendChild(preloadLink);
        }
    </script>
</body>
</html>
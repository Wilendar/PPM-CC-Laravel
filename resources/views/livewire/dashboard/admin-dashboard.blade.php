<div x-data="adminDashboard({{ $refreshInterval }})"
     x-init="init()">

    {{-- Dashboard Header --}}
    <div class="mb-6">
        <h1 class="text-h1 font-bold" style="color: #e0ac7e;">Dashboard</h1>
        <p class="text-gray-400 mt-2">Witaj w panelu administracyjnym PPM - {{ $userRole }}</p>
    </div>

    {{-- Role-Based Content --}}

    @if($userRole === 'Admin')
        {{-- ============================================
            ADMIN DASHBOARD - Full Access
            ============================================ --}}

        {{-- System Health Status Bar --}}
        <div class="mb-8 relative">
            <div class="backdrop-blur-xl rounded-2xl p-8 shadow-2xl relative overflow-hidden" style="background: linear-gradient(135deg, rgba(31, 41, 55, 0.8), rgba(17, 24, 39, 0.6), rgba(31, 41, 55, 0.8)); border: 1px solid rgba(224, 172, 126, 0.2);">
                <div class="absolute inset-0 opacity-5">
                    <div class="absolute inset-0" style="background-image: radial-gradient(circle at 1px 1px, rgba(224, 172, 126, 0.15) 1px, transparent 0); background-size: 20px 20px;"></div>
                </div>

                <div class="relative z-10 mb-6">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-black text-white flex items-center">
                            <div class="w-10 h-10 bg-gradient-to-br from-green-400 to-green-600 rounded-lg flex items-center justify-center mr-3 shadow-lg">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <span class="text-white font-bold">STATUS SYSTEMU</span>
                        </h2>
                        <div class="flex items-center space-x-2 px-3 py-1 rounded-full border" style="background: rgba(5, 150, 105, 0.2); border-color: rgba(5, 150, 105, 0.3);">
                            <div class="w-2 h-2 rounded-full animate-pulse" style="background-color: #059669;"></div>
                            <span class="text-xs font-bold" style="color: #22c55e;">SYSTEM DZIAŁA</span>
                        </div>
                    </div>
                </div>

                <div class="relative z-10 grid grid-cols-2 md:grid-cols-4 gap-6">
                    @foreach($systemHealth as $service => $health)
                    <div class="group relative">
                        <div class="relative backdrop-blur-sm rounded-xl p-4 border transition-all duration-300 hover:scale-105"
                             style="background: linear-gradient(135deg, rgba(55, 65, 81, 0.5), rgba(31, 41, 55, 0.5)); border-color: rgba(75, 85, 99, 0.5);">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <p class="text-sm font-bold text-white mb-1">{{ ucfirst(str_replace('_', ' ', $service)) }}</p>
                                    <p class="text-xs text-gray-400">{{ $health['message'] ?? 'N/A' }}</p>
                                </div>
                                <div class="flex-shrink-0 ml-3">
                                    @if(($health['status'] ?? 'unknown') === 'healthy')
                                        <div class="w-4 h-4 bg-green-500 rounded-full animate-pulse"></div>
                                    @elseif(($health['status'] ?? 'unknown') === 'warning')
                                        <div class="w-4 h-4 bg-yellow-500 rounded-full animate-pulse"></div>
                                    @elseif(($health['status'] ?? 'unknown') === 'error')
                                        <div class="w-4 h-4 bg-red-500 rounded-full animate-pulse"></div>
                                    @else
                                        <div class="w-4 h-4 bg-gray-500 rounded-full"></div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Core Metrics Grid - Colorful Gradient Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 mb-8">

            {{-- Total Products --}}
            <div class="group relative">
                <div class="bg-gradient-to-br from-blue-600/30 via-blue-700/20 to-blue-900/30 backdrop-blur-xl rounded-2xl border border-blue-500/40 p-8 shadow-2xl transform transition-all duration-500 hover:scale-105 hover:shadow-blue-500/20 relative overflow-hidden">
                    {{-- Background glow --}}
                    <div class="absolute inset-0 bg-gradient-to-br from-blue-400/10 to-transparent rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>

                    <div class="relative z-10 flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-16 h-16 bg-gradient-to-br from-blue-400 via-blue-500 to-blue-600 rounded-2xl flex items-center justify-center shadow-lg transform transition-transform duration-300 group-hover:scale-110">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                </svg>
                                {{-- Icon glow --}}
                                <div class="absolute inset-0 rounded-2xl bg-blue-400 opacity-50 blur-lg animate-pulse"></div>
                            </div>
                        </div>
                        <div class="ml-6 flex-1">
                            <p class="text-4xl font-black mb-1" style="color: #60a5fa !important;">
                                {{ number_format($dashboardStats['total_products'] ?? 0) }}
                            </p>
                            <p class="text-sm font-bold tracking-wide uppercase text-white">
                                Produkty w systemie
                            </p>
                        </div>
                    </div>

                    {{-- Metric progress bar --}}
                    <div class="mt-6 relative z-10">
                        <div class="w-full bg-blue-900/30 rounded-full h-2 overflow-hidden">
                            <div class="bg-gradient-to-r from-red-400 to-red-600 h-2 rounded-full transition-all duration-1000" style="width: {{ $dashboardStats['products_with_problems_percent'] ?? 0 }}%;"></div>
                        </div>
                        <p class="text-xs text-red-300 mt-2 font-medium">{{ $dashboardStats['products_with_problems_percent'] ?? 0 }}% produktów ma problemy</p>
                    </div>
                </div>
            </div>

            {{-- Active Users --}}
            <div class="group relative">
                <div class="bg-gradient-to-br from-green-600/30 via-green-700/20 to-green-900/30 backdrop-blur-xl rounded-2xl border border-green-500/40 p-8 shadow-2xl transform transition-all duration-500 hover:scale-105 hover:shadow-green-500/20 relative overflow-hidden">
                    {{-- Background glow --}}
                    <div class="absolute inset-0 bg-gradient-to-br from-green-400/10 to-transparent rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>

                    <div class="relative z-10 flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-16 h-16 bg-gradient-to-br from-green-400 via-green-500 to-green-600 rounded-2xl flex items-center justify-center shadow-lg transform transition-transform duration-300 group-hover:scale-110">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                </svg>
                                {{-- Icon glow --}}
                                <div class="absolute inset-0 rounded-2xl bg-green-400 opacity-50 blur-lg animate-pulse"></div>
                            </div>
                        </div>
                        <div class="ml-6 flex-1">
                            <p class="text-4xl font-black mb-1" style="color: #34d399 !important;">
                                {{ number_format($dashboardStats['active_users'] ?? 0) }}
                            </p>
                            <p class="text-sm font-bold tracking-wide uppercase text-white">
                                Aktywni użytkownicy
                            </p>
                        </div>
                    </div>

                    {{-- Metric progress bar --}}
                    <div class="mt-6 relative z-10">
                        <div class="w-full bg-green-900/30 rounded-full h-2 overflow-hidden">
                            <div class="bg-gradient-to-r from-green-400 to-green-600 h-2 rounded-full transition-all duration-1000" style="width: {{ $dashboardStats['logged_users_percent'] ?? 0 }}%;"></div>
                        </div>
                        <p class="text-xs text-green-300 mt-2 font-medium">{{ $dashboardStats['logged_users_percent'] ?? 0 }}% zalogowanych</p>
                    </div>
                </div>
            </div>

            {{-- Categories --}}
            <div class="group relative">
                <div class="bg-gradient-to-br from-purple-600/30 via-purple-700/20 to-purple-900/30 backdrop-blur-xl rounded-2xl border border-purple-500/40 p-8 shadow-2xl transform transition-all duration-500 hover:scale-105 hover:shadow-purple-500/20 relative overflow-hidden">
                    {{-- Background glow --}}
                    <div class="absolute inset-0 bg-gradient-to-br from-purple-400/10 to-transparent rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>

                    <div class="relative z-10 flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-16 h-16 bg-gradient-to-br from-purple-400 via-purple-500 to-purple-600 rounded-2xl flex items-center justify-center shadow-lg transform transition-transform duration-300 group-hover:scale-110">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                </svg>
                                {{-- Icon glow --}}
                                <div class="absolute inset-0 rounded-2xl bg-purple-400 opacity-50 blur-lg animate-pulse"></div>
                            </div>
                        </div>
                        <div class="ml-6 flex-1">
                            <p class="text-4xl font-black mb-1" style="color: #a78bfa !important;">
                                {{ number_format($dashboardStats['total_categories'] ?? 0) }}
                            </p>
                            <p class="text-sm font-bold tracking-wide uppercase text-white">
                                Kategorie produktów
                            </p>
                        </div>
                    </div>

                    {{-- Metric progress bar --}}
                    <div class="mt-6 relative z-10">
                        <div class="w-full bg-purple-900/30 rounded-full h-2 overflow-hidden">
                            <div class="bg-gradient-to-r from-purple-400 to-purple-600 h-2 rounded-full transition-all duration-1000" style="width: {{ $dashboardStats['categories_with_products_percent'] ?? 0 }}%;"></div>
                        </div>
                        <p class="text-xs text-purple-300 mt-2 font-medium">{{ $dashboardStats['categories_with_products_percent'] ?? 0 }}% kategorii z produktami</p>
                    </div>
                </div>
            </div>

            {{-- Recent Activity - Enhanced with MPP TRADE Colors --}}
            <div class="group relative">
                <div class="backdrop-blur-xl rounded-2xl p-8 shadow-2xl transform transition-all duration-500 hover:scale-105 relative overflow-hidden"
                     style="background: linear-gradient(135deg, rgba(224, 172, 126, 0.4), rgba(209, 151, 90, 0.3), rgba(192, 132, 73, 0.4)); border: 1px solid rgba(224, 172, 126, 0.5);"
                     onmouseover="this.style.boxShadow='0 25px 50px -12px rgba(224, 172, 126, 0.3)'"
                     onmouseout="this.style.boxShadow='0 25px 50px -12px rgba(0, 0, 0, 0.25)'">
                    {{-- Background glow with MPP TRADE branding --}}
                    <div class="absolute inset-0 rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-500" style="background: linear-gradient(135deg, rgba(224, 172, 126, 0.15), transparent);"></div>

                    <div class="relative z-10 flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-16 h-16 rounded-2xl flex items-center justify-center shadow-xl transform transition-transform duration-300 group-hover:scale-110" style="background: linear-gradient(45deg, #e0ac7e, #d1975a);">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                                {{-- Enhanced icon glow for MPP --}}
                                <div class="absolute inset-0 rounded-2xl opacity-60 blur-lg animate-pulse" style="background: #e0ac7e;"></div>
                            </div>
                        </div>
                        <div class="ml-6 flex-1">
                            <p class="text-4xl font-black mb-1" style="color: #fb923c !important;">
                                {{ number_format($dashboardStats['recent_activity'] ?? 0) }}
                            </p>
                            <p class="text-sm font-bold tracking-wide uppercase text-white">
                                Aktywność (24h)
                            </p>
                        </div>
                    </div>

                    {{-- Enhanced metric progress bar for MPP --}}
                    <div class="mt-6 relative z-10">
                        <div class="w-full rounded-full h-2 overflow-hidden" style="background: rgba(192, 132, 73, 0.4);">
                            <div class="h-2 rounded-full transition-all duration-1000 animate-pulse" style="background: linear-gradient(90deg, #e0ac7e, #d1975a, #e0ac7e); width: {{ $dashboardStats['activity_score_percent'] ?? 0 }}%;"></div>
                        </div>
                        <p class="text-xs mt-2 font-bold" style="color: #e0ac7e;">🔥 {{ $dashboardStats['activity_score_percent'] ?? 0 }}% aktywność dzisiaj</p>
                    </div>

                    {{-- MPP TRADE Badge --}}
                    <div class="absolute top-3 right-3 px-2 py-1 rounded-full border opacity-0 group-hover:opacity-100 transition-opacity duration-300" style="background: rgba(224, 172, 126, 0.2); border-color: rgba(224, 172, 126, 0.3);">
                        <span class="text-xs font-black" style="color: #e0ac7e;">MPP</span>
                    </div>
                </div>
            </div>

        </div>

        {{-- Admin Quick Actions --}}
        <div class="mb-8">
            <h2 class="text-h2 font-bold mb-4" style="color: #e0ac7e;">Quick Actions</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <a href="/admin/shops/add" class="btn-enterprise-primary flex items-center justify-center space-x-2 py-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    <span>Dodaj Sklep</span>
                </a>
                <a href="/admin/products/create" class="btn-enterprise-primary flex items-center justify-center space-x-2 py-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    <span>Dodaj Produkt</span>
                </a>
                <a href="/admin/system-settings" class="btn-enterprise-secondary flex items-center justify-center space-x-2 py-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                    <span>Ustawienia</span>
                </a>
            </div>
        </div>

        {{-- Business KPIs with MPP TRADE Colors --}}
        <div class="mb-12 relative">
            <div class="backdrop-blur-xl rounded-2xl p-8 shadow-2xl relative overflow-hidden" style="background: linear-gradient(135deg, rgba(31, 41, 55, 0.8), rgba(17, 24, 39, 0.6), rgba(31, 41, 55, 0.8)); border: 1px solid rgba(224, 172, 126, 0.2);">
                {{-- Background pattern with MPP colors --}}
                <div class="absolute inset-0 opacity-5">
                    <div class="absolute inset-0" style="background-image: radial-gradient(circle at 2px 2px, rgba(224, 172, 126, 0.15) 1px, transparent 0); background-size: 30px 30px;"></div>
                </div>

                {{-- Enhanced Header with MPP Colors --}}
                <div class="relative z-10 mb-8">
                    <div class="flex items-center justify-between">
                        <h2 class="text-2xl font-black text-white flex items-center">
                            <div class="w-12 h-12 bg-gradient-to-br from-orange-400 to-orange-600 rounded-lg flex items-center justify-center mr-4 shadow-xl">
                                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                            </div>
                            <span style="color: #e0ac7e !important; font-weight: bold;">
                                KPI BIZNESOWE
                            </span>
                        </h2>
                        <div class="flex items-center space-x-2 px-4 py-2 rounded-full border" style="background: rgba(224, 172, 126, 0.2); border-color: rgba(224, 172, 126, 0.3);">
                            <svg class="w-4 h-4" style="color: #e0ac7e;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                            </svg>
                            <span class="text-xs font-bold" style="color: #e0ac7e;">ANALITYKA REAL-TIME</span>
                        </div>
                    </div>
                </div>

                {{-- Enhanced KPI Grid --}}
                <div class="relative z-10 grid grid-cols-2 md:grid-cols-4 gap-8">

                    {{-- Products Today --}}
                    <div class="group text-center p-6 bg-gradient-to-br from-green-600/20 to-green-800/20 backdrop-blur-sm rounded-xl border border-green-500/30 hover:border-green-400/50 transition-all duration-300 hover:scale-105 relative">
                        <div class="absolute inset-0 bg-gradient-to-br from-green-400/10 to-transparent rounded-xl opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                        <div class="relative z-10">
                            <div class="w-12 h-12 bg-gradient-to-br from-green-400 to-green-600 rounded-lg flex items-center justify-center mx-auto mb-4 shadow-lg transform transition-transform duration-300 group-hover:scale-110">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                            </div>
                            <p class="text-3xl font-black mb-2" style="color: #34d399 !important;">{{ number_format($businessKpis['products_today'] ?? 0) }}</p>
                            <p class="text-sm font-bold uppercase tracking-wide text-white">Produkty dodane dziś</p>
                        </div>
                    </div>

                    {{-- Empty Categories --}}
                    <div class="group text-center p-6 bg-gradient-to-br from-yellow-600/20 to-yellow-800/20 backdrop-blur-sm rounded-xl border border-yellow-500/30 hover:border-yellow-400/50 transition-all duration-300 hover:scale-105 relative">
                        <div class="absolute inset-0 bg-gradient-to-br from-yellow-400/10 to-transparent rounded-xl opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                        <div class="relative z-10">
                            <div class="w-12 h-12 bg-gradient-to-br from-yellow-400 to-yellow-600 rounded-lg flex items-center justify-center mx-auto mb-4 shadow-lg transform transition-transform duration-300 group-hover:scale-110">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <p class="text-3xl font-black mb-2" style="color: #fbbf24 !important;">{{ number_format($businessKpis['categories_empty'] ?? 0) }}</p>
                            <p class="text-sm font-bold uppercase tracking-wide text-white">Kategorie bez produktów</p>
                        </div>
                    </div>

                    {{-- Products Without Images --}}
                    <div class="group text-center p-6 bg-gradient-to-br from-red-600/20 to-red-800/20 backdrop-blur-sm rounded-xl border border-red-500/30 hover:border-red-400/50 transition-all duration-300 hover:scale-105 relative">
                        <div class="absolute inset-0 bg-gradient-to-br from-red-400/10 to-transparent rounded-xl opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                        <div class="relative z-10">
                            <div class="w-12 h-12 bg-gradient-to-br from-red-400 to-red-600 rounded-lg flex items-center justify-center mx-auto mb-4 shadow-lg transform transition-transform duration-300 group-hover:scale-110">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            <p class="text-3xl font-black mb-2" style="color: #f87171 !important;">{{ number_format($businessKpis['products_no_images'] ?? 0) }}</p>
                            <p class="text-sm font-bold uppercase tracking-wide text-white">Produkty bez zdjęć</p>
                        </div>
                    </div>

                    {{-- Integration Conflicts with MPP TRADE Colors --}}
                    <div class="group text-center p-6 backdrop-blur-sm rounded-xl border transition-all duration-300 hover:scale-105 relative"
                         style="background: linear-gradient(135deg, rgba(224, 172, 126, 0.3), rgba(209, 151, 90, 0.3)); border-color: rgba(224, 172, 126, 0.4);"
                         onmouseover="this.style.borderColor='rgba(224, 172, 126, 0.6)'"
                         onmouseout="this.style.borderColor='rgba(224, 172, 126, 0.4)'">
                        <div class="absolute inset-0 rounded-xl opacity-0 group-hover:opacity-100 transition-opacity duration-300" style="background: linear-gradient(135deg, rgba(224, 172, 126, 0.15), transparent);"></div>
                        <div class="relative z-10">
                            <div class="w-12 h-12 rounded-lg flex items-center justify-center mx-auto mb-4 shadow-xl transform transition-transform duration-300 group-hover:scale-110" style="background: linear-gradient(45deg, #e0ac7e, #d1975a);">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <div class="absolute inset-0 rounded-lg opacity-60 blur-lg animate-pulse" style="background: #e0ac7e;"></div>
                            </div>
                            <p class="text-3xl font-black mb-2" style="color: #fb923c !important;">{{ number_format($businessKpis['integration_conflicts'] ?? 0) }}</p>
                            <p class="text-sm font-bold uppercase tracking-wide text-white">Konflikty integracji</p>
                            {{-- MPP TRADE Badge --}}
                            <div class="absolute top-2 right-2 px-2 py-1 rounded-full border opacity-0 group-hover:opacity-100 transition-opacity duration-300" style="background: rgba(224, 172, 126, 0.2); border-color: rgba(224, 172, 126, 0.3);">
                                <span class="text-xs font-black" style="color: #e0ac7e;">MPP</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Bottom accent line with MPP Colors --}}
                <div class="absolute bottom-0 left-0 right-0 h-px" style="background: linear-gradient(to right, transparent, rgba(224, 172, 126, 0.5), transparent);"></div>
            </div>
        </div>

        {{-- Sync Jobs Monitoring with MPP TRADE Colors --}}
        @if(isset($syncJobsStatus['total_jobs']))
        <div class="mb-8 relative">
            <div class="backdrop-blur-xl rounded-2xl p-8 shadow-2xl relative overflow-hidden" style="background: linear-gradient(135deg, rgba(31, 41, 55, 0.8), rgba(17, 24, 39, 0.6), rgba(31, 41, 55, 0.8)); border: 1px solid rgba(59, 130, 246, 0.3);">
                {{-- Header --}}
                <div class="flex items-center justify-between mb-8">
                    <div class="flex items-center space-x-4">
                        <div class="w-12 h-12 bg-gradient-to-br from-blue-400 via-blue-500 to-blue-600 rounded-xl flex items-center justify-center shadow-xl">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-2xl font-black text-white">ZADANIA SYNCHRONIZACJI</h3>
                            <p class="text-sm text-gray-400 font-medium">Monitoring operacji sync PPM ↔ External Systems</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2 px-3 py-2 rounded-lg border" style="background: rgba(31, 41, 55, 0.8); border-color: rgba(75, 85, 99, 0.2);">
                        @php
                            $healthStatus = $syncJobsStatus['health_status'] ?? 'unknown';
                            $statusColor = match($healthStatus) {
                                'healthy' => '#059669',
                                'warning' => '#d97706',
                                'critical' => '#dc2626',
                                default => '#6b7280'
                            };
                            $statusText = match($healthStatus) {
                                'healthy' => 'ZDROWY',
                                'warning' => 'UWAGA',
                                'critical' => 'KRYTYCZNY',
                                default => 'NIEZNANY'
                            };
                        @endphp
                        <div class="w-2 h-2 rounded-full animate-pulse" style="background-color: {{ $statusColor }};"></div>
                        <span class="text-xs font-bold" style="color: {{ $statusColor }};">{{ $statusText }}</span>
                    </div>
                </div>

                {{-- Sync Jobs Grid --}}
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                    {{-- Running Jobs --}}
                    <div class="group text-center p-6 backdrop-blur-sm rounded-xl border transition-all duration-300 hover:scale-105"
                         style="background: linear-gradient(135deg, rgba(34, 197, 94, 0.3), rgba(22, 163, 74, 0.3)); border-color: rgba(34, 197, 94, 0.4);">
                        <div class="w-12 h-12 bg-gradient-to-br from-green-400 to-green-600 rounded-lg flex items-center justify-center mx-auto mb-4 shadow-xl">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1m4 0h1m-6 4h.01M19 10a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <p class="text-3xl font-black mb-2 text-green-400">{{ number_format($syncJobsStatus['running_jobs'] ?? 0) }}</p>
                        <p class="text-sm font-bold uppercase tracking-wide text-white">Aktywne zadania</p>
                    </div>

                    {{-- Pending Jobs --}}
                    <div class="group text-center p-6 backdrop-blur-sm rounded-xl border transition-all duration-300 hover:scale-105"
                         style="background: linear-gradient(135deg, rgba(251, 146, 60, 0.3), rgba(249, 115, 22, 0.3)); border-color: rgba(251, 146, 60, 0.4);">
                        <div class="w-12 h-12 bg-gradient-to-br from-orange-400 to-orange-600 rounded-lg flex items-center justify-center mx-auto mb-4 shadow-xl">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <p class="text-3xl font-black mb-2 text-orange-400">{{ number_format($syncJobsStatus['pending_jobs'] ?? 0) }}</p>
                        <p class="text-sm font-bold uppercase tracking-wide text-white">Oczekujące</p>
                    </div>

                    {{-- Failed Jobs --}}
                    <div class="group text-center p-6 backdrop-blur-sm rounded-xl border transition-all duration-300 hover:scale-105"
                         style="background: linear-gradient(135deg, rgba(248, 113, 113, 0.3), rgba(239, 68, 68, 0.3)); border-color: rgba(248, 113, 113, 0.4);">
                        <div class="w-12 h-12 bg-gradient-to-br from-red-400 to-red-600 rounded-lg flex items-center justify-center mx-auto mb-4 shadow-xl">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.667-.833-2.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                        </div>
                        <p class="text-3xl font-black mb-2 text-red-400">{{ number_format($syncJobsStatus['failed_jobs'] ?? 0) }}</p>
                        <p class="text-sm font-bold uppercase tracking-wide text-white">Nieudane</p>
                    </div>

                    {{-- Success Rate --}}
                    <div class="group text-center p-6 backdrop-blur-sm rounded-xl border transition-all duration-300 hover:scale-105"
                         style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.3), rgba(37, 99, 235, 0.3)); border-color: rgba(59, 130, 246, 0.4);">
                        <div class="w-12 h-12 bg-gradient-to-br from-blue-400 to-blue-600 rounded-lg flex items-center justify-center mx-auto mb-4 shadow-xl">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                        <p class="text-3xl font-black mb-2 text-blue-400">{{ number_format(100 - ($syncJobsStatus['failure_rate_percent'] ?? 0), 1) }}%</p>
                        <p class="text-sm font-bold uppercase tracking-wide text-white">Sukces</p>
                    </div>
                </div>

                {{-- Performance Metrics --}}
                <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="backdrop-blur-sm rounded-xl p-6 border" style="background: rgba(31, 41, 55, 0.6); border-color: rgba(75, 85, 99, 0.3);">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-lg font-bold text-white">Dziś ukończone</h4>
                            <span class="text-2xl font-black text-green-400">{{ number_format($syncJobsStatus['completed_today'] ?? 0) }}</span>
                        </div>
                        <div class="w-full bg-gray-700 rounded-full h-2">
                            @php $todayProgress = min(($syncJobsStatus['completed_today'] ?? 0) / max(($syncJobsStatus['total_jobs'] ?? 1), 1) * 100, 100); @endphp
                            <div class="bg-gradient-to-r from-green-400 to-green-600 h-2 rounded-full transition-all duration-1000" style="width: {{ $todayProgress }}%;"></div>
                        </div>
                    </div>

                    <div class="backdrop-blur-sm rounded-xl p-6 border" style="background: rgba(31, 41, 55, 0.6); border-color: rgba(75, 85, 99, 0.3);">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-lg font-bold text-white">Średni czas (s)</h4>
                            <span class="text-2xl font-black text-blue-400">{{ number_format($syncJobsStatus['avg_duration_seconds'] ?? 0, 1) }}s</span>
                        </div>
                        <div class="w-full bg-gray-700 rounded-full h-2">
                            @php $durationProgress = min(($syncJobsStatus['avg_duration_seconds'] ?? 0) / 60 * 100, 100); @endphp
                            <div class="bg-gradient-to-r from-blue-400 to-blue-600 h-2 rounded-full transition-all duration-1000" style="width: {{ $durationProgress }}%;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

    @elseif($userRole === 'Manager')
        {{-- ============================================
            MANAGER DASHBOARD - Product Management
            ============================================ --}}

        <div class="mb-8">
            <h2 class="text-h2 font-bold mb-4" style="color: #e0ac7e;">Manager Dashboard</h2>
            <p class="text-gray-400 mb-6">Zarządzanie produktami i synchronizacją</p>
        </div>

        {{-- Manager KPI Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            {{-- Total Products --}}
            <div class="enterprise-card">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-blue-600 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                    </div>
                    <span class="text-2xl font-bold text-white">{{ $dashboardStats['total_products'] ?? 0 }}</span>
                </div>
                <h3 class="text-sm font-semibold text-gray-400 mb-2">Produkty</h3>
                <div class="flex items-center text-xs text-gray-500">
                    <span class="text-green-500 mr-2">{{ $dashboardStats['active_products'] ?? 0 }} aktywnych</span>
                </div>
            </div>

            {{-- Sync Status --}}
            <div class="enterprise-card">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-purple-600 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                    </div>
                    <span class="text-2xl font-bold text-white">{{ $syncJobsStatus['completed_today'] ?? 0 }}</span>
                </div>
                <h3 class="text-sm font-semibold text-gray-400 mb-2">Sync Today</h3>
                <div class="flex items-center text-xs text-gray-500">
                    <span class="text-blue-500 mr-2">{{ $syncJobsStatus['running_jobs'] ?? 0 }} running</span>
                </div>
            </div>

            {{-- Categories --}}
            <div class="enterprise-card">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-indigo-600 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                        </svg>
                    </div>
                    <span class="text-2xl font-bold text-white">{{ $dashboardStats['total_categories'] ?? 0 }}</span>
                </div>
                <h3 class="text-sm font-semibold text-gray-400 mb-2">Kategorie</h3>
                <div class="flex items-center text-xs text-gray-500">
                    <span class="text-gray-400">Zarządzanie katalogiem</span>
                </div>
            </div>
        </div>

        {{-- Manager Quick Actions --}}
        <div class="mb-8">
            <h2 class="text-h2 font-bold mb-4" style="color: #e0ac7e;">Quick Actions</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <a href="/admin/products/create" class="btn-enterprise-primary flex items-center justify-center space-x-2 py-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    <span>Dodaj Produkt</span>
                </a>
                <a href="/admin/products/import" class="btn-enterprise-primary flex items-center justify-center space-x-2 py-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                    </svg>
                    <span>Import CSV</span>
                </a>
                <a href="/admin/reports/products" class="btn-enterprise-secondary flex items-center justify-center space-x-2 py-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <span>Raporty</span>
                </a>
            </div>
        </div>

    @else
        {{-- ============================================
            DEFAULT DASHBOARD - Basic Stats
            ============================================ --}}

        <div class="enterprise-card">
            <h2 class="text-h2 font-bold mb-4" style="color: #e0ac7e;">Dashboard</h2>
            <p class="text-gray-400">Role: {{ $userRole }}</p>
            <p class="text-gray-400 mt-2">Podstawowe statystyki systemu PPM</p>

            <div class="mt-6">
                <div class="text-3xl font-bold text-white mb-2">{{ $dashboardStats['total_products'] ?? 0 }}</div>
                <div class="text-sm text-gray-400">Produktów w systemie</div>
            </div>
        </div>
    @endif

</div>

{{-- Alpine.js Auto-Refresh Script --}}
<script>
function adminDashboard(refreshInterval) {
    return {
        refreshInterval: refreshInterval,
        intervalId: null,

        init() {
            this.startAutoRefresh();
        },

        startAutoRefresh() {
            this.stopAutoRefresh();
            this.intervalId = setInterval(() => {
                Livewire.dispatch('refreshDashboard');
            }, this.refreshInterval * 1000);
        },

        stopAutoRefresh() {
            if (this.intervalId) {
                clearInterval(this.intervalId);
                this.intervalId = null;
            }
        },

        updateRefreshInterval(newInterval) {
            this.refreshInterval = newInterval;
            this.startAutoRefresh();
        }
    }
}
</script>

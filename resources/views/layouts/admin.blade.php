<!DOCTYPE html>
<html lang="pl" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Panel - PPM Management')</title>

    {{-- Tailwind CSS is compiled via Vite in app.css (Tailwind 3.4 properly configured) --}}

    <!-- Font Awesome CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">

    <!-- Livewire Styles -->
    @livewireStyles

    <!-- Application CSS (JS moved to end of body AFTER Livewire) -->
    @vite([
        'resources/css/app.css',
        'resources/css/admin/layout.css',
        'resources/css/admin/components.css',
        'resources/css/admin/category-tree.css',
        'resources/css/products/category-form.css',
        'resources/css/products/product-form.css',
        'resources/css/products/media-gallery.css',
        'resources/css/admin/media-admin.css',
        'resources/css/admin/feature-browser.css',
        'resources/css/components/category-picker.css',
        'resources/css/products/compatibility-tiles.css',
        'resources/css/admin/supplier-panel.css',
        'resources/css/products/import-panel.css'
    ])

    {{-- Alpine.js is included with Livewire 3.x - no need to load separately --}}
</head>
<body class="bg-gradient-to-br from-gray-900 via-gray-800 to-black text-white">
    
    <!-- Animated Background Elements -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none z-0">
        <div class="absolute -top-32 -right-32 w-64 h-64 rounded-full blur-3xl animate-pulse" style="background: radial-gradient(circle, rgba(224, 172, 126, 0.1), rgba(209, 151, 90, 0.05));"></div>
        <div class="absolute -bottom-32 -left-32 w-64 h-64 rounded-full blur-3xl animate-pulse" style="background: radial-gradient(circle, rgba(209, 151, 90, 0.1), rgba(224, 172, 126, 0.05)); animation-delay: 2s;"></div>
        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-96 h-96 rounded-full blur-3xl animate-pulse" style="background: radial-gradient(circle, rgba(224, 172, 126, 0.05), rgba(209, 151, 90, 0.03)); animation-delay: 4s;"></div>
    </div>
    
    <!-- DEV MODE HEADER -->
    <div class="bg-orange-600 text-white text-center p-2 text-sm font-bold relative z-10">
        🚧 DEVELOPMENT MODE - Authentication Disabled 🚧
    </div>
    
    {{-- Flash Messages Component --}}
    <x-flash-messages />

    <div class="relative min-h-screen" x-data="{ sidebarOpen: false, sidebarCollapsed: false }">
        <!-- Admin Header -->
        <div class="admin-header backdrop-blur-xl shadow-2xl fixed top-0 left-0 right-0 z-50" style="background: linear-gradient(135deg, rgba(31, 41, 55, 0.95), rgba(17, 24, 39, 0.95)); border-bottom: 1px solid rgba(224, 172, 126, 0.3); height: 64px;">
            <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <div class="flex items-center min-w-0">
                        <!-- Mobile menu button -->
                        <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden p-2 rounded-md text-gray-400 hover:text-white hover:bg-gray-700 flex-shrink-0">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path x-show="!sidebarOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                                <path x-show="sidebarOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                        
                        <!-- Logo -->
                        <div class="flex-shrink-0 ml-2 lg:ml-0">
                            <div class="relative w-10 h-10 rounded-xl flex items-center justify-center shadow-lg transform transition-transform duration-300 hover:scale-105" style="background: linear-gradient(45deg, #e0ac7e, #d1975a);">
                                <svg class="w-6 h-6 text-white relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-2 sm:ml-4 min-w-0 flex-1 lg:flex-initial">
                            <h1 class="text-base sm:text-lg font-bold tracking-tight truncate" style="color: #e0ac7e;">
                                ADMIN PANEL
                            </h1>
                            <p class="text-xs font-medium text-gray-400 tracking-wide hidden sm:block">
                                PPM Enterprise
                            </p>
                        </div>
                    </div>

                    <!-- Header Right Side -->
                    <div class="flex items-center space-x-2 sm:space-x-4 ml-2">
                        <!-- Quick Search -->
                        <div class="hidden lg:block relative">
                            <input type="text"
                                   placeholder="Szybkie wyszukiwanie..."
                                   class="w-48 xl:w-64 px-4 py-2 pl-10 text-sm text-white placeholder-gray-400 rounded-lg transition-all duration-200 focus:outline-none focus:ring-2"
                                   style="background: rgba(31, 41, 55, 0.8); border: 1px solid rgba(75, 85, 99, 0.5);"
                                   onfocus="this.style.borderColor='#e0ac7e'; this.style.boxShadow='0 0 0 3px rgba(224, 172, 126, 0.1)'"
                                   onblur="this.style.borderColor='rgba(75, 85, 99, 0.5)'; this.style.boxShadow='none'">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </div>
                        </div>
                        
                        <!-- User Menu -->
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" class="flex items-center space-x-2 text-sm rounded-lg p-2 hover:bg-gray-700 transition-colors duration-200">
                                @if(auth()->user() && auth()->user()->avatar)
                                    <img src="{{ asset('storage/' . auth()->user()->avatar) }}"
                                         alt="Avatar"
                                         class="w-8 h-8 rounded-full object-cover border border-[#e0ac7e]/50">
                                @else
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold text-gray-900" style="background: linear-gradient(45deg, #e0ac7e, #d1975a);">
                                        {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                                    </div>
                                @endif
                                <span class="hidden sm:block text-white font-medium">{{ auth()->user()->name ?? 'Admin' }}</span>
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            
                            <!-- User Dropdown -->
                            <div x-show="open" @click.away="open = false" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" class="dropdown-menu absolute right-0 mt-2 w-48 rounded-lg shadow-2xl z-[9999]" style="background: linear-gradient(135deg, rgba(31, 41, 55, 0.95), rgba(17, 24, 39, 0.95)); border: 1px solid rgba(224, 172, 126, 0.3); z-index: 9999;">
                                <div class="py-1">
                                    <a href="/admin/system-settings" class="flex items-center px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 hover:text-white">
                                        <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        </svg>
                                        Ustawienia
                                    </a>
                                    <a href="/profile" class="flex items-center px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 hover:text-white">
                                        <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                        </svg>
                                        Profil
                                    </a>
                                    <hr class="my-1" style="border-color: rgba(224, 172, 126, 0.2);">
                                    <form method="POST" action="/logout">
                                        @csrf
                                        <button type="submit" class="flex items-center w-full text-left px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 hover:text-white">
                                            <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                            </svg>
                                            Wyloguj
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Breadcrumb Navigation -->
        <div class="backdrop-blur-sm border-b relative left-0 right-0 z-40" style="background: linear-gradient(135deg, rgba(17, 24, 39, 0.6), rgba(31, 41, 55, 0.4)); border-color: rgba(224, 172, 126, 0.1);">
            <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center space-x-2 py-1.5 overflow-x-auto">
                    <a href="/admin" class="text-sm text-gray-400 hover:text-white transition-colors duration-200">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h2a2 2 0 012 2v0M8 5a2 2 0 000 4h8a2 2 0 000-4M8 5v0"></path>
                        </svg>
                        Admin Panel
                    </a>
                    @if(isset($breadcrumb))
                        <span class="text-gray-500">/</span>
                        <span class="text-sm font-medium" style="color: #e0ac7e;">{{ $breadcrumb }}</span>
                    @endif
                </div>
            </div>
        </div>
        
        <!-- Mobile Sidebar Overlay -->
        <div x-show="sidebarOpen"
             @click="sidebarOpen = false"
             x-transition:enter="transition-opacity ease-linear duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-30 bg-black bg-opacity-50 lg:hidden"
             x-cloak></div>

        <!-- Main Grid Layout: Desktop grid[sidebar|main], Mobile stacked -->
        <div class="pt-2 lg:grid" :class="sidebarCollapsed ? 'lg:grid-cols-[4rem_1fr]' : 'lg:grid-cols-[16rem_1fr]'">
            <!-- Sidebar: Static positioning (not fixed) -->
            <aside class="relative z-40 transition-all duration-300 ease-in-out"
                 :class="{
                     'translate-x-0': sidebarOpen,
                     '-translate-x-full lg:translate-x-0': !sidebarOpen,
                     'w-64': !sidebarCollapsed,
                     'w-16': sidebarCollapsed
                 }"
                 style="background: linear-gradient(135deg, rgba(17, 24, 39, 0.95), rgba(31, 41, 55, 0.9)); border-right: 1px solid rgba(224, 172, 126, 0.2);">
                
                <div class="flex flex-col h-full pt-4 pb-4 overflow-y-auto">
                    <!-- Collapse Toggle Button (Desktop Only) -->
                    <div class="hidden lg:flex justify-end px-2 mb-2">
                        <button @click="sidebarCollapsed = !sidebarCollapsed"
                                class="p-1.5 rounded-lg hover:bg-gray-700/50 transition-colors duration-200 group"
                                :title="sidebarCollapsed ? 'Rozwiń menu' : 'Zwiń menu'">
                            <svg class="w-5 h-5 text-gray-400 group-hover:text-orange-400 transition-transform duration-300"
                                 :class="{ 'rotate-180': sidebarCollapsed }"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"></path>
                            </svg>
                        </button>
                    </div>

                    <div class="px-4">
                        <h3 class="text-lg font-bold text-white mb-4 flex items-center transition-opacity duration-300"
                            :class="{ 'opacity-0 h-0 mb-0 overflow-hidden': sidebarCollapsed }">
                            <svg class="w-5 h-5 mr-2 flex-shrink-0" style="color: #e0ac7e;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                            <span style="color: #e0ac7e;">Szybki dostęp</span>
                        </h3>
                        
                        <nav class="space-y-2">
                            <!-- Dashboard Link -->
                            <a href="/admin" class="flex items-center px-3 py-2 text-sm font-medium text-gray-300 rounded-lg hover:bg-gray-700 hover:text-white transition-colors duration-200 group relative {{ request()->is('admin') ? 'bg-gray-700 text-white' : '' }}"
                               :title="sidebarCollapsed ? 'Dashboard' : ''"
                               :class="{ 'justify-center': sidebarCollapsed }">
                                <svg class="w-5 h-5 group-hover:text-orange-400 flex-shrink-0"
                                     :class="{ 'mr-0': sidebarCollapsed, 'mr-3': !sidebarCollapsed }"
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                                <span x-show="!sidebarCollapsed" x-transition class="whitespace-nowrap">Dashboard</span>
                            </a>
                            
                            <!-- Shop Management -->
                            <div class="space-y-1">
                                <div class="flex items-center px-3 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wide transition-opacity duration-300"
                                     :class="{ 'opacity-0 h-0 py-0 overflow-hidden': sidebarCollapsed }">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                    </svg>
                                    Sklepy
                                </div>
                                <a href="/admin/shops" class="flex items-center px-3 py-2 text-sm font-medium text-gray-300 rounded-lg hover:bg-gray-700 hover:text-white transition-colors duration-200 {{ request()->is('admin/shops') ? 'bg-gray-700 text-white' : '' }}"
                                   :title="sidebarCollapsed ? 'Lista sklepów' : ''"
                                   :class="{ 'justify-center': sidebarCollapsed }">
                                    <svg class="w-4 h-4 flex-shrink-0"
                                         :class="{ 'mr-0': sidebarCollapsed, 'mr-3': !sidebarCollapsed }"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                    </svg>
                                    <span x-show="!sidebarCollapsed" x-transition class="whitespace-nowrap">Lista sklepów</span>
                                </a>
                                <a href="/admin/shops/add" class="flex items-center px-3 py-2 text-sm font-medium text-gray-300 rounded-lg hover:bg-gray-700 hover:text-white transition-colors duration-200 {{ request()->is('admin/shops/add') ? 'bg-gray-700 text-white' : '' }}"
                                   :title="sidebarCollapsed ? 'Dodaj sklep' : ''"
                                   :class="{ 'justify-center': sidebarCollapsed }">
                                    <svg class="w-4 h-4 flex-shrink-0"
                                         :class="{ 'mr-0': sidebarCollapsed, 'mr-3': !sidebarCollapsed }"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                    <span x-show="!sidebarCollapsed" x-transition class="whitespace-nowrap">Dodaj sklep</span>
                                </a>
                                <a href="/admin/shops/sync" class="flex items-center px-3 py-2 text-sm font-medium text-gray-300 rounded-lg hover:bg-gray-700 hover:text-white transition-colors duration-200 {{ request()->is('admin/shops/sync') ? 'bg-gray-700 text-white' : '' }}"
                                   :title="sidebarCollapsed ? 'Synchronizacja' : ''"
                                   :class="{ 'justify-center': sidebarCollapsed }">
                                    <svg class="w-4 h-4 flex-shrink-0"
                                         :class="{ 'mr-0': sidebarCollapsed, 'mr-3': !sidebarCollapsed }"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                    </svg>
                                    <span x-show="!sidebarCollapsed" x-transition class="whitespace-nowrap">Synchronizacja</span>
                                </a>
                            </div>

                            <!-- ETAP_05: Products Module Menu -->
                            <div class="space-y-1 pt-4">
                                <div class="flex items-center px-3 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wide transition-opacity duration-300"
                                     :class="{ 'opacity-0 h-0 py-0 overflow-hidden': sidebarCollapsed }">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10"></path>
                                    </svg>
                                    Produkty
                                </div>
                                <a href="/admin/products" class="flex items-center px-3 py-2 text-sm font-medium text-gray-300 rounded-lg hover:bg-gray-700 hover:text-white transition-colors duration-200 {{ request()->is('admin/products') && !request()->is('admin/products/*') ? 'bg-gray-700 text-white' : '' }}"
                                   :title="sidebarCollapsed ? 'Lista produktów' : ''"
                                   :class="{ 'justify-center': sidebarCollapsed }">
                                    <svg class="w-4 h-4 flex-shrink-0"
                                         :class="{ 'mr-0': sidebarCollapsed, 'mr-3': !sidebarCollapsed }"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10"></path>
                                    </svg>
                                    <span x-show="!sidebarCollapsed" x-transition class="whitespace-nowrap">Lista produktów</span>
                                </a>
                                <a href="/admin/products/create" class="flex items-center px-3 py-2 text-sm font-medium text-gray-300 rounded-lg hover:bg-gray-700 hover:text-white transition-colors duration-200 {{ request()->is('admin/products/create') ? 'bg-gray-700 text-white' : '' }}"
                                   :title="sidebarCollapsed ? 'Dodaj produkt' : ''"
                                   :class="{ 'justify-center': sidebarCollapsed }">
                                    <svg class="w-4 h-4 flex-shrink-0"
                                         :class="{ 'mr-0': sidebarCollapsed, 'mr-3': !sidebarCollapsed }"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                    <span x-show="!sidebarCollapsed" x-transition class="whitespace-nowrap">Dodaj produkt</span>
                                </a>
                                <a href="/admin/products/categories" class="flex items-center px-3 py-2 text-sm font-medium text-gray-300 rounded-lg hover:bg-gray-700 hover:text-white transition-colors duration-200 {{ request()->is('admin/products/categories*') ? 'bg-gray-700 text-white' : '' }}"
                                   :title="sidebarCollapsed ? 'Kategorie' : ''"
                                   :class="{ 'justify-center': sidebarCollapsed }">
                                    <svg class="w-4 h-4 flex-shrink-0"
                                         :class="{ 'mr-0': sidebarCollapsed, 'mr-3': !sidebarCollapsed }"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                    </svg>
                                    <span x-show="!sidebarCollapsed" x-transition class="whitespace-nowrap">Kategorie</span>
                                </a>
                                <a href="/admin/products/import" class="flex items-center px-3 py-2 text-sm font-medium text-gray-300 rounded-lg hover:bg-gray-700 hover:text-white transition-colors duration-200 {{ request()->is('admin/products/import') ? 'bg-gray-700 text-white' : '' }}"
                                   :title="sidebarCollapsed ? 'Import z pliku' : ''"
                                   :class="{ 'justify-center': sidebarCollapsed }">
                                    <svg class="w-4 h-4 flex-shrink-0"
                                         :class="{ 'mr-0': sidebarCollapsed, 'mr-3': !sidebarCollapsed }"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path>
                                    </svg>
                                    <span x-show="!sidebarCollapsed" x-transition class="whitespace-nowrap">Import z pliku</span>
                                </a>
                                <a href="/admin/products/import-history" class="sidebar-link-disabled flex items-center px-3 py-2 text-sm font-medium text-gray-300 rounded-lg hover:bg-gray-700 hover:text-white transition-colors duration-200"
                                   title="W przygotowaniu"
                                   :class="{ 'justify-center': sidebarCollapsed }">
                                    <svg class="w-4 h-4 flex-shrink-0"
                                         :class="{ 'mr-0': sidebarCollapsed, 'mr-3': !sidebarCollapsed }"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span x-show="!sidebarCollapsed" x-transition class="whitespace-nowrap">Historie importów</span>
                                </a>
                                <a href="/admin/products/search" class="sidebar-link-disabled flex items-center px-3 py-2 text-sm font-medium text-gray-300 rounded-lg hover:bg-gray-700 hover:text-white transition-colors duration-200"
                                   title="W przygotowaniu"
                                   :class="{ 'justify-center': sidebarCollapsed }">
                                    <svg class="w-4 h-4 flex-shrink-0"
                                         :class="{ 'mr-0': sidebarCollapsed, 'mr-3': !sidebarCollapsed }"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                    <span x-show="!sidebarCollapsed" x-transition class="whitespace-nowrap">Szybka Wyszukiwarka</span>
                                </a>
                            </div>

                            <!-- FAZA 4: Price Management Menu -->
                            <div class="space-y-1 pt-4">
                                <div class="flex items-center px-3 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wide transition-opacity duration-300"
                                     :class="{ 'opacity-0 h-0 py-0 overflow-hidden': sidebarCollapsed }">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Cennik
                                </div>
                                <a href="/admin/price-management/price-groups" class="flex items-center px-3 py-2 text-sm font-medium text-gray-300 rounded-lg hover:bg-gray-700 hover:text-white transition-colors duration-200 {{ request()->is('admin/price-management/price-groups') ? 'bg-gray-700 text-white' : '' }}"
                                   :title="sidebarCollapsed ? 'Grupy cenowe' : ''"
                                   :class="{ 'justify-center': sidebarCollapsed }">
                                    <svg class="w-4 h-4 flex-shrink-0"
                                         :class="{ 'mr-0': sidebarCollapsed, 'mr-3': !sidebarCollapsed }"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                                    </svg>
                                    <span x-show="!sidebarCollapsed" x-transition class="whitespace-nowrap">Grupy cenowe</span>
                                </a>
                                <a href="/admin/price-management/product-prices" class="sidebar-link-disabled flex items-center px-3 py-2 text-sm font-medium text-gray-300 rounded-lg hover:bg-gray-700 hover:text-white transition-colors duration-200"
                                   title="W przygotowaniu"
                                   :class="{ 'justify-center': sidebarCollapsed }">
                                    <svg class="w-4 h-4 flex-shrink-0"
                                         :class="{ 'mr-0': sidebarCollapsed, 'mr-3': !sidebarCollapsed }"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span x-show="!sidebarCollapsed" x-transition class="whitespace-nowrap">Ceny produktów</span>
                                </a>
                                <a href="/admin/price-management/bulk-updates" class="sidebar-link-disabled flex items-center px-3 py-2 text-sm font-medium text-gray-300 rounded-lg hover:bg-gray-700 hover:text-white transition-colors duration-200"
                                   title="W przygotowaniu"
                                   :class="{ 'justify-center': sidebarCollapsed }">
                                    <svg class="w-4 h-4 flex-shrink-0"
                                         :class="{ 'mr-0': sidebarCollapsed, 'mr-3': !sidebarCollapsed }"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                    </svg>
                                    <span x-show="!sidebarCollapsed" x-transition class="whitespace-nowrap">Aktualizacja masowa</span>
                                </a>
                            </div>

                            <!-- ZARZADZANIE PRODUKTAMI -->
                            <div class="space-y-1 pt-4">
                                <div class="flex items-center px-3 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wide transition-opacity duration-300"
                                     :class="{ 'opacity-0 h-0 py-0 overflow-hidden': sidebarCollapsed }">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                    Zarzadzanie produktami
                                </div>
                                <a href="/admin/product-parameters" class="flex items-center px-3 py-2 text-sm font-medium text-gray-300 rounded-lg hover:bg-gray-700 hover:text-white transition-colors duration-200 {{ request()->is('admin/product-parameters*') ? 'bg-gray-700 text-white' : '' }}"
                                   :title="sidebarCollapsed ? 'Parametry produktow' : ''"
                                   :class="{ 'justify-center': sidebarCollapsed }">
                                    <svg class="w-4 h-4 flex-shrink-0"
                                         :class="{ 'mr-0': sidebarCollapsed, 'mr-3': !sidebarCollapsed }"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                    <span x-show="!sidebarCollapsed" x-transition class="whitespace-nowrap">Parametry produktow</span>
                                </a>
                                <a href="/admin/features/vehicles" class="flex items-center px-3 py-2 text-sm font-medium text-gray-300 rounded-lg hover:bg-gray-700 hover:text-white transition-colors duration-200 {{ request()->is('admin/features/vehicles') ? 'bg-gray-700 text-white' : '' }}"
                                   :title="sidebarCollapsed ? 'Cechy pojazdów' : ''"
                                   :class="{ 'justify-center': sidebarCollapsed }">
                                    <svg class="w-4 h-4 flex-shrink-0"
                                         :class="{ 'mr-0': sidebarCollapsed, 'mr-3': !sidebarCollapsed }"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                    </svg>
                                    <span x-show="!sidebarCollapsed" x-transition class="whitespace-nowrap">Cechy pojazdów</span>
                                </a>
                                <a href="/admin/compatibility" class="flex items-center px-3 py-2 text-sm font-medium text-gray-300 rounded-lg hover:bg-gray-700 hover:text-white transition-colors duration-200 {{ request()->is('admin/compatibility') ? 'bg-gray-700 text-white' : '' }}"
                                   :title="sidebarCollapsed ? 'Dopasowania czesci' : ''"
                                   :class="{ 'justify-center': sidebarCollapsed }">
                                    <svg class="w-4 h-4 flex-shrink-0"
                                         :class="{ 'mr-0': sidebarCollapsed, 'mr-3': !sidebarCollapsed }"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z"></path>
                                    </svg>
                                    <span x-show="!sidebarCollapsed" x-transition class="whitespace-nowrap">Dopasowania czesci</span>
                                </a>
                                <a href="/admin/suppliers" class="flex items-center px-3 py-2 text-sm font-medium text-gray-300 rounded-lg hover:bg-gray-700 hover:text-white transition-colors duration-200 {{ request()->is('admin/suppliers*') ? 'bg-gray-700 text-white' : '' }}"
                                   :title="sidebarCollapsed ? 'Zarzadzanie dostawcami' : ''"
                                   :class="{ 'justify-center': sidebarCollapsed }">
                                    <svg class="w-4 h-4 flex-shrink-0"
                                         :class="{ 'mr-0': sidebarCollapsed, 'mr-3': !sidebarCollapsed }"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                                    </svg>
                                    <span x-show="!sidebarCollapsed" x-transition class="whitespace-nowrap">Zarzadzanie dostawcami</span>
                                </a>
                            </div>

                            <!-- DOSTAWY & KONTENERY -->
                            <div class="space-y-1 pt-4">
                                <div class="flex items-center px-3 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wide transition-opacity duration-300"
                                     :class="{ 'opacity-0 h-0 py-0 overflow-hidden': sidebarCollapsed }">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                    </svg>
                                    Dostawy & Kontenery
                                </div>
                                <a href="/admin/deliveries" class="sidebar-link-disabled flex items-center px-3 py-2 text-sm font-medium text-gray-300 rounded-lg hover:bg-gray-700 hover:text-white transition-colors duration-200"
                                   title="W przygotowaniu"
                                   :class="{ 'justify-center': sidebarCollapsed }">
                                    <svg class="w-4 h-4 flex-shrink-0"
                                         :class="{ 'mr-0': sidebarCollapsed, 'mr-3': !sidebarCollapsed }"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                                    </svg>
                                    <span x-show="!sidebarCollapsed" x-transition class="whitespace-nowrap">Lista dostaw</span>
                                </a>
                                <a href="/admin/deliveries/containers" class="sidebar-link-disabled flex items-center px-3 py-2 text-sm font-medium text-gray-300 rounded-lg hover:bg-gray-700 hover:text-white transition-colors duration-200"
                                   title="W przygotowaniu"
                                   :class="{ 'justify-center': sidebarCollapsed }">
                                    <svg class="w-4 h-4 flex-shrink-0"
                                         :class="{ 'mr-0': sidebarCollapsed, 'mr-3': !sidebarCollapsed }"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                    </svg>
                                    <span x-show="!sidebarCollapsed" x-transition class="whitespace-nowrap">Kontenery</span>
                                </a>
                                <a href="/admin/deliveries/receiving" class="sidebar-link-disabled flex items-center px-3 py-2 text-sm font-medium text-gray-300 rounded-lg hover:bg-gray-700 hover:text-white transition-colors duration-200"
                                   title="W przygotowaniu"
                                   :class="{ 'justify-center': sidebarCollapsed }">
                                    <svg class="w-4 h-4 flex-shrink-0"
                                         :class="{ 'mr-0': sidebarCollapsed, 'mr-3': !sidebarCollapsed }"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span x-show="!sidebarCollapsed" x-transition class="whitespace-nowrap">Przyjęcia magazynowe</span>
                                </a>
                                <a href="/admin/deliveries/documents" class="sidebar-link-disabled flex items-center px-3 py-2 text-sm font-medium text-gray-300 rounded-lg hover:bg-gray-700 hover:text-white transition-colors duration-200"
                                   title="W przygotowaniu"
                                   :class="{ 'justify-center': sidebarCollapsed }">
                                    <svg class="w-4 h-4 flex-shrink-0"
                                         :class="{ 'mr-0': sidebarCollapsed, 'mr-3': !sidebarCollapsed }"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    <span x-show="!sidebarCollapsed" x-transition class="whitespace-nowrap">Dokumenty odpraw</span>
                                </a>
                            </div>

                            <!-- ZAMÓWIENIA -->
                            <div class="space-y-1 pt-4">
                                <div class="flex items-center px-3 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wide transition-opacity duration-300"
                                     :class="{ 'opacity-0 h-0 py-0 overflow-hidden': sidebarCollapsed }">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                    </svg>
                                    Zamówienia
                                </div>
                                <a href="/admin/orders" class="sidebar-link-disabled flex items-center px-3 py-2 text-sm font-medium text-gray-300 rounded-lg hover:bg-gray-700 hover:text-white transition-colors duration-200"
                                   title="W przygotowaniu"
                                   :class="{ 'justify-center': sidebarCollapsed }">
                                    <svg class="w-4 h-4 flex-shrink-0"
                                         :class="{ 'mr-0': sidebarCollapsed, 'mr-3': !sidebarCollapsed }"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                                    </svg>
                                    <span x-show="!sidebarCollapsed" x-transition class="whitespace-nowrap">Lista zamówień</span>
                                </a>
                                <a href="/admin/orders/reservations" class="sidebar-link-disabled flex items-center px-3 py-2 text-sm font-medium text-gray-300 rounded-lg hover:bg-gray-700 hover:text-white transition-colors duration-200"
                                   title="W przygotowaniu"
                                   :class="{ 'justify-center': sidebarCollapsed }">
                                    <svg class="w-4 h-4 flex-shrink-0"
                                         :class="{ 'mr-0': sidebarCollapsed, 'mr-3': !sidebarCollapsed }"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"></path>
                                    </svg>
                                    <span x-show="!sidebarCollapsed" x-transition class="whitespace-nowrap">Rezerwacje z kontenera</span>
                                </a>
                                <a href="/admin/orders/history" class="sidebar-link-disabled flex items-center px-3 py-2 text-sm font-medium text-gray-300 rounded-lg hover:bg-gray-700 hover:text-white transition-colors duration-200"
                                   title="W przygotowaniu"
                                   :class="{ 'justify-center': sidebarCollapsed }">
                                    <svg class="w-4 h-4 flex-shrink-0"
                                         :class="{ 'mr-0': sidebarCollapsed, 'mr-3': !sidebarCollapsed }"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span x-show="!sidebarCollapsed" x-transition class="whitespace-nowrap">Historia zamówień</span>
                                </a>
                            </div>

                            <!-- REKLAMACJE -->
                            <div class="space-y-1 pt-4">
                                <div class="flex items-center px-3 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wide transition-opacity duration-300"
                                     :class="{ 'opacity-0 h-0 py-0 overflow-hidden': sidebarCollapsed }">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3l-6.928-12c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                    </svg>
                                    Reklamacje
                                </div>
                                <a href="/admin/claims" class="sidebar-link-disabled flex items-center px-3 py-2 text-sm font-medium text-gray-300 rounded-lg hover:bg-gray-700 hover:text-white transition-colors duration-200"
                                   title="W przygotowaniu"
                                   :class="{ 'justify-center': sidebarCollapsed }">
                                    <svg class="w-4 h-4 flex-shrink-0"
                                         :class="{ 'mr-0': sidebarCollapsed, 'mr-3': !sidebarCollapsed }"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3l-6.928-12c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                    </svg>
                                    <span x-show="!sidebarCollapsed" x-transition class="whitespace-nowrap">Lista reklamacji</span>
                                </a>
                                <a href="/admin/claims/create" class="sidebar-link-disabled flex items-center px-3 py-2 text-sm font-medium text-gray-300 rounded-lg hover:bg-gray-700 hover:text-white transition-colors duration-200"
                                   title="W przygotowaniu"
                                   :class="{ 'justify-center': sidebarCollapsed }">
                                    <svg class="w-4 h-4 flex-shrink-0"
                                         :class="{ 'mr-0': sidebarCollapsed, 'mr-3': !sidebarCollapsed }"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                    <span x-show="!sidebarCollapsed" x-transition class="whitespace-nowrap">Nowa reklamacja</span>
                                </a>
                                <a href="/admin/claims/archive" class="sidebar-link-disabled flex items-center px-3 py-2 text-sm font-medium text-gray-300 rounded-lg hover:bg-gray-700 hover:text-white transition-colors duration-200"
                                   title="W przygotowaniu"
                                   :class="{ 'justify-center': sidebarCollapsed }">
                                    <svg class="w-4 h-4 flex-shrink-0"
                                         :class="{ 'mr-0': sidebarCollapsed, 'mr-3': !sidebarCollapsed }"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                                    </svg>
                                    <span x-show="!sidebarCollapsed" x-transition class="whitespace-nowrap">Archiwum</span>
                                </a>
                            </div>

                            <!-- RAPORTY & STATYSTYKI -->
                            <div class="space-y-1 pt-4">
                                <div class="flex items-center px-3 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wide transition-opacity duration-300"
                                     :class="{ 'opacity-0 h-0 py-0 overflow-hidden': sidebarCollapsed }">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                    </svg>
                                    Raporty & Statystyki
                                </div>
                                <a href="/admin/reports/products" class="sidebar-link-disabled flex items-center px-3 py-2 text-sm font-medium text-gray-300 rounded-lg hover:bg-gray-700 hover:text-white transition-colors duration-200"
                                   title="W przygotowaniu"
                                   :class="{ 'justify-center': sidebarCollapsed }">
                                    <svg class="w-4 h-4 flex-shrink-0"
                                         :class="{ 'mr-0': sidebarCollapsed, 'mr-3': !sidebarCollapsed }"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10"></path>
                                    </svg>
                                    <span x-show="!sidebarCollapsed" x-transition class="whitespace-nowrap">Raporty produktowe</span>
                                </a>
                                <a href="/admin/reports/financial" class="sidebar-link-disabled flex items-center px-3 py-2 text-sm font-medium text-gray-300 rounded-lg hover:bg-gray-700 hover:text-white transition-colors duration-200"
                                   title="W przygotowaniu"
                                   :class="{ 'justify-center': sidebarCollapsed }">
                                    <svg class="w-4 h-4 flex-shrink-0"
                                         :class="{ 'mr-0': sidebarCollapsed, 'mr-3': !sidebarCollapsed }"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span x-show="!sidebarCollapsed" x-transition class="whitespace-nowrap">Raporty finansowe</span>
                                </a>
                                <a href="/admin/reports/warehouse" class="sidebar-link-disabled flex items-center px-3 py-2 text-sm font-medium text-gray-300 rounded-lg hover:bg-gray-700 hover:text-white transition-colors duration-200"
                                   title="W przygotowaniu"
                                   :class="{ 'justify-center': sidebarCollapsed }">
                                    <svg class="w-4 h-4 flex-shrink-0"
                                         :class="{ 'mr-0': sidebarCollapsed, 'mr-3': !sidebarCollapsed }"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                    </svg>
                                    <span x-show="!sidebarCollapsed" x-transition class="whitespace-nowrap">Raporty magazynowe</span>
                                </a>
                                <a href="/admin/reports/export" class="sidebar-link-disabled flex items-center px-3 py-2 text-sm font-medium text-gray-300 rounded-lg hover:bg-gray-700 hover:text-white transition-colors duration-200"
                                   title="W przygotowaniu"
                                   :class="{ 'justify-center': sidebarCollapsed }">
                                    <svg class="w-4 h-4 flex-shrink-0"
                                         :class="{ 'mr-0': sidebarCollapsed, 'mr-3': !sidebarCollapsed }"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                    </svg>
                                    <span x-show="!sidebarCollapsed" x-transition class="whitespace-nowrap">Eksport raportów</span>
                                </a>
                            </div>

                            <!-- System Management -->
                            <div class="space-y-1 pt-4">
                                <div class="flex items-center px-3 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wide transition-opacity duration-300"
                                     :class="{ 'opacity-0 h-0 py-0 overflow-hidden': sidebarCollapsed }">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                    System
                                </div>
                                <!-- Users -->
                                <a href="/admin/users" class="flex items-center px-3 py-2 text-sm font-medium text-gray-300 rounded-lg hover:bg-gray-700 hover:text-white transition-colors duration-200 {{ request()->is('admin/users*') ? 'bg-gray-700 text-white' : '' }}"
                                   :title="sidebarCollapsed ? 'Uzytkownicy' : ''"
                                   :class="{ 'justify-center': sidebarCollapsed }">
                                    <svg class="w-4 h-4 flex-shrink-0"
                                         :class="{ 'mr-0': sidebarCollapsed, 'mr-3': !sidebarCollapsed }"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                    </svg>
                                    <span x-show="!sidebarCollapsed" x-transition class="whitespace-nowrap">Uzytkownicy</span>
                                </a>
                                <!-- Roles -->
                                <a href="/admin/roles" class="flex items-center px-3 py-2 text-sm font-medium text-gray-300 rounded-lg hover:bg-gray-700 hover:text-white transition-colors duration-200 {{ request()->is('admin/roles*') ? 'bg-gray-700 text-white' : '' }}"
                                   :title="sidebarCollapsed ? 'Role' : ''"
                                   :class="{ 'justify-center': sidebarCollapsed }">
                                    <svg class="w-4 h-4 flex-shrink-0"
                                         :class="{ 'mr-0': sidebarCollapsed, 'mr-3': !sidebarCollapsed }"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                    </svg>
                                    <span x-show="!sidebarCollapsed" x-transition class="whitespace-nowrap">Role</span>
                                </a>
                                <!-- Permissions -->
                                <a href="/admin/permissions" class="flex items-center px-3 py-2 text-sm font-medium text-gray-300 rounded-lg hover:bg-gray-700 hover:text-white transition-colors duration-200 {{ request()->is('admin/permissions*') ? 'bg-gray-700 text-white' : '' }}"
                                   :title="sidebarCollapsed ? 'Uprawnienia' : ''"
                                   :class="{ 'justify-center': sidebarCollapsed }">
                                    <svg class="w-4 h-4 flex-shrink-0"
                                         :class="{ 'mr-0': sidebarCollapsed, 'mr-3': !sidebarCollapsed }"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                                    </svg>
                                    <span x-show="!sidebarCollapsed" x-transition class="whitespace-nowrap">Uprawnienia</span>
                                </a>
                                <!-- Sessions -->
                                <a href="/admin/sessions" class="flex items-center px-3 py-2 text-sm font-medium text-gray-300 rounded-lg hover:bg-gray-700 hover:text-white transition-colors duration-200 {{ request()->is('admin/sessions*') ? 'bg-gray-700 text-white' : '' }}"
                                   :title="sidebarCollapsed ? 'Sesje' : ''"
                                   :class="{ 'justify-center': sidebarCollapsed }">
                                    <svg class="w-4 h-4 flex-shrink-0"
                                         :class="{ 'mr-0': sidebarCollapsed, 'mr-3': !sidebarCollapsed }"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                    </svg>
                                    <span x-show="!sidebarCollapsed" x-transition class="whitespace-nowrap">Sesje</span>
                                </a>
                                <!-- Security -->
                                <a href="/admin/security" class="flex items-center px-3 py-2 text-sm font-medium text-gray-300 rounded-lg hover:bg-gray-700 hover:text-white transition-colors duration-200 {{ request()->is('admin/security*') ? 'bg-gray-700 text-white' : '' }}"
                                   :title="sidebarCollapsed ? 'Bezpieczenstwo' : ''"
                                   :class="{ 'justify-center': sidebarCollapsed }">
                                    <svg class="w-4 h-4 flex-shrink-0"
                                         :class="{ 'mr-0': sidebarCollapsed, 'mr-3': !sidebarCollapsed }"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                    </svg>
                                    <span x-show="!sidebarCollapsed" x-transition class="whitespace-nowrap">Bezpieczenstwo</span>
                                </a>
                                <!-- Audit Logs -->
                                <a href="/admin/activity-log" class="flex items-center px-3 py-2 text-sm font-medium text-gray-300 rounded-lg hover:bg-gray-700 hover:text-white transition-colors duration-200 {{ request()->is('admin/activity-log*') ? 'bg-gray-700 text-white' : '' }}"
                                   :title="sidebarCollapsed ? 'Logi audytu' : ''"
                                   :class="{ 'justify-center': sidebarCollapsed }">
                                    <svg class="w-4 h-4 flex-shrink-0"
                                         :class="{ 'mr-0': sidebarCollapsed, 'mr-3': !sidebarCollapsed }"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                                    </svg>
                                    <span x-show="!sidebarCollapsed" x-transition class="whitespace-nowrap">Logi audytu</span>
                                </a>
                                <!-- Settings -->
                                <a href="/admin/system-settings" class="flex items-center px-3 py-2 text-sm font-medium text-gray-300 rounded-lg hover:bg-gray-700 hover:text-white transition-colors duration-200 {{ request()->is('admin/system-settings*') ? 'bg-gray-700 text-white' : '' }}"
                                   :title="sidebarCollapsed ? 'Ustawienia' : ''"
                                   :class="{ 'justify-center': sidebarCollapsed }">
                                    <svg class="w-4 h-4 flex-shrink-0"
                                         :class="{ 'mr-0': sidebarCollapsed, 'mr-3': !sidebarCollapsed }"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                    <span x-show="!sidebarCollapsed" x-transition class="whitespace-nowrap">Ustawienia</span>
                                </a>
                                <!-- Backup -->
                                <a href="/admin/backup" class="flex items-center px-3 py-2 text-sm font-medium text-gray-300 rounded-lg hover:bg-gray-700 hover:text-white transition-colors duration-200 {{ request()->is('admin/backup*') ? 'bg-gray-700 text-white' : '' }}"
                                   :title="sidebarCollapsed ? 'Backup' : ''"
                                   :class="{ 'justify-center': sidebarCollapsed }">
                                    <svg class="w-4 h-4 flex-shrink-0"
                                         :class="{ 'mr-0': sidebarCollapsed, 'mr-3': !sidebarCollapsed }"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path>
                                    </svg>
                                    <span x-show="!sidebarCollapsed" x-transition class="whitespace-nowrap">Backup</span>
                                </a>
                                <!-- Maintenance -->
                                <a href="/admin/maintenance" class="flex items-center px-3 py-2 text-sm font-medium text-gray-300 rounded-lg hover:bg-gray-700 hover:text-white transition-colors duration-200 {{ request()->is('admin/maintenance*') ? 'bg-gray-700 text-white' : '' }}"
                                   :title="sidebarCollapsed ? 'Konserwacja' : ''"
                                   :class="{ 'justify-center': sidebarCollapsed }">
                                    <svg class="w-4 h-4 flex-shrink-0"
                                         :class="{ 'mr-0': sidebarCollapsed, 'mr-3': !sidebarCollapsed }"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>
                                    </svg>
                                    <span x-show="!sidebarCollapsed" x-transition class="whitespace-nowrap">Konserwacja</span>
                                </a>
                                <!-- Media -->
                                <a href="/admin/media" class="flex items-center px-3 py-2 text-sm font-medium text-gray-300 rounded-lg hover:bg-gray-700 hover:text-white transition-colors duration-200 {{ request()->is('admin/media') ? 'bg-gray-700 text-white' : '' }}"
                                   :title="sidebarCollapsed ? 'Media' : ''"
                                   :class="{ 'justify-center': sidebarCollapsed }">
                                    <svg class="w-4 h-4 flex-shrink-0"
                                         :class="{ 'mr-0': sidebarCollapsed, 'mr-3': !sidebarCollapsed }"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    <span x-show="!sidebarCollapsed" x-transition class="whitespace-nowrap">Media</span>
                                </a>
                                <!-- Bug Reports -->
                                <a href="/admin/bug-reports" class="flex items-center px-3 py-2 text-sm font-medium text-gray-300 rounded-lg hover:bg-gray-700 hover:text-white transition-colors duration-200 {{ request()->is('admin/bug-reports*') ? 'bg-gray-700 text-white' : '' }}"
                                   :title="sidebarCollapsed ? 'Zgloszenia' : ''"
                                   :class="{ 'justify-center': sidebarCollapsed }">
                                    <svg class="w-4 h-4 flex-shrink-0"
                                         :class="{ 'mr-0': sidebarCollapsed, 'mr-3': !sidebarCollapsed }"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                    </svg>
                                    <span x-show="!sidebarCollapsed" x-transition class="whitespace-nowrap">Zgloszenia</span>
                                </a>
                                <!-- ERP Integrations -->
                                <a href="/admin/integrations" class="flex items-center px-3 py-2 text-sm font-medium text-gray-300 rounded-lg hover:bg-gray-700 hover:text-white transition-colors duration-200 {{ request()->is('admin/integrations*') ? 'bg-gray-700 text-white' : '' }}"
                                   :title="sidebarCollapsed ? 'Integracje ERP' : ''"
                                   :class="{ 'justify-center': sidebarCollapsed }">
                                    <svg class="w-4 h-4 flex-shrink-0"
                                         :class="{ 'mr-0': sidebarCollapsed, 'mr-3': !sidebarCollapsed }"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z"></path>
                                    </svg>
                                    <span x-show="!sidebarCollapsed" x-transition class="whitespace-nowrap">Integracje ERP</span>
                                </a>
                                <!-- Product Scan System - ETAP_10 -->
                                <a href="/admin/scan-products" class="flex items-center px-3 py-2 text-sm font-medium text-gray-300 rounded-lg hover:bg-gray-700 hover:text-white transition-colors duration-200 {{ request()->is('admin/scan-products*') ? 'bg-gray-700 text-white' : '' }}"
                                   :title="sidebarCollapsed ? 'Skanowanie Produktów' : ''"
                                   :class="{ 'justify-center': sidebarCollapsed }">
                                    <svg class="w-4 h-4 flex-shrink-0"
                                         :class="{ 'mr-0': sidebarCollapsed, 'mr-3': !sidebarCollapsed }"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                    <span x-show="!sidebarCollapsed" x-transition class="whitespace-nowrap">Skanowanie Produktów</span>
                                </a>
                            </div>

                            <!-- PROFIL UŻYTKOWNIKA -->
                            <div class="space-y-1 pt-4">
                                <div class="flex items-center px-3 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wide transition-opacity duration-300"
                                     :class="{ 'opacity-0 h-0 py-0 overflow-hidden': sidebarCollapsed }">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                    Profil Użytkownika
                                </div>
                                <a href="/profile/edit" class="flex items-center px-3 py-2 text-sm font-medium text-gray-300 rounded-lg hover:bg-gray-700 hover:text-white transition-colors duration-200 {{ request()->is('profile/edit') ? 'bg-gray-700 text-white' : '' }}"
                                   :title="sidebarCollapsed ? 'Edycja profilu' : ''"
                                   :class="{ 'justify-center': sidebarCollapsed }">
                                    <svg class="w-4 h-4 flex-shrink-0"
                                         :class="{ 'mr-0': sidebarCollapsed, 'mr-3': !sidebarCollapsed }"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                    <span x-show="!sidebarCollapsed" x-transition class="whitespace-nowrap">Edycja profilu</span>
                                </a>
                                <a href="/profile/sessions" class="flex items-center px-3 py-2 text-sm font-medium text-gray-300 rounded-lg hover:bg-gray-700 hover:text-white transition-colors duration-200 {{ request()->is('profile/sessions') ? 'bg-gray-700 text-white' : '' }}"
                                   :title="sidebarCollapsed ? 'Aktywne sesje' : ''"
                                   :class="{ 'justify-center': sidebarCollapsed }">
                                    <svg class="w-4 h-4 flex-shrink-0"
                                         :class="{ 'mr-0': sidebarCollapsed, 'mr-3': !sidebarCollapsed }"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                    </svg>
                                    <span x-show="!sidebarCollapsed" x-transition class="whitespace-nowrap">Aktywne sesje</span>
                                </a>
                                <a href="/profile/activity" class="flex items-center px-3 py-2 text-sm font-medium text-gray-300 rounded-lg hover:bg-gray-700 hover:text-white transition-colors duration-200 {{ request()->is('profile/activity') ? 'bg-gray-700 text-white' : '' }}"
                                   :title="sidebarCollapsed ? 'Historia aktywności' : ''"
                                   :class="{ 'justify-center': sidebarCollapsed }">
                                    <svg class="w-4 h-4 flex-shrink-0"
                                         :class="{ 'mr-0': sidebarCollapsed, 'mr-3': !sidebarCollapsed }"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span x-show="!sidebarCollapsed" x-transition class="whitespace-nowrap">Historia aktywności</span>
                                </a>
                                <a href="/profile/notifications" class="flex items-center px-3 py-2 text-sm font-medium text-gray-300 rounded-lg hover:bg-gray-700 hover:text-white transition-colors duration-200 {{ request()->is('profile/notifications') ? 'bg-gray-700 text-white' : '' }}"
                                   :title="sidebarCollapsed ? 'Ustawienia powiadomień' : ''"
                                   :class="{ 'justify-center': sidebarCollapsed }">
                                    <svg class="w-4 h-4 flex-shrink-0"
                                         :class="{ 'mr-0': sidebarCollapsed, 'mr-3': !sidebarCollapsed }"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                                    </svg>
                                    <span x-show="!sidebarCollapsed" x-transition class="whitespace-nowrap">Ustawienia powiadomień</span>
                                </a>
                                <a href="/profile/bug-reports" class="flex items-center px-3 py-2 text-sm font-medium text-gray-300 rounded-lg hover:bg-gray-700 hover:text-white transition-colors duration-200 {{ request()->is('profile/bug-reports*') ? 'bg-gray-700 text-white' : '' }}"
                                   :title="sidebarCollapsed ? 'Moje zgloszenia' : ''"
                                   :class="{ 'justify-center': sidebarCollapsed }">
                                    <svg class="w-4 h-4 flex-shrink-0"
                                         :class="{ 'mr-0': sidebarCollapsed, 'mr-3': !sidebarCollapsed }"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                    </svg>
                                    <span x-show="!sidebarCollapsed" x-transition class="whitespace-nowrap">Moje zgloszenia</span>
                                </a>
                            </div>

                            <!-- POMOC -->
                            <div class="space-y-1 pt-4">
                                <div class="flex items-center px-3 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wide transition-opacity duration-300"
                                     :class="{ 'opacity-0 h-0 py-0 overflow-hidden': sidebarCollapsed }">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Pomoc
                                </div>
                                <a href="/help" class="flex items-center px-3 py-2 text-sm font-medium text-gray-300 rounded-lg hover:bg-gray-700 hover:text-white transition-colors duration-200 {{ request()->is('help') && !request()->is('help/*') ? 'bg-gray-700 text-white' : '' }}"
                                   :title="sidebarCollapsed ? 'Dokumentacja' : ''"
                                   :class="{ 'justify-center': sidebarCollapsed }">
                                    <svg class="w-4 h-4 flex-shrink-0"
                                         :class="{ 'mr-0': sidebarCollapsed, 'mr-3': !sidebarCollapsed }"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                    </svg>
                                    <span x-show="!sidebarCollapsed" x-transition class="whitespace-nowrap">Dokumentacja</span>
                                </a>
                                <a href="/help/shortcuts" class="flex items-center px-3 py-2 text-sm font-medium text-gray-300 rounded-lg hover:bg-gray-700 hover:text-white transition-colors duration-200 {{ request()->is('help/shortcuts') ? 'bg-gray-700 text-white' : '' }}"
                                   :title="sidebarCollapsed ? 'Skróty klawiszowe' : ''"
                                   :class="{ 'justify-center': sidebarCollapsed }">
                                    <svg class="w-4 h-4 flex-shrink-0"
                                         :class="{ 'mr-0': sidebarCollapsed, 'mr-3': !sidebarCollapsed }"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path>
                                    </svg>
                                    <span x-show="!sidebarCollapsed" x-transition class="whitespace-nowrap">Skróty klawiszowe</span>
                                </a>
                                <a href="/help/support" class="flex items-center px-3 py-2 text-sm font-medium text-gray-300 rounded-lg hover:bg-gray-700 hover:text-white transition-colors duration-200 {{ request()->is('help/support') ? 'bg-gray-700 text-white' : '' }}"
                                   :title="sidebarCollapsed ? 'Wsparcie techniczne' : ''"
                                   :class="{ 'justify-center': sidebarCollapsed }">
                                    <svg class="w-4 h-4 flex-shrink-0"
                                         :class="{ 'mr-0': sidebarCollapsed, 'mr-3': !sidebarCollapsed }"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                    </svg>
                                    <span x-show="!sidebarCollapsed" x-transition class="whitespace-nowrap">Wsparcie techniczne</span>
                                </a>
                            </div>
                        </nav>
                    </div>
                    
                    <!-- Sidebar Footer -->
                    <div class="mt-auto mx-4 p-3 rounded-lg border transition-opacity duration-300"
                         :class="{ 'opacity-0 h-0 p-0 m-0 overflow-hidden': sidebarCollapsed }"
                         style="background: rgba(224, 172, 126, 0.1); border-color: rgba(224, 172, 126, 0.3);">
                        <p class="text-xs font-bold" style="color: #e0ac7e;">MPP TRADE ADMIN</p>
                        <p class="text-xs text-gray-400 mt-1">Centrum kontroli systemu</p>
                    </div>
                </div>
            </aside>

            <!-- Main Content Area: Second grid column on desktop -->
            <main class="min-w-0 w-full min-h-screen p-4 sm:p-6 lg:p-8">
                @isset($slot)
                    {{ $slot }}
                @else
                    @yield('content')
                @endisset
            </main>
        </div>
    </div>
    
    <!-- Toast Notification Container -->
    <div id="toast-container"
         x-data="toastNotifications()"
         x-init="init()"
         class="fixed top-24 right-6 z-[9999999] space-y-3 pointer-events-none"
         style="max-width: min(calc(100vw - 3rem), 420px); min-width: 360px; width: 360px;">

        <template x-for="notification in notifications" :key="notification.id">
            <div x-show="notification.show"
                 x-transition:enter="transition ease-out duration-300 transform"
                 x-transition:enter-start="opacity-0 translate-x-full scale-95"
                 x-transition:enter-end="opacity-100 translate-x-0 scale-100"
                 x-transition:leave="transition ease-in duration-200 transform"
                 x-transition:leave-start="opacity-100 translate-x-0 scale-100"
                 x-transition:leave-end="opacity-0 translate-x-full scale-95"
                 class="pointer-events-auto backdrop-blur-xl rounded-xl shadow-2xl overflow-hidden border"
                 :class="{
                     'bg-gradient-to-r from-green-900/90 to-emerald-900/90 border-green-500/30': notification.type === 'success',
                     'bg-gradient-to-r from-red-900/90 to-rose-900/90 border-red-500/30': notification.type === 'error',
                     'bg-gradient-to-r from-yellow-900/90 to-amber-900/90 border-yellow-500/30': notification.type === 'warning',
                     'bg-gradient-to-r from-blue-900/90 to-cyan-900/90 border-blue-500/30': notification.type === 'info'
                 }">

                <div class="p-4">
                    <div class="flex items-start gap-3">
                        <!-- Icon -->
                        <div class="flex-shrink-0 mt-0.5">
                            <template x-if="notification.type === 'success'">
                                <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </template>
                            <template x-if="notification.type === 'error'">
                                <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </template>
                            <template x-if="notification.type === 'warning'">
                                <svg class="w-6 h-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3l-6.928-12c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                </svg>
                            </template>
                            <template x-if="notification.type === 'info'">
                                <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </template>
                        </div>

                        <!-- Message -->
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-white break-words" x-text="notification.title"></p>
                            <p class="mt-1 text-sm text-gray-300 break-words whitespace-pre-wrap overflow-wrap-anywhere"
                               style="word-break: break-word; overflow-wrap: anywhere;"
                               x-text="notification.message"></p>
                        </div>

                        <!-- Close Button -->
                        <button @click="removeNotification(notification.id)"
                                class="flex-shrink-0 rounded-lg p-1.5 hover:bg-gray-800/10 transition-colors duration-200">
                            <svg class="w-5 h-5 text-gray-400 hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div class="h-1 bg-black/20">
                    <div class="h-full transition-all ease-linear rounded-full"
                         :class="{
                             'bg-green-500': notification.type === 'success',
                             'bg-red-500': notification.type === 'error',
                             'bg-yellow-500': notification.type === 'warning',
                             'bg-blue-500': notification.type === 'info'
                         }"
                         :style="`width: ${notification.progress}%; transition-duration: ${notification.duration}ms`"></div>
                </div>
            </div>
        </template>
    </div>

    <!-- Global Components -->
    <livewire:components.error-details-modal />
    <livewire:products.category-conflict-modal />

    <!-- FIX 2025-11-25: Patch $persist redefine error in Livewire 3.x -->
    <!-- Root cause: livewire.min.js defines $persist, then Livewire.start() tries to redefine -->
    <!-- Solution: Skip $persist redefinition if already exists -->
    <script>
    (function() {
        const originalDefineProperty = Object.defineProperty;
        Object.defineProperty = function(obj, prop, descriptor) {
            if (prop === '$persist') {
                const existing = Object.getOwnPropertyDescriptor(obj, prop);
                if (existing && !existing.configurable) {
                    return obj; // Skip - already defined
                }
            }
            return originalDefineProperty.call(this, obj, prop, descriptor);
        };
    })();
    </script>

    <!-- Livewire Scripts (includes Alpine.js 3.x built-in) -->
    @livewireScripts

    {{-- Application JS (AFTER Livewire so window.Alpine is available) --}}
    @vite(['resources/js/app.js'])

    <!-- Global Notification System Script -->
    <script>
    function toastNotifications() {
        return {
            notifications: [],

            init() {
                // Listen for Livewire events (success, error, warning, info)
                document.addEventListener('livewire:init', () => {
                    // Success notifications
                    Livewire.on('success', (event) => {
                        const data = Array.isArray(event) ? event[0] : event;
                        this.showNotification('success', data.message || data, 'Sukces!');
                    });

                    // Error notifications
                    Livewire.on('error', (event) => {
                        const data = Array.isArray(event) ? event[0] : event;
                        this.showNotification('error', data.message || data, 'Błąd', 8000);
                    });

                    // Warning notifications
                    Livewire.on('warning', (event) => {
                        const data = Array.isArray(event) ? event[0] : event;
                        this.showNotification('warning', data.message || data, 'Ostrzeżenie', 6000);
                    });

                    // Info notifications
                    Livewire.on('info', (event) => {
                        const data = Array.isArray(event) ? event[0] : event;
                        this.showNotification('info', data.message || data, 'Informacja');
                    });

                    // CSV Download listener (Livewire 3.x pattern - FIXED!)
                    document.addEventListener('download-csv', (event) => {
                        const data = event.detail; // Livewire 3.x uses event.detail
                        const filename = data.filename || 'export.csv';
                        const content = data.content || '';

                        // Create blob with UTF-8 BOM for Excel compatibility
                        const BOM = '\uFEFF';
                        const blob = new Blob([BOM + content], { type: 'text/csv;charset=utf-8;' });

                        // Create download link
                        const link = document.createElement('a');
                        const url = URL.createObjectURL(blob);
                        link.setAttribute('href', url);
                        link.setAttribute('download', filename);
                        link.style.visibility = 'hidden';
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                        URL.revokeObjectURL(url);
                    });
                });
            },

            showNotification(type, message, title = '', duration = 5000) {
                const id = Date.now() + Math.random();
                const notification = {
                    id: id,
                    type: type,
                    title: title,
                    message: message,
                    show: true,
                    progress: 100,
                    duration: duration
                };

                this.notifications.push(notification);

                // Animate progress bar
                setTimeout(() => {
                    const notif = this.notifications.find(n => n.id === id);
                    if (notif) notif.progress = 0;
                }, 50);

                // Auto-remove after duration
                setTimeout(() => {
                    this.removeNotification(id);
                }, duration);
            },

            removeNotification(id) {
                const index = this.notifications.findIndex(n => n.id === id);
                if (index > -1) {
                    this.notifications[index].show = false;
                    // Remove from array after animation
                    setTimeout(() => {
                        this.notifications.splice(index, 1);
                    }, 300);
                }
            }
        }
    }

    // Global helper functions
    window.notify = {
        success: (message, title = 'Sukces!') => {
            Livewire.dispatch('success', { message, title });
        },
        error: (message, title = 'Błąd') => {
            Livewire.dispatch('error', { message, title });
        },
        warning: (message, title = 'Ostrzeżenie') => {
            Livewire.dispatch('warning', { message, title });
        },
        info: (message, title = 'Informacja') => {
            Livewire.dispatch('info', { message, title });
        }
    };
    </script>

    <!-- Additional Scripts -->
    @stack('scripts')

    <!-- Scripts Stack for Components -->
    @stack('component-scripts')

    {{-- Bug Report Modal (Global) - works in dev mode without auth --}}
    @livewire('bug-reports.bug-report-modal')

    {{-- Floating Bug Report Button --}}
    <button type="button"
            x-data
            @click="$dispatch('open-bug-report-modal')"
            class="fixed bottom-6 right-6 layer-panel w-14 h-14 rounded-full shadow-lg flex items-center justify-center transition-all duration-200 hover:scale-110 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-900"
            style="background: linear-gradient(135deg, #e0ac7e 0%, #d1975a 50%, #c08449 100%); z-index: 99999;"
            title="Zglos problem lub pomysl"
            aria-label="Zglos problem">
        <svg class="w-7 h-7 text-gray-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
    </button>

</body>
</html>
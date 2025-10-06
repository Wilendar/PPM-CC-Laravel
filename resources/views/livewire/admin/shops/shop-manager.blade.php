<div x-data="{
        showWizard: @entangle('showAddShop').defer,
        showDetails: @entangle('showShopDetails').defer,
        showDeleteConfirm: @entangle('showDeleteConfirm').defer,
        search: @entangle('search').defer,
        statusFilter: @entangle('statusFilter').defer,
        testingConnection: @entangle('testingConnection').defer,
        syncingShop: @entangle('syncingShop').defer
    }" 
    class="min-h-screen bg-gradient-to-br from-gray-900 via-gray-800 to-black relative">
    
    <!-- Animated Background Elements with MPP TRADE Colors -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-32 -right-32 w-64 h-64 rounded-full blur-3xl animate-pulse" style="background: radial-gradient(circle, rgba(224, 172, 126, 0.1), rgba(209, 151, 90, 0.05));"></div>
        <div class="absolute -bottom-32 -left-32 w-64 h-64 rounded-full blur-3xl animate-pulse" style="background: radial-gradient(circle, rgba(209, 151, 90, 0.1), rgba(224, 172, 126, 0.05)); animation-delay: 2s;"></div>
        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-96 h-96 rounded-full blur-3xl animate-pulse" style="background: radial-gradient(circle, rgba(224, 172, 126, 0.05), rgba(209, 151, 90, 0.03)); animation-delay: 4s;"></div>
    </div>
    
    <!-- Page Header -->
    <div class="relative backdrop-blur-xl shadow-2xl" style="background: linear-gradient(135deg, rgba(31, 41, 55, 0.95), rgba(17, 24, 39, 0.95)); border-bottom: 1px solid rgba(224, 172, 126, 0.3); z-index: 1;">
        <div class="max-w-7xl mx-auto px-6 sm:px-8 lg:px-12">
            <div class="flex items-center justify-between h-24">
                <div class="flex items-center">
                    <!-- Logo and Title -->
                    <div class="flex-shrink-0">
                        <div class="relative w-12 h-12 rounded-xl flex items-center justify-center shadow-lg transform transition-transform duration-300 hover:scale-105" style="background: linear-gradient(45deg, #e0ac7e, #d1975a);">
                            <svg class="w-7 h-7 text-white relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                            <div class="absolute inset-0 rounded-xl opacity-75 blur animate-pulse" style="background: linear-gradient(45deg, #e0ac7e, #d1975a);"></div>
                        </div>
                    </div>
                    <div class="ml-4 flex-1 min-w-0">
                        <h1 class="text-xl font-bold tracking-tight" style="color: #e0ac7e !important;">
                            SKLEPY PRESTASHOP
                        </h1>
                        <p class="text-xs font-medium text-gray-400 tracking-wide">
                            Zarządzanie połączeniami ze sklepami PrestaShop
                        </p>
                    </div>
                </div>
                
                <!-- Add Shop Button -->
                <button wire:click="startWizard" 
                        class="relative inline-flex items-center px-6 py-3 border border-transparent text-sm font-bold rounded-lg text-white transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl"
                        style="background: linear-gradient(45deg, #e0ac7e, #d1975a);"
                        onmouseover="this.style.background='linear-gradient(45deg, #d1975a, #c08449)'"
                        onmouseout="this.style.background='linear-gradient(45deg, #e0ac7e, #d1975a)'">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Dodaj Sklep
                </button>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="relative z-10 px-6 sm:px-8 lg:px-12 py-8">

            <!-- Flash Messages -->
            @if (session()->has('success'))
            <div class="mb-6 backdrop-blur-xl rounded-2xl p-4 shadow-2xl relative overflow-hidden"
                 style="background: linear-gradient(135deg, rgba(34, 197, 94, 0.8), rgba(21, 128, 61, 0.6)); border: 1px solid rgba(34, 197, 94, 0.3);"
                 x-data="{ show: true }"
                 x-show="show"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform scale-95"
                 x-transition:enter-end="opacity-100 transform scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 transform scale-100"
                 x-transition:leave-end="opacity-0 transform scale-95">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-white">
                                {{ session('success') }}
                            </p>
                        </div>
                    </div>
                    <div class="flex-shrink-0">
                        <button @click="show = false" class="inline-flex text-white hover:text-green-200 focus:outline-none transition-colors duration-200">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
            @endif

            @if (session()->has('error'))
            <div class="mb-6 backdrop-blur-xl rounded-2xl p-4 shadow-2xl relative overflow-hidden"
                 style="background: linear-gradient(135deg, rgba(239, 68, 68, 0.8), rgba(185, 28, 28, 0.6)); border: 1px solid rgba(239, 68, 68, 0.3);"
                 x-data="{ show: true }"
                 x-show="show"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform scale-95"
                 x-transition:enter-end="opacity-100 transform scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 transform scale-100"
                 x-transition:leave-end="opacity-0 transform scale-95">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-white">
                                {{ session('error') }}
                            </p>
                        </div>
                    </div>
                    <div class="flex-shrink-0">
                        <button @click="show = false" class="inline-flex text-white hover:text-red-200 focus:outline-none transition-colors duration-200">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
            @endif

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-8">
                <!-- Total Shops -->
                <div class="backdrop-blur-xl rounded-2xl p-6 shadow-2xl relative overflow-hidden" 
                     style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.8), rgba(29, 78, 216, 0.6)); border: 1px solid rgba(59, 130, 246, 0.2);">
                    <div class="relative z-10 flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-14 h-14 bg-gradient-to-br from-blue-400 to-blue-600 rounded-xl flex items-center justify-center shadow-xl">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4 flex-1">
                            <p class="text-2xl font-black mb-1 text-white">
                                {{ number_format($stats['total'] ?? 0) }}
                            </p>
                            <p class="text-sm font-medium text-blue-200">
                                Wszystkie sklepy
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Active Shops -->
                <div class="backdrop-blur-xl rounded-2xl p-6 shadow-2xl relative overflow-hidden" 
                     style="background: linear-gradient(135deg, rgba(34, 197, 94, 0.8), rgba(21, 128, 61, 0.6)); border: 1px solid rgba(34, 197, 94, 0.2);">
                    <div class="relative z-10 flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-14 h-14 bg-gradient-to-br from-green-400 to-green-600 rounded-xl flex items-center justify-center shadow-xl">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4 flex-1">
                            <p class="text-2xl font-black mb-1 text-white">
                                {{ number_format($stats['active'] ?? 0) }}
                            </p>
                            <p class="text-sm font-medium text-green-200">
                                Aktywne
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Connected Shops -->
                <div class="backdrop-blur-xl rounded-2xl p-6 shadow-2xl relative overflow-hidden" 
                     style="background: linear-gradient(135deg, rgba(6, 182, 212, 0.8), rgba(8, 145, 178, 0.6)); border: 1px solid rgba(6, 182, 212, 0.2);">
                    <div class="relative z-10 flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-14 h-14 bg-gradient-to-br from-cyan-400 to-cyan-600 rounded-xl flex items-center justify-center shadow-xl">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4 flex-1">
                            <p class="text-2xl font-black mb-1 text-white">
                                {{ number_format($stats['connected'] ?? 0) }}
                            </p>
                            <p class="text-sm font-medium text-cyan-200">
                                Połączone
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Issues -->
                <div class="backdrop-blur-xl rounded-2xl p-6 shadow-2xl relative overflow-hidden" 
                     style="background: linear-gradient(135deg, rgba(245, 158, 11, 0.8), rgba(217, 119, 6, 0.6)); border: 1px solid rgba(245, 158, 11, 0.2);">
                    <div class="relative z-10 flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-14 h-14 bg-gradient-to-br from-yellow-400 to-yellow-600 rounded-xl flex items-center justify-center shadow-xl">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4 flex-1">
                            <p class="text-2xl font-black mb-1 text-white">
                                {{ number_format($stats['issues'] ?? 0) }}
                            </p>
                            <p class="text-sm font-medium text-yellow-200">
                                Problemy
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Sync Due -->
                <div class="backdrop-blur-xl rounded-2xl p-6 shadow-2xl relative overflow-hidden" 
                     style="background: linear-gradient(135deg, rgba(168, 85, 247, 0.8), rgba(124, 58, 237, 0.6)); border: 1px solid rgba(168, 85, 247, 0.2);">
                    <div class="relative z-10 flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-14 h-14 bg-gradient-to-br from-purple-400 to-purple-600 rounded-xl flex items-center justify-center shadow-xl">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4 flex-1">
                            <p class="text-2xl font-black mb-1 text-white">
                                {{ number_format($stats['sync_due'] ?? 0) }}
                            </p>
                            <p class="text-sm font-medium text-purple-200">
                                Do synchronizacji
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters and Search -->
            <div class="backdrop-blur-xl rounded-2xl p-6 shadow-2xl mb-8 relative overflow-hidden" 
                 style="background: linear-gradient(135deg, rgba(31, 41, 55, 0.8), rgba(17, 24, 39, 0.6)); border: 1px solid rgba(224, 172, 126, 0.2);">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between space-y-4 lg:space-y-0 lg:space-x-6">
                    <!-- Search -->
                    <div class="flex-1 max-w-md">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </div>
                            <input wire:model.debounce.300ms="search"
                                   type="text" 
                                   class="block w-full pl-10 pr-3 py-3 border border-gray-600 rounded-lg bg-gray-800 text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:border-transparent transition-colors duration-200" 
                                   style="focus:ring-color: #e0ac7e;"
                                   placeholder="Szukaj sklepów...">
                        </div>
                    </div>

                    <!-- Status Filter -->
                    <div class="flex-shrink-0">
                        <select wire:model="statusFilter" 
                                class="block w-full px-4 py-3 border border-gray-600 rounded-lg bg-gray-800 text-white focus:outline-none focus:ring-2 focus:border-transparent transition-colors duration-200"
                                style="focus:ring-color: #e0ac7e;">
                            <option value="all">Wszystkie statusy</option>
                            <option value="active">Aktywne</option>
                            <option value="inactive">Nieaktywne</option>
                            <option value="connected">Połączone</option>
                            <option value="issues">Z problemami</option>
                            <option value="sync_due">Do synchronizacji</option>
                        </select>
                    </div>

                    <!-- Sort Options -->
                    <div class="flex-shrink-0">
                        <select wire:model="sortBy" 
                                class="block w-full px-4 py-3 border border-gray-600 rounded-lg bg-gray-800 text-white focus:outline-none focus:ring-2 focus:border-transparent transition-colors duration-200"
                                style="focus:ring-color: #e0ac7e;">
                            <option value="name">Sortuj: Nazwa</option>
                            <option value="created_at">Sortuj: Data dodania</option>
                            <option value="last_sync_at">Sortuj: Ostatnia sync</option>
                            <option value="connection_status">Sortuj: Status</option>
                        </select>
                    </div>

                    <!-- Reset Button -->
                    <div class="flex-shrink-0">
                        <button wire:click="resetFilters" 
                                class="px-6 py-3 bg-gray-700 hover:bg-gray-600 text-white font-medium rounded-lg transition-colors duration-200 flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            <span>Reset</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Shops List -->
            <div class="backdrop-blur-xl rounded-2xl shadow-2xl relative overflow-hidden" 
                 style="background: linear-gradient(135deg, rgba(31, 41, 55, 0.8), rgba(17, 24, 39, 0.6)); border: 1px solid rgba(224, 172, 126, 0.2);">
                 
                <!-- Header -->
                <div class="px-6 py-4 border-b" style="border-color: rgba(224, 172, 126, 0.2);">
                    <h3 class="text-lg font-bold text-white flex items-center">
                        <svg class="w-5 h-5 mr-2" style="color: #e0ac7e;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                        <span style="color: #e0ac7e;">Lista Sklepów</span>
                    </h3>
                </div>

                <!-- Content -->
                <div class="p-0">
                    @if($shops->count() > 0)
                        <!-- Desktop Table -->
                        <div class="hidden lg:block overflow-x-auto">
                            <table class="min-w-full">
                                <thead class="bg-gray-800 bg-opacity-50">
                                    <tr class="border-b" style="border-color: rgba(224, 172, 126, 0.1);">
                                        <th wire:click="sortBy('name')" 
                                            class="px-6 py-4 text-left text-sm font-medium text-gray-300 uppercase tracking-wider cursor-pointer hover:text-white transition-colors duration-200">
                                            <div class="flex items-center space-x-1">
                                                <span>Nazwa</span>
                                                @if($sortBy === 'name')
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        @if($sortDirection === 'asc')
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                                        @else
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                        @endif
                                                    </svg>
                                                @endif
                                            </div>
                                        </th>
                                        <th class="px-6 py-4 text-left text-sm font-medium text-gray-300 uppercase tracking-wider">URL</th>
                                        <th class="px-6 py-4 text-left text-sm font-medium text-gray-300 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-4 text-left text-sm font-medium text-gray-300 uppercase tracking-wider">Wersja PS</th>
                                        <th wire:click="sortBy('last_sync_at')" 
                                            class="px-6 py-4 text-left text-sm font-medium text-gray-300 uppercase tracking-wider cursor-pointer hover:text-white transition-colors duration-200">
                                            <div class="flex items-center space-x-1">
                                                <span>Ostatnia Sync</span>
                                                @if($sortBy === 'last_sync_at')
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        @if($sortDirection === 'asc')
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                                        @else
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                        @endif
                                                    </svg>
                                                @endif
                                            </div>
                                        </th>
                                        <th class="px-6 py-4 text-left text-sm font-medium text-gray-300 uppercase tracking-wider">Sukces Rate</th>
                                        <th class="px-6 py-4 text-left text-sm font-medium text-gray-300 uppercase tracking-wider">Akcje</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-700">
                                    @foreach($shops as $shop)
                                    <tr class="hover:bg-gray-800 hover:bg-opacity-30 transition-colors duration-200">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="mr-4">
                                                    @if($shop->is_active)
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                                            </svg>
                                                            Aktywny
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                                            </svg>
                                                            Nieaktywny
                                                        </span>
                                                    @endif
                                                </div>
                                                <div>
                                                    <div class="text-sm font-medium text-white">{{ $shop->name }}</div>
                                                    @if($shop->description)
                                                        <div class="text-sm text-gray-400">{{ \Str::limit($shop->description, 50) }}</div>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <a href="{{ $shop->url }}" target="_blank" class="text-cyan-400 hover:text-cyan-300 transition-colors duration-200 flex items-center">
                                                {{ \Str::limit($shop->url, 40) }}
                                                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                                </svg>
                                            </a>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @switch($shop->connection_status)
                                                @case('connected')
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                                        </svg>
                                                        Połączony
                                                    </span>
                                                    @break
                                                @case('disconnected')
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                                        </svg>
                                                        Brak połączenia
                                                    </span>
                                                    @break
                                                @case('error')
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                                        </svg>
                                                        Błąd połączenia
                                                    </span>
                                                    @break
                                                @case('maintenance')
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                                        </svg>
                                                        Konserwacja
                                                    </span>
                                                    @break
                                                @default
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                                        </svg>
                                                        Nieznany status
                                                    </span>
                                            @endswitch
                                            @if($shop->last_response_time)
                                                <div class="text-xs text-gray-400 mt-1">{{ $shop->last_response_time }}ms</div>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($shop->prestashop_version)
                                                @if($shop->version_compatible)
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                        v{{ $shop->prestashop_version }}
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                        v{{ $shop->prestashop_version }}
                                                    </span>
                                                @endif
                                            @else
                                                <span class="text-gray-400 text-sm">Nieznana</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($shop->last_sync_at)
                                                <div class="text-sm text-white">{{ $shop->last_sync_at->diffForHumans() }}</div>
                                                <div class="text-xs text-gray-400">{{ $shop->products_synced ?? 0 }} produktów</div>
                                            @else
                                                <span class="text-gray-400 text-sm">Nigdy</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if(($shop->sync_success_rate ?? 0) > 0)
                                                <div class="flex items-center">
                                                    <div class="flex-grow bg-gray-700 rounded-full h-2 mr-2">
                                                        <div class="h-2 rounded-full @if($shop->sync_success_rate >= 90) bg-green-500 @elseif($shop->sync_success_rate >= 70) bg-yellow-500 @else bg-red-500 @endif" 
                                                             style="width: {{ $shop->sync_success_rate }}%"></div>
                                                    </div>
                                                    <span class="text-sm text-white">{{ $shop->sync_success_rate }}%</span>
                                                </div>
                                            @else
                                                <span class="text-gray-400 text-sm">N/A</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center space-x-2">
                                                <!-- Details Button -->
                                                <button wire:click="showDetails({{ $shop->id }})" 
                                                        class="p-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors duration-200" 
                                                        title="Szczegóły">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                </button>
                                                
                                                <!-- Test Connection Button -->
                                                <button wire:click="testConnection({{ $shop->id }})" 
                                                        class="p-2 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg transition-colors duration-200"
                                                        wire:loading.attr="disabled"
                                                        wire:target="testConnection({{ $shop->id }})"
                                                        title="Test połączenia">
                                                    <svg wire:loading.remove wire:target="testConnection({{ $shop->id }})" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                                                    </svg>
                                                    <svg wire:loading wire:target="testConnection({{ $shop->id }})" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                    </svg>
                                                </button>

                                                <!-- Sync Button -->
                                                <button wire:click="syncShop({{ $shop->id }})" 
                                                        class="p-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors duration-200"
                                                        wire:loading.attr="disabled"
                                                        wire:target="syncShop({{ $shop->id }})"
                                                        title="Synchronizuj">
                                                    <svg wire:loading.remove wire:target="syncShop({{ $shop->id }})" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                                    </svg>
                                                    <svg wire:loading wire:target="syncShop({{ $shop->id }})" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                    </svg>
                                                </button>

                                                <!-- Toggle Status Button -->
                                                <button wire:click="toggleShopStatus({{ $shop->id }})" 
                                                        class="p-2 @if($shop->is_active) bg-yellow-600 hover:bg-yellow-700 @else bg-green-600 hover:bg-green-700 @endif text-white rounded-lg transition-colors duration-200"
                                                        title="@if($shop->is_active) Dezaktywuj @else Aktywuj @endif">
                                                    @if($shop->is_active)
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                        </svg>
                                                    @else
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1m4 0h1m-6 4h1m4 0h1m-7 4h8a2 2 0 002-2V8a2 2 0 00-2-2H8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                                        </svg>
                                                    @endif
                                                </button>

                                                <!-- Edit Button -->
                                                <button wire:click="editShop({{ $shop->id }})" 
                                                        class="p-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded-lg transition-colors duration-200"
                                                        title="Edytuj sklep">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                    </svg>
                                                </button>

                                                <!-- Delete Button -->
                                                <button wire:click="confirmDeleteShop({{ $shop->id }})" 
                                                        class="p-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors duration-200"
                                                        title="Usuń sklep">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Mobile Cards -->
                        <div class="block lg:hidden space-y-4 p-6">
                            @foreach($shops as $shop)
                            <div class="backdrop-blur-xl rounded-2xl p-6 shadow-2xl relative overflow-hidden" 
                                 style="background: linear-gradient(135deg, rgba(55, 65, 81, 0.8), rgba(31, 41, 55, 0.6)); border: 1px solid rgba(224, 172, 126, 0.2);">
                                <!-- Shop Header -->
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center space-x-3">
                                        @if($shop->is_active)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                Aktywny
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                Nieaktywny
                                            </span>
                                        @endif
                                        @switch($shop->connection_status)
                                            @case('connected')
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    Połączony
                                                </span>
                                                @break
                                            @case('disconnected')
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                    Brak połączenia
                                                </span>
                                                @break
                                            @case('error')
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                    Błąd
                                                </span>
                                                @break
                                            @case('maintenance')
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                    Konserwacja
                                                </span>
                                                @break
                                            @default
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                    Nieznany
                                                </span>
                                        @endswitch
                                    </div>
                                </div>

                                <!-- Shop Details -->
                                <div class="space-y-3">
                                    <div>
                                        <h3 class="text-lg font-bold text-white">{{ $shop->name }}</h3>
                                        @if($shop->description)
                                            <p class="text-sm text-gray-400 mt-1">{{ $shop->description }}</p>
                                        @endif
                                    </div>

                                    <div class="flex items-center text-sm text-gray-300">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                        </svg>
                                        <a href="{{ $shop->url }}" target="_blank" class="text-cyan-400 hover:text-cyan-300 transition-colors duration-200">
                                            {{ $shop->url }}
                                        </a>
                                    </div>

                                    @if($shop->prestashop_version)
                                    <div class="flex items-center text-sm">
                                        <span class="text-gray-400 mr-2">PrestaShop:</span>
                                        @if($shop->version_compatible)
                                            <span class="text-green-400">v{{ $shop->prestashop_version }}</span>
                                        @else
                                            <span class="text-yellow-400">v{{ $shop->prestashop_version }}</span>
                                        @endif
                                    </div>
                                    @endif

                                    @if($shop->last_response_time)
                                    <div class="flex items-center text-sm">
                                        <span class="text-gray-400 mr-2">Czas odpowiedzi:</span>
                                        <span class="text-white">{{ $shop->last_response_time }}ms</span>
                                    </div>
                                    @endif

                                    @if($shop->last_sync_at)
                                    <div class="flex items-center text-sm">
                                        <span class="text-gray-400 mr-2">Ostatnia sync:</span>
                                        <span class="text-white">{{ $shop->last_sync_at->diffForHumans() }}</span>
                                    </div>
                                    @endif
                                </div>

                                <!-- Action Buttons -->
                                <div class="flex items-center space-x-2 mt-6 pt-4 border-t border-gray-600">
                                    <button wire:click="showDetails({{ $shop->id }})" 
                                            class="px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded-lg transition-colors duration-200">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </button>
                                    <button wire:click="testConnection({{ $shop->id }})" 
                                            class="px-3 py-2 bg-cyan-600 hover:bg-cyan-700 text-white text-xs font-medium rounded-lg transition-colors duration-200"
                                            wire:loading.attr="disabled"
                                            wire:target="testConnection({{ $shop->id }})"
                                            title="Test połączenia">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"></path>
                                        </svg>
                                    </button>
                                    <button wire:click="syncShop({{ $shop->id }})" 
                                            class="px-3 py-2 bg-green-600 hover:bg-green-700 text-white text-xs font-medium rounded-lg transition-colors duration-200"
                                            wire:loading.attr="disabled"
                                            wire:target="syncShop({{ $shop->id }})"
                                            title="Synchronizuj">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                        </svg>
                                    </button>
                                    <button wire:click="editShop({{ $shop->id }})" 
                                            class="px-3 py-2 bg-yellow-600 hover:bg-yellow-700 text-white text-xs font-medium rounded-lg transition-colors duration-200"
                                            title="Edytuj sklep">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </button>
                                    <button wire:click="confirmDeleteShop({{ $shop->id }})" 
                                            class="px-3 py-2 bg-red-600 hover:bg-red-700 text-white text-xs font-medium rounded-lg transition-colors duration-200"
                                            title="Usuń sklep">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            @endforeach
                        </div>

                        <!-- Pagination -->
                        <div class="px-6 py-4 border-t" style="border-color: rgba(224, 172, 126, 0.2);">
                            {{ $shops->links() }}
                        </div>

                    @else
                        <!-- Empty State -->
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-white">Brak sklepów</h3>
                            <p class="mt-1 text-sm text-gray-400">
                                @if($search || $statusFilter !== 'all')
                                    Nie znaleziono sklepów spełniających kryteria wyszukiwania.
                                @else
                                    Rozpocznij od dodania swojego pierwszego sklepu PrestaShop.
                                @endif
                            </p>
                            @if(!$search && $statusFilter === 'all')
                            <div class="mt-6">
                                <button wire:click="startWizard" 
                                        class="inline-flex items-center px-6 py-3 border border-transparent text-sm font-medium rounded-lg text-white transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl"
                                        style="background: linear-gradient(45deg, #e0ac7e, #d1975a);">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                    Dodaj pierwszy sklep
                                </button>
                            </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            <!-- Pagination -->
            @if($shops->hasPages())
            <div class="mt-6">
                {{ $shops->links() }}
            </div>
            @endif
    </div>

    <!-- Add Shop Wizard Modal -->
    @if($showAddShop)
    <!-- Modal będzie dodany w następnym kroku -->
    @endif

    <!-- Shop Details Modal -->
    @if($showShopDetails && $selectedShop)
    <div class="fixed inset-0 z-50 overflow-y-auto" style="z-index: 9999;">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div class="fixed inset-0 bg-black bg-opacity-75 transition-opacity" wire:click="closeDetails"></div>

            <!-- Modal -->
            <div class="inline-block align-bottom bg-gray-800 rounded-lg px-6 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full sm:p-6"
                 style="background: linear-gradient(135deg, rgba(31, 41, 55, 0.95), rgba(17, 24, 39, 0.95)); border: 1px solid rgba(224, 172, 126, 0.3);">

                <!-- Header -->
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-xl font-bold text-white">Szczegóły sklepu</h3>
                        <p class="text-sm text-gray-400 mt-1">{{ $selectedShop->name }}</p>
                    </div>
                    <button wire:click="closeDetails" class="p-2 text-gray-400 hover:text-white transition-colors duration-200">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Content Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Basic Information -->
                    <div class="space-y-4">
                        <div class="backdrop-blur-xl rounded-xl p-4" style="background: linear-gradient(135deg, rgba(55, 65, 81, 0.6), rgba(31, 41, 55, 0.4)); border: 1px solid rgba(224, 172, 126, 0.2);">
                            <h4 class="text-lg font-semibold text-white mb-3">Podstawowe informacje</h4>

                            <dl class="space-y-3">
                                <div>
                                    <dt class="text-sm font-medium text-gray-400">Nazwa sklepu</dt>
                                    <dd class="text-sm text-white">{{ $selectedShop->name }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-400">URL</dt>
                                    <dd class="text-sm">
                                        <a href="{{ $selectedShop->url }}" target="_blank" class="text-cyan-400 hover:text-cyan-300">
                                            {{ $selectedShop->url }}
                                        </a>
                                    </dd>
                                </div>
                                @if($selectedShop->description)
                                <div>
                                    <dt class="text-sm font-medium text-gray-400">Opis</dt>
                                    <dd class="text-sm text-white">{{ $selectedShop->description }}</dd>
                                </div>
                                @endif
                                <div>
                                    <dt class="text-sm font-medium text-gray-400">Status</dt>
                                    <dd class="text-sm">
                                        @if($selectedShop->is_active)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                Aktywny
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                Nieaktywny
                                            </span>
                                        @endif
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-400">Wersja PrestaShop</dt>
                                    <dd class="text-sm text-white">{{ $selectedShop->prestashop_version ?? 'Nieznana' }}</dd>
                                </div>
                            </dl>
                        </div>

                        <!-- Connection Status -->
                        <div class="backdrop-blur-xl rounded-xl p-4" style="background: linear-gradient(135deg, rgba(55, 65, 81, 0.6), rgba(31, 41, 55, 0.4)); border: 1px solid rgba(224, 172, 126, 0.2);">
                            <h4 class="text-lg font-semibold text-white mb-3">Status połączenia</h4>

                            <dl class="space-y-3">
                                <div>
                                    <dt class="text-sm font-medium text-gray-400">Status</dt>
                                    <dd class="text-sm">
                                        @switch($selectedShop->connection_status)
                                            @case('connected')
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    Połączony
                                                </span>
                                                @break
                                            @case('disconnected')
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                    Brak połączenia
                                                </span>
                                                @break
                                            @case('error')
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                    Błąd połączenia
                                                </span>
                                                @break
                                            @case('maintenance')
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                    Konserwacja
                                                </span>
                                                @break
                                            @default
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                    Nieznany status
                                                </span>
                                        @endswitch
                                    </dd>
                                </div>
                                @if($selectedShop->last_response_time)
                                <div>
                                    <dt class="text-sm font-medium text-gray-400">Czas odpowiedzi</dt>
                                    <dd class="text-sm text-white">{{ $selectedShop->last_response_time }}ms</dd>
                                </div>
                                @endif
                                @if($selectedShop->last_connection_test)
                                <div>
                                    <dt class="text-sm font-medium text-gray-400">Ostatni test</dt>
                                    <dd class="text-sm text-white">{{ $selectedShop->last_connection_test->diffForHumans() }}</dd>
                                </div>
                                @endif
                                @if($selectedShop->last_error)
                                <div>
                                    <dt class="text-sm font-medium text-gray-400">Ostatni błąd</dt>
                                    <dd class="text-sm text-red-400">{{ $selectedShop->last_error }}</dd>
                                </div>
                                @endif
                            </dl>
                        </div>
                    </div>

                    <!-- Sync Information & Recent Jobs -->
                    <div class="space-y-4">
                        <!-- Sync Statistics -->
                        <div class="backdrop-blur-xl rounded-xl p-4" style="background: linear-gradient(135deg, rgba(55, 65, 81, 0.6), rgba(31, 41, 55, 0.4)); border: 1px solid rgba(224, 172, 126, 0.2);">
                            <h4 class="text-lg font-semibold text-white mb-3">Statystyki synchronizacji</h4>

                            <dl class="space-y-3">
                                <div>
                                    <dt class="text-sm font-medium text-gray-400">Ostatnia synchronizacja</dt>
                                    <dd class="text-sm text-white">
                                        {{ $selectedShop->last_sync_at ? $selectedShop->last_sync_at->diffForHumans() : 'Nigdy' }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-400">Produkty zsynchronizowane</dt>
                                    <dd class="text-sm text-white">{{ $selectedShop->products_synced ?? 0 }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-400">Wskaźnik sukcesu</dt>
                                    <dd class="text-sm">
                                        @if(($selectedShop->sync_success_rate ?? 0) > 0)
                                            <div class="flex items-center">
                                                <div class="flex-grow bg-gray-700 rounded-full h-2 mr-2">
                                                    <div class="h-2 rounded-full @if($selectedShop->sync_success_rate >= 90) bg-green-500 @elseif($selectedShop->sync_success_rate >= 70) bg-yellow-500 @else bg-red-500 @endif"
                                                         style="width: {{ $selectedShop->sync_success_rate }}%"></div>
                                                </div>
                                                <span class="text-white">{{ $selectedShop->sync_success_rate }}%</span>
                                            </div>
                                        @else
                                            <span class="text-gray-400">N/A</span>
                                        @endif
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-400">Następna synchronizacja</dt>
                                    <dd class="text-sm text-white">
                                        {{ $selectedShop->next_scheduled_sync ? $selectedShop->next_scheduled_sync->diffForHumans() : 'Nie zaplanowano' }}
                                    </dd>
                                </div>
                            </dl>
                        </div>

                        <!-- Recent Sync Jobs -->
                        @if($selectedShop->syncJobs && $selectedShop->syncJobs->count() > 0)
                        <div class="backdrop-blur-xl rounded-xl p-4" style="background: linear-gradient(135deg, rgba(55, 65, 81, 0.6), rgba(31, 41, 55, 0.4)); border: 1px solid rgba(224, 172, 126, 0.2);">
                            <h4 class="text-lg font-semibold text-white mb-3">Ostatnie zadania synchronizacji</h4>

                            <div class="space-y-2">
                                @foreach($selectedShop->syncJobs as $job)
                                <div class="flex items-center justify-between py-2 px-3 rounded-lg bg-gray-700 bg-opacity-40">
                                    <div>
                                        <div class="text-sm font-medium text-white">{{ $job->job_name }}</div>
                                        <div class="text-xs text-gray-400">{{ $job->created_at->diffForHumans() }}</div>
                                    </div>
                                    <div>
                                        @if($job->status === 'completed')
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                Ukończone
                                            </span>
                                        @elseif($job->status === 'running')
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                W trakcie
                                            </span>
                                        @elseif($job->status === 'failed')
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                Błąd
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                Oczekuje
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Advanced Connection Metrics Section - SEKCJA 2.1.1.2 -->
                @if(isset($selectedShop->connection_details))
                <div class="mt-8 pt-6 border-t border-gray-600">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xl font-bold text-white">Zaawansowane metryki połączenia</h3>
                        <span class="text-xs text-gray-400">SEKCJA 2.1.1.2 - Connection Status Details</span>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-4">
                        <!-- API Version Compatibility -->
                        <div class="backdrop-blur-xl rounded-xl p-4" style="background: linear-gradient(135deg, rgba(55, 65, 81, 0.6), rgba(31, 41, 55, 0.4)); border: 1px solid rgba(224, 172, 126, 0.2);">
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="text-lg font-semibold text-white">Kompatybilność API</h4>
                                @switch($selectedShop->connection_details['api_version_check']['status'])
                                    @case('compatible')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Kompatybilny</span>
                                        @break
                                    @case('incompatible')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Niekompatybilny</span>
                                        @break
                                    @default
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Nieznany</span>
                                @endswitch
                            </div>
                            <div class="text-sm text-gray-300 mb-3">
                                {{ $selectedShop->connection_details['api_version_check']['message'] }}
                            </div>
                            @if(count($selectedShop->connection_details['api_version_check']['recommendations']) > 0)
                                <div class="text-xs text-yellow-400">
                                    <strong>Zalecenia:</strong>
                                    <ul class="mt-1 list-disc list-inside">
                                        @foreach($selectedShop->connection_details['api_version_check']['recommendations'] as $recommendation)
                                            <li>{{ $recommendation }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>

                        <!-- SSL/TLS Status -->
                        <div class="backdrop-blur-xl rounded-xl p-4" style="background: linear-gradient(135deg, rgba(55, 65, 81, 0.6), rgba(31, 41, 55, 0.4)); border: 1px solid rgba(224, 172, 126, 0.2);">
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="text-lg font-semibold text-white">SSL/TLS</h4>
                                @switch($selectedShop->connection_details['ssl_tls_status']['security_level'])
                                    @case('high')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Wysoki</span>
                                        @break
                                    @case('medium')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Średni</span>
                                        @break
                                    @case('low')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">Niski</span>
                                        @break
                                    @case('critical')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Krytyczny</span>
                                        @break
                                    @default
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Nieznany</span>
                                @endswitch
                            </div>
                            <div class="text-sm text-gray-300 mb-3">
                                {{ $selectedShop->connection_details['ssl_tls_status']['message'] }}
                            </div>
                            @if(count($selectedShop->connection_details['ssl_tls_status']['recommendations']) > 0)
                                <div class="text-xs text-yellow-400">
                                    <strong>Zalecenia:</strong>
                                    <ul class="mt-1 list-disc list-inside">
                                        @foreach($selectedShop->connection_details['ssl_tls_status']['recommendations'] as $recommendation)
                                            <li>{{ $recommendation }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>

                        <!-- API Rate Limits -->
                        <div class="backdrop-blur-xl rounded-xl p-4" style="background: linear-gradient(135deg, rgba(55, 65, 81, 0.6), rgba(31, 41, 55, 0.4)); border: 1px solid rgba(224, 172, 126, 0.2);">
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="text-lg font-semibold text-white">Limity API</h4>
                                @switch($selectedShop->connection_details['rate_limits']['status'])
                                    @case('healthy')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">OK</span>
                                        @break
                                    @case('warning')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Ostrzeżenie</span>
                                        @break
                                    @case('critical')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Krytyczny</span>
                                        @break
                                    @default
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Nieznany</span>
                                @endswitch
                            </div>

                            <div class="space-y-2">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-400">Wykorzystanie:</span>
                                    <span class="text-white">{{ $selectedShop->connection_details['rate_limits']['utilization_percent'] }}%</span>
                                </div>
                                <div class="w-full bg-gray-700 rounded-full h-2">
                                    <div class="h-2 rounded-full @if($selectedShop->connection_details['rate_limits']['utilization_percent'] > 80) bg-red-500 @elseif($selectedShop->connection_details['rate_limits']['utilization_percent'] > 60) bg-yellow-500 @else bg-green-500 @endif"
                                         style="width: {{ $selectedShop->connection_details['rate_limits']['utilization_percent'] }}%"></div>
                                </div>
                                <div class="flex justify-between text-xs text-gray-400">
                                    <span>Pozostało: {{ $selectedShop->connection_details['rate_limits']['remaining_requests'] }}</span>
                                    <span>Limit: {{ $selectedShop->connection_details['rate_limits']['configured_limit'] }}/min</span>
                                </div>
                            </div>
                        </div>

                        <!-- Response Time Metrics -->
                        <div class="backdrop-blur-xl rounded-xl p-4" style="background: linear-gradient(135deg, rgba(55, 65, 81, 0.6), rgba(31, 41, 55, 0.4)); border: 1px solid rgba(224, 172, 126, 0.2);">
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="text-lg font-semibold text-white">Czas odpowiedzi</h4>
                                @switch($selectedShop->connection_details['response_metrics']['status'])
                                    @case('excellent')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Doskonały</span>
                                        @break
                                    @case('good')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">Dobry</span>
                                        @break
                                    @case('slow')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Wolny</span>
                                        @break
                                    @case('critical')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Krytyczny</span>
                                        @break
                                    @default
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Nieznany</span>
                                @endswitch
                            </div>

                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-400">Aktualne:</span>
                                    <span class="text-white">{{ $selectedShop->connection_details['response_metrics']['metrics']['current_response_time'] }}ms</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-400">Średnia 24h:</span>
                                    <span class="text-white">{{ $selectedShop->connection_details['response_metrics']['metrics']['average_24h'] }}ms</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-400">P95 24h:</span>
                                    <span class="text-white">{{ $selectedShop->connection_details['response_metrics']['metrics']['p95_24h'] }}ms</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-400">Min/Max 24h:</span>
                                    <span class="text-white">{{ $selectedShop->connection_details['response_metrics']['metrics']['min_24h'] }}/{{ $selectedShop->connection_details['response_metrics']['metrics']['max_24h'] }}ms</span>
                                </div>
                            </div>
                        </div>

                        <!-- Error Rate Tracking -->
                        <div class="backdrop-blur-xl rounded-xl p-4" style="background: linear-gradient(135deg, rgba(55, 65, 81, 0.6), rgba(31, 41, 55, 0.4)); border: 1px solid rgba(224, 172, 126, 0.2);">
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="text-lg font-semibold text-white">Wskaźnik błędów</h4>
                                @switch($selectedShop->connection_details['error_tracking']['alert_level'])
                                    @case('none')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">OK</span>
                                        @break
                                    @case('warning')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Ostrzeżenie</span>
                                        @break
                                    @case('critical')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Krytyczny</span>
                                        @break
                                    @default
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Nieznany</span>
                                @endswitch
                            </div>

                            <div class="text-sm text-gray-300 mb-3">
                                {{ $selectedShop->connection_details['error_tracking']['alert_message'] }}
                            </div>

                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-400">Błędy 24h:</span>
                                    <span class="text-white">{{ $selectedShop->connection_details['error_tracking']['error_rate_24h'] }}%</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-400">Błędy 7d:</span>
                                    <span class="text-white">{{ $selectedShop->connection_details['error_tracking']['error_rate_7d'] }}%</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-400">Zapytania 24h:</span>
                                    <span class="text-white">{{ $selectedShop->connection_details['error_tracking']['total_requests_24h'] }}</span>
                                </div>
                            </div>

                            @if(count($selectedShop->connection_details['error_tracking']['error_types']) > 0)
                            <div class="mt-3 pt-3 border-t border-gray-600">
                                <div class="text-xs text-gray-400 mb-2">Typy błędów (24h):</div>
                                <div class="space-y-1 text-xs">
                                    @foreach($selectedShop->connection_details['error_tracking']['error_types'] as $type => $count)
                                        @if($count > 0)
                                            <div class="flex justify-between">
                                                <span class="text-gray-400 capitalize">{{ ucfirst($type) }}:</span>
                                                <span class="text-white">{{ $count }}</span>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                @endif

                <!-- Action Buttons -->
                <div class="flex items-center justify-end space-x-3 mt-6 pt-4 border-t border-gray-600">
                    <button wire:click="showConnectionDetails({{ $selectedShop->id }})"
                            class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white font-medium rounded-lg transition-colors duration-200"
                            title="Pokaż zaawansowane metryki połączenia">
                        Zaawansowane metryki
                    </button>
                    <button wire:click="testConnection({{ $selectedShop->id }})"
                            class="px-4 py-2 bg-cyan-600 hover:bg-cyan-700 text-white font-medium rounded-lg transition-colors duration-200">
                        Test połączenia
                    </button>
                    <button wire:click="syncShop({{ $selectedShop->id }})"
                            class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors duration-200">
                        Synchronizuj teraz
                    </button>
                    <button wire:click="editShop({{ $selectedShop->id }})"
                            class="px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white font-medium rounded-lg transition-colors duration-200">
                        Edytuj sklep
                    </button>
                    <button wire:click="closeDetails"
                            class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-lg transition-colors duration-200">
                        Zamknij
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Delete Confirmation Modal -->
    @if($showDeleteConfirm)
    <div class="fixed inset-0 z-50 overflow-y-auto" style="z-index: 9999;">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div class="fixed inset-0 bg-black bg-opacity-75 transition-opacity" wire:click="cancelDelete"></div>

            <!-- Modal -->
            <div class="inline-block align-bottom bg-gray-800 rounded-lg px-6 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6" 
                 style="background: linear-gradient(135deg, rgba(31, 41, 55, 0.95), rgba(17, 24, 39, 0.95)); border: 1px solid rgba(224, 172, 126, 0.3);">
                
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-900 bg-opacity-40 sm:mx-0 sm:h-10 sm:w-10">
                        <svg class="h-6 w-6 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L3.732 19C2.962 20.667 3.924 22 5.464 22z" />
                        </svg>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg leading-6 font-medium text-white">
                            Usuń sklep PrestaShop
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-300">
                                Czy na pewno chcesz usunąć sklep <strong>{{ $shopToDelete ? $shopToDelete->name : '' }}</strong>? 
                                Tej akcji nie można cofnąć. Wszystkie dane synchronizacji zostaną usunięte.
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                    <button wire:click="deleteShop" 
                            class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm transition-colors duration-200">
                        Usuń sklep
                    </button>
                    <button wire:click="cancelDelete" 
                            class="mt-3 w-full inline-flex justify-center rounded-lg border border-gray-600 shadow-sm px-4 py-2 bg-gray-700 bg-opacity-60 text-base font-medium text-gray-300 hover:bg-gray-600 hover:bg-opacity-80 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 sm:mt-0 sm:w-auto sm:text-sm transition-colors duration-200">
                        Anuluj
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<script>
function adminShops() {
    return {
        init() {
            // Initialize shop management functionality
        }
    }
}
</script>
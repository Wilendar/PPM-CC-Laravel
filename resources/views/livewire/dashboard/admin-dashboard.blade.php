<div x-data="adminDashboard({{ $refreshInterval }})" 
     x-init="init()" 
     class="min-h-screen bg-gradient-to-br from-gray-900 via-gray-800 to-black relative">
     
    <!-- Animated Background Elements with MPP TRADE Colors -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-32 -right-32 w-64 h-64 rounded-full blur-3xl animate-pulse" style="background: radial-gradient(circle, rgba(224, 172, 126, 0.1), rgba(209, 151, 90, 0.05));"></div>
        <div class="absolute -bottom-32 -left-32 w-64 h-64 rounded-full blur-3xl animate-pulse" style="background: radial-gradient(circle, rgba(209, 151, 90, 0.1), rgba(224, 172, 126, 0.05)); animation-delay: 2s;"></div>
        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-96 h-96 rounded-full blur-3xl animate-pulse" style="background: radial-gradient(circle, rgba(224, 172, 126, 0.05), rgba(209, 151, 90, 0.03)); animation-delay: 4s;"></div>
    </div>
     
    <!-- Dashboard Header with Enhanced Navigation -->
    <div class="relative backdrop-blur-xl shadow-2xl" style="background: linear-gradient(135deg, rgba(31, 41, 55, 0.95), rgba(17, 24, 39, 0.95)); border-bottom: 1px solid rgba(224, 172, 126, 0.3); overflow: visible !important; z-index: 10000;">
        <div class="max-w-7xl mx-auto px-6 sm:px-8 lg:px-12">
            <div class="flex items-center justify-between h-24">
                <div class="flex items-center">
                    <!-- Enhanced Logo with MPP Colors -->
                    <div class="flex-shrink-0">
                        <div class="relative w-12 h-12 rounded-xl flex items-center justify-center shadow-lg transform transition-transform duration-300 hover:scale-105" style="background: linear-gradient(45deg, #e0ac7e, #d1975a);">
                            <div class="absolute inset-0 rounded-xl" style="background: linear-gradient(135deg, rgba(224, 172, 126, 0.2), transparent);"></div>
                            <svg class="w-7 h-7 text-white relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                            <!-- MPP Brand Glow -->
                            <div class="absolute inset-0 rounded-xl opacity-75 blur animate-pulse" style="background: linear-gradient(45deg, #e0ac7e, #d1975a);"></div>
                        </div>
                    </div>
                    <div class="ml-4 flex-1 min-w-0">
                        <h1 class="text-xl font-bold tracking-tight" style="color: #e0ac7e !important;">
                            ADMIN PANEL
                        </h1>
                        <p class="text-xs font-medium text-gray-400 tracking-wide truncate">
                            PPM Enterprise
                        </p>
                    </div>
                    
                    <!-- MPP TRADE Brand Badge - kompaktowy -->
                    <div class="hidden lg:flex items-center px-2 py-1 rounded-full border ml-2 flex-shrink-0" style="background: linear-gradient(45deg, rgba(224, 172, 126, 0.2), rgba(209, 151, 90, 0.2)); border-color: rgba(224, 172, 126, 0.3);">
                        <div class="w-1.5 h-1.5 rounded-full mr-1.5 animate-pulse" style="background-color: #e0ac7e;"></div>
                        <span class="text-xs font-bold" style="color: #e0ac7e;">MPP</span>
                    </div>
                </div>
                
                <!-- Enhanced Dashboard Controls with Navigation -->
                <div class="flex items-center space-x-2 lg:space-x-4 flex-shrink-0">
                    <!-- Global Search Box -->
                    <div class="relative">
                        <div class="relative">
                            <input 
                                type="text" 
                                wire:model.live="searchQuery" 
                                wire:keyup.enter="handleSearch"
                                placeholder="Szukaj w systemie..."
                                class="w-80 px-4 py-2 pl-10 pr-10 text-sm text-white placeholder-gray-400 rounded-lg transition-all duration-200 focus:outline-none focus:ring-2"
                                style="background: rgba(31, 41, 55, 0.8); border: 1px solid rgba(75, 85, 99, 0.5);"
                                onfocus="this.style.borderColor='#e0ac7e'; this.style.boxShadow='0 0 0 3px rgba(224, 172, 126, 0.1)'"
                                onblur="this.style.borderColor='rgba(75, 85, 99, 0.5)'; this.style.boxShadow='none'">
                            <!-- Search Icon -->
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </div>
                            <!-- Clear Search -->
                            @if($searchQuery)
                            <button wire:click="clearSearch" class="absolute inset-y-0 right-0 flex items-center pr-3">
                                <svg class="w-4 h-4 text-gray-400 hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                            @endif
                        </div>
                        
                        <!-- Search Results Dropdown -->
                        @if($showSearchResults && !empty($searchResults))
                        <div class="absolute top-full left-0 right-0 mt-2 rounded-lg shadow-2xl z-50" style="background: linear-gradient(135deg, rgba(31, 41, 55, 0.95), rgba(17, 24, 39, 0.95)); border: 1px solid rgba(224, 172, 126, 0.3);">
                            <div class="p-4">
                                <h3 class="text-sm font-bold text-white mb-3">Wyniki wyszukiwania</h3>
                                @foreach($searchResults as $category => $results)
                                    @if(!empty($results))
                                        <div class="mb-4">
                                            <h4 class="text-xs uppercase font-semibold text-gray-400 mb-2">{{ ucfirst($category) }}</h4>
                                            @foreach($results as $result)
                                            <a href="{{ $result['url'] }}" class="flex items-center p-2 rounded-lg hover:bg-gray-700 transition-colors duration-200">
                                                <div class="flex-shrink-0 mr-3">
                                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $result['icon'] }}"></path>
                                                    </svg>
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <p class="text-sm font-medium text-white truncate">{{ $result['title'] }}</p>
                                                    <p class="text-xs text-gray-400 truncate">{{ $result['subtitle'] }}</p>
                                                </div>
                                            </a>
                                            @endforeach
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>

                    <!-- Notification Center -->
                    <div class="relative" x-data="{ open: false }" style="z-index: 99999 !important;">
                        <button @click="open = !open" class="relative p-2 rounded-lg transition-all duration-200 hover:bg-gray-700">
                            <svg class="w-6 h-6 text-gray-300 hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                            </svg>
                            @if(count($notifications) > 1 || (count($notifications) === 1 && $notifications[0]['type'] !== 'success'))
                            <span class="absolute -top-1 -right-1 h-4 w-4 rounded-full text-xs font-bold text-white flex items-center justify-center" style="background: #dc2626;">
                                {{ count($notifications) }}
                            </span>
                            @endif
                        </button>
                        
                        <!-- Notification Dropdown -->
                        <div x-show="open" @click.away="open = false" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" class="absolute right-0 mt-2 w-96 rounded-lg shadow-2xl" style="background: linear-gradient(135deg, rgba(31, 41, 55, 0.95), rgba(17, 24, 39, 0.95)); border: 1px solid rgba(224, 172, 126, 0.3); z-index: 1000 !important;">
                            <div class="p-4">
                                <h3 class="text-sm font-bold text-white mb-3 flex items-center">
                                    <svg class="w-4 h-4 mr-2" style="color: #e0ac7e;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                                    </svg>
                                    Powiadomienia systemu
                                </h3>
                                <div class="space-y-3 max-h-80 overflow-y-auto">
                                    @forelse($notifications as $notification)
                                    <div class="flex items-start p-3 rounded-lg transition-colors duration-200 hover:bg-gray-700">
                                        <div class="flex-shrink-0 mr-3">
                                            <div class="w-8 h-8 rounded-full flex items-center justify-center
                                                @if($notification['type'] === 'error') bg-red-600
                                                @elseif($notification['type'] === 'warning') bg-yellow-600  
                                                @elseif($notification['type'] === 'success') bg-green-600
                                                @else bg-blue-600
                                                @endif">
                                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $notification['icon'] }}"></path>
                                                </svg>
                                            </div>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-white">{{ $notification['title'] }}</p>
                                            <p class="text-xs text-gray-400 mt-1">{{ $notification['message'] }}</p>
                                            <p class="text-xs text-gray-500 mt-1">{{ $notification['time'] }}</p>
                                        </div>
                                        @if($notification['action'])
                                        <div class="flex-shrink-0 ml-2">
                                            <a href="{{ $notification['action'] }}" class="text-xs font-medium hover:underline" style="color: #e0ac7e;">
                                                Przejdź
                                            </a>
                                        </div>
                                        @endif
                                    </div>
                                    @empty
                                    <div class="text-center py-6">
                                        <p class="text-gray-400 text-sm">Brak powiadomień</p>
                                    </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- System Status Indicator -->
                    <div class="hidden lg:flex items-center space-x-2 px-3 py-2 rounded-lg border" style="background: rgba(31, 41, 55, 0.8); border-color: rgba(75, 85, 99, 0.2);">
                        <div class="w-2 h-2 rounded-full animate-pulse" style="background-color: #059669;"></div>
                        <span class="text-xs font-medium text-gray-300">SYSTEM OK</span>
                    </div>
                    
                    <!-- Auto-refresh Control -->
                    <div class="flex items-center space-x-3 text-sm rounded-lg px-4 py-2 border" style="background: rgba(31, 41, 55, 0.8); border-color: rgba(75, 85, 99, 0.2);">
                        <svg class="w-4 h-4" style="color: #e0ac7e;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <label class="text-gray-300 font-medium">Auto:</label>
                        <select wire:model="refreshInterval" 
                                @change="updateRefreshInterval($event.target.value)"
                                class="rounded-md text-white px-2 py-1 text-sm transition-all duration-200" 
                                style="background: #374151; border: 1px solid rgba(75, 85, 99, 0.5);"
                                onfocus="this.style.borderColor='#e0ac7e'; this.style.boxShadow='0 0 0 3px rgba(224, 172, 126, 0.1)'"
                                onblur="this.style.borderColor='rgba(75, 85, 99, 0.5)'; this.style.boxShadow='none'">
                            <option value="30">30s</option>
                            <option value="60">1min</option>
                            <option value="300">5min</option>
                        </select>
                    </div>
                    
                    <!-- Enhanced Manual Refresh Button with MPP Colors -->
                    <button wire:click="loadDashboardData" 
                            class="relative inline-flex items-center px-4 py-2.5 border border-transparent text-sm font-bold rounded-lg text-white transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl"
                            style="background: linear-gradient(45deg, #e0ac7e, #d1975a);"
                            onmouseover="this.style.background='linear-gradient(45deg, #d1975a, #c08449)'"
                            onmouseout="this.style.background='linear-gradient(45deg, #e0ac7e, #d1975a)'">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        ODŚWIEŻ
                        <!-- MPP Brand Glow -->
                        <div class="absolute inset-0 rounded-lg opacity-30 blur transition-opacity duration-300 hover:opacity-50" style="background: linear-gradient(45deg, #e0ac7e, #d1975a);"></div>
                    </button>

                    <!-- User Profile Dropdown -->
                    <div class="relative" x-data="{ open: false }" style="z-index: 99999 !important;">
                        <button @click="open = !open" class="flex items-center space-x-2 p-2 rounded-lg transition-all duration-200 hover:bg-gray-700">
                            <!-- Avatar or Initial -->
                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold text-white" style="background: linear-gradient(45deg, #e0ac7e, #d1975a);">
                                {{ strtoupper(substr($userProfile['name'] ?? 'A', 0, 1)) }}
                            </div>
                            <div class="hidden md:block">
                                <p class="text-sm font-medium text-white">{{ $userProfile['name'] ?? 'Admin' }}</p>
                                <p class="text-xs text-gray-400">{{ $userProfile['role'] ?? 'Administrator' }}</p>
                            </div>
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        
                        <!-- Profile Dropdown -->
                        <div x-show="open" @click.away="open = false" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" class="absolute right-0 mt-2 w-72 rounded-lg shadow-2xl" style="background: linear-gradient(135deg, rgba(31, 41, 55, 0.95), rgba(17, 24, 39, 0.95)); border: 1px solid rgba(224, 172, 126, 0.3); z-index: 1000 !important;">
                            <div class="p-4">
                                <!-- Profile Header -->
                                <div class="flex items-center space-x-3 pb-4 border-b" style="border-color: rgba(75, 85, 99, 0.3);">
                                    <div class="w-12 h-12 rounded-full flex items-center justify-center text-lg font-bold text-white" style="background: linear-gradient(45deg, #e0ac7e, #d1975a);">
                                        {{ strtoupper(substr($userProfile['name'] ?? 'A', 0, 1)) }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-bold text-white truncate">{{ $userProfile['name'] ?? 'Admin MPP TRADE' }}</p>
                                        <p class="text-xs text-gray-400 truncate">{{ $userProfile['email'] ?? 'admin@mpptrade.pl' }}</p>
                                        <p class="text-xs font-medium" style="color: #e0ac7e;">{{ $userProfile['role'] ?? 'Administrator' }}</p>
                                    </div>
                                </div>
                                
                                <!-- Profile Stats -->
                                <div class="py-3 border-b" style="border-color: rgba(75, 85, 99, 0.3);">
                                    <div class="grid grid-cols-2 gap-3 text-center">
                                        <div>
                                            <p class="text-lg font-bold text-white">{{ $userProfile['permissions_count'] ?? 47 }}</p>
                                            <p class="text-xs text-gray-400">Uprawnienia</p>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-300">{{ $userProfile['last_login'] ?? '2 godziny temu' }}</p>
                                            <p class="text-xs text-gray-400">Ostatnie logowanie</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Profile Actions -->
                                <div class="pt-3">
                                    @if(!empty($userProfile['actions']))
                                        @foreach($userProfile['actions'] as $action)
                                        <a href="{{ $action['url'] }}" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors duration-200">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $action['icon'] }}"></path>
                                            </svg>
                                            <span class="text-sm text-white">{{ $action['name'] }}</span>
                                        </a>
                                        @endforeach
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Header bottom glow line with MPP Colors -->
        <div class="absolute bottom-0 left-0 right-0 h-px" style="background: linear-gradient(to right, transparent, rgba(224, 172, 126, 0.5), transparent);"></div>
    </div>

    <!-- Breadcrumb Navigation -->
    <div class="relative backdrop-blur-sm" style="background: linear-gradient(135deg, rgba(17, 24, 39, 0.6), rgba(31, 41, 55, 0.4));">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center space-x-2 py-3">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2 2z"></path>
                </svg>
                @foreach($breadcrumbs as $key => $crumb)
                    @if($key > 0)
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    @endif
                    @if($crumb['current'])
                        <span class="text-sm font-medium" style="color: #e0ac7e;">{{ $crumb['name'] }}</span>
                    @else
                        <a href="{{ $crumb['url'] }}" class="text-sm font-medium text-gray-300 hover:text-white transition-colors duration-200">
                            {{ $crumb['name'] }}
                        </a>
                    @endif
                @endforeach
            </div>
        </div>
    </div>
    
    <!-- Main Layout with Sidebar -->
    <div class="flex">
        <!-- Quick Access Sidebar -->
        <div class="w-64 min-h-screen relative" style="background: linear-gradient(135deg, rgba(17, 24, 39, 0.8), rgba(31, 41, 55, 0.6)); border-right: 1px solid rgba(224, 172, 126, 0.2);">
            <div class="sticky top-0 p-4">
                <h3 class="text-lg font-bold text-white mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2" style="color: #e0ac7e;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                    Szybkie Akcje
                </h3>
                <div class="space-y-2">
                    @foreach($quickActions as $action)
                    <a href="{{ $action['url'] }}" class="flex items-center p-3 rounded-lg transition-all duration-200 hover:bg-gray-700 hover:scale-105 group">
                        <div class="flex-shrink-0 mr-3">
                            <div class="w-10 h-10 rounded-lg flex items-center justify-center {{ $action['color'] }} group-hover:shadow-lg transition-shadow duration-200">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $action['icon'] }}"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-white">{{ $action['name'] }}</p>
                            <p class="text-xs text-gray-400 mt-1 leading-tight">{{ $action['description'] }}</p>
                        </div>
                        <svg class="w-4 h-4 text-gray-400 group-hover:text-white transition-colors duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </a>
                    @endforeach
                </div>
                
                <!-- Sidebar Footer -->
                <div class="mt-8 p-3 rounded-lg border" style="background: rgba(224, 172, 126, 0.1); border-color: rgba(224, 172, 126, 0.3);">
                    <p class="text-xs font-bold" style="color: #e0ac7e;">MPP TRADE ADMIN</p>
                    <p class="text-xs text-gray-400 mt-1">Centrum kontroli systemu</p>
                </div>
            </div>
        </div>
    
        <!-- Dashboard Content -->
        <div class="flex-1">
            <div class="px-6 sm:px-8 lg:px-12 py-8">
        
        <!-- System Health Status Bar with MPP Colors -->
        <div class="mb-8 relative">
            <div class="backdrop-blur-xl rounded-2xl p-8 shadow-2xl relative overflow-hidden" style="background: linear-gradient(135deg, rgba(31, 41, 55, 0.8), rgba(17, 24, 39, 0.6), rgba(31, 41, 55, 0.8)); border: 1px solid rgba(224, 172, 126, 0.2);">
                <!-- Background pattern with MPP accent -->
                <div class="absolute inset-0 opacity-5">
                    <div class="absolute inset-0" style="background-image: radial-gradient(circle at 1px 1px, rgba(224, 172, 126, 0.15) 1px, transparent 0); background-size: 20px 20px;"></div>
                </div>
                
                <!-- Header with enhanced styling -->
                <div class="relative z-10 mb-6">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-black text-white flex items-center">
                            <div class="w-10 h-10 bg-gradient-to-br from-green-400 to-green-600 rounded-lg flex items-center justify-center mr-3 shadow-lg">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <span class="text-white font-bold">
                                STATUS SYSTEMU
                            </span>
                        </h2>
                        <div class="flex items-center space-x-2 px-3 py-1 rounded-full border" style="background: rgba(5, 150, 105, 0.2); border-color: rgba(5, 150, 105, 0.3);">
                            <div class="w-2 h-2 rounded-full animate-pulse" style="background-color: #059669;"></div>
                            <span class="text-xs font-bold" style="color: #22c55e;">WSZYSTKIE SYSTEMY DZIAŁAJĄ</span>
                        </div>
                    </div>
                </div>
                
                <!-- Enhanced system health grid -->
                <div class="relative z-10 grid grid-cols-2 md:grid-cols-4 gap-6">
                    @foreach($systemHealth as $service => $health)
                    <div class="group relative">
                        <!-- Card with hover effects -->
                        <div class="relative backdrop-blur-sm rounded-xl p-4 border transition-all duration-300 hover:scale-105 hover:shadow-lg" 
                             style="background: linear-gradient(135deg, rgba(55, 65, 81, 0.5), rgba(31, 41, 55, 0.5)); border-color: rgba(75, 85, 99, 0.5);"
                             onmouseover="this.style.borderColor='rgba(224, 172, 126, 0.5)'; this.style.boxShadow='0 10px 15px -3px rgba(224, 172, 126, 0.2)'"
                             onmouseout="this.style.borderColor='rgba(75, 85, 99, 0.5)'; this.style.boxShadow='0 10px 15px -3px rgba(0, 0, 0, 0.1)'"">
                            <!-- Service info -->
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <p class="text-sm font-bold text-white mb-1">{{ ucfirst(str_replace('_', ' ', $service)) }}</p>
                                    <p class="text-xs text-gray-400 leading-relaxed">{{ $health['message'] ?? 'N/A' }}</p>
                                </div>
                                <div class="flex-shrink-0 ml-3">
                                    @if(($health['status'] ?? 'unknown') === 'healthy')
                                        <div class="relative">
                                            <div class="w-4 h-4 bg-green-500 rounded-full animate-pulse"></div>
                                            <div class="absolute inset-0 w-4 h-4 bg-green-400 rounded-full opacity-50 animate-ping"></div>
                                        </div>
                                    @elseif(($health['status'] ?? 'unknown') === 'warning')
                                        <div class="relative">
                                            <div class="w-4 h-4 bg-yellow-500 rounded-full animate-pulse"></div>
                                            <div class="absolute inset-0 w-4 h-4 bg-yellow-400 rounded-full opacity-50 animate-ping"></div>
                                        </div>
                                    @elseif(($health['status'] ?? 'unknown') === 'error')
                                        <div class="relative">
                                            <div class="w-4 h-4 bg-red-500 rounded-full animate-pulse"></div>
                                            <div class="absolute inset-0 w-4 h-4 bg-red-400 rounded-full opacity-50 animate-ping"></div>
                                        </div>
                                    @else
                                        <div class="w-4 h-4 bg-gray-500 rounded-full"></div>
                                    @endif
                                </div>
                            </div>
                            <!-- Hover glow effect with MPP Colors -->
                            <div class="absolute inset-0 rounded-xl opacity-0 group-hover:opacity-100 transition-opacity duration-300" style="background: linear-gradient(135deg, rgba(224, 172, 126, 0.1), transparent);"></div>
                        </div>
                    </div>
                    @endforeach
                </div>
                
                <!-- Bottom accent line with MPP Colors -->
                <div class="absolute bottom-0 left-0 right-0 h-px" style="background: linear-gradient(to right, transparent, rgba(224, 172, 126, 0.5), transparent);"></div>
            </div>
        </div>
        
        <!-- Core Metrics Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 mb-8">
            
            <!-- Total Products -->
            <div class="group relative">
                <div class="bg-gradient-to-br from-blue-600/30 via-blue-700/20 to-blue-900/30 backdrop-blur-xl rounded-2xl border border-blue-500/40 p-8 shadow-2xl transform transition-all duration-500 hover:scale-105 hover:shadow-blue-500/20 relative overflow-hidden">
                    <!-- Background glow -->
                    <div class="absolute inset-0 bg-gradient-to-br from-blue-400/10 to-transparent rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                    
                    <div class="relative z-10 flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-16 h-16 bg-gradient-to-br from-blue-400 via-blue-500 to-blue-600 rounded-2xl flex items-center justify-center shadow-lg transform transition-transform duration-300 group-hover:scale-110">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                </svg>
                                <!-- Icon glow -->
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
                    
                    <!-- Metric progress bar -->
                    <div class="mt-6 relative z-10">
                        <div class="w-full bg-blue-900/30 rounded-full h-2 overflow-hidden">
                            <div class="bg-gradient-to-r from-red-400 to-red-600 h-2 rounded-full transition-all duration-1000" style="width: {{ $dashboardStats['products_with_problems_percent'] ?? 0 }}%;"></div>
                        </div>
                        <p class="text-xs text-red-300 mt-2 font-medium">{{ $dashboardStats['products_with_problems_percent'] ?? 0 }}% produktów ma problemy</p>
                    </div>
                </div>
            </div>
            
            <!-- Active Users -->
            <div class="group relative">
                <div class="bg-gradient-to-br from-green-600/30 via-green-700/20 to-green-900/30 backdrop-blur-xl rounded-2xl border border-green-500/40 p-8 shadow-2xl transform transition-all duration-500 hover:scale-105 hover:shadow-green-500/20 relative overflow-hidden">
                    <!-- Background glow -->
                    <div class="absolute inset-0 bg-gradient-to-br from-green-400/10 to-transparent rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                    
                    <div class="relative z-10 flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-16 h-16 bg-gradient-to-br from-green-400 via-green-500 to-green-600 rounded-2xl flex items-center justify-center shadow-lg transform transition-transform duration-300 group-hover:scale-110">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                </svg>
                                <!-- Icon glow -->
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
                    
                    <!-- Metric progress bar -->
                    <div class="mt-6 relative z-10">
                        <div class="w-full bg-green-900/30 rounded-full h-2 overflow-hidden">
                            <div class="bg-gradient-to-r from-green-400 to-green-600 h-2 rounded-full transition-all duration-1000" style="width: {{ $dashboardStats['logged_users_percent'] ?? 0 }}%;"></div>
                        </div>
                        <p class="text-xs text-green-300 mt-2 font-medium">{{ $dashboardStats['logged_users_percent'] ?? 0 }}% zalogowanych</p>
                    </div>
                </div>
            </div>
            
            <!-- Categories -->
            <div class="group relative">
                <div class="bg-gradient-to-br from-purple-600/30 via-purple-700/20 to-purple-900/30 backdrop-blur-xl rounded-2xl border border-purple-500/40 p-8 shadow-2xl transform transition-all duration-500 hover:scale-105 hover:shadow-purple-500/20 relative overflow-hidden">
                    <!-- Background glow -->
                    <div class="absolute inset-0 bg-gradient-to-br from-purple-400/10 to-transparent rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                    
                    <div class="relative z-10 flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-16 h-16 bg-gradient-to-br from-purple-400 via-purple-500 to-purple-600 rounded-2xl flex items-center justify-center shadow-lg transform transition-transform duration-300 group-hover:scale-110">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                </svg>
                                <!-- Icon glow -->
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
                    
                    <!-- Metric progress bar -->
                    <div class="mt-6 relative z-10">
                        <div class="w-full bg-purple-900/30 rounded-full h-2 overflow-hidden">
                            <div class="bg-gradient-to-r from-purple-400 to-purple-600 h-2 rounded-full transition-all duration-1000" style="width: {{ $dashboardStats['categories_with_products_percent'] ?? 0 }}%;"></div>
                        </div>
                        <p class="text-xs text-purple-300 mt-2 font-medium">{{ $dashboardStats['categories_with_products_percent'] ?? 0 }}% kategorii z produktami</p>
                    </div>
                </div>
            </div>
            
            <!-- Recent Activity - Enhanced with MPP TRADE Colors -->
            <div class="group relative">
                <div class="backdrop-blur-xl rounded-2xl p-8 shadow-2xl transform transition-all duration-500 hover:scale-105 relative overflow-hidden" 
                     style="background: linear-gradient(135deg, rgba(224, 172, 126, 0.4), rgba(209, 151, 90, 0.3), rgba(192, 132, 73, 0.4)); border: 1px solid rgba(224, 172, 126, 0.5);"
                     onmouseover="this.style.boxShadow='0 25px 50px -12px rgba(224, 172, 126, 0.3)'"
                     onmouseout="this.style.boxShadow='0 25px 50px -12px rgba(0, 0, 0, 0.25)'">
                    <!-- Background glow with MPP TRADE branding -->
                    <div class="absolute inset-0 rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-500" style="background: linear-gradient(135deg, rgba(224, 172, 126, 0.15), transparent);"></div>
                    
                    <div class="relative z-10 flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-16 h-16 rounded-2xl flex items-center justify-center shadow-xl transform transition-transform duration-300 group-hover:scale-110" style="background: linear-gradient(45deg, #e0ac7e, #d1975a);">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                                <!-- Enhanced icon glow for MPP -->
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
                    
                    <!-- Enhanced metric progress bar for MPP -->
                    <div class="mt-6 relative z-10">
                        <div class="w-full rounded-full h-2 overflow-hidden" style="background: rgba(192, 132, 73, 0.4);">
                            <div class="h-2 rounded-full transition-all duration-1000 animate-pulse" style="background: linear-gradient(90deg, #e0ac7e, #d1975a, #e0ac7e); width: {{ $dashboardStats['activity_score_percent'] ?? 0 }}%;"></div>
                        </div>
                        <p class="text-xs mt-2 font-bold" style="color: #e0ac7e;">🔥 {{ $dashboardStats['activity_score_percent'] ?? 0 }}% aktywność dzisiaj</p>
                    </div>
                    
                    <!-- MPP TRADE Badge -->
                    <div class="absolute top-3 right-3 px-2 py-1 rounded-full border opacity-0 group-hover:opacity-100 transition-opacity duration-300" style="background: rgba(224, 172, 126, 0.2); border-color: rgba(224, 172, 126, 0.3);">
                        <span class="text-xs font-black" style="color: #e0ac7e;">MPP</span>
                    </div>
                </div>
            </div>
            
        </div>
        
        <!-- Business KPIs with MPP TRADE Colors -->
        <div class="mb-12 relative">
            <div class="backdrop-blur-xl rounded-2xl p-8 shadow-2xl relative overflow-hidden" style="background: linear-gradient(135deg, rgba(31, 41, 55, 0.8), rgba(17, 24, 39, 0.6), rgba(31, 41, 55, 0.8)); border: 1px solid rgba(224, 172, 126, 0.2);">
                <!-- Background pattern with MPP colors -->
                <div class="absolute inset-0 opacity-5">
                    <div class="absolute inset-0" style="background-image: radial-gradient(circle at 2px 2px, rgba(224, 172, 126, 0.15) 1px, transparent 0); background-size: 30px 30px;"></div>
                </div>
                
                <!-- Enhanced Header with MPP Colors -->
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
                
                <!-- Enhanced KPI Grid -->
                <div class="relative z-10 grid grid-cols-2 md:grid-cols-4 gap-8">
                    
                    <!-- Products Today -->
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
                    
                    <!-- Empty Categories -->
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
                    
                    <!-- Products Without Images -->
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
                    
                    <!-- Integration Conflicts with MPP TRADE Colors -->
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
                            <!-- MPP TRADE Badge -->
                            <div class="absolute top-2 right-2 px-2 py-1 rounded-full border opacity-0 group-hover:opacity-100 transition-opacity duration-300" style="background: rgba(224, 172, 126, 0.2); border-color: rgba(224, 172, 126, 0.3);">
                                <span class="text-xs font-black" style="color: #e0ac7e;">MPP</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Bottom accent line with MPP Colors -->
                <div class="absolute bottom-0 left-0 right-0 h-px" style="background: linear-gradient(to right, transparent, rgba(224, 172, 126, 0.5), transparent);"></div>
            </div>
        </div>
        
        <!-- Sync Jobs Monitoring with MPP TRADE Colors -->
        <div class="mb-8 relative">
            <div class="backdrop-blur-xl rounded-2xl p-8 shadow-2xl relative overflow-hidden" style="background: linear-gradient(135deg, rgba(31, 41, 55, 0.8), rgba(17, 24, 39, 0.6), rgba(31, 41, 55, 0.8)); border: 1px solid rgba(59, 130, 246, 0.3);">
                <!-- Header -->
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
                
                <!-- Sync Jobs Grid -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                    <!-- Running Jobs -->
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
                    
                    <!-- Pending Jobs -->
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
                    
                    <!-- Failed Jobs -->
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
                    
                    <!-- Success Rate -->
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
                
                <!-- Performance Metrics -->
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
        
        <!-- Performance Monitoring Section -->
        <div class="mb-12 relative">
            <div class="backdrop-blur-xl rounded-2xl p-8 shadow-2xl relative overflow-hidden" style="background: linear-gradient(135deg, rgba(31, 41, 55, 0.8), rgba(17, 24, 39, 0.6), rgba(31, 41, 55, 0.8)); border: 1px solid rgba(59, 130, 246, 0.3);">
                <!-- Header -->
                <div class="flex items-center justify-between mb-8">
                    <div class="flex items-center space-x-4">
                        <div class="w-12 h-12 bg-gradient-to-br from-blue-400 via-blue-500 to-blue-600 rounded-xl flex items-center justify-center shadow-xl">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-2xl font-black text-white">MONITORING WYDAJNOŚCI</h3>
                            <p class="text-sm text-gray-400 font-medium">Metryki serwera i aplikacji w czasie rzeczywistym</p>
                        </div>
                    </div>
                </div>

                <!-- Server Metrics Row -->
                <div class="mb-8">
                    <h4 class="text-lg font-bold text-white mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2" style="color: #60a5fa;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M5 8h.01"></path>
                        </svg>
                        Metryki Serwera
                    </h4>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <!-- Application Load (not real CPU) -->
                        <div class="group text-center p-4 backdrop-blur-sm rounded-xl border transition-all duration-300 hover:scale-105" 
                             style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.2), rgba(37, 99, 235, 0.2)); border-color: rgba(59, 130, 246, 0.4);">
                            <div class="w-12 h-12 mx-auto mb-3 rounded-lg flex items-center justify-center
                                @if(($serverMetrics['cpu_usage']['status'] ?? 'normal') === 'critical') bg-red-600
                                @elseif(($serverMetrics['cpu_usage']['status'] ?? 'normal') === 'warning') bg-yellow-600  
                                @else bg-blue-600
                                @endif shadow-xl">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
                                </svg>
                            </div>
                            <p class="text-2xl font-black mb-1 text-white">{{ $serverMetrics['cpu_usage']['percent'] ?? 0 }}%</p>
                            <p class="text-sm font-bold uppercase tracking-wide text-white">App Load</p>
                            <p class="text-xs text-gray-400 mt-1">mem+db+proc</p>
                        </div>

                        <!-- Memory Usage -->
                        <div class="group text-center p-4 backdrop-blur-sm rounded-xl border transition-all duration-300 hover:scale-105" 
                             style="background: linear-gradient(135deg, rgba(34, 197, 94, 0.2), rgba(22, 163, 74, 0.2)); border-color: rgba(34, 197, 94, 0.4);">
                            <div class="w-12 h-12 bg-gradient-to-br from-green-400 to-green-600 rounded-lg flex items-center justify-center mx-auto mb-3 shadow-xl">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"></path>
                                </svg>
                            </div>
                            <p class="text-2xl font-black mb-1 text-white">{{ $serverMetrics['memory_usage']['percent'] ?? 0 }}%</p>
                            <p class="text-sm font-bold uppercase tracking-wide text-white">PHP Memory</p>
                            <p class="text-xs text-gray-400 mt-1">{{ $serverMetrics['memory_usage']['used'] ?? 0 }}/{{ $serverMetrics['memory_usage']['total'] ?? 0 }}MB</p>
                        </div>

                        <!-- Database Connections -->
                        <div class="group text-center p-4 backdrop-blur-sm rounded-xl border transition-all duration-300 hover:scale-105" 
                             style="background: linear-gradient(135deg, rgba(168, 85, 247, 0.2), rgba(147, 51, 234, 0.2)); border-color: rgba(168, 85, 247, 0.4);">
                            <div class="w-12 h-12 bg-gradient-to-br from-purple-400 to-purple-600 rounded-lg flex items-center justify-center mx-auto mb-3 shadow-xl">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path>
                                </svg>
                            </div>
                            <p class="text-2xl font-black mb-1 text-white">{{ $serverMetrics['database_connections']['response_time'] ?? 0 }}ms</p>
                            <p class="text-sm font-bold uppercase tracking-wide text-white">DB Health</p>
                            <p class="text-xs text-gray-400 mt-1">{{ $serverMetrics['database_connections']['health'] ?? 'healthy' }}</p>
                        </div>

                        <!-- Response Time -->
                        <div class="group text-center p-4 backdrop-blur-sm rounded-xl border transition-all duration-300 hover:scale-105" 
                             style="background: linear-gradient(135deg, rgba(251, 146, 60, 0.2), rgba(249, 115, 22, 0.2)); border-color: rgba(251, 146, 60, 0.4);">
                            <div class="w-12 h-12 bg-gradient-to-br from-orange-400 to-orange-600 rounded-lg flex items-center justify-center mx-auto mb-3 shadow-xl">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <p class="text-2xl font-black mb-1 text-white">{{ $serverMetrics['response_time']['avg_total'] ?? 0 }}ms</p>
                            <p class="text-sm font-bold uppercase tracking-wide text-white">Response Time</p>
                            <p class="text-xs text-gray-400 mt-1">DB: {{ $serverMetrics['response_time']['current_db'] ?? 0 }}ms</p>
                        </div>
                    </div>
                </div>

                <!-- Application Metrics Row -->
                <div>
                    <h4 class="text-lg font-bold text-white mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2" style="color: #34d399;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
                        </svg>
                        Metryki Aplikacji
                    </h4>
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                        <!-- Queue Jobs -->
                        <div class="group text-center p-4 backdrop-blur-sm rounded-xl border transition-all duration-300 hover:scale-105" 
                             style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.2), rgba(37, 99, 235, 0.2)); border-color: rgba(59, 130, 246, 0.4);">
                            <div class="w-10 h-10 bg-gradient-to-br from-blue-400 to-blue-600 rounded-lg flex items-center justify-center mx-auto mb-3">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                </svg>
                            </div>
                            <p class="text-xl font-black mb-1 text-white">{{ $applicationMetrics['queue_jobs']['total'] ?? 0 }}</p>
                            <p class="text-xs font-bold uppercase text-white">Queue Jobs</p>
                            <div class="text-xs text-gray-400 mt-1">
                                <span class="text-yellow-400">{{ $applicationMetrics['queue_jobs']['pending'] ?? 0 }}p</span>
                                <span class="text-red-400 ml-1">{{ $applicationMetrics['queue_jobs']['failed'] ?? 0 }}f</span>
                            </div>
                        </div>

                        <!-- Cache Hit Rate -->
                        <div class="group text-center p-4 backdrop-blur-sm rounded-xl border transition-all duration-300 hover:scale-105" 
                             style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.2), rgba(5, 150, 105, 0.2)); border-color: rgba(16, 185, 129, 0.4);">
                            <div class="w-10 h-10 bg-gradient-to-br from-emerald-400 to-emerald-600 rounded-lg flex items-center justify-center mx-auto mb-3">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                            </div>
                            <p class="text-xl font-black mb-1 text-white">{{ $applicationMetrics['cache_performance']['hit_rate'] ?? 0 }}%</p>
                            <p class="text-xs font-bold uppercase text-white">Cache Hit</p>
                            <p class="text-xs text-gray-400 mt-1">{{ $applicationMetrics['cache_performance']['read_time_ms'] ?? 0 }}ms</p>
                        </div>

                        <!-- Log Files -->
                        <div class="group text-center p-4 backdrop-blur-sm rounded-xl border transition-all duration-300 hover:scale-105" 
                             style="background: linear-gradient(135deg, rgba(245, 158, 11, 0.2), rgba(217, 119, 6, 0.2)); border-color: rgba(245, 158, 11, 0.4);">
                            <div class="w-10 h-10 bg-gradient-to-br from-yellow-400 to-yellow-600 rounded-lg flex items-center justify-center mx-auto mb-3">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <p class="text-xl font-black mb-1 text-white">{{ $applicationMetrics['log_files']['size_mb'] ?? 0 }}MB</p>
                            <p class="text-xs font-bold uppercase text-white">Log Files</p>
                            <p class="text-xs text-gray-400 mt-1">{{ $applicationMetrics['log_files']['files_count'] ?? 0 }} files</p>
                        </div>

                        <!-- Active Sessions -->
                        <div class="group text-center p-4 backdrop-blur-sm rounded-xl border transition-all duration-300 hover:scale-105" 
                             style="background: linear-gradient(135deg, rgba(129, 140, 248, 0.2), rgba(99, 102, 241, 0.2)); border-color: rgba(129, 140, 248, 0.4);">
                            <div class="w-10 h-10 bg-gradient-to-br from-indigo-400 to-indigo-600 rounded-lg flex items-center justify-center mx-auto mb-3">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                </svg>
                            </div>
                            <p class="text-xl font-black mb-1 text-white">{{ $serverMetrics['active_sessions'] ?? 0 }}</p>
                            <p class="text-xs font-bold uppercase text-white">Sessions</p>
                            <p class="text-xs text-gray-400 mt-1">Active 15min</p>
                        </div>

                        <!-- Background Sync -->
                        <div class="group text-center p-4 backdrop-blur-sm rounded-xl border transition-all duration-300 hover:scale-105" 
                             style="background: linear-gradient(135deg, rgba(236, 72, 153, 0.2), rgba(219, 39, 119, 0.2)); border-color: rgba(236, 72, 153, 0.4);">
                            <div class="w-10 h-10 bg-gradient-to-br from-pink-400 to-pink-600 rounded-lg flex items-center justify-center mx-auto mb-3">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                            </div>
                            <p class="text-xl font-black mb-1 text-white">{{ $applicationMetrics['background_sync']['active'] ?? 0 }}</p>
                            <p class="text-xs font-bold uppercase text-white">Sync Jobs</p>
                            <p class="text-xs text-gray-400 mt-1">{{ $applicationMetrics['background_sync']['status'] ?? 'idle' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Enhanced Footer with MPP TRADE Branding -->
        <div class="relative">
            <!-- Background elements with MPP Colors -->
            <div class="absolute inset-0 backdrop-blur-sm rounded-xl" style="background: linear-gradient(135deg, rgba(31, 41, 55, 0.5), rgba(17, 24, 39, 0.3), rgba(31, 41, 55, 0.5));"></div>
            
            <div class="relative z-10 text-center py-8 px-6">
                <!-- Main branding with MPP Colors -->
                <div class="flex items-center justify-center space-x-4 mb-4">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center shadow-lg" style="background: linear-gradient(45deg, #e0ac7e, #d1975a);">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <div class="text-center">
                        <h3 class="text-xl font-black" style="color: #e0ac7e !important;">
                            PPM ENTERPRISE v1.0
                        </h3>
                        <p class="text-sm text-gray-400 font-medium">Prestashop Product Manager • Professional Dashboard</p>
                    </div>
                </div>
                
                <!-- System info -->
                <div class="flex items-center justify-center space-x-8 mb-4 text-sm">
                    <div class="flex items-center space-x-2">
                        <div class="w-2 h-2 rounded-full animate-pulse" style="background-color: #059669;"></div>
                        <span class="text-gray-300 font-medium">System Online</span>
                    </div>
                    <div class="text-gray-400">
                        <span class="font-medium">Ostatnie odświeżenie:</span> {{ now()->format('d.m.Y H:i:s') }}
                    </div>
                </div>
                
                <!-- MPP TRADE Motto -->
                <div class="pt-4" style="border-top: 1px solid rgba(224, 172, 126, 0.2);">
                    <p class="text-lg font-black tracking-widest" style="color: #e0ac7e !important;">
                        /// TWORZYMY PASJE /// DOSTARCZAMY EMOCJE ///
                    </p>
                    <p class="text-xs text-gray-500 mt-2 font-medium">
                        Powered by <span class="font-bold" style="color: #e0ac7e;">MPP TRADE</span> Technology Stack
                    </p>
                </div>
            </div>
            
            <!-- Bottom glow line with MPP Colors -->
            <div class="absolute bottom-0 left-0 right-0 h-px" style="background: linear-gradient(to right, transparent, rgba(224, 172, 126, 0.6), transparent);"></div>
        </div>
        
            </div>
        </div>
    </div>
</div>

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

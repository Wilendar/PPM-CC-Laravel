<div x-data="adminDashboard({{ $refreshInterval }})" x-init="init()" class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <!-- Dashboard Header -->
    <div class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        Admin Dashboard
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        System monitoring i zarządzanie PPM
                    </p>
                </div>
                
                <!-- Dashboard Controls -->
                <div class="flex items-center space-x-4">
                    <!-- Auto-refresh Toggle -->
                    <div class="flex items-center space-x-2">
                        <label for="autoRefresh" class="text-sm text-gray-700 dark:text-gray-300">
                            Auto-refresh:
                        </label>
                        <select wire:model="refreshInterval" 
                                @change="updateRefreshInterval($event.target.value)"
                                class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                            <option value="30">30s</option>
                            <option value="60">1min</option>
                            <option value="300">5min</option>
                        </select>
                    </div>
                    
                    <!-- Manual Refresh -->
                    <button wire:click="loadDashboardData" 
                            class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Odśwież
                    </button>
                    
                    <!-- Notification Bell -->
                    <button @click="toggleNotifications()" 
                            class="relative p-2 text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-3.5-3.5a50.002 50.002 0 00-7-7A50.002 50.002 0 003.5 13.5L0 17h5m10 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                        </svg>
                        <span x-show="hasUnreadNotifications" 
                              class="absolute -top-0.5 -right-0.5 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-red-100 bg-red-600 rounded-full">
                            !
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div wire:loading class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 flex items-center space-x-3">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            <span class="text-gray-900 dark:text-white">Ładowanie dashboard...</span>
        </div>
    </div>

    <!-- Dashboard Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Quick Stats Row -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
            <!-- Total Products Widget -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg border border-gray-200 dark:border-gray-700">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                    Produkty
                                </dt>
                                <dd class="flex items-baseline">
                                    <div class="text-2xl font-semibold text-gray-900 dark:text-white">
                                        {{ number_format($stats['total_products']['total']) }}
                                    </div>
                                    @if($stats['total_products']['trend'] !== 0)
                                        <div class="ml-2 flex items-baseline text-sm font-semibold {{ $stats['total_products']['trend'] > 0 ? 'text-green-600' : 'text-red-600' }}">
                                            <svg class="w-3 h-3 flex-shrink-0 {{ $stats['total_products']['trend'] > 0 ? 'text-green-500' : 'text-red-500 rotate-180' }}" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                                            </svg>
                                            <span>{{ $stats['total_products']['trend_percentage'] }}%</span>
                                        </div>
                                    @endif
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Active Users Widget -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg border border-gray-200 dark:border-gray-700">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                    Aktywni użytkownicy
                                </dt>
                                <dd class="flex items-baseline">
                                    <div class="text-2xl font-semibold text-gray-900 dark:text-white">
                                        {{ $stats['active_users']['today'] }}
                                    </div>
                                    <div class="ml-2 text-sm text-gray-500 dark:text-gray-400">
                                        / {{ $stats['active_users']['total_users'] }}
                                    </div>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Integration Status Widget -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg border border-gray-200 dark:border-gray-700">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-purple-500 rounded-md flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                    Integracje
                                </dt>
                                <dd class="flex items-center space-x-2">
                                    @foreach($stats['integration_status'] as $service => $status)
                                        <div class="flex items-center">
                                            <div class="w-2 h-2 rounded-full {{ $status['status'] === 'healthy' ? 'bg-green-400' : ($status['status'] === 'warning' ? 'bg-yellow-400' : 'bg-red-400') }}"></div>
                                            <span class="ml-1 text-xs text-gray-600 dark:text-gray-300 capitalize">{{ $service }}</span>
                                        </div>
                                    @endforeach
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity Widget -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg border border-gray-200 dark:border-gray-700">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-orange-500 rounded-md flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                    Aktywność (24h)
                                </dt>
                                <dd class="text-2xl font-semibold text-gray-900 dark:text-white">
                                    {{ $stats['recent_activity'] }}
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- System Health Widget -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg border border-gray-200 dark:border-gray-700">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 {{ $stats['system_health']['status'] === 'healthy' ? 'bg-green-500' : ($stats['system_health']['status'] === 'warning' ? 'bg-yellow-500' : 'bg-red-500') }} rounded-md flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                    System
                                </dt>
                                <dd class="text-lg font-semibold {{ $stats['system_health']['status'] === 'healthy' ? 'text-green-600' : ($stats['system_health']['status'] === 'warning' ? 'text-yellow-600' : 'text-red-600') }}">
                                    {{ ucfirst($stats['system_health']['status']) }}
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Widgets Grid (draggable) -->
        <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-8"
             x-data="{
                dragging: null,
                startDrag(e, key){ this.dragging = key; e.dataTransfer.effectAllowed='move'; },
                over(e){ e.preventDefault(); e.dataTransfer.dropEffect='move'; },
                drop(e, key){ e.preventDefault(); if(this.dragging===null||this.dragging===key) return; const order=[];
                    const cards=[...$el.querySelectorAll('[data-widget]')];
                    const draggingEl = cards.find(c=>c.dataset.widget===this.dragging);
                    const targetEl = cards.find(c=>c.dataset.widget===key);
                    if(!draggingEl||!targetEl) return;
                    const draggingOrder = parseInt(draggingEl.style.order||'0');
                    const targetOrder = parseInt(targetEl.style.order||'0');
                    draggingEl.style.order = targetOrder;
                    targetEl.style.order = draggingOrder;
                    [...$el.querySelectorAll('[data-widget]')]
                        .sort((a,b)=> (parseInt(a.style.order||'0')||0) - (parseInt(b.style.order||'0')||0))
                        .forEach((c,i)=>{ c.style.order = i+1; order.push(c.dataset.widget); });
                    $wire.reorderWidgets(order);
                    this.dragging=null;
                }
             }">
            
            <!-- Performance Metrics Widget -->
            @if($showPerformanceWidget)
            <div class="lg:col-span-1 xl:col-span-1" data-widget="performance"
                 draggable="true"
                 @dragstart="startDrag($event, 'performance')"
                 @dragover="over($event)"
                 @drop="drop($event, 'performance')"
                 style="order: {{ $this->getWidgetOrder('performance') }}">
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                                System Performance
                            </h3>
                            <button wire:click="toggleWidget('Performance')" 
                                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            <!-- CPU Usage -->
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">CPU Usage</span>
                                <span class="text-sm text-gray-500 dark:text-gray-400">{{ $performance['cpu_usage']['current'] }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $performance['cpu_usage']['current'] }}%"></div>
                            </div>

                            <!-- Memory Usage -->
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Memory</span>
                                <span class="text-sm text-gray-500 dark:text-gray-400">{{ $performance['memory_usage']['used'] }} / {{ $performance['memory_usage']['limit'] }}</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-green-600 h-2 rounded-full" style="width: {{ $performance['memory_usage']['percentage'] }}%"></div>
                            </div>

                            <!-- Database Connections -->
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">DB Connections</span>
                                <span class="text-sm text-gray-500 dark:text-gray-400">{{ $performance['database_connections']['active'] }} / {{ $performance['database_connections']['max'] }}</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-purple-600 h-2 rounded-full" style="width: {{ $performance['database_connections']['percentage'] }}%"></div>
                            </div>

                            <!-- Response Time -->
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Avg Response Time</span>
                                <span class="text-sm text-gray-500 dark:text-gray-400">{{ $performance['response_time']['current'] }}ms</span>
                            </div>

                            <!-- Active Sessions -->
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Active Sessions</span>
                                <span class="text-sm text-gray-500 dark:text-gray-400">{{ $performance['active_sessions'] }}</span>
                            </div>

                            <div class="border-t border-gray-200 dark:border-gray-700 my-4"></div>

                            <!-- Queue Jobs -->
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Queue Jobs</span>
                                <span class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $performance['queue_jobs']['pending'] ?? '—' }} pending /
                                    {{ $performance['queue_jobs']['failed'] ?? '—' }} failed
                                </span>
                            </div>

                            <!-- Cache Hit Rate -->
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Cache Hit Rate</span>
                                <span class="text-sm text-gray-500 dark:text-gray-400">
                                    @if($performance['cache_hit_rate']['supported'] ?? false)
                                        {{ $performance['cache_hit_rate']['percentage'] }}%
                                    @else
                                        N/A
                                    @endif
                                </span>
                            </div>

                            <!-- Log Files Size -->
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Log Files</span>
                                <span class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $performance['log_files']['files'] ?? '—' }} files / {{ $performance['log_files']['size_human'] ?? '—' }}
                                </span>
                            </div>

                            <!-- Background Sync -->
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Background Sync</span>
                                <span class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $performance['background_sync']['running'] ?? '—' }} running /
                                    {{ $performance['background_sync']['pending'] ?? '—' }} pending /
                                    {{ $performance['background_sync']['failed'] ?? '—' }} failed
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Business Intelligence Widget -->
            @if($showBusinessWidget)
            <div class="lg:col-span-1 xl:col-span-1" data-widget="business"
                 draggable="true"
                 @dragstart="startDrag($event, 'business')"
                 @dragover="over($event)"
                 @drop="drop($event, 'business')"
                 style="order: {{ $this->getWidgetOrder('business') }}">
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                                Business Intelligence
                            </h3>
                            <button wire:click="toggleWidget('Business')" 
                                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="w-3 h-3 bg-green-400 rounded-full mr-3"></div>
                                    <span class="text-sm text-gray-700 dark:text-gray-300">Produkty dodane dziś</span>
                                </div>
                                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $businessMetrics['products_added_today'] }}</span>
                            </div>

                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="w-3 h-3 bg-yellow-400 rounded-full mr-3"></div>
                                    <span class="text-sm text-gray-700 dark:text-gray-300">Kategorie bez produktów</span>
                                </div>
                                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $businessMetrics['categories_without_products'] }}</span>
                            </div>

                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="w-3 h-3 bg-red-400 rounded-full mr-3"></div>
                                    <span class="text-sm text-gray-700 dark:text-gray-300">Produkty bez zdjęć</span>
                                </div>
                                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $businessMetrics['products_missing_images'] }}</span>
                            </div>

                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="w-3 h-3 bg-orange-400 rounded-full mr-3"></div>
                                    <span class="text-sm text-gray-700 dark:text-gray-300">Niespójne ceny</span>
                                </div>
                                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $businessMetrics['price_inconsistencies'] }}</span>
                            </div>

                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="w-3 h-3 bg-purple-400 rounded-full mr-3"></div>
                                    <span class="text-sm text-gray-700 dark:text-gray-300">Konflikty integracji</span>
                                </div>
                                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $businessMetrics['integration_conflicts'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Charts Widget -->
            @if($showChartsWidget)
            <div class="lg:col-span-2 xl:col-span-1" data-widget="charts"
                 draggable="true"
                 @dragstart="startDrag($event, 'charts')"
                 @dragover="over($event)"
                 @drop="drop($event, 'charts')"
                 style="order: {{ $this->getWidgetOrder('charts') }}">
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                                Produkty według kategorii
                            </h3>
                            <button wire:click="toggleWidget('Charts')" 
                                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="space-y-3">
                            @foreach($charts['products_by_category'] as $category)
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="w-3 h-3 rounded-full mr-3" style="background-color: {{ $category['color'] }}"></div>
                                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ Str::limit($category['name'], 20) }}</span>
                                </div>
                                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $category['count'] }}</span>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- Recent Activity Section -->
        <div class="mt-8">
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                        Ostatnia aktywność
                    </h3>
                </div>
                <div class="p-6">
                    @if($recentActivity->count() > 0)
                        <div class="flow-root">
                            <ul class="-mb-8">
                                @foreach($recentActivity as $index => $activity)
                                <li>
                                    <div class="relative pb-8">
                                        @if(!$loop->last)
                                        <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200 dark:bg-gray-600"></span>
                                        @endif
                                        <div class="relative flex space-x-3">
                                            <div>
                                                <span class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center ring-8 ring-white dark:ring-gray-800">
                                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                                    </svg>
                                                </span>
                                            </div>
                                            <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                                <div>
                                                    <p class="text-sm text-gray-900 dark:text-white">
                                                        <strong>{{ $activity['user'] }}</strong> - {{ $activity['action'] }}
                                                    </p>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                                        {{ $activity['ip_address'] }}
                                                    </p>
                                                </div>
                                                <div class="text-right text-sm whitespace-nowrap text-gray-500 dark:text-gray-400">
                                                    {{ $activity['timestamp']->diffForHumans() }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                                @endforeach
                            </ul>
                        </div>
                    @else
                        <div class="text-center py-6">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Brak aktywności</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                W ciągu ostatnich 24 godzin nie zarejestrowano żadnej aktywności.
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Notification Panel -->
    <div x-show="showNotifications" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 transform scale-95"
         x-transition:enter-end="opacity-100 transform scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="opacity-100 transform scale-100"
         x-transition:leave-end="opacity-0 transform scale-95"
         @click.away="showNotifications = false"
         class="fixed inset-0 z-50 overflow-y-auto">
        
        <div class="flex items-start justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-black bg-opacity-25 transition-opacity"></div>
            
            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                                System Notifications
                            </h3>
                            <div class="mt-2">
                                <div class="space-y-3">
                                    <!-- Sample notifications -->
                                    <div class="flex items-start space-x-3">
                                        <div class="flex-shrink-0">
                                            <div class="w-3 h-3 bg-green-400 rounded-full mt-2"></div>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm text-gray-900 dark:text-white">System działa prawidłowo</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">2 minuty temu</p>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-start space-x-3">
                                        <div class="flex-shrink-0">
                                            <div class="w-3 h-3 bg-blue-400 rounded-full mt-2"></div>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm text-gray-900 dark:text-white">Nowy użytkownik zarejestrowany</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">15 minut temu</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" @click="showNotifications = false"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Zamknij
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Alpine.js Dashboard Controller -->
<script>
function adminDashboard(refreshInterval) {
    return {
        refreshInterval: refreshInterval,
        showNotifications: false,
        hasUnreadNotifications: false,
        refreshTimer: null,
        
        init() {
            this.startAutoRefresh();
            
            // Listen for Livewire events
            Livewire.on('dashboardRefreshed', () => {
                console.log('Dashboard refreshed successfully');
            });
            
            Livewire.on('showAlert', (message, type) => {
                this.showAlert(message, type);
            });
        },
        
        startAutoRefresh() {
            if (this.refreshTimer) {
                clearInterval(this.refreshTimer);
            }
            
            this.refreshTimer = setInterval(() => {
                Livewire.emit('refreshDashboard');
            }, this.refreshInterval * 1000);
        },
        
        updateRefreshInterval(newInterval) {
            this.refreshInterval = parseInt(newInterval);
            this.startAutoRefresh();
            Livewire.emit('updateRefreshInterval', this.refreshInterval);
        },
        
        toggleNotifications() {
            this.showNotifications = !this.showNotifications;
            if (this.showNotifications) {
                this.hasUnreadNotifications = false;
            }
        },
        
        showAlert(message, type = 'info') {
            // Create toast notification
            const toast = document.createElement('div');
            toast.className = `fixed top-4 right-4 z-50 p-4 mb-4 text-sm rounded-lg ${
                type === 'error' ? 'text-red-800 bg-red-50 dark:bg-gray-800 dark:text-red-400' :
                type === 'warning' ? 'text-yellow-800 bg-yellow-50 dark:bg-gray-800 dark:text-yellow-300' :
                type === 'success' ? 'text-green-800 bg-green-50 dark:bg-gray-800 dark:text-green-400' :
                'text-blue-800 bg-blue-50 dark:bg-gray-800 dark:text-blue-400'
            }`;
            
            toast.innerHTML = `
                <div class="flex items-center">
                    <div class="ml-3 text-sm font-normal">${message}</div>
                    <button type="button" class="ml-auto -mx-1.5 -my-1.5 rounded-lg focus:ring-2 p-1.5 hover:bg-gray-100 inline-flex h-8 w-8" onclick="this.parentElement.parentElement.remove()">
                        <span class="sr-only">Close</span>
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            `;
            
            document.body.appendChild(toast);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                if (toast && toast.parentNode) {
                    toast.remove();
                }
            }, 5000);
        }
    }
}
</script>

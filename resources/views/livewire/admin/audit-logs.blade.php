<div x-data="auditLogs()" class="max-w-7xl mx-auto">
    <!-- Header z stats i alerts -->
    <div class="mb-6 flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white">
                Logi Audytu
            </h1>
            <p class="text-gray-400 mt-1">
                Monitoring i analiza aktywności w systemie PPM
            </p>
        </div>
        
        <!-- Quick stats -->
        <div class="flex flex-wrap gap-4">
            <div class="bg-blue-900/20 px-3 py-2 rounded-lg">
                <div class="text-sm text-blue-400">Łącznie logów</div>
                <div class="font-bold text-blue-100">{{ number_format($activityStats['total_logs'] ?? 0) }}</div>
            </div>
            <div class="bg-green-900/20 px-3 py-2 rounded-lg">
                <div class="text-sm text-green-400">Aktywnych użytkowników</div>
                <div class="font-bold text-green-100">{{ $activityStats['unique_users'] ?? 0 }}</div>
            </div>
            <div class="bg-purple-900/20 px-3 py-2 rounded-lg">
                <div class="text-sm text-purple-400">Unikalnych IP</div>
                <div class="font-bold text-purple-100">{{ $activityStats['unique_ips'] ?? 0 }}</div>
            </div>
        </div>
    </div>

    <!-- Suspicious activity alerts -->
    @if(!empty($suspiciousActivities) && $showSuspiciousAlerts)
    <div class="mb-6 space-y-3">
        @foreach($suspiciousActivities as $index => $activity)
        <div class="bg-{{ $activity['severity'] === 'high' ? 'red' : 'yellow' }}-900/20 border border-{{ $activity['severity'] === 'high' ? 'red' : 'yellow' }}-800 rounded-lg p-4">
            <div class="flex items-start justify-between">
                <div class="flex items-start space-x-3">
                    <div class="flex-shrink-0">
                        <svg class="w-5 h-5 text-{{ $activity['severity'] === 'high' ? 'red' : 'yellow' }}-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"></path>
                        </svg>
                    </div>
                    <div>
                        <div class="text-{{ $activity['severity'] === 'high' ? 'red' : 'yellow' }}-200 font-medium">
                            Podejrzana aktywność: {{ $activity['type'] }}
                        </div>
                        <div class="text-{{ $activity['severity'] === 'high' ? 'red' : 'yellow' }}-300 text-sm mt-1">
                            {{ $activity['message'] }}
                        </div>
                    </div>
                </div>
                <button wire:click="dismissSuspiciousActivity({{ $index }})" 
                        class="text-{{ $activity['severity'] === 'high' ? 'red' : 'yellow' }}-400 hover:text-{{ $activity['severity'] === 'high' ? 'red' : 'yellow' }}-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    <!-- Controls bar -->
    <div class="bg-gray-800 rounded-lg shadow-sm border border-gray-700 p-4 mb-6">
        <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
            <!-- Search and view mode -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center space-y-2 sm:space-y-0 sm:space-x-4">
                <div class="relative">
                    <input type="text" 
                           wire:model.debounce.300ms="search"
                           placeholder="Szukaj w logach..."
                           class="pl-10 pr-4 py-2 border border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-gray-700 text-white">
                    <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                
                <div class="flex items-center space-x-2 bg-gray-700 rounded-lg p-1">
                    <button wire:click="setViewMode('table')" 
                            :class="{ 'bg-gray-800 shadow-sm': $wire.viewMode === 'table' }"
                            class="px-3 py-1.5 text-sm rounded-md transition-colors">
                        Tabela
                    </button>
                    <button wire:click="setViewMode('timeline')" 
                            :class="{ 'bg-gray-800 shadow-sm': $wire.viewMode === 'timeline' }"
                            class="px-3 py-1.5 text-sm rounded-md transition-colors">
                        Timeline
                    </button>
                    <button wire:click="setViewMode('chart')" 
                            :class="{ 'bg-gray-800 shadow-sm': $wire.viewMode === 'chart' }"
                            class="px-3 py-1.5 text-sm rounded-md transition-colors">
                        Wykresy
                    </button>
                </div>
            </div>
            
            <!-- Quick filters and actions -->
            <div class="flex flex-wrap items-center gap-2">
                <button wire:click="setQuickFilter('today')" 
                        class="px-3 py-1 text-xs bg-gray-700 hover:bg-gray-600 rounded-full transition-colors">
                    Dzisiaj
                </button>
                <button wire:click="setQuickFilter('week')" 
                        class="px-3 py-1 text-xs bg-gray-700 hover:bg-gray-600 rounded-full transition-colors">
                    Tydzień
                </button>
                <button wire:click="setQuickFilter('month')" 
                        class="px-3 py-1 text-xs bg-gray-700 hover:bg-gray-600 rounded-full transition-colors">
                    Miesiąc
                </button>
                
                <div class="h-4 border-l border-gray-600"></div>
                
                <button wire:click="$toggle('showFilters')" 
                        :class="{ 'bg-blue-900 text-blue-200': $wire.showFilters }"
                        class="px-3 py-1.5 text-sm rounded-lg hover:bg-gray-700 transition-colors">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                    </svg>
                    Filtry
                </button>
                
                <button wire:click="openExportModal"
                        class="btn-enterprise-ghost">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Eksport
                </button>
            </div>
        </div>
        
        <!-- Advanced filters -->
        @if($showFilters)
        <div class="mt-4 pt-4 border-t border-gray-600 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-300 mb-1">Użytkownik</label>
                <select wire:model="userFilter" 
                        class="w-full px-3 py-2 text-sm border border-gray-600 rounded focus:ring-2 focus:ring-blue-500 bg-gray-700 text-white">
                    <option value="all">Wszyscy</option>
                    <option value="system">System</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}">{{ $user->full_name }}</option>
                    @endforeach
                </select>
            </div>
            
            <div>
                <label class="block text-xs font-medium text-gray-300 mb-1">Akcja</label>
                <select wire:model="actionFilter" 
                        class="w-full px-3 py-2 text-sm border border-gray-600 rounded focus:ring-2 focus:ring-blue-500 bg-gray-700 text-white">
                    <option value="all">Wszystkie</option>
                    @foreach($actions as $action)
                        <option value="{{ $action }}">{{ ucfirst(str_replace('_', ' ', $action)) }}</option>
                    @endforeach
                </select>
            </div>
            
            <div>
                <label class="block text-xs font-medium text-gray-300 mb-1">Model</label>
                <select wire:model="modelFilter" 
                        class="w-full px-3 py-2 text-sm border border-gray-600 rounded focus:ring-2 focus:ring-blue-500 bg-gray-700 text-white">
                    <option value="all">Wszystkie</option>
                    @foreach($models as $model)
                        <option value="{{ $model['short'] }}">{{ $model['short'] }}</option>
                    @endforeach
                </select>
            </div>
            
            <div>
                <label class="block text-xs font-medium text-gray-300 mb-1">Od daty</label>
                <input type="date" 
                       wire:model="dateFromFilter"
                       class="w-full px-3 py-2 text-sm border border-gray-600 rounded focus:ring-2 focus:ring-blue-500 bg-gray-700 text-white">
            </div>
            
            <div>
                <label class="block text-xs font-medium text-gray-300 mb-1">Do daty</label>
                <input type="date" 
                       wire:model="dateToFilter"
                       class="w-full px-3 py-2 text-sm border border-gray-600 rounded focus:ring-2 focus:ring-blue-500 bg-gray-700 text-white">
            </div>
            
            <div class="flex items-end">
                <label class="flex items-center">
                    <input type="checkbox" 
                           wire:model="suspiciousOnly"
                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                    <span class="ml-2 text-sm text-gray-300">Tylko podejrzane</span>
                </label>
            </div>
        </div>
        
        @if($search || $userFilter !== 'all' || $actionFilter !== 'all' || $modelFilter !== 'all' || $suspiciousOnly)
        <div class="mt-4 pt-4 border-t border-gray-600 flex items-center justify-between">
            <div class="text-sm text-gray-400">
                Zastosowane filtry: 
                @if($search) <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-blue-900/50 text-blue-200 ml-1">Szukaj: {{ $search }}</span> @endif
                @if($userFilter !== 'all') <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-green-900/50 text-green-200 ml-1">Użytkownik</span> @endif
                @if($actionFilter !== 'all') <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-purple-900/50 text-purple-200 ml-1">Akcja</span> @endif
                @if($suspiciousOnly) <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-red-900/50 text-red-200 ml-1">Podejrzane</span> @endif
            </div>
            <button wire:click="clearFilters" 
                    class="text-sm text-blue-400 hover:text-blue-200">
                Wyczyść filtry
            </button>
        </div>
        @endif
        @endif
    </div>

    <!-- Main content area -->
    @if($viewMode === 'table')
    <!-- Table view -->
    <div class="bg-gray-800 rounded-lg shadow-sm border border-gray-700 overflow-hidden">
        @if($logs->count())
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider cursor-pointer"
                                wire:click="sortBy('created_at')">
                                Data
                                @include('components.sort-icon', ['field' => 'created_at'])
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider cursor-pointer"
                                wire:click="sortBy('user_id')">
                                Użytkownik
                                @include('components.sort-icon', ['field' => 'user_id'])
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider cursor-pointer"
                                wire:click="sortBy('action')">
                                Akcja
                                @include('components.sort-icon', ['field' => 'action'])
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                                Model
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                                IP Address
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                                Zmiany
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                                Akcje
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-gray-800 divide-y divide-gray-700">
                        @foreach($logs as $log)
                        <tr class="hover:bg-gray-700/50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-100">
                                <div>{{ $log->created_at->format('d.m.Y H:i') }}</div>
                                <div class="text-xs text-gray-400">{{ $log->created_at->diffForHumans() }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($log->user)
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-8 w-8">
                                            <img class="h-8 w-8 rounded-full" 
                                                 src="{{ $log->user->avatar_url }}" 
                                                 alt="{{ $log->user->full_name }}">
                                        </div>
                                        <div class="ml-3">
                                            <div class="text-sm font-medium text-gray-100">
                                                {{ $log->user->full_name }}
                                            </div>
                                            <div class="text-xs text-gray-400">
                                                {{ $log->user->getRoleNames()->first() }}
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-8 w-8 bg-gray-600 rounded-full flex items-center justify-center">
                                            <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                            </svg>
                                        </div>
                                        <div class="ml-3">
                                            <div class="text-sm font-medium text-gray-400">
                                                System
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    {{ str_contains($log->action, 'create') ? 'bg-green-900/50 text-green-200' : '' }}
                                    {{ str_contains($log->action, 'update') ? 'bg-blue-900/50 text-blue-200' : '' }}
                                    {{ str_contains($log->action, 'delete') ? 'bg-red-900/50 text-red-200' : '' }}
                                    {{ str_contains($log->action, 'login') ? 'bg-purple-900/50 text-purple-200' : '' }}
                                    {{ str_contains($log->action, 'failed') ? 'bg-red-900/50 text-red-200' : '' }}
                                    {{ !str_contains($log->action, 'create') && !str_contains($log->action, 'update') && !str_contains($log->action, 'delete') && !str_contains($log->action, 'login') && !str_contains($log->action, 'failed') ? 'bg-gray-700 text-gray-200' : '' }}">
                                    {{ ucfirst(str_replace('_', ' ', $log->action)) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-100">
                                @if($log->model_type && $log->model_id)
                                    <div>{{ class_basename($log->model_type) }}</div>
                                    <div class="text-xs text-gray-400">ID: {{ $log->model_id }}</div>
                                @else
                                    <span class="text-gray-500">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-100">
                                {{ $log->ip_address ?: '-' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-100">
                                @if($log->old_values || $log->new_values)
                                    @php $changes = $this->getFormattedChanges($log); @endphp
                                    @if(count($changes) > 0)
                                        <div class="max-w-xs">
                                            @foreach(array_slice($changes, 0, 2) as $field => $change)
                                                <div class="text-xs">
                                                    <span class="font-medium">{{ $change['field'] }}:</span>
                                                    <span class="text-red-400">{{ Str::limit($change['old'], 20) }}</span>
                                                    →
                                                    <span class="text-green-400">{{ Str::limit($change['new'], 20) }}</span>
                                                </div>
                                            @endforeach
                                            @if(count($changes) > 2)
                                                <div class="text-xs text-gray-400">
                                                    +{{ count($changes) - 2 }} więcej...
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                @else
                                    <span class="text-gray-500">Brak zmian</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-2">
                                    <button wire:click="showLogDetails({{ $log->id }})" 
                                            class="text-blue-400 hover:text-blue-200">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </button>
                                    @if($log->old_values || $log->new_values)
                                    <button wire:click="showLogDiff({{ $log->id }})" 
                                            class="text-green-400 hover:text-green-200">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div class="px-6 py-4 border-t border-gray-700">
                {{ $logs->links() }}
            </div>
        @else
            <div class="p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <h3 class="mt-4 text-sm font-medium text-white">Brak logów</h3>
                <p class="mt-1 text-sm text-gray-400">
                    Nie znaleziono logów audytu spełniających kryteria wyszukiwania.
                </p>
            </div>
        @endif
    </div>
    @endif

    <!-- Log Details Modal -->
    @if($showDetailsModal && $selectedLog)
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" 
                 wire:click="closeDetailsModal"></div>
            
            <div class="inline-block align-bottom bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                <div class="bg-gray-800 px-6 pt-6 pb-4">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg leading-6 font-medium text-white">
                            Szczegóły wpisu audytu
                        </h3>
                        <button wire:click="closeDetailsModal" 
                                class="text-gray-400 hover:text-gray-200">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Basic info -->
                        <div>
                            <h4 class="font-medium text-white mb-3">Informacje podstawowe</h4>
                            <dl class="space-y-2">
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-400">Data:</dt>
                                    <dd class="text-sm text-gray-100">{{ $selectedLog->created_at->format('d.m.Y H:i:s') }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-400">Użytkownik:</dt>
                                    <dd class="text-sm text-gray-100">{{ $selectedLog->user->full_name ?? 'System' }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-400">Akcja:</dt>
                                    <dd class="text-sm text-gray-100">{{ $selectedLog->action }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-400">Model:</dt>
                                    <dd class="text-sm text-gray-100">{{ class_basename($selectedLog->model_type) }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-400">ID Modelu:</dt>
                                    <dd class="text-sm text-gray-100">{{ $selectedLog->model_id }}</dd>
                                </div>
                            </dl>
                        </div>
                        
                        <!-- Technical info -->
                        <div>
                            <h4 class="font-medium text-white mb-3">Informacje techniczne</h4>
                            <dl class="space-y-2">
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-400">IP Address:</dt>
                                    <dd class="text-sm text-gray-100 font-mono">{{ $selectedLog->ip_address }}</dd>
                                </div>
                                <div class="flex flex-col">
                                    <dt class="text-sm text-gray-400 mb-1">User Agent:</dt>
                                    <dd class="text-xs text-gray-100 font-mono bg-gray-700 p-2 rounded">{{ $selectedLog->user_agent }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                    
                    <!-- Changes -->
                    @if($selectedLog->old_values || $selectedLog->new_values)
                    <div class="mt-6">
                        <h4 class="font-medium text-white mb-3">Zmiany</h4>
                        @php $changes = $this->getFormattedChanges($selectedLog); @endphp
                        @if(count($changes) > 0)
                            <div class="space-y-3">
                                @foreach($changes as $field => $change)
                                <div class="bg-gray-700 rounded-lg p-4">
                                    <div class="font-medium text-sm text-white mb-2">{{ $change['field'] }}</div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <div class="text-xs text-red-400 font-medium mb-1">Poprzednia wartość:</div>
                                            <div class="text-sm bg-red-900/20 p-2 rounded font-mono">{{ $change['old'] ?? 'NULL' }}</div>
                                        </div>
                                        <div>
                                            <div class="text-xs text-green-400 font-medium mb-1">Nowa wartość:</div>
                                            <div class="text-sm bg-green-900/20 p-2 rounded font-mono">{{ $change['new'] ?? 'NULL' }}</div>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    @endif
                </div>
                
                <div class="bg-gray-700 px-6 py-3 flex justify-end">
                    <button wire:click="closeDetailsModal" 
                            class="btn-enterprise-secondary">
                        Zamknij
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Export Modal -->
    @if($showExportModal)
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" 
                 wire:click="closeExportModal"></div>
            
            <div class="inline-block align-bottom bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-gray-800 px-6 pt-6 pb-4">
                    <h3 class="text-lg leading-6 font-medium text-white mb-4">
                        Eksport logów audytu
                    </h3>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">Format eksportu</label>
                            <select wire:model="exportFormat" 
                                    class="w-full px-3 py-2 border border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-gray-700 text-white">
                                <option value="excel">Excel (.xlsx)</option>
                                <option value="csv">CSV (.csv)</option>
                                <option value="pdf">PDF (.pdf)</option>
                            </select>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-2">Data od</label>
                                <input type="date" 
                                       wire:model="exportDateFrom"
                                       class="w-full px-3 py-2 border border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-gray-700 text-white">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-2">Data do</label>
                                <input type="date" 
                                       wire:model="exportDateTo"
                                       class="w-full px-3 py-2 border border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-gray-700 text-white">
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">Pola do eksportu</label>
                            <div class="space-y-2">
                                @foreach($exportFields as $field => $enabled)
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           wire:model="exportFields.{{ $field }}"
                                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    <span class="ml-2 text-sm text-gray-300">
                                        {{ ucfirst(str_replace('_', ' ', $field)) }}
                                    </span>
                                </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-700 px-6 py-3 flex justify-between">
                    <button wire:click="closeExportModal" 
                            class="btn-enterprise-secondary">
                        Anuluj
                    </button>
                    <button wire:click="exportLogs" 
                            class="btn-enterprise-primary">
                        Eksportuj
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<script>
function auditLogs() {
    return {
        init() {
            // Initialize component
        }
    }
}
</script>
<div class="api-management">
    <!-- Page Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">API Management</h1>
            <p class="text-gray-600">Monitor i zarządzaj API systemu</p>
        </div>
        <div class="flex items-center space-x-3">
            <!-- Auto-refresh toggle -->
            <div class="flex items-center space-x-2">
                <label class="text-sm text-gray-600">Auto-odświeżanie:</label>
                <button wire:click="toggleAutoRefresh" 
                        class="relative inline-flex h-6 w-11 items-center rounded-full {{ $autoRefresh ? 'bg-blue-600' : 'bg-gray-200' }}">
                    <span class="inline-block h-4 w-4 transform rounded-full bg-white transition {{ $autoRefresh ? 'translate-x-6' : 'translate-x-1' }}"></span>
                </button>
                @if($autoRefresh)
                    <select wire:model="refreshInterval" class="text-sm border-gray-300 rounded">
                        <option value="15">15s</option>
                        <option value="30">30s</option>
                        <option value="60">60s</option>
                    </select>
                @endif
            </div>
            <button wire:click="refreshData" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                <i class="fas fa-sync-alt mr-2"></i>
                Odśwież
            </button>
        </div>
    </div>

    <!-- Health Status Alert -->
    @if($healthStatus['status'] !== 'healthy')
        <div class="mb-6 p-4 rounded-lg {{ $healthStatus['status'] === 'critical' ? 'bg-red-100 border border-red-400' : 'bg-yellow-100 border border-yellow-400' }}">
            <div class="flex items-center">
                <i class="{{ $this->getHealthIcon($healthStatus['status']) }} {{ $this->getHealthColor($healthStatus['status']) }} mr-3"></i>
                <div>
                    <h3 class="font-medium {{ $this->getHealthColor($healthStatus['status']) }}">
                        Status API: {{ ucfirst($healthStatus['status']) }}
                    </h3>
                    @if(count($healthStatus['issues']) > 0)
                        <ul class="text-sm {{ $healthStatus['status'] === 'critical' ? 'text-red-700' : 'text-yellow-700' }} mt-1">
                            @foreach($healthStatus['issues'] as $issue)
                                <li>• {{ $issue }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-2 bg-blue-100 rounded-lg">
                    <i class="fas fa-exchange-alt text-blue-600"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Wszystkie żądania</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($statistics['total_requests']) }}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-2 bg-green-100 rounded-lg">
                    <i class="fas fa-check-circle text-green-600"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Wskaźnik sukcesu</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $statistics['success_rate'] }}%</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-2 bg-yellow-100 rounded-lg">
                    <i class="fas fa-clock text-yellow-600"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Śr. czas odpowiedzi</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $statistics['avg_response_time'] }}ms</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-2 bg-red-100 rounded-lg">
                    <i class="fas fa-shield-alt text-red-600"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Podejrzane żądania</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $statistics['suspicious_requests'] }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Daily Trends Chart -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Trendy dzienne (7 dni)</h3>
            <canvas id="dailyTrendsChart" height="200"></canvas>
        </div>

        <!-- Hourly Distribution Chart -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Rozkład godzinowy (dziś)</h3>
            <canvas id="hourlyChart" height="200"></canvas>
        </div>
    </div>

    <!-- Performance Metrics -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <!-- Response Time Percentiles -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Percentyle czasu odpowiedzi</h3>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">50% (mediana)</span>
                    <span class="font-medium">{{ $responseTimePercentiles['p50'] }}ms</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">90%</span>
                    <span class="font-medium">{{ $responseTimePercentiles['p90'] }}ms</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">95%</span>
                    <span class="font-medium">{{ $responseTimePercentiles['p95'] }}ms</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">99%</span>
                    <span class="font-medium">{{ $responseTimePercentiles['p99'] }}ms</span>
                </div>
            </div>
        </div>

        <!-- Top Error Endpoints -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Najczęściej błędne endpointy</h3>
            <div class="space-y-2">
                @forelse($topErrors->take(5) as $error)
                    <div class="flex justify-between items-center">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">{{ $error->endpoint }}</p>
                            <p class="text-xs text-gray-500">HTTP {{ $error->response_code }}</p>
                        </div>
                        <span class="ml-2 px-2 py-1 text-xs bg-red-100 text-red-800 rounded">
                            {{ $error->error_count }}
                        </span>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">Brak błędów w wybranym okresie</p>
                @endforelse
            </div>
        </div>

        <!-- Suspicious Activity -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Podejrzana aktywność</h3>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">Całkowicie podejrzane</span>
                    <span class="font-medium">{{ $suspiciousActivity['total_suspicious'] }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">Unikalne IP</span>
                    <span class="font-medium">{{ $suspiciousActivity['unique_ips'] }}</span>
                </div>
                @if($suspiciousActivity['suspicious_patterns']->count() > 0)
                    <div class="mt-3">
                        <p class="text-xs text-gray-500 mb-2">Top wzorce:</p>
                        @foreach($suspiciousActivity['suspicious_patterns']->take(3) as $pattern => $count)
                            <div class="text-xs text-gray-700">{{ $pattern }} ({{ $count }})</div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Main Content with Tabs -->
    <div class="bg-white rounded-lg shadow">
        <!-- Tabs -->
        <div class="border-b border-gray-200">
            <nav class="flex space-x-8 px-6" aria-label="Tabs">
                <button wire:click="setActiveTab('overview')" 
                        class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'overview' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                    Przegląd
                </button>
                <button wire:click="setActiveTab('logs')" 
                        class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'logs' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                    Logi API
                </button>
                <button wire:click="setActiveTab('endpoints')" 
                        class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'endpoints' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                    Endpointy
                </button>
                <button wire:click="setActiveTab('users')" 
                        class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'users' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                    Użytkownicy
                </button>
                <button wire:click="setActiveTab('errors')" 
                        class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'errors' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                    Błędy
                </button>
                <button wire:click="setActiveTab('suspicious')" 
                        class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'suspicious' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                    Podejrzane
                </button>
            </nav>
        </div>

        <!-- Filters -->
        <div class="p-6 border-b border-gray-200">
            <div class="flex flex-wrap items-center gap-4">
                <!-- Date Range -->
                <div class="flex items-center space-x-2">
                    <label class="text-sm font-medium text-gray-700">Od:</label>
                    <input type="datetime-local" wire:model="dateFrom" class="border-gray-300 rounded-md text-sm">
                </div>

                <div class="flex items-center space-x-2">
                    <label class="text-sm font-medium text-gray-700">Do:</label>
                    <input type="datetime-local" wire:model="dateTo" class="border-gray-300 rounded-md text-sm">
                </div>

                <!-- Endpoint Filter -->
                <div class="flex items-center space-x-2">
                    <label class="text-sm font-medium text-gray-700">Endpoint:</label>
                    <select wire:model="selectedEndpoint" class="border-gray-300 rounded-md text-sm">
                        <option value="">Wszystkie endpointy</option>
                        @foreach($this->getEndpoints() as $endpoint)
                            <option value="{{ $endpoint }}">{{ $endpoint }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Status Filter -->
                <div class="flex items-center space-x-2">
                    <label class="text-sm font-medium text-gray-700">Status:</label>
                    <select wire:model="selectedStatus" class="border-gray-300 rounded-md text-sm">
                        <option value="">Wszystkie statusy</option>
                        @foreach($this->getStatusOptions() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Quick Filters -->
                <label class="flex items-center">
                    <input type="checkbox" wire:model="showSuspiciousOnly" class="rounded border-gray-300">
                    <span class="ml-2 text-sm text-gray-700">Tylko podejrzane</span>
                </label>

                <label class="flex items-center">
                    <input type="checkbox" wire:model="showSlowOnly" class="rounded border-gray-300">
                    <span class="ml-2 text-sm text-gray-700">Tylko powolne</span>
                </label>

                <!-- Actions -->
                <div class="flex items-center space-x-2 ml-auto">
                    <button wire:click="clearFilters" class="text-sm text-blue-600 hover:text-blue-800">
                        Wyczyść filtry
                    </button>
                    <button wire:click="exportLogs" class="px-3 py-2 bg-green-600 text-white text-sm rounded hover:bg-green-700">
                        <i class="fas fa-download mr-1"></i>
                        Export CSV
                    </button>
                    <button wire:click="cleanupOldLogs" 
                            class="px-3 py-2 bg-red-600 text-white text-sm rounded hover:bg-red-700"
                            onclick="return confirm('Czy na pewno chcesz usunąć stare logi?')">
                        <i class="fas fa-trash mr-1"></i>
                        Wyczyść stare
                    </button>
                </div>
            </div>
        </div>

        <!-- Tab Content -->
        @if($activeTab === 'overview' || $activeTab === 'logs')
            <!-- API Logs Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Czas</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Endpoint</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Metoda</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Czas</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Użytkownik</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Flagi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($recentLogs as $log)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $log->requested_at->format('H:i:s') }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900 max-w-xs truncate">
                                    {{ $log->endpoint }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <span class="px-2 py-1 text-xs font-medium rounded 
                                        {{ $log->method === 'GET' ? 'bg-blue-100 text-blue-800' : '' }}
                                        {{ $log->method === 'POST' ? 'bg-green-100 text-green-800' : '' }}
                                        {{ $log->method === 'PUT' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                        {{ $log->method === 'DELETE' ? 'bg-red-100 text-red-800' : '' }}">
                                        {{ $log->method }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <span class="px-2 py-1 text-xs font-medium rounded {{ $log->status_color }}
                                        {{ $log->isSuccessful() ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $log->response_code }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <span class="{{ $log->performance_color }}">
                                        {{ $log->response_time_ms }}ms
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $log->ip_address }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $log->user ? $log->user->name : 'Anonim' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <div class="flex space-x-1">
                                        @if($log->suspicious)
                                            <span class="px-2 py-1 text-xs bg-red-100 text-red-800 rounded" title="Podejrzane">
                                                <i class="fas fa-shield-alt"></i>
                                            </span>
                                        @endif
                                        @if($log->isSlow(5000))
                                            <span class="px-2 py-1 text-xs bg-yellow-100 text-yellow-800 rounded" title="Powolne">
                                                <i class="fas fa-turtle"></i>
                                            </span>
                                        @endif
                                        @if($log->rate_limited)
                                            <span class="px-2 py-1 text-xs bg-orange-100 text-orange-800 rounded" title="Rate Limited">
                                                <i class="fas fa-ban"></i>
                                            </span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @if($log->error_message)
                                <tr class="bg-red-50">
                                    <td colspan="8" class="px-6 py-2 text-sm text-red-700">
                                        <strong>Błąd:</strong> {{ $log->error_message }}
                                    </td>
                                </tr>
                            @endif
                            @if($log->security_notes)
                                <tr class="bg-yellow-50">
                                    <td colspan="8" class="px-6 py-2 text-sm text-yellow-700">
                                        <strong>Uwagi bezpieczeństwa:</strong> {{ $log->security_notes }}
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                    <i class="fas fa-search text-2xl mb-2"></i>
                                    <p>Brak logów API spełniających kryteria</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($recentLogs->hasPages())
                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $recentLogs->links() }}
                </div>
            @endif

        @elseif($activeTab === 'endpoints')
            <!-- Endpoint Statistics -->
            <div class="p-6">
                <div class="grid grid-cols-1 gap-4">
                    @forelse($endpointStats as $stat)
                        <div class="border border-gray-200 rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <h4 class="font-medium text-gray-900">{{ $stat->endpoint }}</h4>
                                    <div class="mt-2 grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                        <div>
                                            <span class="text-gray-500">Żądania:</span>
                                            <span class="font-medium ml-1">{{ number_format($stat->total_requests) }}</span>
                                        </div>
                                        <div>
                                            <span class="text-gray-500">Śr. czas:</span>
                                            <span class="font-medium ml-1">{{ $stat->avg_response_time }}ms</span>
                                        </div>
                                        <div>
                                            <span class="text-gray-500">Błędy:</span>
                                            <span class="font-medium ml-1 {{ $stat->error_rate > 10 ? 'text-red-600' : 'text-green-600' }}">
                                                {{ $stat->error_rate }}%
                                            </span>
                                        </div>
                                        <div>
                                            <span class="text-gray-500">Podejrzane:</span>
                                            <span class="font-medium ml-1">{{ $stat->suspicious_count }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-center text-gray-500 py-8">Brak danych o endpointach</p>
                    @endforelse
                </div>
            </div>

        @elseif($activeTab === 'users')
            <!-- User Statistics -->
            <div class="p-6">
                <div class="grid grid-cols-1 gap-4">
                    @forelse($userStats as $stat)
                        <div class="border border-gray-200 rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <h4 class="font-medium text-gray-900">
                                        {{ $stat->user ? $stat->user->name : 'Anonim' }}
                                        @if($stat->user)
                                            <span class="text-sm text-gray-500 ml-2">{{ $stat->user->email }}</span>
                                        @endif
                                    </h4>
                                    <div class="mt-2 grid grid-cols-2 md:grid-cols-5 gap-4 text-sm">
                                        <div>
                                            <span class="text-gray-500">Żądania:</span>
                                            <span class="font-medium ml-1">{{ number_format($stat->total_requests) }}</span>
                                        </div>
                                        <div>
                                            <span class="text-gray-500">Śr. czas:</span>
                                            <span class="font-medium ml-1">{{ $stat->avg_response_time }}ms</span>
                                        </div>
                                        <div>
                                            <span class="text-gray-500">Błędy:</span>
                                            <span class="font-medium ml-1">{{ $stat->error_rate }}%</span>
                                        </div>
                                        <div>
                                            <span class="text-gray-500">Endpointy:</span>
                                            <span class="font-medium ml-1">{{ $stat->unique_endpoints }}</span>
                                        </div>
                                        <div>
                                            <span class="text-gray-500">Ostatnie:</span>
                                            <span class="font-medium ml-1">{{ Carbon\Carbon::parse($stat->last_request)->diffForHumans() }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-center text-gray-500 py-8">Brak danych o użytkownikach</p>
                    @endforelse
                </div>
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('livewire:load', function () {
    let autoRefreshInterval;

    // Daily Trends Chart
    const dailyTrendsCtx = document.getElementById('dailyTrendsChart').getContext('2d');
    const dailyTrendsData = @json($dailyTrends);
    
    new Chart(dailyTrendsCtx, {
        type: 'line',
        data: {
            labels: dailyTrendsData.map(d => d.date),
            datasets: [{
                label: 'Żądania',
                data: dailyTrendsData.map(d => d.total_requests),
                borderColor: 'rgb(59, 130, 246)',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.1
            }, {
                label: 'Błędy',
                data: dailyTrendsData.map(d => d.errors),
                borderColor: 'rgb(239, 68, 68)',
                backgroundColor: 'rgba(239, 68, 68, 0.1)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Hourly Distribution Chart
    const hourlyCtx = document.getElementById('hourlyChart').getContext('2d');
    const hourlyData = @json(array_values($hourlyDistribution));
    
    new Chart(hourlyCtx, {
        type: 'bar',
        data: {
            labels: Array.from({length: 24}, (_, i) => i + ':00'),
            datasets: [{
                label: 'Żądania',
                data: hourlyData.map(h => h.requests),
                backgroundColor: 'rgba(59, 130, 246, 0.8)',
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Auto-refresh functionality
    window.addEventListener('startAutoRefresh', event => {
        const interval = event.detail * 1000;
        autoRefreshInterval = setInterval(() => {
            @this.call('refreshData');
        }, interval);
    });

    window.addEventListener('stopAutoRefresh', event => {
        if (autoRefreshInterval) {
            clearInterval(autoRefreshInterval);
            autoRefreshInterval = null;
        }
    });

    window.addEventListener('updateRefreshInterval', event => {
        if (autoRefreshInterval) {
            clearInterval(autoRefreshInterval);
            const interval = event.detail * 1000;
            autoRefreshInterval = setInterval(() => {
                @this.call('refreshData');
            }, interval);
        }
    });
});
</script>
@endpush
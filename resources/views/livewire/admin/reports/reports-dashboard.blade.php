<div class="reports-dashboard">
    <!-- Page Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-white">Raporty i Analytics</h1>
            <p class="text-gray-600">Zarządzaj raportami systemowymi i analizuj dane</p>
        </div>
        <button wire:click="showGenerateModal" 
                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center">
            <i class="fas fa-plus mr-2"></i>
            Generuj raport
        </button>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-2 bg-blue-100 rounded-lg">
                    <i class="fas fa-chart-bar text-blue-600"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Wszystkie raporty</p>
                    <p class="text-2xl font-bold text-white">{{ $statistics['total_reports'] ?? 0 }}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-2 bg-green-100 rounded-lg">
                    <i class="fas fa-check-circle text-green-600"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Ukończone</p>
                    <p class="text-2xl font-bold text-white">{{ $statistics['completed_reports'] ?? 0 }}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-2 bg-yellow-100 rounded-lg">
                    <i class="fas fa-spinner text-yellow-600"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Generowane</p>
                    <p class="text-2xl font-bold text-white">{{ $statistics['generating_reports'] ?? 0 }}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-2 bg-red-100 rounded-lg">
                    <i class="fas fa-exclamation-triangle text-red-600"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Błędy</p>
                    <p class="text-2xl font-bold text-white">{{ $statistics['failed_reports'] ?? 0 }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Report Trends Chart -->
        <div class="bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-white mb-4">Trendy generowania raportów (30 dni)</h3>
            <canvas id="reportTrendsChart" height="200"></canvas>
        </div>

        <!-- Type Distribution Chart -->
        <div class="bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-white mb-4">Rozkład typów raportów</h3>
            <canvas id="typeDistributionChart" height="200"></canvas>
        </div>
    </div>

    <!-- Latest Reports -->
    <div class="bg-gray-800 rounded-lg shadow mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-white">Najnowsze raporty</h3>
        </div>
        <div class="p-6">
            @if($latestReports->count() > 0)
                <div class="space-y-4">
                    @foreach($latestReports as $report)
                        <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                            <div class="flex items-center space-x-4">
                                <div class="flex-shrink-0">
                                    <i class="{{ $report->status_icon }} {{ $report->status_color }}"></i>
                                </div>
                                <div>
                                    <h4 class="text-sm font-medium text-white">{{ $report->name }}</h4>
                                    <p class="text-sm text-gray-600">
                                        {{ $report->getTypeLabel() }} • {{ $report->getPeriodLabel() }} • 
                                        {{ $report->report_date->format('d.m.Y') }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                @if($report->status === 'completed')
                                    <button wire:click="downloadReport({{ $report->id }})"
                                            class="text-blue-600 hover:text-blue-800">
                                        <i class="fas fa-download"></i>
                                    </button>
                                @endif
                                <span class="text-xs text-gray-500">
                                    {{ $report->created_at->diffForHumans() }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500 text-center py-8">Brak raportów do wyświetlenia</p>
            @endif
        </div>
    </div>

    <!-- Main Reports Section -->
    <div class="bg-gray-800 rounded-lg shadow">
        <!-- Tabs -->
        <div class="border-b border-gray-200">
            <nav class="flex space-x-8 px-6" aria-label="Tabs">
                <button wire:click="setActiveTab('overview')" 
                        class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'overview' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                    Przegląd
                </button>
                <button wire:click="setActiveTab('completed')" 
                        class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'completed' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                    Ukończone ({{ $statistics['completed_reports'] ?? 0 }})
                </button>
                <button wire:click="setActiveTab('generating')" 
                        class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'generating' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                    Generowane
                </button>
                <button wire:click="setActiveTab('failed')" 
                        class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'failed' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                    Błędy
                </button>
                <button wire:click="setActiveTab('usage')" 
                        class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'usage' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                    Usage Analytics
                </button>
                <button wire:click="setActiveTab('performance')" 
                        class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'performance' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                    Performance
                </button>
                <button wire:click="setActiveTab('business')" 
                        class="py-4 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'business' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                    Business Intelligence
                </button>
            </nav>
        </div>

        <!-- Filters -->
        <div class="p-6 border-b border-gray-200">
            <div class="flex flex-wrap items-center gap-4">
                <!-- Type Filter -->
                <div class="flex items-center space-x-2">
                    <label class="text-sm font-medium text-gray-700">Typ:</label>
                    <select wire:model="selectedType" class="border-gray-300 rounded-md text-sm">
                        <option value="">Wszystkie typy</option>
                        @foreach($this->getReportTypes() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Period Filter -->
                <div class="flex items-center space-x-2">
                    <label class="text-sm font-medium text-gray-700">Okres:</label>
                    <select wire:model="selectedPeriod" class="border-gray-300 rounded-md text-sm">
                        <option value="">Wszystkie okresy</option>
                        @foreach($this->getPeriods() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Date Range -->
                <div class="flex items-center space-x-2">
                    <label class="text-sm font-medium text-gray-700">Od:</label>
                    <input type="date" wire:model="dateFrom" class="border-gray-300 rounded-md text-sm">
                </div>

                <div class="flex items-center space-x-2">
                    <label class="text-sm font-medium text-gray-700">Do:</label>
                    <input type="date" wire:model="dateTo" class="border-gray-300 rounded-md text-sm">
                </div>

                <!-- Clear Filters -->
                @if($selectedType || $selectedPeriod || $dateFrom !== now()->subDays(7)->format('Y-m-d') || $dateTo !== now()->format('Y-m-d'))
                    <button wire:click="clearFilters" 
                            class="text-sm text-blue-600 hover:text-blue-800">
                        Wyczyść filtry
                    </button>
                @endif
            </div>
        </div>

        <!-- Reports List -->
        <div class="divide-y divide-gray-200">
            @forelse($reports as $report)
                <div class="p-6 hover:bg-gray-50">
                    <div class="flex items-start justify-between">
                        <div class="flex items-start space-x-4 flex-1">
                            <div class="flex-shrink-0 pt-1">
                                <i class="{{ $report->status_icon }} {{ $report->status_color }} text-xl"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="text-lg font-medium text-white">
                                    {{ $report->name }}
                                </h3>
                                <div class="mt-1 flex items-center space-x-4 text-sm text-gray-500">
                                    <span>
                                        <i class="fas fa-tag mr-1"></i>
                                        {{ $report->getTypeLabel() }}
                                    </span>
                                    <span>
                                        <i class="fas fa-calendar mr-1"></i>
                                        {{ $report->getPeriodLabel() }}
                                    </span>
                                    <span>
                                        <i class="fas fa-clock mr-1"></i>
                                        {{ $report->report_date->format('d.m.Y') }}
                                    </span>
                                    @if($report->generator)
                                        <span>
                                            <i class="fas fa-user mr-1"></i>
                                            {{ $report->generator->name }}
                                        </span>
                                    @endif
                                    @if($report->generation_time_seconds)
                                        <span>
                                            <i class="fas fa-stopwatch mr-1"></i>
                                            {{ $report->generation_time_seconds }}s
                                        </span>
                                    @endif
                                </div>
                                
                                @if($report->summary && $report->status === 'completed')
                                    <div class="mt-3 p-3 bg-gray-50 rounded-lg">
                                        <p class="text-sm text-gray-700 whitespace-pre-line">{{ $report->summary }}</p>
                                    </div>
                                @endif

                                @if($report->status === 'failed' && $report->metadata && isset($report->metadata['error']))
                                    <div class="mt-3 p-3 bg-red-50 rounded-lg border border-red-200">
                                        <p class="text-sm text-red-700">
                                            <i class="fas fa-exclamation-triangle mr-1"></i>
                                            {{ $report->metadata['error'] }}
                                        </p>
                                    </div>
                                @endif
                            </div>
                        </div>
                        
                        <div class="flex items-center space-x-2 ml-4">
                            @if($report->status === 'completed')
                                <button wire:click="downloadReport({{ $report->id }})" 
                                        class="text-blue-600 hover:text-blue-800 p-2"
                                        title="Pobierz raport">
                                    <i class="fas fa-download"></i>
                                </button>
                            @endif
                            
                            @if(in_array($report->status, ['completed', 'failed']))
                                <button wire:click="regenerateReport({{ $report->id }})" 
                                        class="text-green-600 hover:text-green-800 p-2"
                                        title="Regeneruj raport">
                                    <i class="fas fa-redo"></i>
                                </button>
                            @endif
                            
                            <button wire:click="deleteReport({{ $report->id }})" 
                                    class="text-red-600 hover:text-red-800 p-2"
                                    title="Usuń raport"
                                    onclick="return confirm('Czy na pewno chcesz usunąć ten raport?')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    
                    @if($report->data && $report->status === 'completed' && is_array($report->data))
                        <div class="mt-4 p-3 bg-gray-50 rounded-lg">
                            <h4 class="text-sm font-medium text-gray-700 mb-2">Kluczowe metryki:</h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
                                @foreach(array_slice($report->data, 0, 6) as $key => $section)
                                    @if(is_array($section) && !empty($section))
                                        <div>
                                            <span class="font-medium text-gray-600">{{ ucfirst(str_replace('_', ' ', $key)) }}:</span>
                                            <span class="text-gray-800">
                                                @if(is_numeric(array_values($section)[0]))
                                                    {{ array_values($section)[0] ?? 'N/A' }}
                                                @else
                                                    {{ count($section) }} elementów
                                                @endif
                                            </span>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @empty
                <div class="p-12 text-center text-gray-500">
                    <i class="fas fa-chart-bar text-4xl mb-4"></i>
                    <p class="text-lg">Brak raportów spełniających kryteria</p>
                    @if($selectedType || $selectedPeriod || $dateFrom !== now()->subDays(7)->format('Y-m-d') || $dateTo !== now()->format('Y-m-d'))
                        <button wire:click="clearFilters" 
                                class="mt-2 text-blue-600 hover:text-blue-800">
                            Wyczyść filtry aby zobaczyć wszystkie raporty
                        </button>
                    @endif
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        @if($reports->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $reports->links() }}
            </div>
        @endif
    </div>

    <!-- Generate Report Modal -->
    @if($showGenerateModal)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" 
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-gray-800">
                <div class="mt-3">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-white">Generuj nowy raport</h3>
                        <button wire:click="hideGenerateModal" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <form wire:submit.prevent="generateReport" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Typ raportu</label>
                            <select wire:model="generateType" 
                                    class="w-full border-gray-300 rounded-md">
                                @foreach($this->getReportTypes() as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Okres</label>
                            <select wire:model="generatePeriod" 
                                    class="w-full border-gray-300 rounded-md">
                                @foreach($this->getPeriods() as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Data raportu</label>
                            <input type="date" 
                                   wire:model="generateDate" 
                                   max="{{ now()->format('Y-m-d') }}"
                                   class="w-full border-gray-300 rounded-md">
                        </div>
                        
                        <div class="flex items-center justify-end space-x-3 pt-4">
                            <button type="button" 
                                    wire:click="hideGenerateModal"
                                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200">
                                Anuluj
                            </button>
                            <button type="submit"
                                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700">
                                <i class="fas fa-cog mr-2"></i>
                                Generuj
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('livewire:load', function () {
    // Report Trends Chart
    const reportTrendsCtx = document.getElementById('reportTrendsChart').getContext('2d');
    const reportTrendsData = @json($chartData['report_trends'] ?? []);
    
    new Chart(reportTrendsCtx, {
        type: 'line',
        data: {
            labels: Object.keys(reportTrendsData),
            datasets: [{
                label: 'Raporty generowane',
                data: Object.values(reportTrendsData),
                borderColor: 'rgb(59, 130, 246)',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });

    // Type Distribution Chart
    const typeDistributionCtx = document.getElementById('typeDistributionChart').getContext('2d');
    const typeDistributionData = @json($chartData['type_distribution'] ?? []);
    
    const typeLabels = {
        'usage_analytics': 'Usage Analytics',
        'performance': 'Performance',
        'business_intelligence': 'Business Intelligence',
        'integration_performance': 'Integration Performance'
    };
    
    new Chart(typeDistributionCtx, {
        type: 'doughnut',
        data: {
            labels: Object.keys(typeDistributionData).map(key => typeLabels[key] || key),
            datasets: [{
                data: Object.values(typeDistributionData),
                backgroundColor: [
                    'rgba(59, 130, 246, 0.8)',
                    'rgba(16, 185, 129, 0.8)',
                    'rgba(245, 158, 11, 0.8)',
                    'rgba(239, 68, 68, 0.8)'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
});
</script>
@endpush
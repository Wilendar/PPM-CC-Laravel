<div class="reports-dashboard">
    <!-- Page Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-white">Raporty i Analytics</h1>
            <p class="text-gray-400">Zarzadzaj raportami systemowymi i analizuj dane</p>
        </div>
        <button wire:click="openGenerateModal"
                class="btn-enterprise-primary flex items-center">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Generuj raport
        </button>
    </div>

    <!-- Flash Messages -->
    @if(session()->has('success'))
        <div class="mb-4 p-3 rounded-lg bg-green-900/30 text-green-300 border border-green-800">
            {{ session('success') }}
        </div>
    @endif
    @if(session()->has('error'))
        <div class="mb-4 p-3 rounded-lg bg-red-900/30 text-red-300 border border-red-800">
            {{ session('error') }}
        </div>
    @endif

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-gray-800/50 rounded-lg border border-gray-700 p-6">
            <div class="flex items-center">
                <div class="p-2 bg-blue-900/50 rounded-lg">
                    <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-400">Wszystkie raporty</p>
                    <p class="text-2xl font-bold text-white">{{ $statistics['total_reports'] ?? 0 }}</p>
                </div>
            </div>
        </div>

        <div class="bg-gray-800/50 rounded-lg border border-gray-700 p-6">
            <div class="flex items-center">
                <div class="p-2 bg-green-900/50 rounded-lg">
                    <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-400">Ukonczone</p>
                    <p class="text-2xl font-bold text-white">{{ $statistics['completed_reports'] ?? 0 }}</p>
                </div>
            </div>
        </div>

        <div class="bg-gray-800/50 rounded-lg border border-gray-700 p-6">
            <div class="flex items-center">
                <div class="p-2 bg-yellow-900/50 rounded-lg">
                    <svg class="w-5 h-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-400">Generowane</p>
                    <p class="text-2xl font-bold text-white">{{ $statistics['generating_reports'] ?? 0 }}</p>
                </div>
            </div>
        </div>

        <div class="bg-gray-800/50 rounded-lg border border-gray-700 p-6">
            <div class="flex items-center">
                <div class="p-2 bg-red-900/50 rounded-lg">
                    <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-400">Bledy</p>
                    <p class="text-2xl font-bold text-white">{{ $statistics['failed_reports'] ?? 0 }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <div class="bg-gray-800/50 rounded-lg border border-gray-700 p-6">
            <h3 class="text-lg font-medium text-white mb-4">Trendy generowania raportow (30 dni)</h3>
            <div class="relative h-64">
                <canvas id="reportTrendsChart"></canvas>
            </div>
        </div>
        <div class="bg-gray-800/50 rounded-lg border border-gray-700 p-6">
            <h3 class="text-lg font-medium text-white mb-4">Rozklad typow raportow</h3>
            <div class="relative h-64">
                <canvas id="typeDistributionChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Latest Reports -->
    <div class="bg-gray-800/50 rounded-lg border border-gray-700 mb-6">
        <div class="px-6 py-4 border-b border-gray-700">
            <h3 class="text-lg font-medium text-white">Najnowsze raporty</h3>
        </div>
        <div class="p-6">
            @if($latestReports->count() > 0)
                <div class="space-y-4">
                    @foreach($latestReports as $report)
                        <div class="flex items-center justify-between p-4 border border-gray-700 rounded-lg hover:bg-gray-700/50 transition-colors">
                            <div class="flex items-center space-x-4">
                                <div class="flex-shrink-0">
                                    <i class="{{ $report->status_icon }} {{ $report->status_color }}"></i>
                                </div>
                                <div>
                                    <h4 class="text-sm font-medium text-white">{{ $report->name }}</h4>
                                    <p class="text-sm text-gray-400">
                                        {{ $report->getTypeLabel() }} &bull; {{ $report->getPeriodLabel() }} &bull;
                                        {{ $report->report_date->format('d.m.Y') }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                @if($report->status === 'completed')
                                    <button wire:click="downloadReport({{ $report->id }})"
                                            class="text-blue-400 hover:text-blue-300">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
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
                <p class="text-gray-500 text-center py-8">Brak raportow do wyswietlenia</p>
            @endif
        </div>
    </div>

    <!-- Main Reports Section -->
    <div class="bg-gray-800/50 rounded-lg border border-gray-700">
        <!-- Tabs -->
        <div class="border-b border-gray-700">
            <nav class="flex space-x-8 px-6" aria-label="Tabs">
                @php
                    $tabs = [
                        'overview' => 'Przeglad',
                        'completed' => 'Ukonczone (' . ($statistics['completed_reports'] ?? 0) . ')',
                        'generating' => 'Generowane',
                        'failed' => 'Bledy',
                        'usage' => 'Usage Analytics',
                        'performance' => 'Performance',
                        'business' => 'Business Intelligence',
                    ];
                @endphp
                @foreach($tabs as $tabKey => $tabLabel)
                    <button wire:click="setActiveTab('{{ $tabKey }}')"
                            class="py-4 px-1 border-b-2 font-medium text-sm transition-colors {{ $activeTab === $tabKey ? 'border-[#e0ac7e] text-[#e0ac7e]' : 'border-transparent text-gray-400 hover:text-gray-300' }}">
                        {{ $tabLabel }}
                    </button>
                @endforeach
            </nav>
        </div>

        <!-- Filters -->
        <div class="p-6 border-b border-gray-700">
            <div class="flex flex-wrap items-center gap-4">
                <div class="flex items-center space-x-2">
                    <label class="text-sm font-medium text-gray-400">Typ:</label>
                    <select wire:model.live="selectedType" class="form-input-enterprise text-sm py-1.5">
                        <option value="">Wszystkie typy</option>
                        @foreach($this->getReportTypes() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-center space-x-2">
                    <label class="text-sm font-medium text-gray-400">Okres:</label>
                    <select wire:model.live="selectedPeriod" class="form-input-enterprise text-sm py-1.5">
                        <option value="">Wszystkie okresy</option>
                        @foreach($this->getPeriods() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-center space-x-2">
                    <label class="text-sm font-medium text-gray-400">Od:</label>
                    <input type="date" wire:model.live="dateFrom" class="form-input-enterprise text-sm py-1.5">
                </div>

                <div class="flex items-center space-x-2">
                    <label class="text-sm font-medium text-gray-400">Do:</label>
                    <input type="date" wire:model.live="dateTo" class="form-input-enterprise text-sm py-1.5">
                </div>

                @if($selectedType || $selectedPeriod || $dateFrom !== now()->subDays(7)->format('Y-m-d') || $dateTo !== now()->format('Y-m-d'))
                    <button wire:click="clearFilters"
                            class="text-sm text-[#e0ac7e] hover:text-[#d1975a]">
                        Wyczysc filtry
                    </button>
                @endif
            </div>
        </div>

        <!-- Reports List -->
        <div class="divide-y divide-gray-700">
            @forelse($reports as $report)
                <div class="p-6 hover:bg-gray-700/30 transition-colors">
                    <div class="flex items-start justify-between">
                        <div class="flex items-start space-x-4 flex-1">
                            <div class="flex-shrink-0 pt-1">
                                <i class="{{ $report->status_icon }} {{ $report->status_color }} text-xl"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="text-lg font-medium text-white">
                                    {{ $report->name }}
                                </h3>
                                <div class="mt-1 flex items-center space-x-4 text-sm text-gray-400">
                                    <span>
                                        <svg class="w-3.5 h-3.5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                                        {{ $report->getTypeLabel() }}
                                    </span>
                                    <span>
                                        <svg class="w-3.5 h-3.5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        {{ $report->getPeriodLabel() }}
                                    </span>
                                    <span>
                                        <svg class="w-3.5 h-3.5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        {{ $report->report_date->format('d.m.Y') }}
                                    </span>
                                    @if($report->generator)
                                        <span>
                                            <svg class="w-3.5 h-3.5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                            {{ $report->generator->name }}
                                        </span>
                                    @endif
                                    @if($report->generation_time_seconds)
                                        <span>
                                            <svg class="w-3.5 h-3.5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                            {{ $report->generation_time_seconds }}s
                                        </span>
                                    @endif
                                </div>

                                @if($report->summary && $report->status === 'completed')
                                    <div class="mt-3 p-3 bg-gray-700/50 rounded-lg border border-gray-600">
                                        <p class="text-sm text-gray-300 whitespace-pre-line">{{ $report->summary }}</p>
                                    </div>
                                @endif

                                @if($report->status === 'failed' && $report->metadata && isset($report->metadata['error']))
                                    <div class="mt-3 p-3 bg-red-900/30 rounded-lg border border-red-800">
                                        <p class="text-sm text-red-300">
                                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                                            {{ $report->metadata['error'] }}
                                        </p>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="flex items-center space-x-1 ml-4">
                            @if($report->status === 'completed')
                                <button wire:click="downloadReport({{ $report->id }})"
                                        class="p-2 text-blue-400 hover:text-blue-300 hover:bg-blue-900/20 rounded transition-colors"
                                        title="Pobierz raport">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                </button>
                            @endif

                            @if(in_array($report->status, ['completed', 'failed']))
                                <button wire:click="regenerateReport({{ $report->id }})"
                                        class="p-2 text-green-400 hover:text-green-300 hover:bg-green-900/20 rounded transition-colors"
                                        title="Regeneruj raport">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                </button>
                            @endif

                            <button wire:click="deleteReport({{ $report->id }})"
                                    wire:confirm="Czy na pewno chcesz usunac ten raport?"
                                    class="p-2 text-red-400 hover:text-red-300 hover:bg-red-900/20 rounded transition-colors"
                                    title="Usun raport">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                    </div>

                    @if($report->data && $report->status === 'completed' && is_array($report->data))
                        <div class="mt-4">
                            <button wire:click="toggleReportDetails({{ $report->id }})"
                                    class="flex items-center text-sm text-[#e0ac7e] hover:text-[#d1975a] transition-colors mb-2">
                                <svg class="w-4 h-4 mr-1 transition-transform {{ $expandedReportId === $report->id ? 'rotate-90' : '' }}"
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                                {{ $expandedReportId === $report->id ? 'Ukryj szczegoly' : 'Pokaz szczegoly raportu' }}
                            </button>

                            @if($expandedReportId === $report->id)
                                <div class="space-y-3">
                                    @foreach($report->data as $sectionKey => $sectionData)
                                        <div class="p-4 bg-gray-700/50 rounded-lg border border-gray-600">
                                            <h4 class="text-sm font-semibold text-[#e0ac7e] mb-3 uppercase tracking-wider">
                                                {{ ucfirst(str_replace('_', ' ', $sectionKey)) }}
                                            </h4>

                                            @if(is_string($sectionData))
                                                <p class="text-sm text-gray-300 whitespace-pre-line">{{ $sectionData }}</p>
                                            @elseif(is_array($sectionData))
                                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                                    @foreach($sectionData as $metricKey => $metricValue)
                                                        <div class="p-3 bg-gray-800/50 rounded border border-gray-600">
                                                            <span class="block text-xs font-medium text-gray-400 mb-1">
                                                                {{ ucfirst(str_replace('_', ' ', $metricKey)) }}
                                                            </span>
                                                            @if(is_array($metricValue))
                                                                @if(empty($metricValue))
                                                                    <span class="text-sm text-gray-500">Brak danych</span>
                                                                @else
                                                                    <div class="space-y-1">
                                                                        @foreach(array_slice($metricValue, 0, 10) as $subKey => $subValue)
                                                                            <div class="flex justify-between text-xs">
                                                                                <span class="text-gray-400">{{ $subKey }}:</span>
                                                                                <span class="text-gray-200 font-mono">{{ is_numeric($subValue) ? number_format($subValue, is_float($subValue) ? 2 : 0) : $subValue }}</span>
                                                                            </div>
                                                                        @endforeach
                                                                        @if(count($metricValue) > 10)
                                                                            <span class="text-xs text-gray-500">+ {{ count($metricValue) - 10 }} wiecej...</span>
                                                                        @endif
                                                                    </div>
                                                                @endif
                                                            @elseif(is_numeric($metricValue))
                                                                <span class="text-lg font-bold text-white">
                                                                    {{ is_float($metricValue) ? number_format($metricValue, 2) : number_format($metricValue) }}
                                                                </span>
                                                            @elseif(is_null($metricValue))
                                                                <span class="text-sm text-gray-500">N/A</span>
                                                            @else
                                                                <span class="text-sm text-gray-200">{{ $metricValue }}</span>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @else
                                                <span class="text-sm text-gray-300">{{ $sectionData }}</span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            @empty
                <div class="p-12 text-center text-gray-500">
                    <svg class="w-12 h-12 mx-auto mb-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    <p class="text-lg">Brak raportow spelniajacych kryteria</p>
                    @if($selectedType || $selectedPeriod || $dateFrom !== now()->subDays(7)->format('Y-m-d') || $dateTo !== now()->format('Y-m-d'))
                        <button wire:click="clearFilters"
                                class="mt-2 text-[#e0ac7e] hover:text-[#d1975a]">
                            Wyczysc filtry aby zobaczyc wszystkie raporty
                        </button>
                    @endif
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        @if($reports->hasPages())
            <div class="px-6 py-4 border-t border-gray-700">
                {{ $reports->links() }}
            </div>
        @endif
    </div>

    <!-- Generate Report Modal -->
    @if($showGenerateModal)
        <div class="fixed inset-0 bg-black/60 overflow-y-auto h-full w-full layer-overlay"
             wire:click.self="hideGenerateModal">
            <div class="relative top-20 mx-auto p-6 w-96 bg-gray-800 border border-gray-700 rounded-lg shadow-xl">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-white">Generuj nowy raport</h3>
                    <button wire:click="closeGenerateModal" class="text-gray-400 hover:text-gray-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form wire:submit.prevent="generateReport" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Typ raportu</label>
                        <select wire:model="generateType" class="form-input-enterprise w-full">
                            @foreach($this->getReportTypes() as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Okres</label>
                        <select wire:model="generatePeriod" class="form-input-enterprise w-full">
                            @foreach($this->getPeriods() as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Data raportu</label>
                        <input type="date"
                               wire:model="generateDate"
                               max="{{ now()->format('Y-m-d') }}"
                               class="form-input-enterprise w-full">
                    </div>

                    @error('generateType') <p class="text-red-400 text-sm">{{ $message }}</p> @enderror
                    @error('generatePeriod') <p class="text-red-400 text-sm">{{ $message }}</p> @enderror
                    @error('generateDate') <p class="text-red-400 text-sm">{{ $message }}</p> @enderror

                    <div class="flex items-center justify-end space-x-3 pt-4">
                        <button type="button"
                                wire:click="closeGenerateModal"
                                class="btn-enterprise-secondary">
                            Anuluj
                        </button>
                        <button type="submit"
                                wire:loading.attr="disabled"
                                class="btn-enterprise-primary">
                            <span wire:loading.remove wire:target="generateReport">
                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                Generuj
                            </span>
                            <span wire:loading wire:target="generateReport">
                                <svg class="w-4 h-4 inline mr-1 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                Generowanie...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('livewire:init', function () {
    const reportTrendsCtx = document.getElementById('reportTrendsChart');
    if (reportTrendsCtx) {
        const reportTrendsData = @json($chartData['report_trends'] ?? []);
        new Chart(reportTrendsCtx.getContext('2d'), {
            type: 'line',
            data: {
                labels: Object.keys(reportTrendsData),
                datasets: [{
                    label: 'Raporty generowane',
                    data: Object.values(reportTrendsData),
                    borderColor: 'rgb(96, 165, 250)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { labels: { color: '#9ca3af' } } },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1, color: '#9ca3af' }, grid: { color: 'rgba(75, 85, 99, 0.3)' } },
                    x: { ticks: { color: '#9ca3af' }, grid: { color: 'rgba(75, 85, 99, 0.3)' } }
                }
            }
        });
    }

    const typeDistCtx = document.getElementById('typeDistributionChart');
    if (typeDistCtx) {
        const typeDistributionData = @json($chartData['type_distribution'] ?? []);
        const typeLabels = {
            'usage_analytics': 'Usage Analytics',
            'performance': 'Performance',
            'business_intelligence': 'Business Intelligence',
            'integration_performance': 'Integration Performance'
        };
        new Chart(typeDistCtx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: Object.keys(typeDistributionData).map(key => typeLabels[key] || key),
                datasets: [{
                    data: Object.values(typeDistributionData),
                    backgroundColor: [
                        'rgba(96, 165, 250, 0.7)',
                        'rgba(52, 211, 153, 0.7)',
                        'rgba(251, 191, 36, 0.7)',
                        'rgba(248, 113, 113, 0.7)'
                    ],
                    borderColor: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { labels: { color: '#9ca3af' } } }
            }
        });
    }
});
</script>
@endpush

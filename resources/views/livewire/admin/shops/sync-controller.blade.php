<div class="min-h-screen bg-gradient-to-br from-gray-900 via-gray-800 to-black relative">
    
    <!-- Animated Background Elements with MPP TRADE Colors -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-32 -right-32 w-64 h-64 rounded-full blur-3xl animate-pulse" style="background: radial-gradient(circle, rgba(224, 172, 126, 0.1), rgba(209, 151, 90, 0.05));"></div>
        <div class="absolute -bottom-32 -left-32 w-64 h-64 rounded-full blur-3xl animate-pulse" style="background: radial-gradient(circle, rgba(209, 151, 90, 0.1), rgba(224, 172, 126, 0.05)); animation-delay: 2s;"></div>
    </div>
    
    <!-- Page Header -->
    <div class="relative backdrop-blur-xl shadow-2xl" style="background: linear-gradient(135deg, rgba(31, 41, 55, 0.95), rgba(17, 24, 39, 0.95)); border-bottom: 1px solid rgba(224, 172, 126, 0.3); z-index: 10000;">
        <div class="max-w-7xl mx-auto px-6 sm:px-8 lg:px-12">
            <div class="flex items-center justify-between h-24">
                <div class="flex items-center">
                    <!-- Logo and Title -->
                    <div class="flex-shrink-0">
                        <div class="relative w-12 h-12 rounded-xl flex items-center justify-center shadow-lg transform transition-transform duration-300 hover:scale-105" style="background: linear-gradient(45deg, #e0ac7e, #d1975a);">
                            <svg class="w-7 h-7 text-white relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            <div class="absolute inset-0 rounded-xl opacity-75 blur animate-pulse" style="background: linear-gradient(45deg, #e0ac7e, #d1975a);"></div>
                        </div>
                    </div>
                    <div class="ml-4 flex-1 min-w-0">
                        <h1 class="text-xl font-bold tracking-tight" style="color: #e0ac7e !important;">
                            KONTROLA SYNCHRONIZACJI
                        </h1>
                        <p class="text-xs font-medium text-gray-400 tracking-wide">
                            Zarządzanie synchronizacją sklepów PrestaShop
                        </p>
                    </div>
                </div>

                <!-- Admin Actions & Navigation -->
                <div class="flex items-center gap-3">
                    <!-- Clear Cache + Queue Restart Button - 2025-11-12 -->
                    <button wire:click="clearCacheAndRestartQueue"
                            type="button"
                            class="relative inline-flex items-center px-4 py-3 border border-transparent text-sm font-bold rounded-lg text-white transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl"
                            style="background: linear-gradient(45deg, rgba(124, 58, 237, 0.8), rgba(109, 40, 217, 0.8)); border: 1px solid rgba(124, 58, 237, 0.5);"
                            onmouseover="this.style.background='linear-gradient(45deg, rgba(109, 40, 217, 0.9), rgba(91, 33, 182, 0.9))'"
                            onmouseout="this.style.background='linear-gradient(45deg, rgba(124, 58, 237, 0.8), rgba(109, 40, 217, 0.8))'">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Clear Cache
                    </button>

                    <!-- Back Button -->
                    <a href="{{ route('admin.shops') }}"
                       class="relative inline-flex items-center px-6 py-3 border border-transparent text-sm font-bold rounded-lg text-white transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl"
                       style="background: linear-gradient(45deg, rgba(224, 172, 126, 0.8), rgba(209, 151, 90, 0.8)); border: 1px solid rgba(224, 172, 126, 0.5);"
                       onmouseover="this.style.background='linear-gradient(45deg, rgba(209, 151, 90, 0.9), rgba(194, 132, 73, 0.9))'"
                       onmouseout="this.style.background='linear-gradient(45deg, rgba(224, 172, 126, 0.8), rgba(209, 151, 90, 0.8))'">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Powrót do sklepów
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Content Section -->
    <div class="relative z-10 max-w-7xl mx-auto px-6 sm:px-8 lg:px-12 py-8">

        <!-- Sync Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
            <div class="relative backdrop-blur-xl shadow-lg rounded-lg p-6 border" 
                 style="background: linear-gradient(135deg, rgba(31, 41, 55, 0.8), rgba(17, 24, 39, 0.8)); border: 1px solid rgba(224, 172, 126, 0.2);">
                <div class="flex items-center">
                    <div class="flex-shrink-0 w-8 h-8 bg-blue-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-400">Sklepy</p>
                        <p class="text-2xl font-bold text-white">{{ $stats['total_shops'] }}</p>
                    </div>
                </div>
            </div>

            <div class="relative backdrop-blur-xl shadow-lg rounded-lg p-6 border" 
                 style="background: linear-gradient(135deg, rgba(31, 41, 55, 0.8), rgba(17, 24, 39, 0.8)); border: 1px solid rgba(224, 172, 126, 0.2);">
                <div class="flex items-center">
                    <div class="flex-shrink-0 w-8 h-8 bg-yellow-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-400">Aktywne zadania</p>
                        <p class="text-2xl font-bold text-white">{{ $stats['active_sync_jobs'] }}</p>
                    </div>
                </div>
            </div>

            <div class="relative backdrop-blur-xl shadow-lg rounded-lg p-6 border" 
                 style="background: linear-gradient(135deg, rgba(31, 41, 55, 0.8), rgba(17, 24, 39, 0.8)); border: 1px solid rgba(224, 172, 126, 0.2);">
                <div class="flex items-center">
                    <div class="flex-shrink-0 w-8 h-8 bg-green-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-400">Dzisiaj ukończone</p>
                        <p class="text-2xl font-bold text-white">{{ $stats['completed_today'] }}</p>
                    </div>
                </div>
            </div>

            <div class="relative backdrop-blur-xl shadow-lg rounded-lg p-6 border" 
                 style="background: linear-gradient(135deg, rgba(31, 41, 55, 0.8), rgba(17, 24, 39, 0.8)); border: 1px solid rgba(224, 172, 126, 0.2);">
                <div class="flex items-center">
                    <div class="flex-shrink-0 w-8 h-8 bg-red-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-400">Dzisiaj błędy</p>
                        <p class="text-2xl font-bold text-white">{{ $stats['failed_today'] }}</p>
                    </div>
                </div>
            </div>

            <div class="relative backdrop-blur-xl shadow-lg rounded-lg p-6 border" 
                 style="background: linear-gradient(135deg, rgba(31, 41, 55, 0.8), rgba(17, 24, 39, 0.8)); border: 1px solid rgba(224, 172, 126, 0.2);">
                <div class="flex items-center">
                    <div class="flex-shrink-0 w-8 h-8 bg-orange-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-400">Wymagają sync</p>
                        <p class="text-2xl font-bold text-white">{{ $stats['sync_due_count'] }}</p>
                    </div>
                </div>
            </div>

            <div class="relative backdrop-blur-xl shadow-lg rounded-lg p-6 border"
                 style="background: linear-gradient(135deg, rgba(31, 41, 55, 0.8), rgba(17, 24, 39, 0.8)); border: 1px solid rgba(224, 172, 126, 0.2);">
                <div class="flex items-center">
                    <div class="flex-shrink-0 w-8 h-8 bg-purple-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-400">Średni czas</p>
                        <p class="text-2xl font-bold text-white">{{ number_format($stats['avg_sync_time'], 1) }}s</p>
                    </div>
                </div>
            </div>

            <!-- Queue Infrastructure Stats (Phase 1) -->
            <div class="relative backdrop-blur-xl shadow-lg rounded-lg p-6 border stat-card stat-queue-active"
                 style="background: linear-gradient(135deg, rgba(31, 41, 55, 0.8), rgba(17, 24, 39, 0.8)); border: 1px solid rgba(224, 172, 126, 0.2);">
                <div class="flex items-center">
                    <div class="flex-shrink-0 w-8 h-8 bg-blue-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-400">Aktywne w Kolejce</p>
                        <p class="text-2xl font-bold text-white">{{ $stats['active_queue_jobs'] ?? 0 }}</p>
                        <p class="stat-help">Jobs w queue (pending + processing)</p>
                    </div>
                </div>
            </div>

            <div class="relative backdrop-blur-xl shadow-lg rounded-lg p-6 border stat-card stat-queue-stuck"
                 style="background: linear-gradient(135deg, rgba(31, 41, 55, 0.8), rgba(17, 24, 39, 0.8)); border: 1px solid rgba(224, 172, 126, 0.2);">
                <div class="flex items-center">
                    <div class="flex-shrink-0 w-8 h-8 bg-orange-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-400">Zablokowane</p>
                        <p class="text-2xl font-bold text-white">{{ $stats['stuck_queue_jobs'] ?? 0 }}</p>
                        <p class="stat-help">Jobs >5min bez update</p>
                    </div>
                </div>
            </div>

            <div class="relative backdrop-blur-xl shadow-lg rounded-lg p-6 border stat-card stat-queue-failed"
                 style="background: linear-gradient(135deg, rgba(31, 41, 55, 0.8), rgba(17, 24, 39, 0.8)); border: 1px solid rgba(224, 172, 126, 0.2);">
                <div class="flex items-center">
                    <div class="flex-shrink-0 w-8 h-8 bg-red-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-400">Failed Queue</p>
                        <p class="text-2xl font-bold text-white">{{ $stats['failed_queue_jobs'] ?? 0 }}</p>
                        <p class="stat-help">Failed jobs w failed_jobs table</p>
                    </div>
                </div>
            </div>

            <div class="relative backdrop-blur-xl shadow-lg rounded-lg p-6 border stat-card stat-queue-health"
                 style="background: linear-gradient(135deg, rgba(31, 41, 55, 0.8), rgba(17, 24, 39, 0.8)); border: 1px solid rgba(224, 172, 126, 0.2);">
                <div class="flex flex-col">
                    <div class="flex items-center mb-2">
                        <div class="flex-shrink-0 w-8 h-8 bg-green-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-400">Zdrowie Kolejki</p>
                            <p class="text-2xl font-bold text-white">{{ $stats['queue_health'] ?? 0 }}%</p>
                        </div>
                    </div>
                    <div class="stat-progress">
                        <div class="progress-bar" style="width: {{ $stats['queue_health'] ?? 0 }}%"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Queue Worker Status Panel -->
        <div class="relative backdrop-blur-xl shadow-2xl rounded-xl border p-6 mb-8"
             style="background: linear-gradient(135deg, rgba(31, 41, 55, 0.95), rgba(17, 24, 39, 0.95)); border: 1px solid rgba(124, 58, 237, 0.3);">

            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-semibold text-white flex items-center">
                    <svg class="w-6 h-6 text-purple-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"></path>
                    </svg>
                    Status Queue Worker

                    <!-- Status Badge -->
                    @php
                        $statusColors = [
                            'idle' => 'bg-green-500',
                            'processing' => 'bg-blue-500',
                            'stopped' => 'bg-red-500',
                            'unknown' => 'bg-gray-500',
                            'error' => 'bg-red-500',
                        ];
                        $statusLabels = [
                            'idle' => 'Bezczynny',
                            'processing' => 'Przetwarza',
                            'stopped' => 'Zatrzymany',
                            'unknown' => 'Nieznany',
                            'error' => 'Błąd',
                        ];
                        $statusColor = $statusColors[$queueWorkerStatus['status']] ?? 'bg-gray-500';
                        $statusLabel = $statusLabels[$queueWorkerStatus['status']] ?? 'Nieznany';
                    @endphp
                    <span class="ml-3 inline-flex items-center px-3 py-1 rounded-full text-xs font-medium {{ $statusColor }} text-white">
                        <span class="w-2 h-2 rounded-full bg-white mr-2 {{ $queueWorkerStatus['status'] === 'processing' ? 'animate-pulse' : '' }}"></span>
                        {{ $statusLabel }}
                    </span>
                </h3>

                <!-- Run Queue Worker Button -->
                <button wire:click="runQueueWorker(10)"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-50 cursor-not-allowed"
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-bold rounded-lg text-white transition-all duration-300 transform hover:scale-105 shadow-lg"
                        style="background: linear-gradient(45deg, rgba(124, 58, 237, 0.8), rgba(109, 40, 217, 0.8));">
                    <svg wire:loading.remove wire:target="runQueueWorker" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <svg wire:loading wire:target="runQueueWorker" class="w-5 h-5 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    <span wire:loading.remove wire:target="runQueueWorker">Uruchom Worker</span>
                    <span wire:loading wire:target="runQueueWorker">Przetwarzam...</span>
                    @if($queueWorkerStatus['total_pending'] > 0)
                        <span class="ml-2 inline-flex items-center justify-center px-2 py-0.5 rounded-full text-xs font-bold bg-yellow-500 text-black">
                            {{ $queueWorkerStatus['total_pending'] }}
                        </span>
                    @endif
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Pending Jobs by Queue -->
                <div class="p-4 rounded-lg" style="background: rgba(17, 24, 39, 0.6);">
                    <p class="text-sm font-medium text-gray-400 mb-2">Jobs w kolejce</p>
                    @if(count($queueWorkerStatus['jobs_by_queue'] ?? []) > 0)
                        <div class="space-y-1">
                            @foreach($queueWorkerStatus['jobs_by_queue'] as $queue => $count)
                                <div class="flex justify-between items-center">
                                    <span class="text-xs text-gray-300">{{ $queue }}</span>
                                    <span class="text-sm font-bold {{ $count > 0 ? 'text-yellow-400' : 'text-gray-500' }}">{{ $count }}</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-green-400 text-sm font-medium">Brak oczekujących</p>
                    @endif
                </div>

                <!-- Oldest Job Waiting -->
                <div class="p-4 rounded-lg" style="background: rgba(17, 24, 39, 0.6);">
                    <p class="text-sm font-medium text-gray-400 mb-2">Najdłużej czekający</p>
                    @if($queueWorkerStatus['oldest_job_age_seconds'])
                        @php
                            $age = $queueWorkerStatus['oldest_job_age_seconds'];
                            $ageColor = $age > 300 ? 'text-red-400' : ($age > 60 ? 'text-yellow-400' : 'text-green-400');
                            $ageText = $age > 3600 ? round($age / 3600, 1) . 'h' : ($age > 60 ? round($age / 60) . 'min' : $age . 's');
                        @endphp
                        <p class="text-2xl font-bold {{ $ageColor }}">{{ $ageText }}</p>
                        <p class="text-xs text-gray-500">queue: {{ $queueWorkerStatus['oldest_job_queue'] }}</p>
                    @else
                        <p class="text-green-400 text-sm font-medium">-</p>
                    @endif
                </div>

                <!-- Last Processed Job -->
                <div class="p-4 rounded-lg" style="background: rgba(17, 24, 39, 0.6);">
                    <p class="text-sm font-medium text-gray-400 mb-2">Ostatnio przetworzony</p>
                    @if($queueWorkerStatus['last_processed_at'])
                        <p class="text-sm text-white truncate" title="{{ $queueWorkerStatus['last_processed_job_name'] }}">
                            {{ Str::limit($queueWorkerStatus['last_processed_job_name'] ?? 'Unknown', 25) }}
                        </p>
                        <p class="text-xs text-gray-500">{{ $queueWorkerStatus['last_processed_at']->diffForHumans() }}</p>
                        @if($queueWorkerStatus['last_processed_status'] === 'completed')
                            <span class="inline-flex items-center text-xs text-green-400">
                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                                completed
                            </span>
                        @elseif($queueWorkerStatus['last_processed_status'] === 'failed')
                            <span class="inline-flex items-center text-xs text-red-400">
                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                                failed
                            </span>
                        @endif
                    @else
                        <p class="text-gray-500 text-sm">Brak danych</p>
                    @endif
                </div>

                <!-- Failed Jobs -->
                <div class="p-4 rounded-lg" style="background: rgba(17, 24, 39, 0.6);">
                    <p class="text-sm font-medium text-gray-400 mb-2">Failed Jobs</p>
                    <p class="text-2xl font-bold {{ $queueWorkerStatus['failed_jobs_count'] > 0 ? 'text-red-400' : 'text-green-400' }}">
                        {{ $queueWorkerStatus['failed_jobs_count'] }}
                    </p>
                    @if($queueWorkerStatus['failed_jobs_count'] > 0)
                        <p class="text-xs text-red-400">Wymaga uwagi!</p>
                    @else
                        <p class="text-xs text-green-400">Wszystko OK</p>
                    @endif
                </div>
            </div>

            <!-- Warning if worker seems stopped -->
            @if($queueWorkerStatus['status'] === 'stopped')
                <div class="mt-4 p-3 rounded-lg bg-red-500 bg-opacity-20 border border-red-500 border-opacity-30">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-red-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        <p class="text-sm text-red-300">
                            <strong>Queue Worker prawdopodobnie zatrzymany!</strong>
                            Jobs czekają ponad 5 minut bez przetwarzania. Kliknij "Uruchom Worker" aby przetworzyć oczekujące zadania.
                        </p>
                    </div>
                </div>
            @endif
        </div>

        <!-- Sync Configuration Panel -->
        <div class="relative backdrop-blur-xl shadow-2xl rounded-xl border p-6 mb-8" 
             style="background: linear-gradient(135deg, rgba(31, 41, 55, 0.95), rgba(17, 24, 39, 0.95)); border: 1px solid rgba(224, 172, 126, 0.3);">
            
            <h3 class="text-lg font-semibold text-white mb-6 flex items-center">
                <svg class="w-6 h-6 text-[#e0ac7e] mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                Konfiguracja Synchronizacji
            </h3>
            
            <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
                <!-- Sync Types -->
                <div>
                    <label class="block text-sm font-medium text-white mb-3">Typ synchronizacji</label>
                    <div class="space-y-2">
                        @foreach(['products' => 'Produkty', 'categories' => 'Kategorie', 'prices' => 'Ceny', 'stock' => 'Stany'] as $type => $label)
                            <label class="flex items-center">
                                <input type="checkbox"
                                       wire:model.live="selectedSyncTypes"
                                       value="{{ $type }}"
                                       class="rounded border-gray-600 bg-gray-800 bg-opacity-60 text-[#e0ac7e] focus:ring-[#e0ac7e]">
                                <span class="ml-2 text-sm text-white">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <!-- Sync Frequency (VISIBLE - user request 2026-01-19) -->
                <div>
                    <label for="autoSyncFrequency" class="block text-sm font-medium text-white mb-2">Częstotliwość sync</label>
                    <select id="autoSyncFrequency"
                            wire:model.live="autoSyncFrequency"
                            class="w-full px-4 py-3 bg-gray-800 bg-opacity-60 border border-gray-600 text-white rounded-lg focus:ring-2 focus:ring-[#e0ac7e] focus:border-[#e0ac7e] transition-all duration-200">
                        <option value="hourly">Co godzinę</option>
                        <option value="daily">Codziennie</option>
                        <option value="weekly">Tygodniowo</option>
                    </select>
                    <p class="text-gray-400 text-xs mt-1">
                        @if($autoSyncEnabled)
                            <span class="text-green-400">● Auto-sync ON</span>
                        @else
                            <span class="text-red-400">● Auto-sync OFF</span>
                        @endif
                    </p>
                </div>

                <!-- Batch Size -->
                <div>
                    <label for="batchSize" class="block text-sm font-medium text-white mb-2">Wielkość paczki</label>
                    <input type="number" 
                           id="batchSize"
                           wire:model.live="batchSize" 
                           min="1" max="100"
                           class="w-full px-4 py-3 bg-gray-800 bg-opacity-60 border border-gray-600 text-white rounded-lg focus:ring-2 focus:ring-[#e0ac7e] focus:border-[#e0ac7e] transition-all duration-200">
                    <p class="text-gray-400 text-xs mt-1">1-100 rekordów na raz</p>
                </div>

                <!-- Timeout -->
                <div>
                    <label for="syncTimeout" class="block text-sm font-medium text-white mb-2">Timeout (sekundy)</label>
                    <input type="number" 
                           id="syncTimeout"
                           wire:model.live="syncTimeout" 
                           min="60" max="3600"
                           class="w-full px-4 py-3 bg-gray-800 bg-opacity-60 border border-gray-600 text-white rounded-lg focus:ring-2 focus:ring-[#e0ac7e] focus:border-[#e0ac7e] transition-all duration-200">
                    <p class="text-gray-400 text-xs mt-1">60-3600 sekund</p>
                </div>

                <!-- Conflict Resolution -->
                <div>
                    <label for="conflictResolution" class="block text-sm font-medium text-white mb-2">Rozwiązywanie konfliktów</label>
                    <select id="conflictResolution"
                            wire:model.live="conflictResolution"
                            class="w-full px-4 py-3 bg-gray-800 bg-opacity-60 border border-gray-600 text-white rounded-lg focus:ring-2 focus:ring-[#e0ac7e] focus:border-[#e0ac7e] transition-all duration-200">
                        <option value="ppm_wins">PPM wygrywa</option>
                        <option value="prestashop_wins">PrestaShop wygrywa</option>
                        <option value="newest_wins">Najnowsze wygrywa</option>
                        <option value="manual">Manualne</option>
                    </select>
                </div>
            </div>

            <!-- Configuration Action Buttons -->
            <div class="flex items-center justify-center gap-4 mt-6 pt-4 border-t border-gray-600">
                <!-- Save Configuration Button (user request 2026-01-19) -->
                <button wire:click="saveSyncConfiguration"
                        wire:loading.attr="disabled"
                        class="px-6 py-3 text-white font-medium rounded-lg transition-all duration-200 flex items-center hover:scale-105"
                        style="background: linear-gradient(45deg, #e0ac7e, #d1975a);">
                    <svg wire:loading.remove wire:target="saveSyncConfiguration" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <svg wire:loading wire:target="saveSyncConfiguration" class="w-5 h-5 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    Zapisz konfigurację
                </button>

                <!-- Advanced Configuration Toggle -->
                <button wire:click="toggleSyncConfig"
                        class="px-6 py-3 bg-purple-600 hover:bg-purple-700 text-white font-medium rounded-lg transition-colors duration-200 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4"></path>
                    </svg>
                    {{ $showSyncConfig ? 'Ukryj zaawansowaną konfigurację' : 'Pokaż zaawansowaną konfigurację' }}
                </button>
            </div>
        </div>

        {{-- ========== ERP Sync Configuration Card (FAZA 5) ========== --}}
        <div class="relative backdrop-blur-xl shadow-2xl rounded-xl border p-6 mb-8"
             style="background: linear-gradient(135deg, rgba(31, 41, 55, 0.95), rgba(17, 24, 39, 0.95)); border: 1px solid rgba(16, 185, 129, 0.3);">

            <h3 class="text-lg font-semibold text-white mb-6 flex items-center">
                <svg class="w-6 h-6 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
                Konfiguracja Synchronizacji ERP
                <span class="ml-3 text-xs px-2 py-1 rounded-full bg-green-500/20 text-green-400">Subiekt GT / Baselinker</span>
            </h3>

            {{-- Row 1: Connection + Data Sources --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                {{-- ERP Connection Select --}}
                <div>
                    <label class="block text-sm font-medium text-white mb-2">Polaczenie ERP</label>
                    <select wire:model.live="selectedErpConnectionId"
                            wire:change="loadErpConfig"
                            class="w-full px-4 py-3 bg-gray-800 bg-opacity-60 border border-gray-600 text-white rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200">
                        <option value="">-- Wybierz polaczenie --</option>
                        @foreach($this->erpConnectionsList as $conn)
                            <option value="{{ $conn->id }}">{{ $conn->instance_name }} ({{ ucfirst(str_replace('_', ' ', $conn->erp_type)) }})</option>
                        @endforeach
                    </select>
                    @if(!$this->erpConnectionsList->count())
                        <p class="text-xs text-gray-500 mt-1">Brak aktywnych polaczen ERP. Dodaj polaczenie w panelu ERP Manager.</p>
                    @endif
                </div>

                {{-- Source Flags --}}
                <div class="lg:col-span-2">
                    <label class="block text-sm font-medium text-white mb-2">Zrodlo danych</label>
                    <div class="flex flex-wrap gap-6 mt-1">
                        <label class="flex items-center gap-3 cursor-pointer {{ !$selectedErpConnectionId ? 'opacity-50' : '' }}">
                            <input type="checkbox"
                                   wire:model="erpIsPriceSource"
                                   class="rounded border-gray-600 bg-gray-800 bg-opacity-60 text-green-500 focus:ring-green-500"
                                   {{ !$selectedErpConnectionId ? 'disabled' : '' }}>
                            <div>
                                <span class="text-sm font-medium text-white">ERP jest zrodlem cen</span>
                                <p class="text-xs text-gray-400">Ceny z ERP nadpisuja ceny w PPM</p>
                            </div>
                        </label>

                        <label class="flex items-center gap-3 cursor-pointer {{ !$selectedErpConnectionId ? 'opacity-50' : '' }}">
                            <input type="checkbox"
                                   wire:model="erpIsStockSource"
                                   class="rounded border-gray-600 bg-gray-800 bg-opacity-60 text-green-500 focus:ring-green-500"
                                   {{ !$selectedErpConnectionId ? 'disabled' : '' }}>
                            <div>
                                <span class="text-sm font-medium text-white">ERP jest zrodlem stanow</span>
                                <p class="text-xs text-gray-400">Stany z ERP nadpisuja stany w PPM</p>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            {{-- Row 2: 3 Independent Sync Frequencies --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                {{-- Price Sync Frequency --}}
                <div>
                    <label class="block text-sm font-medium text-white mb-2 flex items-center">
                        <svg class="w-4 h-4 text-yellow-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Sync cen
                    </label>
                    <select wire:model="erpPriceSyncFrequency"
                            class="w-full px-4 py-3 bg-gray-800 bg-opacity-60 border border-gray-600 text-white rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200"
                            {{ !$selectedErpConnectionId ? 'disabled' : '' }}>
                        @foreach(\App\Models\ERPConnection::getFrequencyOptions() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-400 mt-1">Aktualizacja cen produktow</p>
                </div>

                {{-- Stock Sync Frequency --}}
                <div>
                    <label class="block text-sm font-medium text-white mb-2 flex items-center">
                        <svg class="w-4 h-4 text-blue-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                        Sync stanow
                    </label>
                    <select wire:model="erpStockSyncFrequency"
                            class="w-full px-4 py-3 bg-gray-800 bg-opacity-60 border border-gray-600 text-white rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200"
                            {{ !$selectedErpConnectionId ? 'disabled' : '' }}>
                        @foreach(\App\Models\ERPConnection::getFrequencyOptions() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-400 mt-1">Aktualizacja stanow magazynowych</p>
                </div>

                {{-- Basic Data Sync Frequency --}}
                <div>
                    <label class="block text-sm font-medium text-white mb-2 flex items-center">
                        <svg class="w-4 h-4 text-purple-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Sync danych podstawowych
                    </label>
                    <select wire:model="erpBasicDataSyncFrequency"
                            class="w-full px-4 py-3 bg-gray-800 bg-opacity-60 border border-gray-600 text-white rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200"
                            {{ !$selectedErpConnectionId ? 'disabled' : '' }}>
                        @foreach(\App\Models\ERPConnection::getFrequencyOptions() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-400 mt-1">Nazwa, opis, parametry (ERP Tab)</p>
                </div>
            </div>

            {{-- Save Button --}}
            <div class="flex items-center justify-end pt-4 border-t border-gray-600">
                <button wire:click="saveErpSyncConfig"
                        wire:loading.attr="disabled"
                        class="px-6 py-3 text-white font-medium rounded-lg transition-all duration-200 flex items-center hover:scale-105"
                        style="background: linear-gradient(45deg, #10b981, #059669);"
                        {{ !$selectedErpConnectionId ? 'disabled' : '' }}>
                    <svg wire:loading.remove wire:target="saveErpSyncConfig" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <svg wire:loading wire:target="saveErpSyncConfig" class="w-5 h-5 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    Zapisz konfiguracje ERP
                </button>
            </div>
        </div>

        <!-- Advanced Sync Configuration Panel - SEKCJA 2.2.1.2 -->
        @if($showSyncConfig)
        <div class="relative backdrop-blur-xl shadow-2xl rounded-xl border p-6 mb-8"
             style="background: linear-gradient(135deg, rgba(31, 41, 55, 0.95), rgba(17, 24, 39, 0.95)); border: 1px solid rgba(224, 172, 126, 0.3);">

            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-white flex items-center">
                    <svg class="w-6 h-6 text-[#e0ac7e] mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4"></path>
                    </svg>
                    Zaawansowana Konfiguracja Synchronizacji
                </h3>
                <span class="text-xs text-gray-400">SEKCJA 2.2.1.2 - Sync Configuration</span>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-5 gap-6">

                <!-- Auto-sync Scheduler - 2.2.1.2.1 -->
                <div class="backdrop-blur-xl rounded-xl p-4" style="background: linear-gradient(135deg, rgba(55, 65, 81, 0.6), rgba(31, 41, 55, 0.4)); border: 1px solid rgba(224, 172, 126, 0.2);">
                    <h4 class="text-lg font-semibold text-white mb-4 flex items-center">
                        <svg class="w-5 h-5 text-blue-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Harmonogram
                    </h4>

                    <div class="space-y-4">
                        <label class="flex items-center">
                            <input type="checkbox"
                                   wire:model.live="autoSyncEnabled"
                                   class="rounded border-gray-600 bg-gray-800 bg-opacity-60 text-[#e0ac7e] focus:ring-[#e0ac7e]">
                            <span class="ml-2 text-sm text-white">Włącz auto-sync</span>
                        </label>

                        @if($autoSyncEnabled)
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">Częstotliwość</label>
                            <select wire:model.live="autoSyncFrequency"
                                    class="w-full px-3 py-2 bg-gray-800 bg-opacity-60 border border-gray-600 text-white rounded-lg focus:ring-2 focus:ring-[#e0ac7e] focus:border-[#e0ac7e] text-sm">
                                <option value="hourly">Co godzinę</option>
                                <option value="daily">Codziennie</option>
                                <option value="weekly">Tygodniowo</option>
                            </select>
                        </div>

                        @if($autoSyncFrequency !== 'hourly')
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">Godzina</label>
                            <input type="number"
                                   wire:model.live="autoSyncScheduleHour"
                                   min="0" max="23"
                                   class="w-full px-3 py-2 bg-gray-800 bg-opacity-60 border border-gray-600 text-white rounded-lg focus:ring-2 focus:ring-[#e0ac7e] focus:border-[#e0ac7e] text-sm">
                        </div>
                        @endif

                        @if($autoSyncFrequency === 'weekly')
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">Dni tygodnia</label>
                            <div class="space-y-1">
                                @foreach(['monday' => 'Pon', 'tuesday' => 'Wt', 'wednesday' => 'Śr', 'thursday' => 'Czw', 'friday' => 'Pt', 'saturday' => 'Sob', 'sunday' => 'Nd'] as $day => $label)
                                    <label class="flex items-center">
                                        <input type="checkbox"
                                               wire:model.live="autoSyncDaysOfWeek"
                                               value="{{ $day }}"
                                               class="rounded border-gray-600 bg-gray-800 bg-opacity-60 text-[#e0ac7e] focus:ring-[#e0ac7e]">
                                        <span class="ml-2 text-sm text-white">{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        <label class="flex items-center">
                            <input type="checkbox"
                                   wire:model.live="autoSyncOnlyConnected"
                                   class="rounded border-gray-600 bg-gray-800 bg-opacity-60 text-[#e0ac7e] focus:ring-[#e0ac7e]">
                            <span class="ml-2 text-sm text-white">Tylko połączone</span>
                        </label>

                        <label class="flex items-center">
                            <input type="checkbox"
                                   wire:model.live="autoSyncSkipMaintenanceMode"
                                   class="rounded border-gray-600 bg-gray-800 bg-opacity-60 text-[#e0ac7e] focus:ring-[#e0ac7e]">
                            <span class="ml-2 text-sm text-white">Pomiń konserwację</span>
                        </label>
                        @endif
                    </div>
                </div>

                <!-- Retry Logic - 2.2.1.2.2 -->
                <div class="backdrop-blur-xl rounded-xl p-4" style="background: linear-gradient(135deg, rgba(55, 65, 81, 0.6), rgba(31, 41, 55, 0.4)); border: 1px solid rgba(224, 172, 126, 0.2);">
                    <h4 class="text-lg font-semibold text-white mb-4 flex items-center">
                        <svg class="w-5 h-5 text-yellow-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Ponawianie
                    </h4>

                    <div class="space-y-4">
                        <label class="flex items-center">
                            <input type="checkbox"
                                   wire:model.live="retryEnabled"
                                   class="rounded border-gray-600 bg-gray-800 bg-opacity-60 text-[#e0ac7e] focus:ring-[#e0ac7e]">
                            <span class="ml-2 text-sm text-white">Włącz retry</span>
                        </label>

                        @if($retryEnabled)
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">Max prób</label>
                            <input type="number"
                                   wire:model.live="maxRetryAttempts"
                                   min="1" max="10"
                                   class="w-full px-3 py-2 bg-gray-800 bg-opacity-60 border border-gray-600 text-white rounded-lg focus:ring-2 focus:ring-[#e0ac7e] focus:border-[#e0ac7e] text-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">Opóźnienie (min)</label>
                            <input type="number"
                                   wire:model.live="retryDelayMinutes"
                                   min="1" max="1440"
                                   class="w-full px-3 py-2 bg-gray-800 bg-opacity-60 border border-gray-600 text-white rounded-lg focus:ring-2 focus:ring-[#e0ac7e] focus:border-[#e0ac7e] text-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">Mnożnik backoff</label>
                            <input type="number"
                                   wire:model.live="retryBackoffMultiplier"
                                   min="1" max="5" step="0.1"
                                   class="w-full px-3 py-2 bg-gray-800 bg-opacity-60 border border-gray-600 text-white rounded-lg focus:ring-2 focus:ring-[#e0ac7e] focus:border-[#e0ac7e] text-sm">
                        </div>

                        <label class="flex items-center">
                            <input type="checkbox"
                                   wire:model.live="retryOnlyTransientErrors"
                                   class="rounded border-gray-600 bg-gray-800 bg-opacity-60 text-[#e0ac7e] focus:ring-[#e0ac7e]">
                            <span class="ml-2 text-sm text-white">Tylko błędy przejściowe</span>
                        </label>
                        @endif
                    </div>
                </div>

                <!-- Notifications - 2.2.1.2.3 -->
                <div class="backdrop-blur-xl rounded-xl p-4" style="background: linear-gradient(135deg, rgba(55, 65, 81, 0.6), rgba(31, 41, 55, 0.4)); border: 1px solid rgba(224, 172, 126, 0.2);">
                    <h4 class="text-lg font-semibold text-white mb-4 flex items-center">
                        <svg class="w-5 h-5 text-green-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM4 13h6l-6 6v-6zM9 3l8 8-8 8V3z"></path>
                        </svg>
                        Powiadomienia
                    </h4>

                    <div class="space-y-4">
                        <label class="flex items-center">
                            <input type="checkbox"
                                   wire:model.live="notificationsEnabled"
                                   class="rounded border-gray-600 bg-gray-800 bg-opacity-60 text-[#e0ac7e] focus:ring-[#e0ac7e]">
                            <span class="ml-2 text-sm text-white">Wlacz powiadomienia</span>
                        </label>

                        @if($notificationsEnabled)
                        <div class="space-y-2">
                            <label class="flex items-center">
                                <input type="checkbox"
                                       wire:model.live="notifyOnSuccess"
                                       class="rounded border-gray-600 bg-gray-800 bg-opacity-60 text-[#e0ac7e] focus:ring-[#e0ac7e]">
                                <span class="ml-2 text-sm text-white">Sukces</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox"
                                       wire:model.live="notifyOnFailure"
                                       class="rounded border-gray-600 bg-gray-800 bg-opacity-60 text-[#e0ac7e] focus:ring-[#e0ac7e]">
                                <span class="ml-2 text-sm text-white">Bledy</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox"
                                       wire:model.live="notifyOnRetryExhausted"
                                       class="rounded border-gray-600 bg-gray-800 bg-opacity-60 text-[#e0ac7e] focus:ring-[#e0ac7e]">
                                <span class="ml-2 text-sm text-white">Retry wyczerpane</span>
                            </label>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">Kanaly</label>
                            <div class="space-y-1">
                                <label class="flex items-center">
                                    <input type="checkbox"
                                           wire:model.live="notificationChannels"
                                           value="email"
                                           class="rounded border-gray-600 bg-gray-800 bg-opacity-60 text-[#e0ac7e] focus:ring-[#e0ac7e]">
                                    <span class="ml-2 text-sm text-white">Email</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox"
                                           wire:model.live="notificationChannels"
                                           value="slack"
                                           class="rounded border-gray-600 bg-gray-800 bg-opacity-60 text-[#e0ac7e] focus:ring-[#e0ac7e]">
                                    <span class="ml-2 text-sm text-white">Slack</span>
                                </label>
                            </div>
                        </div>

                        <!-- Email Recipients - 2.2.1.2.3 Extended -->
                        @if(in_array('email', $notificationChannels))
                        <div x-data="{
                            emails: @entangle('emailRecipients'),
                            newEmail: '',
                            addEmail() {
                                if (this.newEmail && this.newEmail.includes('@') && !this.emails.includes(this.newEmail)) {
                                    this.emails.push(this.newEmail);
                                    this.newEmail = '';
                                }
                            },
                            removeEmail(index) {
                                this.emails.splice(index, 1);
                            }
                        }">
                            <label class="block text-sm font-medium text-gray-300 mb-2">Odbiorcy Email</label>
                            <!-- Lista obecnych odbiorcow -->
                            <div class="space-y-2 mb-2">
                                <template x-for="(email, index) in emails" :key="index">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm text-white bg-gray-700 px-3 py-1 rounded" x-text="email"></span>
                                        <button @click="removeEmail(index)" type="button" class="text-red-400 hover:text-red-300">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </template>
                                <template x-if="emails.length === 0">
                                    <p class="text-xs text-gray-500">Brak odbiorcow - dodaj ponizej</p>
                                </template>
                            </div>
                            <!-- Input do dodawania -->
                            <div class="space-y-2">
                                <input type="email"
                                       x-model="newEmail"
                                       @keydown.enter.prevent="addEmail()"
                                       placeholder="email@example.com"
                                       class="w-full px-3 py-2 bg-gray-800 bg-opacity-60 border border-gray-600 text-white rounded-lg text-sm focus:ring-[#e0ac7e] focus:border-[#e0ac7e]">
                                <button @click="addEmail()" type="button" class="btn-enterprise-secondary text-sm px-3 py-2 w-full">
                                    Dodaj
                                </button>
                            </div>
                        </div>
                        @endif

                        <!-- Microsoft Teams - 2.2.1.2.3 Extended -->
                        <div class="pt-2 border-t border-gray-700">
                            <label class="flex items-center">
                                <input type="checkbox"
                                       wire:model.live="teamsEnabled"
                                       class="rounded border-gray-600 bg-gray-800 bg-opacity-60 text-[#e0ac7e] focus:ring-[#e0ac7e]">
                                <span class="ml-2 text-sm text-white">Microsoft Teams</span>
                            </label>

                            @if($teamsEnabled)
                            <div class="mt-3 space-y-2">
                                <label class="block text-sm font-medium text-gray-300">Teams Webhook URL</label>
                                <input type="url"
                                       wire:model.live="teamsWebhookUrl"
                                       placeholder="https://outlook.office.com/webhook/..."
                                       class="w-full px-3 py-2 bg-gray-800 bg-opacity-60 border border-gray-600 text-white rounded-lg text-sm focus:ring-[#e0ac7e] focus:border-[#e0ac7e]">
                                <p class="text-xs text-gray-400">Skopiuj URL z konfiguracji Incoming Webhook w Teams</p>
                                <button wire:click="testTeamsWebhook"
                                        type="button"
                                        class="btn-enterprise-secondary text-xs px-3 py-1.5"
                                        @if(empty($teamsWebhookUrl)) disabled @endif>
                                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                    </svg>
                                    Test polaczenia
                                </button>
                            </div>
                            @endif
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Performance - 2.2.1.2.4 -->
                <div class="backdrop-blur-xl rounded-xl p-4" style="background: linear-gradient(135deg, rgba(55, 65, 81, 0.6), rgba(31, 41, 55, 0.4)); border: 1px solid rgba(224, 172, 126, 0.2);">
                    <h4 class="text-lg font-semibold text-white mb-4 flex items-center">
                        <svg class="w-5 h-5 text-red-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                        Wydajność
                    </h4>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">Tryb wydajności</label>
                            <select wire:model.live="performanceMode"
                                    class="w-full px-3 py-2 bg-gray-800 bg-opacity-60 border border-gray-600 text-white rounded-lg focus:ring-2 focus:ring-[#e0ac7e] focus:border-[#e0ac7e] text-sm">
                                <option value="economy">Ekonomiczny</option>
                                <option value="balanced">Zrównoważony</option>
                                <option value="performance">Wydajnościowy</option>
                            </select>
                            <p class="text-xs text-gray-400 mt-1">{{ $this->getPerformanceModeDescription($performanceMode) }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">Max równoczesnych</label>
                            <input type="number"
                                   wire:model.live="maxConcurrentJobs"
                                   min="1" max="10"
                                   class="w-full px-3 py-2 bg-gray-800 bg-opacity-60 border border-gray-600 text-white rounded-lg focus:ring-2 focus:ring-[#e0ac7e] focus:border-[#e0ac7e] text-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">Opóźnienie (ms)</label>
                            <input type="number"
                                   wire:model.live="jobProcessingDelay"
                                   min="0" max="5000"
                                   class="w-full px-3 py-2 bg-gray-800 bg-opacity-60 border border-gray-600 text-white rounded-lg focus:ring-2 focus:ring-[#e0ac7e] focus:border-[#e0ac7e] text-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">Pamięć (MB)</label>
                            <input type="number"
                                   wire:model.live="memoryLimit"
                                   min="128" max="2048"
                                   class="w-full px-3 py-2 bg-gray-800 bg-opacity-60 border border-gray-600 text-white rounded-lg focus:ring-2 focus:ring-[#e0ac7e] focus:border-[#e0ac7e] text-sm">
                        </div>
                    </div>
                </div>

                <!-- Backup - 2.2.1.2.5 -->
                <div class="backdrop-blur-xl rounded-xl p-4" style="background: linear-gradient(135deg, rgba(55, 65, 81, 0.6), rgba(31, 41, 55, 0.4)); border: 1px solid rgba(224, 172, 126, 0.2);">
                    <h4 class="text-lg font-semibold text-white mb-4 flex items-center">
                        <svg class="w-5 h-5 text-purple-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path>
                        </svg>
                        Backup
                    </h4>

                    <div class="space-y-4">
                        <label class="flex items-center">
                            <input type="checkbox"
                                   wire:model.live="backupBeforeSync"
                                   class="rounded border-gray-600 bg-gray-800 bg-opacity-60 text-[#e0ac7e] focus:ring-[#e0ac7e]">
                            <span class="ml-2 text-sm text-white">Backup przed sync</span>
                        </label>

                        @if($backupBeforeSync)
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">Retencja (dni)</label>
                            <input type="number"
                                   wire:model.live="backupRetentionDays"
                                   min="1" max="365"
                                   class="w-full px-3 py-2 bg-gray-800 bg-opacity-60 border border-gray-600 text-white rounded-lg focus:ring-2 focus:ring-[#e0ac7e] focus:border-[#e0ac7e] text-sm">
                        </div>

                        <label class="flex items-center">
                            <input type="checkbox"
                                   wire:model.live="backupOnlyOnMajorChanges"
                                   class="rounded border-gray-600 bg-gray-800 bg-opacity-60 text-[#e0ac7e] focus:ring-[#e0ac7e]">
                            <span class="ml-2 text-sm text-white">Tylko duże zmiany</span>
                        </label>

                        <label class="flex items-center">
                            <input type="checkbox"
                                   wire:model.live="backupCompressionEnabled"
                                   class="rounded border-gray-600 bg-gray-800 bg-opacity-60 text-[#e0ac7e] focus:ring-[#e0ac7e]">
                            <span class="ml-2 text-sm text-white">Kompresja</span>
                        </label>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Configuration Actions -->
            <div class="flex items-center justify-between mt-8 pt-6 border-t border-gray-600">
                <div class="flex items-center space-x-3">
                    <button wire:click="saveSyncConfiguration"
                            wire:loading.attr="disabled"
                            class="px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors duration-200 flex items-center disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span wire:loading.remove wire:target="saveSyncConfiguration">
                            Zapisz konfigurację
                        </span>
                        <span wire:loading wire:target="saveSyncConfiguration">
                            Zapisywanie...
                        </span>
                    </button>

                    <button wire:click="testSyncConfiguration"
                            wire:loading.attr="disabled"
                            class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors duration-200 flex items-center disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span wire:loading.remove wire:target="testSyncConfiguration">
                            Testuj konfigurację
                        </span>
                        <span wire:loading wire:target="testSyncConfiguration">
                            Testowanie...
                        </span>
                    </button>

                    <button wire:click="resetSyncConfigurationToDefaults"
                            wire:loading.attr="disabled"
                            class="px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-lg transition-colors duration-200 flex items-center disabled:opacity-50 disabled:cursor-not-allowed"
                            onclick="return confirm('Czy na pewno chcesz zresetować konfigurację do wartości domyślnych?')">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        <span wire:loading.remove wire:target="resetSyncConfigurationToDefaults">
                            Reset do domyślnych
                        </span>
                        <span wire:loading wire:target="resetSyncConfigurationToDefaults">
                            Resetowanie...
                        </span>
                    </button>
                </div>

                <div class="text-sm text-gray-400">
                    {{ $this->getSyncScheduleDescription() }}
                </div>
            </div>

            <!-- Flash Messages (MVP - Priority 1) -->
            <div class="mt-6 space-y-3">
                @if (session()->has('success'))
                    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 transform scale-95"
                         x-transition:enter-end="opacity-100 transform scale-100"
                         x-transition:leave="transition ease-in duration-200"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0"
                         class="bg-green-900 bg-opacity-20 border border-green-500 border-opacity-30 rounded-lg p-4 backdrop-blur-sm">
                        <div class="flex items-start">
                            <svg class="w-5 h-5 text-green-400 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <div class="flex-1">
                                <h3 class="text-sm font-medium text-green-300 mb-1">Sukces</h3>
                                <p class="text-sm text-green-200 whitespace-pre-line">{{ session('success') }}</p>
                            </div>
                            <button @click="show = false" class="ml-3 text-green-400 hover:text-green-300 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                @endif

                @if (session()->has('error'))
                    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 10000)"
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 transform scale-95"
                         x-transition:enter-end="opacity-100 transform scale-100"
                         x-transition:leave="transition ease-in duration-200"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0"
                         class="bg-red-900 bg-opacity-20 border border-red-500 border-opacity-30 rounded-lg p-4 backdrop-blur-sm">
                        <div class="flex items-start">
                            <svg class="w-5 h-5 text-red-400 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <div class="flex-1">
                                <h3 class="text-sm font-medium text-red-300 mb-1">Błąd</h3>
                                <p class="text-sm text-red-200 whitespace-pre-line">{{ session('error') }}</p>
                            </div>
                            <button @click="show = false" class="ml-3 text-red-400 hover:text-red-300 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                @endif

                @if (session()->has('warning'))
                    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 7000)"
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 transform scale-95"
                         x-transition:enter-end="opacity-100 transform scale-100"
                         x-transition:leave="transition ease-in duration-200"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0"
                         class="bg-yellow-900 bg-opacity-20 border border-yellow-500 border-opacity-30 rounded-lg p-4 backdrop-blur-sm">
                        <div class="flex items-start">
                            <svg class="w-5 h-5 text-yellow-400 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                            <div class="flex-1">
                                <h3 class="text-sm font-medium text-yellow-300 mb-1">Ostrzeżenie</h3>
                                <p class="text-sm text-yellow-200 whitespace-pre-line">{{ session('warning') }}</p>
                            </div>
                            <button @click="show = false" class="ml-3 text-yellow-400 hover:text-yellow-300 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        </div>
        @endif

        <!-- Bulk Actions -->
        <div class="relative backdrop-blur-xl shadow-lg rounded-lg p-6 mb-8 border" 
             style="background: linear-gradient(135deg, rgba(31, 41, 55, 0.8), rgba(17, 24, 39, 0.8)); border: 1px solid rgba(224, 172, 126, 0.2);">
            
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <label class="flex items-center">
                        <input type="checkbox" 
                               wire:model="selectAll"
                               wire:click="toggleSelectAll"
                               class="rounded border-gray-600 bg-gray-800 bg-opacity-60 text-[#e0ac7e] focus:ring-[#e0ac7e]">
                        <span class="ml-2 text-sm text-white">Zaznacz wszystkie</span>
                    </label>
                    
                    @if(count($selectedShops) > 0)
                        <span class="text-sm text-gray-400">
                            Wybrano: {{ count($selectedShops) }} sklepów
                        </span>
                    @endif
                </div>

                <div class="flex items-center space-x-3">
                    @if(count($selectedShops) > 0)
                        <button wire:click="syncSelectedShops" 
                                wire:loading.attr="disabled"
                                class="relative px-6 py-2 text-white rounded-lg transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl flex items-center font-medium"
                                style="background: linear-gradient(45deg, rgba(34, 197, 94, 0.8), rgba(22, 163, 74, 0.8)); border: 1px solid rgba(34, 197, 94, 0.5);">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            <span wire:loading.remove wire:target="syncSelectedShops">Synchronizuj wybrane</span>
                            <span wire:loading wire:target="syncSelectedShops">Uruchamianie...</span>
                        </button>
                    @endif
                    
                    <button wire:click="resetFilters" 
                            class="px-6 py-2 bg-gray-700 bg-opacity-60 text-gray-300 rounded-lg hover:bg-gray-600 hover:bg-opacity-80 transition-colors duration-200 flex items-center border border-gray-600">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Reset
                    </button>
                </div>
            </div>
        </div>

        <!-- Error Messages -->
        @if ($errors->any())
            <div class="mb-6 bg-red-900 bg-opacity-20 border border-red-500 border-opacity-30 rounded-lg p-4 backdrop-blur-sm">
                <div class="flex">
                    <svg class="w-5 h-5 text-red-400 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div>
                        <h3 class="text-sm font-medium text-red-300 mb-2">Błędy:</h3>
                        <ul class="text-sm text-red-200 list-disc list-inside space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        <!-- Active Sync Jobs -->
        @if(count($activeSyncJobs) > 0)
            <div class="relative backdrop-blur-xl shadow-lg rounded-lg p-6 mb-8 border" 
                 style="background: linear-gradient(135deg, rgba(31, 41, 55, 0.8), rgba(17, 24, 39, 0.8)); border: 1px solid rgba(224, 172, 126, 0.2);">
                
                <h3 class="text-lg font-semibold text-white mb-4 flex items-center">
                    <svg class="w-5 h-5 text-yellow-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Aktywne synchronizacje
                </h3>

                <div class="space-y-3">
                    @foreach($activeSyncJobs as $job)
                        <div class="bg-gray-800 bg-opacity-40 border border-gray-600 rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="font-medium text-white">{{ $job['job_name'] ?? 'Synchronizacja' }}</h4>
                                    <p class="text-sm text-gray-400">ID: {{ $job['job_id'] }}</p>
                                </div>
                                
                                <div class="flex items-center space-x-3">
                                    @if(isset($syncProgress[$job['job_id']]))
                                        <div class="w-32 bg-gray-700 rounded-full h-2">
                                            <div class="bg-[#e0ac7e] h-2 rounded-full transition-all duration-300" 
                                                 style="width: {{ $syncProgress[$job['job_id']]['progress'] ?? 0 }}%"></div>
                                        </div>
                                        <span class="text-sm text-gray-300">{{ $syncProgress[$job['job_id']]['progress'] ?? 0 }}%</span>
                                    @endif
                                    
                                    <button wire:click="cancelSyncJob('{{ $job['job_id'] }}')" 
                                            class="px-3 py-1 bg-red-600 bg-opacity-60 text-red-200 rounded text-xs hover:bg-red-500 hover:bg-opacity-80 transition-colors">
                                        Anuluj
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Shops List -->
        <div class="relative backdrop-blur-xl shadow-2xl rounded-xl border" 
             style="background: linear-gradient(135deg, rgba(31, 41, 55, 0.95), rgba(17, 24, 39, 0.95)); border: 1px solid rgba(224, 172, 126, 0.3);">
            
            <!-- Search and Filters -->
            <div class="p-6 border-b border-gray-600">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-white">Sklepy PrestaShop</h3>
                </div>
                
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                    <!-- Search -->
                    <div>
                        <input type="text" 
                               wire:model.debounce.300ms="search"
                               placeholder="Szukaj sklepów..."
                               class="w-full px-4 py-2 bg-gray-800 bg-opacity-60 border border-gray-600 text-white rounded-lg focus:ring-2 focus:ring-[#e0ac7e] focus:border-[#e0ac7e] transition-all duration-200 placeholder-gray-400">
                    </div>
                    
                    <!-- Status Filter -->
                    <div>
                        <select wire:model="statusFilter"
                                class="w-full px-4 py-2 bg-gray-800 bg-opacity-60 border border-gray-600 text-white rounded-lg focus:ring-2 focus:ring-[#e0ac7e] focus:border-[#e0ac7e] transition-all duration-200">
                            <option value="all">Wszystkie sklepy</option>
                            <option value="connected">Połączone</option>
                            <option value="sync_due">Wymagają synchronizacji</option>
                            <option value="sync_errors">Z błędami sync</option>
                            <option value="never_synced">Nigdy nie synchronizowane</option>
                        </select>
                    </div>
                    
                    <!-- Sort -->
                    <div>
                        <select wire:model="sortBy"
                                class="w-full px-4 py-2 bg-gray-800 bg-opacity-60 border border-gray-600 text-white rounded-lg focus:ring-2 focus:ring-[#e0ac7e] focus:border-[#e0ac7e] transition-all duration-200">
                            <option value="last_sync_at">Ostatnia synchronizacja</option>
                            <option value="name">Nazwa sklepu</option>
                            <option value="created_at">Data dodania</option>
                            <option value="sync_success_count">Udane synchronizacje</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Shops Table -->
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-800 bg-opacity-40 border-b border-gray-600">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                                <input type="checkbox" wire:model="selectAll" wire:click="toggleSelectAll" class="rounded border-gray-600 bg-gray-800 text-[#e0ac7e]">
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Sklep</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Queue Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Ostatnia sync</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Statystyki</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Akcje</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-600">
                        @forelse($shops as $shop)
                            <tr class="hover:bg-gray-800 hover:bg-opacity-30 transition-colors duration-200">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <input type="checkbox" 
                                           wire:click="toggleShopSelection({{ $shop->id }})"
                                           @if(in_array($shop->id, $selectedShops)) checked @endif
                                           class="rounded border-gray-600 bg-gray-800 text-[#e0ac7e]">
                                </td>
                                
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div>
                                        <div class="text-sm font-medium text-white">{{ $shop->name }}</div>
                                        <div class="text-sm text-gray-400">{{ Str::limit($shop->url, 40) }}</div>
                                    </div>
                                </td>
                                
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        @if($shop->connection_status === 'connected') bg-green-900 bg-opacity-40 text-green-300
                                        @elseif($shop->connection_status === 'error') bg-red-900 bg-opacity-40 text-red-300
                                        @else bg-gray-700 bg-opacity-40 text-gray-300 @endif">
                                        @if($shop->connection_status === 'connected') Połączony
                                        @elseif($shop->connection_status === 'error') Błąd połączenia
                                        @else Nieznany @endif
                                    </span>
                                </td>

                                <!-- FAZA 9 Phase 3: Queue Status Column -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $queueStatus = $this->getShopQueueStatus($shop->id);
                                    @endphp

                                    @if($queueStatus['has_queue_job'])
                                        <button wire:click="toggleShopDetails({{ $shop->id }})"
                                                type="button"
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium cursor-pointer transition-all duration-200 hover:shadow-md
                                                    @if($queueStatus['queue_job_status'] === 'processing') bg-yellow-900 bg-opacity-40 text-yellow-300 hover:bg-yellow-900 hover:bg-opacity-60
                                                    @else bg-green-900 bg-opacity-40 text-green-300 hover:bg-green-900 hover:bg-opacity-60 @endif">
                                            @if($queueStatus['queue_job_status'] === 'processing')
                                                <svg class="w-3 h-3 mr-1 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                                </svg>
                                                Processing ({{ $queueStatus['queue_job_count'] }})
                                            @else
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                                In Queue ({{ $queueStatus['queue_job_count'] }})
                                            @endif
                                        </button>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-700 bg-opacity-40 text-gray-400">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                                            </svg>
                                            Idle
                                        </span>
                                    @endif
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($shop->last_sync_at_computed)
                                        <div class="text-sm text-white">{{ $shop->last_sync_at_computed->format('d.m.Y H:i') }}</div>
                                        <div class="text-xs text-gray-400">{{ $shop->last_sync_at_computed->diffForHumans() }}</div>
                                    @else
                                        <span class="text-sm text-gray-400">Nigdy</span>
                                    @endif
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center space-x-4 text-xs">
                                        <span class="text-green-400">✓{{ $shop->sync_success_count_computed }}</span>
                                        <span class="text-red-400">✗{{ $shop->sync_error_count_computed }}</span>
                                        <span class="text-gray-400">{{ $shop->products_synced_computed }} prod.</span>
                                    </div>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    @php
                                        // Check if shop has pending sync jobs (2025-11-12)
                                        $hasPendingJob = $shop->hasPendingSyncJob();
                                        $pendingJob = $hasPendingJob ? $shop->getPendingSyncJob() : null;
                                    @endphp

                                    <div class="flex items-center justify-end space-x-2">
                                        {{-- SYNC NOW Button (Execute Pending Job Immediately) --}}
                                        {{-- User Request (2025-11-12): "przycisk powinien wymuszać uruchomienie pending JOB dla wybranego sklepu,
                                             jeżeli nie ma pending to przycisk jest nieaktywny" --}}
                                        <button wire:click="syncNow({{ $shop->id }})"
                                                wire:loading.attr="disabled"
                                                wire:loading.class="opacity-75 cursor-not-allowed"
                                                wire:target="syncNow({{ $shop->id }})"
                                                @if(!$hasPendingJob) disabled @endif
                                                class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-[#e0ac7e] to-[#d1975a] text-white rounded-lg text-sm transition-all duration-200 font-bold shadow-lg
                                                    {{ $hasPendingJob ? 'hover:from-[#d1975a] hover:to-[#c28449] hover:shadow-xl transform hover:scale-105 cursor-pointer' : 'opacity-50 cursor-not-allowed' }}"
                                                title="{{ $hasPendingJob && $pendingJob ?
                                                    'Wykonaj NATYCHMIAST pending job (pomija kolejkę): ' . ($pendingJob->job_type === 'import_products' ? '← Import z PrestaShop' : '→ Eksport do PrestaShop') :
                                                    'Brak oczekujących zadań - przycisk nieaktywny' }}">

                                            {{-- Lightning Bolt icon (default) - symbolizes INSTANT execution --}}
                                            <svg wire:loading.remove wire:target="syncNow({{ $shop->id }})"
                                                 class="w-4 h-4 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"></path>
                                            </svg>

                                            {{-- Spinner icon (loading) --}}
                                            <svg wire:loading wire:target="syncNow({{ $shop->id }})"
                                                 class="animate-spin w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>

                                            {{-- Button text --}}
                                            <span wire:loading.remove wire:target="syncNow({{ $shop->id }})">
                                                SYNC NOW
                                                @if($hasPendingJob && $pendingJob)
                                                    <span class="text-xs opacity-75">
                                                        ({{ $pendingJob->job_type === 'import_products' ? '←' : '→' }})
                                                    </span>
                                                @endif
                                            </span>
                                            <span wire:loading wire:target="syncNow({{ $shop->id }})">Wykonuję...</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- FAZA 9 Phase 3: Expandable Queue Details Row -->
                            @if($expandedShopId === $shop->id && $queueStatus['has_queue_job'])
                                <tr wire:key="queue-details-{{ $shop->id }}" class="bg-gray-800 bg-opacity-20">
                                    <td colspan="7" class="px-6 py-4">
                                        <div class="space-y-3">
                                            <div class="flex items-center justify-between mb-3">
                                                <h4 class="text-sm font-semibold text-white flex items-center">
                                                    <svg class="w-4 h-4 text-blue-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                                    </svg>
                                                    Queue Jobs dla sklepu: {{ $shop->name }}
                                                    <span class="ml-2 px-2 py-0.5 bg-blue-900 bg-opacity-40 text-blue-300 text-xs rounded-full">
                                                        {{ count($queueStatus['jobs']) }} jobs
                                                    </span>
                                                </h4>

                                                <!-- Quick Actions -->
                                                @if($this->getSelectedQueueJobsCount($shop->id) > 0)
                                                    <div class="flex items-center space-x-2">
                                                        <span class="text-xs text-gray-400 mr-2">
                                                            Zaznaczono: {{ $this->getSelectedQueueJobsCount($shop->id) }}
                                                        </span>
                                                        <button wire:click="executeJobsNow({{ $shop->id }})" type="button"
                                                                class="inline-flex items-center px-3 py-1.5 bg-green-600 text-white text-xs font-medium rounded hover:bg-green-700 transition-colors">
                                                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                            </svg>
                                                            Wykonaj teraz
                                                        </button>
                                                        <button wire:click="retryQueueJobs({{ $shop->id }})" type="button"
                                                                class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white text-xs font-medium rounded hover:bg-blue-700 transition-colors">
                                                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                                            </svg>
                                                            Powtórz
                                                        </button>
                                                        <button wire:click="cancelQueueJobs({{ $shop->id }})" type="button"
                                                                class="inline-flex items-center px-3 py-1.5 bg-red-600 text-white text-xs font-medium rounded hover:bg-red-700 transition-colors">
                                                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                            </svg>
                                                            Anuluj
                                                        </button>
                                                    </div>
                                                @endif
                                            </div>

                                            <div class="overflow-x-auto">
                                                <table class="w-full">
                                                    <thead class="bg-gray-700 bg-opacity-40 border-b border-gray-600">
                                                        <tr>
                                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-300 uppercase w-10">
                                                                <input type="checkbox"
                                                                       wire:click="toggleAllQueueJobs({{ $shop->id }})"
                                                                       @if($this->areAllQueueJobsSelected($shop->id)) checked @endif
                                                                       class="rounded border-gray-600 bg-gray-800 text-[#e0ac7e]">
                                                            </th>
                                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-300 uppercase">Job Name</th>
                                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-300 uppercase">Status</th>
                                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-300 uppercase">Created At</th>
                                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-300 uppercase">Attempts</th>
                                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-300 uppercase">Details</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-gray-600">
                                                        @foreach($queueStatus['jobs'] as $job)
                                                            <tr class="hover:bg-gray-700 hover:bg-opacity-20 transition-colors">
                                                                <td class="px-4 py-2">
                                                                    <input type="checkbox"
                                                                           wire:model="selectedQueueJobs.{{ $shop->id }}.{{ $job['id'] }}"
                                                                           value="{{ $job['id'] }}"
                                                                           class="rounded border-gray-600 bg-gray-800 text-[#e0ac7e]">
                                                                </td>
                                                                <td class="px-4 py-2">
                                                                    <div class="text-sm font-medium text-white">{{ $job['job_name'] ?? 'Unknown' }}</div>
                                                                    @if(isset($job['data']['sku']))
                                                                        <div class="text-xs text-gray-400">SKU: {{ $job['data']['sku'] }}</div>
                                                                    @endif
                                                                </td>
                                                                <td class="px-4 py-2">
                                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                                                        @if($job['status'] === 'processing') bg-yellow-900 bg-opacity-40 text-yellow-300
                                                                        @else bg-green-900 bg-opacity-40 text-green-300 @endif">
                                                                        @if($job['status'] === 'processing')
                                                                            <svg class="w-3 h-3 mr-1 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                                                            </svg>
                                                                        @endif
                                                                        {{ ucfirst($job['status']) }}
                                                                    </span>
                                                                </td>
                                                                <td class="px-4 py-2">
                                                                    <div class="text-sm text-white">{{ $job['created_at']->format('d.m.Y H:i') }}</div>
                                                                    <div class="text-xs text-gray-400">{{ $job['created_at']->diffForHumans() }}</div>
                                                                </td>
                                                                <td class="px-4 py-2">
                                                                    <span class="text-sm text-gray-300">{{ $job['attempts'] }}</span>
                                                                </td>
                                                                <td class="px-4 py-2">
                                                                    @if(isset($job['data']['product_id']))
                                                                        <div class="text-xs text-gray-400">Product ID: {{ $job['data']['product_id'] }}</div>
                                                                    @endif
                                                                    <div class="text-xs text-gray-500">Queue: {{ $job['queue'] }}</div>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center">
                                    <div class="text-gray-400">
                                        <svg class="w-12 h-12 mx-auto mb-4 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                        </svg>
                                        <p class="text-lg font-medium">Brak sklepów</p>
                                        <p class="text-sm">Dodaj pierwszy sklep PrestaShop aby rozpocząć synchronizację</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($shops->hasPages())
                <div class="px-6 py-4 border-t border-gray-600">
                    {{ $shops->links() }}
                </div>
            @endif
        </div>

        <!-- BUG #4 FIX - ETAP_08.4: ERP Connections Section -->
        <div class="mt-8 relative backdrop-blur-xl shadow-lg rounded-lg border"
             style="background: linear-gradient(135deg, rgba(31, 41, 55, 0.8), rgba(17, 24, 39, 0.8)); border: 1px solid rgba(37, 99, 235, 0.3);">

            <div class="px-6 py-4 border-b border-blue-600/30">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-white flex items-center">
                        <svg class="w-5 h-5 text-blue-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                        </svg>
                        Polaczenia ERP
                        <span class="ml-2 px-2 py-0.5 text-xs font-medium bg-blue-500/20 text-blue-300 rounded-full">
                            {{ $stats['total_erp_connections'] ?? 0 }}
                        </span>
                    </h3>

                    <div class="flex items-center gap-3">
                        <!-- Queue Worker Button -->
                        <button wire:click="runQueueWorker(10)"
                                type="button"
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white transition-all duration-200 hover:scale-105 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Uruchom Queue Worker
                            @if(($stats['erp_jobs_in_queue'] ?? 0) > 0)
                                <span class="ml-1 px-1.5 py-0.5 text-xs bg-yellow-500 text-black rounded-full">
                                    {{ $stats['erp_jobs_in_queue'] }}
                                </span>
                            @endif
                        </button>

                        <a href="{{ route('admin.integrations') }}"
                           class="inline-flex items-center px-4 py-2 border border-blue-500/50 text-sm font-medium rounded-lg text-blue-300 hover:bg-blue-500/10 transition-all duration-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            Zarzadzaj ERP
                        </a>
                    </div>
                </div>
            </div>

            <!-- ERP Statistics Cards -->
            <div class="px-6 py-4 grid grid-cols-1 md:grid-cols-4 gap-4 border-b border-blue-600/20">
                <div class="flex items-center p-3 rounded-lg bg-blue-500/10">
                    <div class="w-8 h-8 bg-blue-500/20 rounded-lg flex items-center justify-center mr-3">
                        <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">Wszystkie</p>
                        <p class="text-lg font-bold text-white">{{ $stats['total_erp_connections'] ?? 0 }}</p>
                    </div>
                </div>

                <div class="flex items-center p-3 rounded-lg bg-green-500/10">
                    <div class="w-8 h-8 bg-green-500/20 rounded-lg flex items-center justify-center mr-3">
                        <svg class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">Aktywne</p>
                        <p class="text-lg font-bold text-white">{{ $stats['active_erp_connections'] ?? 0 }}</p>
                    </div>
                </div>

                <div class="flex items-center p-3 rounded-lg bg-emerald-500/10">
                    <div class="w-8 h-8 bg-emerald-500/20 rounded-lg flex items-center justify-center mr-3">
                        <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">Zdrowe</p>
                        <p class="text-lg font-bold text-white">{{ $stats['healthy_erp_connections'] ?? 0 }}</p>
                    </div>
                </div>

                <div class="flex items-center p-3 rounded-lg bg-yellow-500/10">
                    <div class="w-8 h-8 bg-yellow-500/20 rounded-lg flex items-center justify-center mr-3">
                        <svg class="w-4 h-4 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">Jobs w kolejce</p>
                        <p class="text-lg font-bold text-white">{{ $stats['erp_jobs_in_queue'] ?? 0 }}</p>
                    </div>
                </div>
            </div>

            <!-- ERP Connections Table -->
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-blue-900/20 border-b border-blue-600/30">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-blue-300 uppercase tracking-wider">Typ ERP</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-blue-300 uppercase tracking-wider">Instancja</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-blue-300 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-blue-300 uppercase tracking-wider">Auth</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-blue-300 uppercase tracking-wider">Ostatnia sync</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-blue-300 uppercase tracking-wider">Statystyki</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-blue-600/20">
                        @forelse($erpConnections as $erp)
                            <tr class="hover:bg-blue-900/10 transition-colors duration-200">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 rounded-lg flex items-center justify-center mr-3
                                            @if($erp->erp_type === 'baselinker') bg-orange-500/20
                                            @elseif($erp->erp_type === 'subiekt_gt') bg-blue-500/20
                                            @elseif($erp->erp_type === 'dynamics') bg-purple-500/20
                                            @else bg-gray-500/20 @endif">
                                            @if($erp->erp_type === 'baselinker')
                                                <span class="text-orange-400 font-bold text-xs">BL</span>
                                            @elseif($erp->erp_type === 'subiekt_gt')
                                                <span class="text-blue-400 font-bold text-xs">SG</span>
                                            @elseif($erp->erp_type === 'dynamics')
                                                <span class="text-purple-400 font-bold text-xs">MD</span>
                                            @else
                                                <span class="text-gray-400 font-bold text-xs">ERP</span>
                                            @endif
                                        </div>
                                        <span class="text-sm font-medium text-white">
                                            {{ ucfirst(str_replace('_', ' ', $erp->erp_type)) }}
                                        </span>
                                    </div>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div>
                                        <div class="text-sm font-medium text-white">{{ $erp->instance_name }}</div>
                                        <div class="text-xs text-gray-400">{{ Str::limit($erp->description, 30) }}</div>
                                    </div>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($erp->is_active)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            @if($erp->connection_status === 'connected') bg-green-900/40 text-green-300
                                            @elseif($erp->connection_status === 'error') bg-red-900/40 text-red-300
                                            @elseif($erp->connection_status === 'rate_limited') bg-yellow-900/40 text-yellow-300
                                            @else bg-gray-700/40 text-gray-300 @endif">
                                            @if($erp->connection_status === 'connected')
                                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 8 8"><circle cx="4" cy="4" r="3"/></svg>
                                                Polaczony
                                            @elseif($erp->connection_status === 'error')
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                                </svg>
                                                Blad
                                            @elseif($erp->connection_status === 'rate_limited')
                                                Rate Limited
                                            @else
                                                {{ ucfirst($erp->connection_status ?? 'nieznany') }}
                                            @endif
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-700/40 text-gray-400">
                                            Nieaktywny
                                        </span>
                                    @endif
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        @if($erp->auth_status === 'authenticated') bg-green-900/40 text-green-300
                                        @elseif($erp->auth_status === 'expired') bg-red-900/40 text-red-300
                                        @elseif($erp->auth_status === 'pending') bg-yellow-900/40 text-yellow-300
                                        @else bg-gray-700/40 text-gray-300 @endif">
                                        @if($erp->auth_status === 'authenticated') Autoryzowany
                                        @elseif($erp->auth_status === 'expired') Wygasly
                                        @elseif($erp->auth_status === 'pending') Oczekuje
                                        @else {{ ucfirst($erp->auth_status ?? 'nieznany') }}
                                        @endif
                                    </span>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($erp->last_sync_at)
                                        <div class="text-sm text-white">{{ $erp->last_sync_at->format('d.m.Y H:i') }}</div>
                                        <div class="text-xs text-gray-400">{{ $erp->last_sync_at->diffForHumans() }}</div>
                                    @else
                                        <span class="text-sm text-gray-400">Nigdy</span>
                                    @endif
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="text-center">
                                            <span class="text-xs text-gray-400 block">Sukces</span>
                                            <span class="text-sm font-medium text-green-400">{{ $erp->sync_success_count ?? 0 }}</span>
                                        </div>
                                        <div class="text-center">
                                            <span class="text-xs text-gray-400 block">Bledy</span>
                                            <span class="text-sm font-medium text-red-400">{{ $erp->sync_error_count ?? 0 }}</span>
                                        </div>
                                        @if($erp->sync_success_rate > 0)
                                            <div class="text-center">
                                                <span class="text-xs text-gray-400 block">Rate</span>
                                                <span class="text-sm font-medium text-blue-400">{{ number_format($erp->sync_success_rate, 0) }}%</span>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-400">
                                    <svg class="w-12 h-12 mx-auto mb-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                    </svg>
                                    <p class="text-lg font-medium">Brak polaczen ERP</p>
                                    <p class="text-sm mt-1">Dodaj polaczenie w sekcji Integracje ERP</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Sync Jobs (BUG #9 FIX #2: Added wire:poll.5s for auto-refresh) -->
        <!-- ETAP_08.5: Removed separate "Ostatnie joby ERP" section - ERP jobs are now shown here with target_type=baselinker -->
        <div class="mt-8 relative backdrop-blur-xl shadow-lg rounded-lg border"
             style="background: linear-gradient(135deg, rgba(31, 41, 55, 0.8), rgba(17, 24, 39, 0.8)); border: 1px solid rgba(224, 172, 126, 0.2);"
             wire:poll.5s>

            <div class="px-6 py-4 border-b border-gray-600">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-white flex items-center">
                        <svg class="w-5 h-5 text-[#e0ac7e] mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Ostatnie zadania synchronizacji
                        <span wire:loading wire:target="$refresh" class="ml-2 text-sm text-gray-400 italic">
                            (odświeżanie...)
                        </span>
                    </h3>

                    <!-- Buttons Container (2025-11-12): Wrapper dla obu przycisków -->
                    <div class="flex gap-2">
                        <!-- BUG #9 FIX #4 + ENHANCED (2025-11-12): Clear Old Logs Button -->
                        <div x-data="{
                        showModal: false,
                        selectedType: 'all',
                        daysThreshold: 30,
                        clearAllAges: false,
                        confirmClear() {
                            this.showModal = false;
                            this.$wire.clearOldLogs(this.selectedType, this.daysThreshold, this.clearAllAges);
                        }
                    }">
                        <button
                            @click="showModal = true"
                            wire:loading.attr="disabled"
                            wire:target="clearOldLogs"
                            class="btn-enterprise-secondary px-3 py-1.5 text-sm"
                            title="Wyczyść zadania synchronizacji z zaawansowanymi opcjami">

                            <!-- Normal state -->
                            <span wire:loading.remove wire:target="clearOldLogs" class="flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                                Wyczysc Stare Logi
                            </span>

                            <!-- Loading state -->
                            <span wire:loading wire:target="clearOldLogs" class="flex items-center gap-1.5">
                                <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Czyszcze...
                            </span>
                        </button>

                        <!-- Advanced Cleanup Modal -->
                        <div
                            x-show="showModal"
                            x-cloak
                            @click.away="showModal = false"
                            class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
                            style="display: none;">

                            <div class="bg-gray-800 rounded-lg shadow-xl p-6 max-w-lg mx-4 border border-gray-700" @click.stop>
                                <div class="flex items-start gap-4">
                                    <!-- Warning Icon -->
                                    <div class="flex-shrink-0">
                                        <svg class="w-12 h-12 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </div>

                                    <!-- Content -->
                                    <div class="flex-1">
                                        <h4 class="text-lg font-semibold text-white mb-4">
                                            Wyczysc Stare Logi Synchronizacji
                                        </h4>

                                        <!-- Type Selection -->
                                        <div class="mb-4">
                                            <label class="block text-sm font-medium text-gray-300 mb-2">
                                                Typ zadan do usuniecia:
                                            </label>
                                            <div class="space-y-2">
                                                <label class="flex items-center gap-2 cursor-pointer">
                                                    <input type="radio" x-model="selectedType" value="all"
                                                           class="text-[#e0ac7e] bg-gray-700 border-gray-600 focus:ring-[#e0ac7e]">
                                                    <span class="text-sm text-gray-200">Wszystkie (completed, failed, canceled, timeout)</span>
                                                </label>
                                                <label class="flex items-center gap-2 cursor-pointer">
                                                    <input type="radio" x-model="selectedType" value="completed"
                                                           class="text-[#e0ac7e] bg-gray-700 border-gray-600 focus:ring-[#e0ac7e]">
                                                    <span class="text-sm text-gray-200">Tylko ukonczone (completed)</span>
                                                </label>
                                                <label class="flex items-center gap-2 cursor-pointer">
                                                    <input type="radio" x-model="selectedType" value="completed_with_errors"
                                                           class="text-[#e0ac7e] bg-gray-700 border-gray-600 focus:ring-[#e0ac7e]">
                                                    <span class="text-sm text-gray-200">Tylko z bledami (completed_with_errors)</span>
                                                </label>
                                                <label class="flex items-center gap-2 cursor-pointer">
                                                    <input type="radio" x-model="selectedType" value="failed"
                                                           class="text-[#e0ac7e] bg-gray-700 border-gray-600 focus:ring-[#e0ac7e]">
                                                    <span class="text-sm text-gray-200">Tylko nieudane (failed)</span>
                                                </label>
                                            </div>
                                        </div>

                                        <!-- Age Threshold -->
                                        <div class="mb-4" x-show="!clearAllAges">
                                            <label class="block text-sm font-medium text-gray-300 mb-2">
                                                Starsze niz (dni):
                                            </label>
                                            <input type="number" x-model.number="daysThreshold" min="1" max="365"
                                                   class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#e0ac7e] focus:border-transparent">
                                        </div>

                                        <!-- Clear All Ages Checkbox -->
                                        <div class="mb-4">
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input type="checkbox" x-model="clearAllAges"
                                                       class="rounded text-red-600 bg-gray-700 border-gray-600 focus:ring-red-500">
                                                <span class="text-sm text-red-300 font-medium">Wyczysc wszystkie (ignoruj wiek)</span>
                                            </label>
                                        </div>

                                        <!-- Warning Notice -->
                                        <div class="bg-yellow-900 bg-opacity-30 border border-yellow-700 rounded p-3 mb-4">
                                            <p class="text-xs text-yellow-200 flex items-start gap-2">
                                                <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                                </svg>
                                                <span>Zadania w trakcie wykonania (pending, running) nigdy nie beda usuniete.</span>
                                            </p>
                                        </div>

                                        <!-- Preview Summary -->
                                        <div class="bg-gray-900 bg-opacity-40 rounded p-3 border border-gray-700 mb-4" x-show="!clearAllAges">
                                            <p class="text-xs text-gray-300">
                                                <strong>Zostana usuniete:</strong> Zadania typu
                                                <span class="text-[#e0ac7e]" x-text="selectedType === 'all' ? 'WSZYSTKIE' : selectedType"></span>
                                                starsze niz
                                                <span class="text-[#e0ac7e]" x-text="daysThreshold"></span> dni
                                            </p>
                                        </div>
                                        <div class="bg-red-900 bg-opacity-40 rounded p-3 border border-red-700 mb-4" x-show="clearAllAges">
                                            <p class="text-xs text-red-300 font-semibold">
                                                UWAGA: Zostana usuniete WSZYSTKIE zadania typu
                                                <span x-text="selectedType === 'all' ? 'WSZYSTKIE' : selectedType"></span>
                                                niezaleznie od wieku!
                                            </p>
                                        </div>

                                        <!-- Buttons -->
                                        <div class="flex gap-3 justify-end">
                                            <button
                                                @click="showModal = false"
                                                class="btn-enterprise-secondary px-4 py-2 text-sm border-gray-600 text-gray-300 hover:bg-gray-700">
                                                Anuluj
                                            </button>

                                            <button
                                                @click="confirmClear()"
                                                class="bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-red-700 transition-colors flex items-center gap-2">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                                Wyczysc Logi
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TASK C (2025-11-12): Archive Old Logs Button -->
                    <div x-data="{
                        showArchiveModal: false,
                        archiveType: 'all',
                        archiveDays: 90,
                        archiveAllAges: false,
                        confirmArchive() {
                            this.showArchiveModal = false;
                            this.$wire.archiveOldLogs(this.archiveType, this.archiveDays, this.archiveAllAges);
                        }
                    }">
                        <button
                            @click="showArchiveModal = true"
                            wire:loading.attr="disabled"
                            wire:target="archiveOldLogs"
                            class="btn-enterprise-secondary px-3 py-1.5 text-sm"
                            title="Archiwizuj zadania synchronizacji (export do JSON + usuń)">

                            <!-- Normal state -->
                            <span wire:loading.remove wire:target="archiveOldLogs" class="flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                                </svg>
                                Archiwizuj
                            </span>

                            <!-- Loading state -->
                            <span wire:loading wire:target="archiveOldLogs" class="flex items-center gap-1.5">
                                <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Archiwizuje...
                            </span>
                        </button>

                        <!-- Archive Modal -->
                        <div
                            x-show="showArchiveModal"
                            x-cloak
                            @click.away="showArchiveModal = false"
                            class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
                            style="display: none;">

                            <div class="bg-gray-800 rounded-lg shadow-xl p-6 max-w-lg mx-4 border border-gray-700" @click.stop>
                                <div class="flex items-start gap-4">
                                    <!-- Archive Icon -->
                                    <div class="flex-shrink-0">
                                        <svg class="w-12 h-12 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                                        </svg>
                                    </div>

                                    <!-- Content -->
                                    <div class="flex-1">
                                        <h4 class="text-lg font-semibold text-white mb-4">
                                            Archiwizuj Logi Synchronizacji
                                        </h4>

                                        <!-- Type Selection -->
                                        <div class="mb-4">
                                            <label class="block text-sm font-medium text-gray-300 mb-2">
                                                Typ zadan do archiwizacji:
                                            </label>
                                            <div class="space-y-2">
                                                <label class="flex items-center gap-2 cursor-pointer">
                                                    <input type="radio" x-model="archiveType" value="all"
                                                           class="text-[#e0ac7e] bg-gray-700 border-gray-600 focus:ring-[#e0ac7e]">
                                                    <span class="text-sm text-gray-200">Wszystkie (completed, failed, canceled, timeout)</span>
                                                </label>
                                                <label class="flex items-center gap-2 cursor-pointer">
                                                    <input type="radio" x-model="archiveType" value="completed"
                                                           class="text-[#e0ac7e] bg-gray-700 border-gray-600 focus:ring-[#e0ac7e]">
                                                    <span class="text-sm text-gray-200">Tylko ukonczone (completed)</span>
                                                </label>
                                                <label class="flex items-center gap-2 cursor-pointer">
                                                    <input type="radio" x-model="archiveType" value="completed_with_errors"
                                                           class="text-[#e0ac7e] bg-gray-700 border-gray-600 focus:ring-[#e0ac7e]">
                                                    <span class="text-sm text-gray-200">Tylko z bledami (completed_with_errors)</span>
                                                </label>
                                                <label class="flex items-center gap-2 cursor-pointer">
                                                    <input type="radio" x-model="archiveType" value="failed"
                                                           class="text-[#e0ac7e] bg-gray-700 border-gray-600 focus:ring-[#e0ac7e]">
                                                    <span class="text-sm text-gray-200">Tylko nieudane (failed)</span>
                                                </label>
                                            </div>
                                        </div>

                                        <!-- Age Threshold -->
                                        <div class="mb-4" x-show="!archiveAllAges">
                                            <label class="block text-sm font-medium text-gray-300 mb-2">
                                                Starsze niz (dni):
                                            </label>
                                            <input type="number" x-model.number="archiveDays" min="1" max="365"
                                                   class="w-full bg-gray-700 border-gray-600 text-white rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#e0ac7e] focus:border-transparent">
                                        </div>

                                        <!-- Archive All Ages Checkbox -->
                                        <div class="mb-4">
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input type="checkbox" x-model="archiveAllAges"
                                                       class="rounded text-blue-600 bg-gray-700 border-gray-600 focus:ring-blue-500">
                                                <span class="text-sm text-blue-300 font-medium">Archiwizuj wszystkie (ignoruj wiek)</span>
                                            </label>
                                        </div>

                                        <!-- Info Notice -->
                                        <div class="bg-blue-900 bg-opacity-30 border border-blue-700 rounded p-3 mb-4">
                                            <p class="text-xs text-blue-200 flex items-start gap-2">
                                                <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                                </svg>
                                                <span>Zadania zostana wyeksportowane do JSON (storage/app/sync_jobs_archive/) a nastepnie usuniete z bazy.</span>
                                            </p>
                                        </div>

                                        <!-- Warning Notice -->
                                        <div class="bg-yellow-900 bg-opacity-30 border border-yellow-700 rounded p-3 mb-4">
                                            <p class="text-xs text-yellow-200 flex items-start gap-2">
                                                <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                                </svg>
                                                <span>Zadania w trakcie wykonania (pending, running) nigdy nie beda zarchiwizowane.</span>
                                            </p>
                                        </div>

                                        <!-- Preview Summary -->
                                        <div class="bg-gray-900 bg-opacity-40 rounded p-3 border border-gray-700 mb-4" x-show="!archiveAllAges">
                                            <p class="text-xs text-gray-300">
                                                <strong>Zostana zarchiwizowane:</strong> Zadania typu
                                                <span class="text-[#e0ac7e]" x-text="archiveType === 'all' ? 'WSZYSTKIE' : archiveType"></span>
                                                starsze niz
                                                <span class="text-[#e0ac7e]" x-text="archiveDays"></span> dni
                                            </p>
                                        </div>
                                        <div class="bg-blue-900 bg-opacity-40 rounded p-3 border border-blue-700 mb-4" x-show="archiveAllAges">
                                            <p class="text-xs text-blue-300 font-semibold">
                                                UWAGA: Zostana zarchiwizowane WSZYSTKIE zadania typu
                                                <span x-text="archiveType === 'all' ? 'WSZYSTKIE' : archiveType"></span>
                                                niezaleznie od wieku!
                                            </p>
                                        </div>

                                        <!-- Buttons -->
                                        <div class="flex gap-3 justify-end">
                                            <button
                                                @click="showArchiveModal = false"
                                                class="btn-enterprise-secondary px-4 py-2 text-sm border-gray-600 text-gray-300 hover:bg-gray-700">
                                                Anuluj
                                            </button>

                                            <button
                                                @click="confirmArchive()"
                                                class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-blue-700 transition-colors flex items-center gap-2">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                                                </svg>
                                                Archiwizuj
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    </div> <!-- Zamyka buttons wrapper (flex gap-2) -->
                </div> <!-- Zamyka flex justify-between -->
            </div>

            {{-- NEW: FILTERS BAR (BUG #9 FIX #7) --}}
            <div class="px-6 py-4 bg-gray-800 bg-opacity-30 border-b border-gray-600">
                <div class="flex items-center gap-2 mb-3">
                    <svg class="w-5 h-5 text-[#e0ac7e]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                    </svg>
                    <span class="text-sm font-semibold text-white">Filtry:</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-3">
                    {{-- Filter 1: Job Type --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-300 mb-1">Typ</label>
                        <select wire:model.live="filterJobType"
                                class="w-full text-sm rounded-lg border-gray-600 bg-gray-700 text-white focus:border-[#e0ac7e] focus:ring-[#e0ac7e]">
                            <option value="">Wszystkie</option>
                            <option value="import_products">← Import</option>
                            <option value="product_sync">Sync →</option>
                        </select>
                    </div>

                    {{-- Filter 2: Order By --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-300 mb-1">Sortowanie</label>
                        <select wire:model.live="filterOrderBy"
                                class="w-full text-sm rounded-lg border-gray-600 bg-gray-700 text-white focus:border-[#e0ac7e] focus:ring-[#e0ac7e]">
                            <option value="desc">Najnowsze</option>
                            <option value="asc">Najstarsze</option>
                        </select>
                    </div>

                    {{-- Filter 3: User --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-300 mb-1">Użytkownik</label>
                        <select wire:model.live="filterUserId"
                                class="w-full text-sm rounded-lg border-gray-600 bg-gray-700 text-white focus:border-[#e0ac7e] focus:ring-[#e0ac7e]">
                            <option value="">Wszyscy</option>
                            @if(isset($filterUsers))
                                @foreach($filterUsers as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    {{-- Filter 4: Status --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-300 mb-1">Status</label>
                        <select wire:model.live="filterStatus"
                                class="w-full text-sm rounded-lg border-gray-600 bg-gray-700 text-white focus:border-[#e0ac7e] focus:ring-[#e0ac7e]">
                            <option value="">Wszystkie</option>
                            <option value="completed">Ukończone</option>
                            <option value="failed">Nieudane</option>
                            <option value="running">W trakcie</option>
                            <option value="pending">Oczekujące</option>
                            <option value="canceled">Anulowane</option>
                        </select>
                    </div>

                    {{-- Filter 5: Shop --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-300 mb-1">Sklep</label>
                        <select wire:model.live="filterShopId"
                                class="w-full text-sm rounded-lg border-gray-600 bg-gray-700 text-white focus:border-[#e0ac7e] focus:ring-[#e0ac7e]">
                            <option value="">Wszystkie</option>
                            @if(isset($filterShops))
                                @foreach($filterShops as $shop)
                                    <option value="{{ $shop->id }}">{{ $shop->name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    {{-- Reset Button --}}
                    <div class="flex items-end">
                        <button
                            wire:click="resetSyncJobFilters"
                            wire:loading.attr="disabled"
                            wire:target="resetSyncJobFilters"
                            class="btn-enterprise-secondary w-full px-3 py-2 text-sm border-gray-600 text-gray-300 hover:bg-gray-700"
                            title="Resetuj wszystkie filtry">

                            <span wire:loading.remove wire:target="resetSyncJobFilters" class="flex items-center justify-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                                Resetuj
                            </span>

                            <span wire:loading wire:target="resetSyncJobFilters" class="flex items-center justify-center gap-1.5">
                                <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Resetuję...
                            </span>
                        </button>
                    </div>
                </div>

                {{-- Active Filters Count --}}
                <div class="mt-3 text-xs text-gray-400">
                    @php
                        $activeFiltersCount = 0;
                        if ($this->filterJobType) $activeFiltersCount++;
                        if ($this->filterUserId) $activeFiltersCount++;
                        if ($this->filterStatus) $activeFiltersCount++;
                        if ($this->filterShopId) $activeFiltersCount++;
                        if ($this->filterOrderBy !== 'desc') $activeFiltersCount++;
                    @endphp

                    @if($activeFiltersCount > 0)
                        <span class="inline-flex items-center gap-1 text-[#e0ac7e]">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Aktywne filtry: {{ $activeFiltersCount }}
                        </span>
                    @else
                        <span class="text-gray-500">Brak aktywnych filtrów</span>
                    @endif
                </div>
            </div>

            <div class="p-6">
                <div class="space-y-3">
                    @forelse($recentSyncJobs as $job)
                        <div class="bg-gray-800 bg-opacity-40 rounded-lg border border-gray-600 overflow-hidden">
                            {{-- Clickable Header --}}
                            <div wire:click="toggleRecentJobDetails({{ $job->id }})"
                                 class="flex items-center justify-between p-3 cursor-pointer hover:bg-gray-700 hover:bg-opacity-30 transition-colors">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        {{-- BUG #9 FIX #3: Job Type Badge --}}
                                        {{-- ETAP_08.5 + FAZA 5: Target Type Badges (ERP systems) --}}
                                        @switch($job->target_type ?? 'prestashop')
                                            @case('prestashop')
                                                <span class="sync-job-type-badge inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-900 bg-opacity-40 text-blue-300 border border-blue-700">
                                                    <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                                                    </svg>
                                                    PrestaShop
                                                </span>
                                                @break
                                            @case('subiekt_gt')
                                                <span class="sync-job-type-badge inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-green-900 bg-opacity-40 text-green-300 border border-green-700">
                                                    <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                    </svg>
                                                    Subiekt GT
                                                </span>
                                                @break
                                            @case('baselinker')
                                                <span class="sync-job-type-badge inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-orange-900 bg-opacity-40 text-orange-300 border border-orange-700">
                                                    <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                                    </svg>
                                                    Baselinker
                                                </span>
                                                @break
                                            @case('dynamics')
                                                <span class="sync-job-type-badge inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-purple-900 bg-opacity-40 text-purple-300 border border-purple-700">
                                                    <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                                                    </svg>
                                                    Dynamics
                                                </span>
                                                @break
                                            @default
                                                <span class="sync-job-type-badge inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-gray-700 bg-opacity-40 text-gray-300 border border-gray-600">
                                                    {{ strtoupper($job->target_type ?? 'unknown') === 'PPM' ? 'PPM' : ucfirst(str_replace('_', ' ', $job->target_type ?? 'unknown')) }}
                                                </span>
                                        @endswitch

                                        {{-- Job Type Badge (Import/Sync direction) --}}
                                        @if($job->job_type === 'bulk_import' || $job->job_type === 'import_products' || str_starts_with($job->job_type ?? '', 'pull_'))
                                            <span class="sync-job-type-badge inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-cyan-900 bg-opacity-30 text-cyan-300 border border-cyan-700">
                                                ← Import
                                            </span>
                                        @elseif($job->job_type === 'product_sync' || $job->job_type === 'erp_sync' || str_starts_with($job->job_type ?? '', 'push_'))
                                            <span class="sync-job-type-badge inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-amber-900 bg-opacity-30 text-amber-300 border border-amber-700">
                                                Sync →
                                            </span>
                                        @endif

                                        <div class="text-sm font-medium text-white">{{ $job->job_name }}</div>
                                        <span class="ml-2 text-xs text-gray-500">
                                            (ID: {{ substr($job->job_id, 0, 8) }}...)
                                        </span>
                                    </div>
                                    <div class="text-xs text-gray-400 mt-1">
                                        {{ $job->created_at->format('d.m.Y H:i') }} • {{ $job->created_at->diffForHumans() }}
                                        @if($job->user_id && $job->user)
                                            • <span class="text-[#e0ac7e]">{{ $job->user->name }}</span>
                                        @else
                                            • <span class="text-gray-500 italic">SYSTEM</span>
                                        @endif
                                    </div>
                                </div>

                                <div class="flex items-center space-x-3">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        @if($job->status === 'completed') bg-green-900 bg-opacity-40 text-green-300
                                        @elseif($job->status === 'failed') bg-red-900 bg-opacity-40 text-red-300
                                        @elseif($job->status === 'running') bg-yellow-900 bg-opacity-40 text-yellow-300
                                        @else bg-gray-700 bg-opacity-40 text-gray-300 @endif">
                                        {{ ucfirst($job->status) }}
                                    </span>

                                    @if($job->duration_seconds)
                                        <span class="text-xs text-gray-400">{{ $job->duration_seconds }}s</span>
                                    @endif

                                    {{-- Expand/Collapse Icon --}}
                                    <svg class="w-5 h-5 text-gray-400 transition-transform duration-200 @if($expandedRecentJobId === $job->id) rotate-180 @endif"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </div>
                            </div>

                            {{-- Expanded Details Panel --}}
                            @if($expandedRecentJobId === $job->id)
                            <div class="border-t border-gray-600 bg-gray-900 bg-opacity-60 p-4 space-y-4">

                                {{-- Performance Metrics Grid --}}
                                <div>
                                    <h4 class="text-xs font-semibold text-[#e0ac7e] uppercase tracking-wide mb-2 flex items-center">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                        </svg>
                                        Performance Metrics
                                    </h4>
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                        <div class="bg-gray-800 bg-opacity-40 rounded p-2 border border-gray-700">
                                            <div class="text-xs text-gray-400">Duration</div>
                                            <div class="text-sm font-medium text-white">{{ $job->duration_seconds ?? 0 }}s</div>
                                        </div>
                                        <div class="bg-gray-800 bg-opacity-40 rounded p-2 border border-gray-700">
                                            <div class="text-xs text-gray-400">Memory Peak</div>
                                            <div class="text-sm font-medium text-white">{{ $job->memory_peak_mb ?? 0 }} MB</div>
                                        </div>
                                        <div class="bg-gray-800 bg-opacity-40 rounded p-2 border border-gray-700">
                                            <div class="text-xs text-gray-400">CPU Time</div>
                                            <div class="text-sm font-medium text-white">{{ number_format($job->cpu_time_seconds ?? 0, 3) }}s</div>
                                        </div>
                                        <div class="bg-gray-800 bg-opacity-40 rounded p-2 border border-gray-700">
                                            <div class="text-xs text-gray-400">API Calls</div>
                                            <div class="text-sm font-medium text-white">{{ $job->api_calls_made ?? 0 }}</div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Progress Stats --}}
                                <div>
                                    <h4 class="text-xs font-semibold text-[#e0ac7e] uppercase tracking-wide mb-2 flex items-center">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        Progress
                                    </h4>
                                    <div class="grid grid-cols-2 md:grid-cols-6 gap-3">
                                        <div class="bg-gray-800 bg-opacity-40 rounded p-2 border border-gray-700">
                                            <div class="text-xs text-gray-400">Total Items</div>
                                            <div class="text-sm font-medium text-white">{{ $job->total_items ?? 0 }}</div>
                                        </div>
                                        <div class="bg-gray-800 bg-opacity-40 rounded p-2 border border-gray-700">
                                            <div class="text-xs text-gray-400">Processed</div>
                                            <div class="text-sm font-medium text-white">{{ $job->processed_items ?? 0 }}</div>
                                        </div>
                                        <div class="bg-gray-800 bg-opacity-40 rounded p-2 border border-green-700">
                                            <div class="text-xs text-gray-400">Successful</div>
                                            <div class="text-sm font-medium text-green-300">{{ $job->successful_items ?? 0 }}</div>
                                        </div>
                                        {{-- Skipped tile (date_upd optimization 2026-01-19) --}}
                                        <div class="bg-gray-800 bg-opacity-40 rounded p-2 border border-yellow-600">
                                            <div class="text-xs text-gray-400">Skipped</div>
                                            <div class="text-sm font-medium text-yellow-300">{{ $job->result_summary['skipped'] ?? 0 }}</div>
                                        </div>
                                        <div class="bg-gray-800 bg-opacity-40 rounded p-2 border border-red-700">
                                            <div class="text-xs text-gray-400">Failed</div>
                                            <div class="text-sm font-medium text-red-300">{{ $job->failed_items ?? 0 }}</div>
                                        </div>
                                        <div class="bg-gray-800 bg-opacity-40 rounded p-2 border border-gray-700">
                                            <div class="text-xs text-gray-400">Progress</div>
                                            <div class="text-sm font-medium text-white">{{ number_format($job->progress_percentage ?? 0, 1) }}%</div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Result Summary - Enhanced Display (FIX 2025-12-22) --}}
                                @if($job->result_summary)
                                <div x-data="{ resultSummaryExpanded: false }">
                                    <h4 @click="resultSummaryExpanded = !resultSummaryExpanded"
                                        class="text-xs font-semibold text-[#e0ac7e] uppercase tracking-wide mb-2 flex items-center cursor-pointer hover:text-[#d1975a] transition-colors">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        Result Summary
                                        <span class="ml-2 text-xs text-gray-500">(kliknij aby rozwinąć)</span>
                                        <svg class="w-4 h-4 ml-auto transition-transform duration-200"
                                             :class="{ 'rotate-180': resultSummaryExpanded }"
                                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    </h4>
                                    <div x-show="resultSummaryExpanded"
                                         x-transition:enter="transition ease-out duration-200"
                                         x-transition:enter-start="opacity-0 transform scale-95"
                                         x-transition:enter-end="opacity-100 transform scale-100"
                                         x-transition:leave="transition ease-in duration-150"
                                         x-transition:leave-start="opacity-100 transform scale-100"
                                         x-transition:leave-end="opacity-0 transform scale-95"
                                         class="bg-gray-800 bg-opacity-60 rounded border border-gray-700 p-3 space-y-3">

                                        {{-- Primary Stats Grid --}}
                                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                            @if(isset($job->result_summary['imported']))
                                            <div class="bg-green-900 bg-opacity-30 rounded p-2 border border-green-700">
                                                <div class="text-xs text-green-400">Zaimportowano</div>
                                                <div class="text-lg font-bold text-green-300">{{ $job->result_summary['imported'] }}</div>
                                            </div>
                                            @endif
                                            @if(isset($job->result_summary['updated']))
                                            <div class="bg-blue-900 bg-opacity-30 rounded p-2 border border-blue-700">
                                                <div class="text-xs text-blue-400">Zaktualizowano</div>
                                                <div class="text-lg font-bold text-blue-300">{{ $job->result_summary['updated'] }}</div>
                                            </div>
                                            @endif
                                            @if(isset($job->result_summary['skipped']))
                                            <div class="bg-yellow-900 bg-opacity-30 rounded p-2 border border-yellow-700">
                                                <div class="text-xs text-yellow-400">Pominięto</div>
                                                <div class="text-lg font-bold text-yellow-300">{{ $job->result_summary['skipped'] }}</div>
                                            </div>
                                            @endif
                                            @if(isset($job->result_summary['errors_count']))
                                            <div class="bg-red-900 bg-opacity-30 rounded p-2 border border-red-700">
                                                <div class="text-xs text-red-400">Błędy</div>
                                                <div class="text-lg font-bold text-red-300">{{ $job->result_summary['errors_count'] }}</div>
                                            </div>
                                            @endif
                                        </div>

                                        {{-- Extended Stats (FIX 2025-12-22) --}}
                                        @if(
                                            isset($job->result_summary['categories_assigned']) ||
                                            isset($job->result_summary['features_imported']) ||
                                            isset($job->result_summary['variants_imported']) ||
                                            isset($job->result_summary['types_detected']) ||
                                            isset($job->result_summary['compatibilities_imported']) ||
                                            isset($job->result_summary['media_synced'])
                                        )
                                        <div class="border-t border-gray-700 pt-3">
                                            <div class="text-xs text-gray-400 mb-2 uppercase tracking-wide">Szczegółowe dane importu</div>
                                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                                @if(isset($job->result_summary['categories_assigned']) && $job->result_summary['categories_assigned'] > 0)
                                                <div class="bg-gray-800 bg-opacity-40 rounded p-2 border border-gray-700">
                                                    <div class="text-xs text-gray-400">Kategorie</div>
                                                    <div class="text-sm font-medium text-white">{{ $job->result_summary['categories_assigned'] }}</div>
                                                </div>
                                                @endif
                                                @if(isset($job->result_summary['features_imported']) && $job->result_summary['features_imported'] > 0)
                                                <div class="bg-gray-800 bg-opacity-40 rounded p-2 border border-gray-700">
                                                    <div class="text-xs text-gray-400">Cechy produktów</div>
                                                    <div class="text-sm font-medium text-white">{{ $job->result_summary['features_imported'] }}</div>
                                                </div>
                                                @endif
                                                @if(isset($job->result_summary['variants_imported']) && $job->result_summary['variants_imported'] > 0)
                                                <div class="bg-gray-800 bg-opacity-40 rounded p-2 border border-gray-700">
                                                    <div class="text-xs text-gray-400">Warianty</div>
                                                    <div class="text-sm font-medium text-white">{{ $job->result_summary['variants_imported'] }}</div>
                                                </div>
                                                @endif
                                                @if(isset($job->result_summary['types_detected']) && $job->result_summary['types_detected'] > 0)
                                                <div class="bg-purple-900 bg-opacity-30 rounded p-2 border border-purple-700">
                                                    <div class="text-xs text-purple-400">Typy autowykryte</div>
                                                    <div class="text-sm font-medium text-purple-300">{{ $job->result_summary['types_detected'] }}</div>
                                                </div>
                                                @endif
                                                @if(isset($job->result_summary['compatibilities_imported']) && $job->result_summary['compatibilities_imported'] > 0)
                                                <div class="bg-gray-800 bg-opacity-40 rounded p-2 border border-gray-700">
                                                    <div class="text-xs text-gray-400">Dopasowania pojazdów</div>
                                                    <div class="text-sm font-medium text-white">{{ $job->result_summary['compatibilities_imported'] }}</div>
                                                </div>
                                                @endif
                                                @if(isset($job->result_summary['media_synced']) && $job->result_summary['media_synced'] > 0)
                                                <div class="bg-gray-800 bg-opacity-40 rounded p-2 border border-gray-700">
                                                    <div class="text-xs text-gray-400">Media zsync.</div>
                                                    <div class="text-sm font-medium text-white">{{ $job->result_summary['media_synced'] }}</div>
                                                </div>
                                                @endif
                                            </div>
                                        </div>
                                        @endif

                                        {{-- Execution Time --}}
                                        @if(isset($job->result_summary['execution_time_ms']))
                                        <div class="text-xs text-gray-500 pt-2 border-t border-gray-700">
                                            Czas wykonania: {{ number_format($job->result_summary['execution_time_ms'] / 1000, 2) }}s
                                        </div>
                                        @endif

                                        {{-- Raw JSON (collapsible for debugging) --}}
                                        <div x-data="{ showRawJson: false }" class="pt-2">
                                            <button @click="showRawJson = !showRawJson" class="text-xs text-gray-500 hover:text-gray-400">
                                                <span x-text="showRawJson ? 'Ukryj JSON' : 'Pokaż JSON'"></span>
                                            </button>
                                            <div x-show="showRawJson" x-cloak class="mt-2">
                                                <pre class="text-xs text-gray-400 font-mono overflow-x-auto bg-gray-900 p-2 rounded">{{ json_encode($job->result_summary, JSON_PRETTY_PRINT) }}</pre>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endif

                                {{-- Errors (if failed) --}}
                                @if($job->status === 'failed' || $job->error_message)
                                <div>
                                    <h4 class="text-xs font-semibold text-red-400 uppercase tracking-wide mb-2 flex items-center">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        Error Details
                                    </h4>
                                    <div class="bg-red-900 bg-opacity-20 rounded border border-red-700 p-3 space-y-2">
                                        @if($job->error_message)
                                        <div>
                                            <div class="text-xs text-gray-400 mb-1">Error Message:</div>
                                            <div class="text-sm text-red-300">{{ $job->error_message }}</div>
                                        </div>
                                        @endif
                                        @if($job->error_details)
                                        <div>
                                            <div class="text-xs text-gray-400 mb-1">Error Details:</div>
                                            <div class="text-xs text-red-200 font-mono">{{ $job->error_details }}</div>
                                        </div>
                                        @endif
                                        @if($job->stack_trace)
                                        <details class="mt-2">
                                            <summary class="text-xs text-gray-400 cursor-pointer hover:text-gray-300">Stack Trace (click to expand)</summary>
                                            <pre class="text-xs text-red-200 font-mono mt-2 overflow-x-auto max-h-48">{{ $job->stack_trace }}</pre>
                                        </details>
                                        @endif
                                    </div>
                                </div>
                                @endif

                                {{-- Validation Errors & Warnings --}}
                                @if($job->validation_errors || $job->warnings)
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    @if($job->validation_errors)
                                    <div>
                                        <h4 class="text-xs font-semibold text-yellow-400 uppercase tracking-wide mb-2">Validation Errors</h4>
                                        <div class="bg-yellow-900 bg-opacity-20 rounded border border-yellow-700 p-2">
                                            <pre class="text-xs text-yellow-200">{{ json_encode($job->validation_errors, JSON_PRETTY_PRINT) }}</pre>
                                        </div>
                                    </div>
                                    @endif
                                    @if($job->warnings)
                                    <div>
                                        <h4 class="text-xs font-semibold text-yellow-400 uppercase tracking-wide mb-2">Warnings</h4>
                                        <div class="bg-yellow-900 bg-opacity-20 rounded border border-yellow-700 p-2">
                                            <pre class="text-xs text-yellow-200">{{ json_encode($job->warnings, JSON_PRETTY_PRINT) }}</pre>
                                        </div>
                                    </div>
                                    @endif
                                </div>
                                @endif

                                {{-- Timestamps --}}
                                <div>
                                    <h4 class="text-xs font-semibold text-[#e0ac7e] uppercase tracking-wide mb-2">Timestamps</h4>
                                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                                        @if($job->scheduled_at)
                                        <div class="text-xs">
                                            <span class="text-gray-400">Scheduled:</span>
                                            <span class="text-white ml-1">{{ $job->scheduled_at->format('d.m.Y H:i:s') }}</span>
                                        </div>
                                        @endif
                                        @if($job->started_at)
                                        <div class="text-xs">
                                            <span class="text-gray-400">Started:</span>
                                            <span class="text-white ml-1">{{ $job->started_at->format('d.m.Y H:i:s') }}</span>
                                        </div>
                                        @endif
                                        @if($job->completed_at)
                                        <div class="text-xs">
                                            <span class="text-gray-400">Completed:</span>
                                            <span class="text-white ml-1">{{ $job->completed_at->format('d.m.Y H:i:s') }}</span>
                                        </div>
                                        @endif
                                    </div>
                                </div>

                                {{-- Changed Fields (UPDATE only) - 2025-11-07 --}}
                                @if(isset($job->result_summary['operation']) && $job->result_summary['operation'] === 'update' && isset($job->result_summary['changed_fields']) && !empty($job->result_summary['changed_fields']))
                                <div>
                                    <h4 class="text-xs font-semibold text-[#e0ac7e] uppercase tracking-wide mb-2 flex items-center">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                                        </svg>
                                        Changed Fields
                                        <span class="ml-2 px-2 py-0.5 bg-[#d1975a] bg-opacity-40 text-[#e0ac7e] text-xs rounded-full">
                                            {{ count($job->result_summary['changed_fields']) }} zmiany
                                        </span>
                                    </h4>
                                    <div class="bg-gray-800 bg-opacity-60 rounded border border-gray-700 overflow-hidden">
                                        <table class="w-full text-xs">
                                            <thead class="bg-gray-700 bg-opacity-40">
                                                <tr>
                                                    <th class="px-3 py-2 text-left text-gray-300 font-medium">Pole</th>
                                                    <th class="px-3 py-2 text-left text-gray-300 font-medium">Poprzednia wartość</th>
                                                    <th class="px-3 py-2 text-left text-gray-300 font-medium">Nowa wartość</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($job->result_summary['changed_fields'] as $field => $change)
                                                <tr class="border-t border-gray-700">
                                                    {{-- FIELD-LEVEL INDICATOR: Niebieski gradient dla zmienionego pola (kolor "Oczekuje") --}}
                                                    <td class="px-3 py-2 font-mono text-white font-medium"
                                                        style="background: linear-gradient(90deg, rgba(37, 99, 235, 0.15), rgba(37, 99, 235, 0.05));
                                                               border-left: 3px solid rgba(37, 99, 235, 0.5);
                                                               color: #60a5fa;">
                                                        {{ $field }}
                                                    </td>
                                                    <td class="px-3 py-2 text-red-300">
                                                        @if(is_array($change['old']))
                                                            <code class="text-xs">{{ json_encode($change['old']) }}</code>
                                                        @elseif(is_null($change['old']))
                                                            <span class="text-gray-500 italic">null</span>
                                                        @else
                                                            {{ $change['old'] }}
                                                        @endif
                                                    </td>
                                                    <td class="px-3 py-2 text-green-300">
                                                        @if(is_array($change['new']))
                                                            <code class="text-xs">{{ json_encode($change['new']) }}</code>
                                                        @elseif(is_null($change['new']))
                                                            <span class="text-gray-500 italic">null</span>
                                                        @else
                                                            {{ $change['new'] }}
                                                        @endif
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                @endif

                                {{-- Queue Info --}}
                                <div>
                                    <h4 class="text-xs font-semibold text-[#e0ac7e] uppercase tracking-wide mb-2">Queue Information</h4>
                                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3 text-xs">
                                        <div>
                                            <span class="text-gray-400">Queue Name:</span>
                                            <span class="text-white ml-1 font-mono">{{ $job->queue_name ?? 'default' }}</span>
                                        </div>
                                        <div>
                                            <span class="text-gray-400">Queue Job ID:</span>
                                            <span class="text-white ml-1 font-mono">{{ $job->queue_job_id ?? 'N/A' }}</span>
                                        </div>
                                        <div>
                                            <span class="text-gray-400">Queue Attempts:</span>
                                            <span class="text-white ml-1">{{ $job->queue_attempts ?? 0 }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                    @empty
                        <div class="text-center text-gray-400 py-8">
                            <svg class="w-12 h-12 mx-auto mb-3 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                            </svg>
                            <p class="text-sm mb-2">Brak zadań synchronizacji spełniających kryteria filtrów</p>

                            @if($activeFiltersCount > 0)
                                <button wire:click="resetSyncJobFilters" class="mt-2 text-sm text-[#e0ac7e] hover:underline">
                                    Wyczyść filtry
                                </button>
                            @endif
                        </div>
                    @endforelse
                </div>

                {{-- NEW: PAGINATION (BUG #9 FIX #7) --}}
                @if($recentSyncJobs->hasPages())
                    <div class="mt-4 border-t border-gray-600 pt-4">
                        {{ $recentSyncJobs->links() }}
                    </div>
                @endif
            </div>
        </div>

        {{-- ERP Sync Configuration Card (FAZA 5) - Przeniesione pod sekcje PrestaShop --}}
        {{-- UWAGA: Ta sekcja jest duplikatem - glowna sekcja ERP jest teraz wyzej, pod Konfiguracja Synchronizacji --}}

        <!-- FAZA 9 Phase 2: Queue Infrastructure Panel -->
        <div class="mt-8 relative backdrop-blur-xl shadow-lg rounded-lg border"
             style="background: linear-gradient(135deg, rgba(31, 41, 55, 0.8), rgba(17, 24, 39, 0.8)); border: 1px solid rgba(224, 172, 126, 0.2);"
             wire:poll.5s>

            <div class="px-6 py-4 border-b border-gray-600">
                <h3 class="text-lg font-semibold text-white flex items-center">
                    <svg class="w-5 h-5 text-[#e0ac7e] mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                    </svg>
                    Queue Infrastructure
                    <span class="ml-2 text-xs text-gray-400">(auto-refresh co 5s)</span>
                </h3>
            </div>

            <div class="p-6">
                <!-- Failed Jobs Table -->
                <div class="mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="text-md font-semibold text-white flex items-center">
                            <span class="text-red-400 mr-2">❌</span>
                            Failed Jobs
                            <span class="ml-2 px-2 py-0.5 bg-red-900 bg-opacity-40 text-red-300 text-xs rounded-full">
                                {{ $this->failedJobs->count() }}
                            </span>
                        </h4>
                    </div>

                    @if($this->failedJobs->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-800 bg-opacity-60 border-b border-gray-600">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase">Job Name</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase">Queue</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase">Failed At</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase">Exception</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-300 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-600">
                                @foreach($this->failedJobs as $job)
                                <tr class="hover:bg-gray-800 hover:bg-opacity-30 transition-colors duration-200">
                                    <td class="px-4 py-3">
                                        <div class="text-sm font-medium text-white">{{ $job['job_name'] ?? 'Unknown' }}</div>
                                        @if(isset($job['data']['sku']))
                                            <div class="text-xs text-gray-400">SKU: {{ $job['data']['sku'] }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="text-xs font-mono text-gray-300">{{ $job['queue'] ?? 'default' }}</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="text-sm text-white">{{ \Carbon\Carbon::parse($job['failed_at'])->format('d.m.Y H:i') }}</div>
                                        <div class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($job['failed_at'])->diffForHumans() }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="text-xs text-red-300 truncate max-w-md" title="{{ $job['exception_message'] ?? 'Unknown error' }}">
                                            {{ Str::limit($job['exception_message'] ?? 'Unknown error', 80) }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex items-center justify-end space-x-2">
                                            <button wire:click="retryFailedJob('{{ $job['uuid'] }}')"
                                                    class="inline-flex items-center px-3 py-1 bg-green-600 bg-opacity-80 text-white rounded text-xs hover:bg-green-700 transition-colors">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                                </svg>
                                                Retry
                                            </button>
                                            <button wire:click="deleteFailedJob('{{ $job['uuid'] }}')"
                                                    class="inline-flex items-center px-3 py-1 bg-red-600 bg-opacity-80 text-white rounded text-xs hover:bg-red-700 transition-colors">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                                Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="text-center text-gray-400 py-8 bg-gray-800 bg-opacity-20 rounded-lg border border-gray-600">
                        <svg class="w-12 h-12 mx-auto mb-2 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p class="text-sm font-medium">Brak failed jobs</p>
                        <p class="text-xs text-gray-500 mt-1">Wszystkie joby wykonane pomyślnie</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

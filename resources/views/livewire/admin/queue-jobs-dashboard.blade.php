<div class="queue-jobs-dashboard">
    {{-- Flash Message --}}
    @if(session()->has('message'))
        <div class="flash-message flash-success">
            {{ session('message') }}
        </div>
    @endif

    {{-- Stats Cards Grid --}}
    <div class="stats-grid">
        <div class="stat-card stat-pending">
            <div class="stat-label">Oczekujące</div>
            <div class="stat-value">{{ $stats['pending'] }}</div>
        </div>

        <div class="stat-card stat-processing">
            <div class="stat-label">W trakcie</div>
            <div class="stat-value">{{ $stats['processing'] }}</div>
        </div>

        <div class="stat-card stat-failed">
            <div class="stat-label">Błędy</div>
            <div class="stat-value">{{ $stats['failed'] }}</div>
        </div>

        <div class="stat-card stat-stuck">
            <div class="stat-label">Utknięte</div>
            <div class="stat-value">{{ $stats['stuck'] }}</div>
        </div>
    </div>

    {{-- Filters Bar --}}
    <div class="filters">
        <button wire:click="$set('filter', 'all')"
                type="button"
                class="filter-btn {{ $filter === 'all' ? 'active' : '' }}">
            Wszystkie
        </button>

        <button wire:click="$set('filter', 'pending')"
                type="button"
                class="filter-btn {{ $filter === 'pending' ? 'active' : '' }}">
            Oczekujące
        </button>

        <button wire:click="$set('filter', 'processing')"
                type="button"
                class="filter-btn {{ $filter === 'processing' ? 'active' : '' }}">
            W trakcie
        </button>

        <button wire:click="$set('filter', 'failed')"
                type="button"
                class="filter-btn {{ $filter === 'failed' ? 'active' : '' }}">
            Błędy
        </button>

        <button wire:click="$set('filter', 'stuck')"
                type="button"
                class="filter-btn {{ $filter === 'stuck' ? 'active' : '' }}">
            Utknięte
        </button>
    </div>

    {{-- Bulk Actions (visible only for failed filter) --}}
    @if($filter === 'failed' && count($jobs) > 0)
        <div class="bulk-actions">
            <button wire:click="retryAllFailed"
                    wire:confirm="Czy na pewno chcesz ponowić wszystkie błędne joby?"
                    type="button"
                    class="btn-primary">
                Ponów wszystkie
            </button>

            <button wire:click="clearAllFailed"
                    wire:confirm="Czy na pewno chcesz usunąć wszystkie błędne joby? Ta operacja jest nieodwracalna!"
                    type="button"
                    class="btn-danger">
                Usuń wszystkie
            </button>
        </div>
    @endif

    {{-- Jobs Table with Real-Time Polling --}}
    <div class="jobs-table" wire:poll.5s>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Job Name</th>
                    <th>Queue</th>
                    <th>Status</th>
                    <th>Data</th>
                    <th>Attempts</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($jobs as $job)
                    <tr class="job-row job-status-{{ $job['status'] ?? 'unknown' }}" wire:key="job-{{ $job['id'] ?? $job['uuid'] ?? uniqid() }}">
                        <td class="job-id">
                            @if(isset($job['uuid']))
                                <span title="{{ $job['uuid'] }}">{{ substr($job['uuid'], 0, 8) }}...</span>
                            @else
                                {{ $job['id'] ?? '-' }}
                            @endif
                        </td>

                        <td class="job-name">{{ $job['job_name'] ?? 'Unknown' }}</td>

                        <td class="job-queue">{{ $job['queue'] ?? 'default' }}</td>

                        <td class="job-status">
                            <span class="status-badge status-{{ $job['status'] ?? 'unknown' }}">
                                @switch($job['status'] ?? 'unknown')
                                    @case('pending')
                                        Oczekujący
                                        @break
                                    @case('processing')
                                        W trakcie
                                        @break
                                    @case('failed')
                                        Błąd
                                        @break
                                    @default
                                        Nieznany
                                @endswitch
                            </span>
                        </td>

                        <td class="job-data">
                            @if(isset($job['data']['sku']))
                                <span class="data-badge">SKU: {{ $job['data']['sku'] }}</span>
                            @elseif(isset($job['data']['shop_name']))
                                <span class="data-badge">Shop: {{ $job['data']['shop_name'] }}</span>
                            @elseif(isset($job['data']['product_id']))
                                <span class="data-badge">Product #{{ $job['data']['product_id'] }}</span>
                            @elseif(isset($job['data']['shop_id']))
                                <span class="data-badge">Shop #{{ $job['data']['shop_id'] }}</span>
                            @else
                                <span class="data-empty">-</span>
                            @endif
                        </td>

                        <td class="job-attempts">{{ $job['attempts'] ?? 0 }}</td>

                        <td class="job-created">
                            @if(isset($job['created_at']))
                                <span title="{{ $job['created_at']->format('Y-m-d H:i:s') }}">
                                    {{ $job['created_at']->diffForHumans() }}
                                </span>
                            @elseif(isset($job['failed_at']))
                                <span title="{{ $job['failed_at'] }}">
                                    {{ \Carbon\Carbon::parse($job['failed_at'])->diffForHumans() }}
                                </span>
                            @else
                                -
                            @endif
                        </td>

                        <td class="job-actions">
                            @if(($job['status'] ?? '') === 'failed' || $filter === 'failed')
                                <button wire:click="retryJob('{{ $job['uuid'] }}')"
                                        type="button"
                                        class="btn-action btn-retry"
                                        title="Ponów job">
                                    Ponów
                                </button>

                                <button wire:click="deleteFailedJob('{{ $job['uuid'] }}')"
                                        wire:confirm="Czy na pewno chcesz usunąć ten job?"
                                        type="button"
                                        class="btn-action btn-delete"
                                        title="Usuń job">
                                    Usuń
                                </button>
                            @elseif(($job['status'] ?? '') === 'pending')
                                <button wire:click="cancelJob({{ $job['id'] }})"
                                        wire:confirm="Czy na pewno chcesz anulować ten job?"
                                        type="button"
                                        class="btn-action btn-cancel"
                                        title="Anuluj job">
                                    Anuluj
                                </button>
                            @elseif(($job['status'] ?? '') === 'stuck')
                                <button wire:click="cancelJob({{ $job['id'] }})"
                                        wire:confirm="Czy na pewno chcesz anulować ten utknięty job?"
                                        type="button"
                                        class="btn-action btn-cancel"
                                        title="Anuluj utknięty job">
                                    Anuluj
                                </button>
                            @else
                                <span class="no-actions">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="empty-state">
                            <div class="empty-icon">📭</div>
                            <div class="empty-text">Brak jobów do wyświetlenia</div>
                            @if($filter !== 'all')
                                <button wire:click="$set('filter', 'all')"
                                        type="button"
                                        class="empty-action">
                                    Pokaż wszystkie
                                </button>
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

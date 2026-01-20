{{-- Admin Bug Report List --}}
<div class="min-h-screen py-6 px-4 sm:px-6 lg:px-8">
    {{-- Header --}}
    <div class="mb-8">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-4">
                <div class="icon-chip">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-white">Zgloszenia</h1>
                    <p class="text-gray-400">Zarzadzanie zgloszeniami uzytkownikow</p>
                </div>
            </div>
            {{-- Link to Solutions Library --}}
            <a href="{{ route('admin.bug-reports.solutions') }}"
               class="btn-enterprise-secondary flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                </svg>
                Biblioteka rozwiazan
            </a>
        </div>

        {{-- Statistics Cards --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-4 mb-6">
            <div class="enterprise-card p-4 text-center cursor-pointer hover:border-gray-500/50"
                 wire:click="$set('statusFilter', '')">
                <p class="text-2xl font-bold text-white">{{ $this->stats['total'] }}</p>
                <p class="text-xs text-gray-400">Wszystkie</p>
            </div>
            <div class="enterprise-card p-4 text-center cursor-pointer hover:border-blue-500/50"
                 wire:click="$set('statusFilter', 'new')">
                <p class="text-2xl font-bold text-blue-400">{{ $this->stats['new'] }}</p>
                <p class="text-xs text-gray-400">Nowe</p>
            </div>
            <div class="enterprise-card p-4 text-center cursor-pointer hover:border-yellow-500/50"
                 wire:click="$set('statusFilter', 'in_progress')">
                <p class="text-2xl font-bold text-yellow-400">{{ $this->stats['in_progress'] }}</p>
                <p class="text-xs text-gray-400">W trakcie</p>
            </div>
            <div class="enterprise-card p-4 text-center cursor-pointer hover:border-purple-500/50"
                 wire:click="$set('statusFilter', 'waiting')">
                <p class="text-2xl font-bold text-purple-400">{{ $this->stats['waiting'] }}</p>
                <p class="text-xs text-gray-400">Oczekuje</p>
            </div>
            <div class="enterprise-card p-4 text-center cursor-pointer hover:border-green-500/50"
                 wire:click="$set('statusFilter', 'resolved')">
                <p class="text-2xl font-bold text-green-400">{{ $this->stats['resolved'] }}</p>
                <p class="text-xs text-gray-400">Rozwiazane</p>
            </div>
            <div class="enterprise-card p-4 text-center cursor-pointer hover:border-orange-500/50"
                 wire:click="$set('assignedToFilter', 0)">
                <p class="text-2xl font-bold text-orange-400">{{ $this->stats['unassigned'] }}</p>
                <p class="text-xs text-gray-400">Nieprzypisane</p>
            </div>
            <div class="enterprise-card p-4 text-center cursor-pointer hover:border-red-500/50"
                 wire:click="$set('severityFilter', 'critical')">
                <p class="text-2xl font-bold text-red-400">{{ $this->stats['critical'] }}</p>
                <p class="text-xs text-gray-400">Krytyczne</p>
            </div>
        </div>

        {{-- Filters --}}
        <div class="enterprise-card p-4 mb-6">
            <div class="flex flex-wrap gap-4 items-end">
                {{-- Search --}}
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-xs text-gray-400 mb-1">Szukaj</label>
                    <div class="relative">
                        <input type="text"
                               wire:model.live.debounce.300ms="search"
                               class="form-input-enterprise w-full pl-10"
                               placeholder="Tytul, opis lub ID...">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                </div>

                {{-- Status --}}
                <div>
                    <label class="block text-xs text-gray-400 mb-1">Status</label>
                    <select wire:model.live="statusFilter" class="form-input-enterprise">
                        <option value="">Wszystkie</option>
                        @foreach($statuses as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Type --}}
                <div>
                    <label class="block text-xs text-gray-400 mb-1">Typ</label>
                    <select wire:model.live="typeFilter" class="form-input-enterprise">
                        <option value="">Wszystkie</option>
                        @foreach($types as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Severity --}}
                <div>
                    <label class="block text-xs text-gray-400 mb-1">Priorytet</label>
                    <select wire:model.live="severityFilter" class="form-input-enterprise">
                        <option value="">Wszystkie</option>
                        @foreach($severities as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Assigned To --}}
                <div>
                    <label class="block text-xs text-gray-400 mb-1">Przypisane do</label>
                    <select wire:model.live="assignedToFilter" class="form-input-enterprise">
                        <option value="">Wszystkie</option>
                        <option value="0">Nieprzypisane</option>
                        @foreach($this->assignees as $assignee)
                            <option value="{{ $assignee->id }}">{{ $assignee->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Clear Filters --}}
                @if($search || $statusFilter || $typeFilter || $severityFilter || $assignedToFilter !== null)
                    <button wire:click="clearFilters" class="text-sm text-gray-400 hover:text-white transition-colors">
                        Wyczysc filtry
                    </button>
                @endif
            </div>
        </div>

        {{-- Bulk Actions --}}
        @if(count($selectedReports) > 0)
            <div class="enterprise-card p-4 mb-6 flex items-center gap-4">
                <span class="text-sm text-gray-400">
                    Zaznaczono: <strong class="text-white">{{ count($selectedReports) }}</strong>
                </span>
                <div class="flex gap-2">
                    <select wire:change="bulkChangeStatus($event.target.value)" class="form-input-enterprise text-sm">
                        <option value="">Zmien status...</option>
                        @foreach($statuses as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <select wire:change="bulkAssign($event.target.value ? $event.target.value : null)" class="form-input-enterprise text-sm">
                        <option value="">Przypisz do...</option>
                        <option value="">Usun przypisanie</option>
                        @foreach($this->assignees as $assignee)
                            <option value="{{ $assignee->id }}">{{ $assignee->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button wire:click="$set('selectedReports', []); $set('selectAll', false)"
                        class="text-sm text-gray-400 hover:text-white">
                    Odznacz wszystkie
                </button>
            </div>
        @endif
    </div>

    {{-- Main Content --}}
    <div class="grid grid-cols-1 {{ $previewReportId ? 'lg:grid-cols-3' : '' }} gap-6">
        {{-- Reports Table --}}
        <div class="{{ $previewReportId ? 'lg:col-span-2' : '' }}">
            <div class="enterprise-card overflow-hidden">
                <table class="min-w-full divide-y divide-gray-700/50">
                    <thead class="bg-gray-800/50">
                        <tr>
                            <th class="px-4 py-3 text-left">
                                <input type="checkbox"
                                       wire:model.live="selectAll"
                                       class="checkbox-enterprise">
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">
                                Zgloszenie
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">
                                Typ
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">
                                Priorytet
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">
                                Status
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">
                                Przypisane
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">
                                Data
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-400 uppercase tracking-wider">
                                Akcje
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700/50">
                        @forelse($reports as $report)
                            <tr wire:key="report-{{ $report->id }}"
                                class="hover:bg-gray-800/30 transition-colors cursor-pointer {{ $previewReportId === $report->id ? 'bg-gray-800/50' : '' }}"
                                wire:click="preview({{ $report->id }})">
                                <td class="px-4 py-3" wire:click.stop>
                                    <input type="checkbox"
                                           wire:model.live="selectedReports"
                                           value="{{ $report->id }}"
                                           class="checkbox-enterprise">
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-start gap-3">
                                        <div class="flex-1 min-w-0">
                                            <p class="font-medium text-white truncate max-w-xs">
                                                {{ $report->title }}
                                            </p>
                                            <p class="text-xs text-gray-500 mt-0.5">
                                                #{{ $report->id }} &middot; {{ $report->reporter?->name ?? 'Unknown' }}
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="{{ $report->type_badge }}">
                                        <i class="{{ $report->type_icon }}"></i>
                                        {{ $report->type_label }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="{{ $report->severity_badge }}">
                                        <i class="{{ $report->severity_icon }}"></i>
                                        {{ $report->severity_label }}
                                    </span>
                                </td>
                                <td class="px-4 py-3" wire:click.stop>
                                    <select wire:change="quickStatusChange({{ $report->id }}, $event.target.value)"
                                            class="bugreport-status-select {{ $report->status_badge }}">
                                        @foreach($statuses as $value => $label)
                                            <option value="{{ $value }}" {{ $report->status === $value ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-4 py-3">
                                    @if($report->assignee)
                                        <span class="text-sm text-white">{{ $report->assignee->name }}</span>
                                    @else
                                        <span class="text-sm text-gray-500">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <p class="text-sm text-gray-400">{{ $report->created_at->format('d.m.Y') }}</p>
                                    <p class="text-xs text-gray-500">{{ $report->created_at->format('H:i') }}</p>
                                </td>
                                <td class="px-4 py-3 text-right" wire:click.stop>
                                    <button wire:click="viewReport({{ $report->id }})"
                                            class="text-gray-400 hover:text-white transition-colors"
                                            title="Zobacz szczegoly">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-12 text-center">
                                    <svg class="w-12 h-12 mx-auto text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                    </svg>
                                    <p class="text-gray-400">Brak zgloszen do wyswietlenia</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($reports->hasPages())
                <div class="mt-4">
                    {{ $reports->links() }}
                </div>
            @endif
        </div>

        {{-- Preview Panel --}}
        @if($this->previewReport)
            <div class="lg:col-span-1">
                <div class="enterprise-card p-6 sticky top-6">
                    <div class="flex items-start justify-between mb-4">
                        <span class="badge-enterprise px-2 py-1 text-xs rounded-full {{ $this->previewReport->type_badge }}">
                            {{ $this->previewReport->type_label }}
                        </span>
                        <button wire:click="closePreview" class="text-gray-400 hover:text-white">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <h3 class="text-lg font-semibold text-white mb-2">{{ $this->previewReport->title }}</h3>

                    <div class="flex gap-2 mb-4">
                        <span class="badge-enterprise px-2 py-1 text-xs rounded-full {{ $this->previewReport->status_badge }}">
                            {{ $this->previewReport->status_label }}
                        </span>
                        <span class="badge-enterprise px-2 py-1 text-xs rounded-full {{ $this->previewReport->severity_badge }}">
                            {{ $this->previewReport->severity_label }}
                        </span>
                    </div>

                    <p class="text-sm text-gray-400 mb-4 line-clamp-4">{{ $this->previewReport->description }}</p>

                    <div class="text-xs text-gray-500 mb-4 space-y-1">
                        <p>Zgloszono przez: <span class="text-white">{{ $this->previewReport->reporter?->name }}</span></p>
                        <p>Data: {{ $this->previewReport->created_at->format('d.m.Y H:i') }}</p>
                        @if($this->previewReport->context_url)
                            <p>URL: <span class="text-blue-400 truncate block">{{ Str::limit($this->previewReport->context_url, 40) }}</span></p>
                        @endif
                    </div>

                    <button wire:click="viewReport({{ $this->previewReport->id }})"
                            class="btn-enterprise-primary w-full">
                        Zobacz szczegoly
                    </button>
                </div>
            </div>
        @endif
    </div>
</div>

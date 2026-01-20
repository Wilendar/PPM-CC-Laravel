<div class="min-h-screen">
    {{-- Header --}}
    <div class="page-header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="page-title flex items-center gap-3">
                    <div class="icon-chip">
                        <i class="fas fa-book-open"></i>
                    </div>
                    Biblioteka Rozwiazan
                </h1>
                <p class="text-gray-400 mt-1">Baza wiedzy z rozwiazaniami zgloszonych problemow</p>
            </div>

            <div class="flex items-center gap-3">
                @if($this->stats['total_solutions'] > 0)
                    <button wire:click="exportAllToZip"
                            class="btn-enterprise-secondary flex items-center gap-2"
                            wire:loading.attr="disabled"
                            wire:target="exportAllToZip">
                        <i class="fas fa-file-archive" wire:loading.remove wire:target="exportAllToZip"></i>
                        <i class="fas fa-spinner fa-spin" wire:loading wire:target="exportAllToZip"></i>
                        Pobierz wszystkie (.zip)
                    </button>
                @endif
                <a href="{{ route('admin.bug-reports.index') }}"
                   class="btn-enterprise-secondary flex items-center gap-2">
                    <i class="fas fa-arrow-left"></i>
                    Powrot do zgloszen
                </a>
            </div>
        </div>
    </div>

    {{-- Statistics Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="enterprise-card p-4">
            <div class="flex items-center gap-3">
                <div class="icon-chip icon-chip-sm bg-green-500/15">
                    <i class="fas fa-check-circle text-green-400"></i>
                </div>
                <div>
                    <div class="text-2xl font-bold text-white">{{ $this->stats['total_solutions'] }}</div>
                    <div class="text-xs text-gray-400">Wszystkich rozwiazan</div>
                </div>
            </div>
        </div>

        <div class="enterprise-card p-4">
            <div class="flex items-center gap-3">
                <div class="icon-chip icon-chip-sm bg-blue-500/15">
                    <i class="fas fa-calendar text-blue-400"></i>
                </div>
                <div>
                    <div class="text-2xl font-bold text-white">{{ $this->stats['this_month'] }}</div>
                    <div class="text-xs text-gray-400">W tym miesiacu</div>
                </div>
            </div>
        </div>

        <div class="enterprise-card p-4">
            <div class="flex items-center gap-3">
                <div class="icon-chip icon-chip-sm bg-red-500/15">
                    <i class="fas fa-bug text-red-400"></i>
                </div>
                <div>
                    <div class="text-2xl font-bold text-white">{{ $this->stats['by_type']['bug'] }}</div>
                    <div class="text-xs text-gray-400">Naprawionych bledow</div>
                </div>
            </div>
        </div>

        <div class="enterprise-card p-4">
            <div class="flex items-center gap-3">
                <div class="icon-chip icon-chip-sm bg-purple-500/15">
                    <i class="fas fa-lightbulb text-purple-400"></i>
                </div>
                <div>
                    <div class="text-2xl font-bold text-white">{{ $this->stats['by_type']['feature'] + $this->stats['by_type']['improvement'] }}</div>
                    <div class="text-xs text-gray-400">Nowych funkcji</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="enterprise-card p-4 mb-6">
        <div class="flex flex-wrap items-center gap-4">
            {{-- Search --}}
            <div class="flex-1 min-w-[250px]">
                <div class="relative">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-500"></i>
                    <input type="text"
                           wire:model.live.debounce.300ms="search"
                           placeholder="Szukaj w rozwiazaniach..."
                           class="form-input-enterprise w-full pl-10">
                </div>
            </div>

            {{-- Type Filter --}}
            <div class="w-40">
                <select wire:model.live="typeFilter" class="form-input-enterprise w-full">
                    <option value="">Wszystkie typy</option>
                    @foreach($types as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Severity Filter --}}
            <div class="w-40">
                <select wire:model.live="severityFilter" class="form-input-enterprise w-full">
                    <option value="">Wszystkie priorytety</option>
                    @foreach($severities as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Clear Filters --}}
            @if($search || $typeFilter || $severityFilter)
                <button wire:click="clearFilters"
                        class="btn-enterprise-sm text-gray-400 hover:text-white">
                    <i class="fas fa-times mr-1"></i>
                    Wyczysc
                </button>
            @endif
        </div>
    </div>

    {{-- Solutions Table --}}
    @if($solutions->count() > 0)
        <div class="enterprise-card overflow-hidden mb-6">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-700/50">
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">ID</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Tytul</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Typ</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Priorytet</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Rozwiazano</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-400 uppercase tracking-wider">Akcje</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700/30">
                    @foreach($solutions as $solution)
                        <tr class="hover:bg-gray-800/30 transition-colors">
                            {{-- ID --}}
                            <td class="px-4 py-3">
                                <span class="text-sm text-gray-500 font-mono">#{{ $solution->id }}</span>
                            </td>

                            {{-- Title + Resolution preview --}}
                            <td class="px-4 py-3">
                                <div class="max-w-md">
                                    <div class="text-sm font-medium text-white truncate">
                                        {{ $solution->title }}
                                    </div>
                                    @if($solution->resolution)
                                        <div class="text-xs text-gray-500 truncate mt-1">
                                            <i class="fas fa-check-circle text-green-500 mr-1"></i>
                                            {{ Str::limit(strip_tags($solution->resolution), 80) }}
                                        </div>
                                    @endif
                                </div>
                            </td>

                            {{-- Type --}}
                            <td class="px-4 py-3">
                                <span class="{{ $solution->type_badge }}">
                                    <i class="{{ $solution->type_icon }}"></i>
                                    {{ $solution->type_label }}
                                </span>
                            </td>

                            {{-- Severity --}}
                            <td class="px-4 py-3">
                                <span class="{{ $solution->severity_badge }}">
                                    <i class="{{ $solution->severity_icon }}"></i>
                                    {{ $solution->severity_label }}
                                </span>
                            </td>

                            {{-- Resolved Date --}}
                            <td class="px-4 py-3">
                                <div class="text-sm text-gray-400">
                                    {{ $solution->resolved_at?->format('d.m.Y') ?? '-' }}
                                </div>
                                @if($solution->assignee)
                                    <div class="text-xs text-gray-500">
                                        {{ $solution->assignee->name }}
                                    </div>
                                @endif
                            </td>

                            {{-- Actions --}}
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button wire:click="viewReport({{ $solution->id }})"
                                            class="btn-enterprise-sm btn-enterprise-primary"
                                            title="Zobacz szczegoly">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button wire:click="exportToMarkdown({{ $solution->id }})"
                                            class="btn-enterprise-sm btn-enterprise-secondary"
                                            title="Pobierz .md">
                                        <i class="fas fa-file-export"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="enterprise-card p-4">
            {{ $solutions->links() }}
        </div>
    @else
        {{-- Empty State --}}
        <div class="enterprise-card p-12 text-center">
            <div class="icon-chip mx-auto mb-4">
                <i class="fas fa-folder-open text-gray-600"></i>
            </div>
            <h3 class="text-lg font-medium text-white mb-2">Brak rozwiazan</h3>
            <p class="text-gray-400 mb-4">
                @if($search || $typeFilter || $severityFilter)
                    Nie znaleziono rozwiazan pasujacych do kryteriow wyszukiwania.
                @else
                    Nie ma jeszcze zadnych rozwiazanych zgloszen w bibliotece.
                @endif
            </p>
            @if($search || $typeFilter || $severityFilter)
                <button wire:click="clearFilters" class="btn-enterprise-secondary">
                    <i class="fas fa-times mr-2"></i>
                    Wyczysc filtry
                </button>
            @endif
        </div>
    @endif
</div>

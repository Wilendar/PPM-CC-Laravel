{{-- User Bug Reports - Personal History --}}
<div class="min-h-screen py-6 px-4 sm:px-6 lg:px-8">
    {{-- Header --}}
    <div class="mb-8">
        <div class="flex items-center gap-4 mb-4">
            <div class="icon-chip">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-white">Moje Zgloszenia</h1>
                <p class="text-gray-400">Historia Twoich zgloszen i prosb o wsparcie</p>
            </div>
        </div>

        {{-- Status Summary Cards --}}
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-4 mb-6">
            <div class="enterprise-card p-4 text-center">
                <p class="text-2xl font-bold text-white">{{ $this->statusCounts['all'] }}</p>
                <p class="text-sm text-gray-400">Wszystkie</p>
            </div>
            <div class="enterprise-card p-4 text-center cursor-pointer hover:border-blue-500/50 transition-colors"
                 wire:click="$set('statusFilter', 'new')">
                <p class="text-2xl font-bold text-blue-400">{{ $this->statusCounts['new'] }}</p>
                <p class="text-sm text-gray-400">Nowe</p>
            </div>
            <div class="enterprise-card p-4 text-center cursor-pointer hover:border-yellow-500/50 transition-colors"
                 wire:click="$set('statusFilter', 'in_progress')">
                <p class="text-2xl font-bold text-yellow-400">{{ $this->statusCounts['in_progress'] }}</p>
                <p class="text-sm text-gray-400">W trakcie</p>
            </div>
            <div class="enterprise-card p-4 text-center cursor-pointer hover:border-green-500/50 transition-colors"
                 wire:click="$set('statusFilter', 'resolved')">
                <p class="text-2xl font-bold text-green-400">{{ $this->statusCounts['resolved'] }}</p>
                <p class="text-sm text-gray-400">Rozwiazane</p>
            </div>
            <div class="enterprise-card p-4 text-center cursor-pointer hover:border-gray-500/50 transition-colors"
                 wire:click="$set('statusFilter', 'closed')">
                <p class="text-2xl font-bold text-gray-400">{{ $this->statusCounts['closed'] }}</p>
                <p class="text-sm text-gray-400">Zamkniete</p>
            </div>
        </div>

        {{-- Filters --}}
        <div class="flex flex-wrap gap-4 items-center">
            <div>
                <select wire:model.live="statusFilter" class="form-input-enterprise">
                    <option value="">Wszystkie statusy</option>
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <select wire:model.live="typeFilter" class="form-input-enterprise">
                    <option value="">Wszystkie typy</option>
                    @foreach($types as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            @if($statusFilter || $typeFilter)
                <button wire:click="$set('statusFilter', ''); $set('typeFilter', '')"
                        class="text-sm text-gray-400 hover:text-white transition-colors">
                    Wyczysc filtry
                </button>
            @endif

            <div class="ml-auto">
                <button type="button"
                        x-data
                        @click="$dispatch('open-bug-report-modal')"
                        class="btn-enterprise-primary">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Nowe zgloszenie
                </button>
            </div>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Reports List --}}
        <div class="lg:col-span-2 space-y-4">
            @forelse($reports as $report)
                <div wire:key="report-{{ $report->id }}"
                     wire:click="viewReport({{ $report->id }})"
                     class="enterprise-card p-4 cursor-pointer transition-all duration-200 hover:scale-[1.01] {{ $selectedReportId === $report->id ? 'ring-2 ring-offset-2 ring-offset-gray-900' : '' }}"
                     style="{{ $selectedReportId === $report->id ? 'ring-color: var(--mpp-primary);' : '' }}">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1 min-w-0">
                            {{-- Title & Type --}}
                            <div class="flex items-center gap-2 mb-2">
                                <i class="{{ $report->type_icon }} text-gray-400"></i>
                                <h3 class="font-semibold text-white truncate">{{ $report->title }}</h3>
                            </div>

                            {{-- Description Preview --}}
                            <p class="text-sm text-gray-400 line-clamp-2 mb-3">
                                {{ Str::limit($report->description, 150) }}
                            </p>

                            {{-- Meta --}}
                            <div class="flex items-center gap-4 text-xs text-gray-500">
                                <span>{{ $report->created_at->format('d.m.Y H:i') }}</span>
                                <span>#{{ $report->id }}</span>
                            </div>
                        </div>

                        {{-- Badges --}}
                        <div class="flex flex-col items-end gap-2">
                            <span class="badge-enterprise px-2 py-1 text-xs rounded-full {{ $report->status_badge }}">
                                {{ $report->status_label }}
                            </span>
                            <span class="badge-enterprise px-2 py-1 text-xs rounded-full {{ $report->severity_badge }}">
                                <i class="{{ $report->severity_icon }} mr-1"></i>
                                {{ $report->severity_label }}
                            </span>
                        </div>
                    </div>
                </div>
            @empty
                <div class="enterprise-card p-12 text-center">
                    <svg class="w-16 h-16 mx-auto text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    <h3 class="text-lg font-medium text-white mb-2">Brak zgloszen</h3>
                    <p class="text-gray-400 mb-4">Nie masz jeszcze zadnych zgloszen.</p>
                    <button type="button"
                            x-data
                            @click="$dispatch('open-bug-report-modal')"
                            class="btn-enterprise-primary">
                        Utworz pierwsze zgloszenie
                    </button>
                </div>
            @endforelse

            {{-- Pagination --}}
            @if($reports->hasPages())
                <div class="mt-6">
                    {{ $reports->links() }}
                </div>
            @endif
        </div>

        {{-- Details Panel --}}
        <div class="lg:col-span-1">
            @if($this->selectedReport)
                <div class="enterprise-card p-6 sticky top-6">
                    {{-- Header --}}
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <span class="badge-enterprise px-2 py-1 text-xs rounded-full {{ $this->selectedReport->type_badge }}">
                                {{ $this->selectedReport->type_label }}
                            </span>
                            <h3 class="text-lg font-semibold text-white mt-2">
                                {{ $this->selectedReport->title }}
                            </h3>
                        </div>
                        <button wire:click="closeDetails" class="text-gray-400 hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    {{-- Status & Severity --}}
                    <div class="flex gap-2 mb-4">
                        <span class="badge-enterprise px-2 py-1 text-xs rounded-full {{ $this->selectedReport->status_badge }}">
                            {{ $this->selectedReport->status_label }}
                        </span>
                        <span class="badge-enterprise px-2 py-1 text-xs rounded-full {{ $this->selectedReport->severity_badge }}">
                            {{ $this->selectedReport->severity_label }}
                        </span>
                    </div>

                    {{-- Description --}}
                    <div class="mb-4">
                        <h4 class="text-sm font-medium text-gray-300 mb-2">Opis</h4>
                        <p class="text-sm text-gray-400 whitespace-pre-wrap">{{ $this->selectedReport->description }}</p>
                    </div>

                    {{-- Steps to Reproduce --}}
                    @if($this->selectedReport->steps_to_reproduce)
                        <div class="mb-4">
                            <h4 class="text-sm font-medium text-gray-300 mb-2">Kroki do odtworzenia</h4>
                            <p class="text-sm text-gray-400 whitespace-pre-wrap">{{ $this->selectedReport->steps_to_reproduce }}</p>
                        </div>
                    @endif

                    {{-- Screenshot --}}
                    @if($this->selectedReport->screenshot_path)
                        <div class="mb-4">
                            <h4 class="text-sm font-medium text-gray-300 mb-2">Zrzut ekranu</h4>
                            <img src="{{ Storage::url($this->selectedReport->screenshot_path) }}"
                                 alt="Screenshot"
                                 class="rounded-lg border border-gray-700 max-h-48 w-auto">
                        </div>
                    @endif

                    {{-- Resolution --}}
                    @if($this->selectedReport->resolution)
                        <div class="mb-4 p-3 rounded-lg bg-green-500/10 border border-green-500/30">
                            <h4 class="text-sm font-medium text-green-400 mb-1">Rozwiazanie</h4>
                            <p class="text-sm text-gray-300">{{ $this->selectedReport->resolution }}</p>
                        </div>
                    @endif

                    {{-- Meta --}}
                    <div class="mb-4 text-xs text-gray-500 space-y-1">
                        <p>Utworzono: {{ $this->selectedReport->created_at->format('d.m.Y H:i') }}</p>
                        @if($this->selectedReport->resolved_at)
                            <p>Rozwiazano: {{ $this->selectedReport->resolved_at->format('d.m.Y H:i') }}</p>
                        @endif
                    </div>

                    {{-- Comments --}}
                    <div class="border-t border-gray-700/50 pt-4">
                        <h4 class="text-sm font-medium text-gray-300 mb-3">Komentarze</h4>

                        @if($this->selectedReport->publicComments->count() > 0)
                            <div class="space-y-3 mb-4 max-h-48 overflow-y-auto">
                                @foreach($this->selectedReport->publicComments as $comment)
                                    <div class="bg-gray-800/50 rounded-lg p-3">
                                        <div class="flex items-center gap-2 mb-1">
                                            <span class="text-sm font-medium text-white">{{ $comment->user->name }}</span>
                                            <span class="text-xs text-gray-500">{{ $comment->created_at->diffForHumans() }}</span>
                                        </div>
                                        <p class="text-sm text-gray-400">{{ $comment->content }}</p>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-gray-500 mb-4">Brak komentarzy</p>
                        @endif

                        {{-- Add Comment --}}
                        @if($this->selectedReport->isOpen())
                            <form wire:submit="addComment">
                                <textarea wire:model="newComment"
                                          rows="2"
                                          class="form-input-enterprise w-full mb-2 text-sm"
                                          placeholder="Dodaj komentarz..."></textarea>
                                @error('newComment')
                                    <p class="text-xs text-red-400 mb-2">{{ $message }}</p>
                                @enderror
                                <button type="submit" class="btn-enterprise-primary btn-enterprise-sm w-full">
                                    Dodaj komentarz
                                </button>
                            </form>
                        @else
                            <p class="text-sm text-gray-500 italic">Zgloszenie zamkniete - nie mozna dodac komentarzy</p>
                        @endif
                    </div>
                </div>
            @else
                <div class="enterprise-card p-6 text-center">
                    <svg class="w-12 h-12 mx-auto text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                    <p class="text-gray-400">Wybierz zgloszenie z listy, aby zobaczyc szczegoly</p>
                </div>
            @endif
        </div>
    </div>
</div>

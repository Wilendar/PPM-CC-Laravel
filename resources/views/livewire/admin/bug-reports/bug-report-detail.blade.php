{{-- Bug Report Detail - Admin Management --}}
<div class="min-h-screen py-6 px-4 sm:px-6 lg:px-8">
    {{-- Header --}}
    <div class="mb-6">
        <div class="flex items-center gap-4 mb-4">
            <button wire:click="backToList" class="btn-enterprise-secondary btn-enterprise-sm">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Powrot
            </button>
            <div class="icon-chip">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-white">Zgloszenie #{{ $report->id }}</h1>
                <p class="text-gray-400">{{ $report->created_at->format('d.m.Y H:i') }}</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Content --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Report Details --}}
            <div class="enterprise-card p-6">
                <div class="flex items-start justify-between mb-4">
                    <h2 class="text-xl font-semibold text-white">{{ $report->title }}</h2>
                    <div class="flex gap-2">
                        <span class="badge-enterprise px-3 py-1 text-sm rounded-full {{ $report->type_badge }}">
                            <i class="{{ $report->type_icon }} mr-1"></i>
                            {{ $report->type_label }}
                        </span>
                        <span class="badge-enterprise px-3 py-1 text-sm rounded-full {{ $report->severity_badge }}">
                            <i class="{{ $report->severity_icon }} mr-1"></i>
                            {{ $report->severity_label }}
                        </span>
                        <span class="badge-enterprise px-3 py-1 text-sm rounded-full {{ $report->status_badge }}">
                            {{ $report->status_label }}
                        </span>
                    </div>
                </div>

                {{-- Reporter --}}
                <div class="mb-4 pb-4 border-b border-gray-700/50">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-gray-700 flex items-center justify-center">
                            <span class="text-sm font-medium text-white">
                                {{ substr($report->reporter->name ?? 'U', 0, 1) }}
                            </span>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-white">{{ $report->reporter->name ?? 'Nieznany' }}</p>
                            <p class="text-xs text-gray-400">{{ $report->reporter->email ?? '' }}</p>
                        </div>
                    </div>
                </div>

                {{-- Description --}}
                <div class="mb-6">
                    <h3 class="text-sm font-medium text-gray-300 mb-2">Opis</h3>
                    <div class="bg-gray-800/50 rounded-lg p-4">
                        <p class="text-gray-300 whitespace-pre-wrap">{{ $report->description }}</p>
                    </div>
                </div>

                {{-- Steps to Reproduce --}}
                @if($report->steps_to_reproduce)
                    <div class="mb-6">
                        <h3 class="text-sm font-medium text-gray-300 mb-2">Kroki do odtworzenia</h3>
                        <div class="bg-gray-800/50 rounded-lg p-4">
                            <p class="text-gray-300 whitespace-pre-wrap">{{ $report->steps_to_reproduce }}</p>
                        </div>
                    </div>
                @endif

                {{-- Screenshot --}}
                @if($report->screenshot_path)
                    <div class="mb-6">
                        <h3 class="text-sm font-medium text-gray-300 mb-2">Zrzut ekranu</h3>
                        <div class="bg-gray-800/50 rounded-lg p-2">
                            <img src="{{ Storage::url($report->screenshot_path) }}"
                                 alt="Screenshot"
                                 class="rounded-lg max-h-96 w-auto cursor-pointer hover:opacity-90 transition-opacity"
                                 onclick="window.open(this.src, '_blank')">
                        </div>
                    </div>
                @endif

                {{-- Diagnostics --}}
                @if($report->context_url || $report->browser_info || $report->console_errors || $report->user_actions)
                    <div class="mb-6">
                        <h3 class="text-sm font-medium text-gray-300 mb-2">Diagnostyka</h3>
                        <div class="bg-gray-800/50 rounded-lg p-4 space-y-3">
                            @if($report->context_url)
                                <div>
                                    <span class="text-xs text-gray-500">URL:</span>
                                    <a href="{{ $report->context_url }}" target="_blank"
                                       class="text-sm text-blue-400 hover:text-blue-300 ml-2 break-all">
                                        {{ $report->context_url }}
                                    </a>
                                </div>
                            @endif
                            @if($report->browser_info)
                                <div>
                                    <span class="text-xs text-gray-500">Przegladarka:</span>
                                    <span class="text-sm text-gray-300 ml-2">{{ $report->browser_info }}</span>
                                </div>
                            @endif
                            @if($report->os_info)
                                <div>
                                    <span class="text-xs text-gray-500">System:</span>
                                    <span class="text-sm text-gray-300 ml-2">{{ $report->os_info }}</span>
                                </div>
                            @endif
                            @if($report->console_errors && count($report->console_errors) > 0)
                                <div>
                                    <span class="text-xs text-gray-500">Bledy konsoli ({{ count($report->console_errors) }}):</span>
                                    <div class="mt-2 space-y-1">
                                        @foreach($report->console_errors as $error)
                                            <div class="text-xs bg-red-500/10 border border-red-500/30 rounded p-2 text-red-300 font-mono break-all">
                                                {{ $error['message'] ?? $error }}
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                            @if($report->user_actions && count($report->user_actions) > 0)
                                <div>
                                    <span class="text-xs text-gray-500">Ostatnie akcje ({{ count($report->user_actions) }}):</span>
                                    <div class="mt-2 space-y-1">
                                        @foreach($report->user_actions as $action)
                                            <div class="text-xs bg-gray-700/50 rounded p-2 text-gray-400 font-mono">
                                                {{ $action['action'] ?? $action }}
                                                @if(isset($action['timestamp']))
                                                    <span class="text-gray-600 ml-2">
                                                        {{ \Carbon\Carbon::createFromTimestampMs($action['timestamp'])->format('H:i:s') }}
                                                    </span>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                {{-- Resolution --}}
                @if($report->resolution)
                    <div class="p-4 rounded-lg {{ $report->status === 'resolved' ? 'bg-green-500/10 border border-green-500/30' : 'bg-red-500/10 border border-red-500/30' }}">
                        <h3 class="text-sm font-medium {{ $report->status === 'resolved' ? 'text-green-400' : 'text-red-400' }} mb-2">
                            {{ $report->status === 'resolved' ? 'Rozwiazanie' : 'Powod odrzucenia' }}
                        </h3>
                        <p class="text-gray-300 whitespace-pre-wrap">{{ $report->resolution }}</p>
                        @if($report->resolved_at)
                            <p class="text-xs text-gray-500 mt-2">
                                {{ $report->resolved_at->format('d.m.Y H:i') }}
                            </p>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Comments Section --}}
            <div class="enterprise-card p-6">
                <h3 class="text-lg font-semibold text-white mb-4">Komentarze</h3>

                {{-- Comments List --}}
                <div class="space-y-4 mb-6">
                    @forelse($this->comments as $comment)
                        <div class="p-4 rounded-lg {{ $comment->is_internal ? 'bg-yellow-500/10 border border-yellow-500/30' : 'bg-gray-800/50' }}">
                            <div class="flex items-start justify-between mb-2">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-full bg-gray-700 flex items-center justify-center">
                                        <span class="text-xs font-medium text-white">
                                            {{ substr($comment->user->name ?? 'U', 0, 1) }}
                                        </span>
                                    </div>
                                    <div>
                                        <span class="text-sm font-medium text-white">{{ $comment->user->name ?? 'Nieznany' }}</span>
                                        @if($comment->is_internal)
                                            <span class="ml-2 px-2 py-0.5 text-xs rounded bg-yellow-500/20 text-yellow-400">
                                                Wewnetrzny
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                <span class="text-xs text-gray-500">{{ $comment->created_at->diffForHumans() }}</span>
                            </div>
                            <p class="text-sm text-gray-300 whitespace-pre-wrap">{{ $comment->content }}</p>
                        </div>
                    @empty
                        <p class="text-gray-500 text-center py-4">Brak komentarzy</p>
                    @endforelse
                </div>

                {{-- Add Comment Form --}}
                @if(!$report->isClosed())
                    <form wire:submit="addComment" class="space-y-3">
                        <div>
                            <textarea wire:model="newComment"
                                      rows="3"
                                      class="form-input-enterprise w-full"
                                      placeholder="Dodaj komentarz..."></textarea>
                            @error('newComment')
                                <p class="text-xs text-red-400 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="flex items-center justify-between">
                            <label class="flex items-center gap-2 text-sm text-gray-400 cursor-pointer">
                                <input type="checkbox"
                                       wire:model="isInternalComment"
                                       class="checkbox-enterprise">
                                <span>Komentarz wewnetrzny (tylko dla adminow)</span>
                            </label>
                            <button type="submit" class="btn-enterprise-primary btn-enterprise-sm">
                                Dodaj komentarz
                            </button>
                        </div>
                    </form>
                @else
                    <p class="text-sm text-gray-500 italic text-center">
                        Zgloszenie zamkniete - nie mozna dodac komentarzy
                    </p>
                @endif
            </div>
        </div>

        {{-- Sidebar - Management --}}
        <div class="space-y-6">
            {{-- Status Management --}}
            <div class="enterprise-card p-6">
                <h3 class="text-lg font-semibold text-white mb-4">Zarzadzanie</h3>

                {{-- Status Change --}}
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-300 mb-2">Status</label>
                    <div class="flex gap-2">
                        <select wire:model="newStatus" class="form-input-enterprise flex-1">
                            @foreach($statuses as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <button wire:click="updateStatus"
                                class="btn-enterprise-secondary btn-enterprise-sm"
                                @if($newStatus === $report->status) disabled @endif>
                            Zmien
                        </button>
                    </div>
                </div>

                {{-- Assignment --}}
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-300 mb-2">Przypisany do</label>
                    <div class="flex gap-2">
                        <select wire:model="assignedTo" class="form-input-enterprise flex-1">
                            <option value="">Nieprzypisany</option>
                            @foreach($this->assignees as $assignee)
                                <option value="{{ $assignee->id }}">{{ $assignee->name }}</option>
                            @endforeach
                        </select>
                        <button wire:click="updateAssignment"
                                class="btn-enterprise-secondary btn-enterprise-sm"
                                @if($assignedTo == $report->assigned_to) disabled @endif>
                            Zmien
                        </button>
                    </div>
                </div>

                {{-- Quick Actions --}}
                <div class="border-t border-gray-700/50 pt-4 mt-4 space-y-2">
                    @if(!$report->isClosed() && !$report->isResolved())
                        <button wire:click="$set('showResolutionForm', true)"
                                class="btn-enterprise-primary w-full">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Oznacz jako rozwiazane
                        </button>
                        <button wire:click="$set('showResolutionForm', true)"
                                class="btn-enterprise-danger w-full">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            Odrzuc zgloszenie
                        </button>
                    @endif

                    @if($report->isResolved() && !$report->isClosed())
                        <button wire:click="closeReport" class="btn-enterprise-secondary w-full">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8"></path>
                            </svg>
                            Zamknij zgloszenie
                        </button>
                    @endif

                    @if($report->isClosed())
                        <button wire:click="reopenReport" class="btn-enterprise-secondary w-full">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            Otworz ponownie
                        </button>
                    @endif
                </div>
            </div>

            {{-- Resolution Form Modal --}}
            @if($showResolutionForm)
                <div class="enterprise-card p-6 border-2 border-yellow-500/30">
                    <h3 class="text-lg font-semibold text-white mb-4">Rozwiazanie / Odrzucenie</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">
                                Opis rozwiazania / powod odrzucenia
                            </label>
                            <textarea wire:model="resolution"
                                      rows="4"
                                      class="form-input-enterprise w-full"
                                      placeholder="Opisz jak rozwiazano problem lub dlaczego zgloszenie zostalo odrzucone..."></textarea>
                            @error('resolution')
                                <p class="text-xs text-red-400 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="flex gap-2">
                            <button wire:click="markResolved" class="btn-enterprise-primary flex-1">
                                Rozwiazane
                            </button>
                            <button wire:click="markRejected" class="btn-enterprise-danger flex-1">
                                Odrzuc
                            </button>
                        </div>
                        <button wire:click="$set('showResolutionForm', false)"
                                class="btn-enterprise-secondary w-full">
                            Anuluj
                        </button>
                    </div>
                </div>
            @endif

            {{-- Report Meta --}}
            <div class="enterprise-card p-6">
                <h3 class="text-lg font-semibold text-white mb-4">Informacje</h3>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-400">ID:</dt>
                        <dd class="text-white font-mono">#{{ $report->id }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-400">Utworzono:</dt>
                        <dd class="text-white">{{ $report->created_at->format('d.m.Y H:i') }}</dd>
                    </div>
                    @if($report->resolved_at)
                        <div class="flex justify-between">
                            <dt class="text-gray-400">Rozwiazano:</dt>
                            <dd class="text-white">{{ $report->resolved_at->format('d.m.Y H:i') }}</dd>
                        </div>
                    @endif
                    @if($report->closed_at)
                        <div class="flex justify-between">
                            <dt class="text-gray-400">Zamknieto:</dt>
                            <dd class="text-white">{{ $report->closed_at->format('d.m.Y H:i') }}</dd>
                        </div>
                    @endif
                    @if($report->assignee)
                        <div class="flex justify-between">
                            <dt class="text-gray-400">Przypisany:</dt>
                            <dd class="text-white">{{ $report->assignee->name }}</dd>
                        </div>
                    @endif
                </dl>
            </div>
        </div>
    </div>
</div>

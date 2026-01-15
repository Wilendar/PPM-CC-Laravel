{{-- Template Modal --}}
<div
    class="fixed inset-0 z-50 flex items-center justify-center p-4"
    x-data="{ }"
    @keydown.escape.window="$wire.closeTemplateModal()"
>
    {{-- Backdrop --}}
    <div
        class="absolute inset-0 bg-black/50"
        wire:click="closeTemplateModal"
    ></div>

    {{-- Modal Content --}}
    <div class="relative w-full max-w-2xl bg-gray-800 rounded-xl shadow-2xl border border-gray-700">
        {{-- Header --}}
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-700">
            <h2 class="text-lg font-semibold text-gray-100">
                @if($templateModalMode === 'save')
                    Zapisz jako szablon
                @else
                    Zaladuj szablon
                @endif
            </h2>
            <button
                wire:click="closeTemplateModal"
                class="p-1 text-gray-400 hover:text-gray-200 transition"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Body --}}
        <div class="p-6">
            @if($templateModalMode === 'save')
                {{-- Save Template Form --}}
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Nazwa szablonu</label>
                        <input
                            type="text"
                            wire:model="newTemplateName"
                            class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-gray-100 focus:ring-2 focus:ring-blue-500"
                            placeholder="Np. Opis produktu motocyklowego"
                        >
                        @error('newTemplateName')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Opis (opcjonalny)</label>
                        <textarea
                            wire:model="newTemplateDescription"
                            rows="3"
                            class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-gray-100 focus:ring-2 focus:ring-blue-500"
                            placeholder="Krotki opis szablonu..."
                        ></textarea>
                    </div>

                    <div class="pt-4">
                        <button
                            wire:click="saveAsTemplate"
                            class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-lg transition"
                        >
                            Zapisz szablon
                        </button>
                    </div>
                </div>
            @else
                {{-- Load Template List --}}
                <div class="space-y-4">
                    {{-- Search --}}
                    <div>
                        <input
                            type="text"
                            wire:model.live.debounce.300ms="templateSearch"
                            class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-gray-100 focus:ring-2 focus:ring-blue-500"
                            placeholder="Szukaj szablonow..."
                        >
                    </div>

                    {{-- Template List --}}
                    <div class="max-h-96 overflow-y-auto space-y-2">
                        @forelse($this->templates as $template)
                            <div class="flex items-center justify-between p-3 bg-gray-900 border border-gray-700 rounded-lg hover:border-gray-600 transition group">
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-sm font-medium text-gray-200 truncate">{{ $template->name }}</h4>
                                    @if($template->description)
                                        <p class="text-xs text-gray-500 truncate">{{ $template->description }}</p>
                                    @endif
                                    <div class="flex items-center gap-2 mt-1 text-xs text-gray-500">
                                        <span>{{ count($template->blocks_json ?? []) }} blokow</span>
                                        <span>•</span>
                                        <span>{{ $template->created_at->diffForHumans() }}</span>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition">
                                    <button
                                        wire:click="loadTemplate({{ $template->id }})"
                                        class="px-3 py-1.5 text-xs bg-blue-600 hover:bg-blue-500 text-white rounded transition"
                                    >
                                        Zaladuj
                                    </button>
                                    @php
                                        $canDelete = $template->created_by === auth()->id() ||
                                            (auth()->user() && method_exists(auth()->user(), 'hasRole') && auth()->user()->hasRole('admin'));
                                    @endphp
                                    @if($canDelete)
                                        <button
                                            wire:click="deleteTemplate({{ $template->id }})"
                                            wire:confirm="Czy na pewno chcesz usunac ten szablon?"
                                            class="p-1.5 text-gray-400 hover:text-red-400 transition"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-8 text-gray-500">
                                <p>Brak dostepnych szablonow</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            @endif
        </div>

        {{-- Footer --}}
        <div class="flex items-center justify-between px-6 py-4 border-t border-gray-700">
            @if($templateModalMode === 'load')
                <button
                    wire:click="$set('templateModalMode', 'save')"
                    class="px-4 py-2 text-sm bg-gray-700 hover:bg-gray-600 text-gray-300 rounded-lg transition"
                >
                    Zapisz aktualny jako szablon
                </button>
            @else
                <button
                    wire:click="$set('templateModalMode', 'load')"
                    class="px-4 py-2 text-sm bg-gray-700 hover:bg-gray-600 text-gray-300 rounded-lg transition"
                >
                    Powrot do listy
                </button>
            @endif

            <button
                wire:click="closeTemplateModal"
                class="px-4 py-2 text-sm text-gray-400 hover:text-gray-200 transition"
            >
                Anuluj
            </button>
        </div>
    </div>
</div>

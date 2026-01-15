<div class="feature-browser">
    {{-- Header --}}
    <div class="feature-browser__header">
        <div class="flex items-center gap-3">
            <h3 class="text-lg font-semibold text-white">Szablony Cech</h3>
            <span class="feature-browser__badge">{{ $this->templates->count() }}</span>
        </div>
        <div class="flex items-center gap-2">
            {{-- Filter --}}
            <select wire:model.live="filter" class="bg-gray-700 border border-gray-600 rounded-lg px-3 py-1.5 text-sm text-gray-200">
                <option value="all">Wszystkie</option>
                <option value="predefined">Predefiniowane</option>
                <option value="custom">Wlasne</option>
            </select>
            {{-- Add Template Button --}}
            <button wire:click="openTemplateModal" class="btn-primary-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nowy szablon
            </button>
        </div>
    </div>

    {{-- 2-Column Layout --}}
    <div class="feature-library__columns">
        {{-- Left Column: Templates List --}}
        <div class="feature-browser__column">
            <div class="feature-browser__column-header">
                <span>Szablony</span>
                <span class="feature-browser__badge feature-browser__badge--small">{{ $this->templates->count() }}</span>
            </div>
            <div class="feature-browser__column-content">
                @forelse($this->templates as $template)
                    <button
                        wire:click="selectTemplate({{ $template['id'] }})"
                        class="feature-library__group-item {{ $selectedTemplateId === $template['id'] ? 'active' : '' }}"
                    >
                        <span class="text-lg mr-2">{{ $template['icon'] }}</span>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="truncate font-medium">{{ $template['name'] }}</span>
                                @if($template['is_predefined'])
                                    <span class="text-xs px-1.5 py-0.5 rounded bg-blue-500/20 text-blue-400">PRE</span>
                                @endif
                            </div>
                            <div class="text-xs text-gray-500 mt-0.5">
                                {{ $template['features_count'] }} cech
                            </div>
                        </div>
                        <span class="feature-browser__badge {{ $template['usage_count'] > 0 ? 'feature-browser__badge--active' : 'feature-browser__badge--zero' }}">
                            {{ $template['usage_count'] }}
                        </span>
                    </button>
                @empty
                    <div class="feature-browser__empty-state">
                        <svg class="w-12 h-12 mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <p class="text-sm">Brak szablonow</p>
                    </div>
                @endforelse
            </div>
            <div class="feature-browser__column-footer">
                <span class="text-xs">
                    {{ $this->templates->where('is_predefined', true)->count() }} predefiniowanych,
                    {{ $this->templates->where('is_predefined', false)->count() }} wlasnych
                </span>
            </div>
        </div>

        {{-- Right Column: Template Preview --}}
        <div class="feature-browser__column">
            <div class="feature-browser__column-header">
                <span>Podglad szablonu</span>
                @if($this->selectedTemplate)
                    <div class="flex items-center gap-1">
                        @if(!$this->selectedTemplate['is_predefined'])
                            <button
                                wire:click="editTemplate({{ $this->selectedTemplate['id'] }})"
                                class="p-1 hover:bg-gray-600 rounded transition-colors"
                                title="Edytuj"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </button>
                        @endif
                        <button
                            wire:click="duplicateTemplate({{ $this->selectedTemplate['id'] }})"
                            class="p-1 hover:bg-gray-600 rounded transition-colors"
                            title="Duplikuj"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                        </button>
                        <button
                            wire:click="openBulkAssignModal({{ $this->selectedTemplate['id'] }})"
                            class="p-1 hover:bg-gray-600 rounded transition-colors"
                            title="Przypisz do produktow"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                            </svg>
                        </button>
                        @if(!$this->selectedTemplate['is_predefined'])
                            <button
                                wire:click="deleteTemplate({{ $this->selectedTemplate['id'] }})"
                                wire:confirm="Na pewno usunac ten szablon?"
                                class="p-1 hover:bg-red-600/50 rounded transition-colors text-red-400"
                                title="Usun"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        @endif
                    </div>
                @endif
            </div>
            <div class="feature-browser__column-content">
                @if($this->selectedTemplate)
                    {{-- Template Info --}}
                    <div class="p-4 mb-4 bg-gray-800/50 rounded-lg border border-gray-700">
                        <div class="flex items-center gap-3 mb-3">
                            <span class="text-2xl">{{ $this->selectedTemplate['icon'] }}</span>
                            <div>
                                <h4 class="font-semibold text-white">{{ $this->selectedTemplate['name'] }}</h4>
                                <span class="text-xs text-gray-400">
                                    {{ $this->selectedTemplate['is_predefined'] ? 'Szablon predefiniowany' : 'Szablon wlasny' }}
                                    &bull; {{ $this->selectedTemplate['category'] }}
                                </span>
                            </div>
                        </div>
                        <div class="flex items-center gap-4 text-sm text-gray-400">
                            <span>{{ $this->selectedTemplate['features_count'] }} cech</span>
                            <span>{{ $this->selectedTemplate['usage_count'] }} uzyc</span>
                        </div>
                    </div>

                    {{-- Features List --}}
                    <div class="space-y-1">
                        <div class="feature-browser__section-label text-xs text-gray-500 uppercase tracking-wide">
                            Cechy w szablonie
                        </div>
                        @forelse($this->selectedTemplate['features'] as $index => $feature)
                            <div class="feature-library__feature-item">
                                <span class="w-6 h-6 flex items-center justify-center bg-gray-700 rounded text-xs text-gray-400">
                                    {{ $index + 1 }}
                                </span>
                                <div class="flex-1 min-w-0 ml-3">
                                    <div class="font-medium text-gray-200 truncate">
                                        {{ $feature['name'] ?? 'Bez nazwy' }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        {{ $feature['type'] ?? 'text' }}
                                        @if(!empty($feature['required']))
                                            &bull; wymagane
                                        @endif
                                        @if(!empty($feature['default']))
                                            &bull; domyslnie: {{ $feature['default'] }}
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="p-4 text-center text-gray-500 text-sm">
                                Szablon nie zawiera cech
                            </div>
                        @endforelse
                    </div>
                @else
                    <div class="feature-browser__empty-state">
                        <svg class="w-16 h-16 mb-4 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/>
                        </svg>
                        <p class="text-gray-400">Wybierz szablon z listy</p>
                        <p class="text-xs text-gray-500 mt-1">aby zobaczyc podglad</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Job Progress Bar --}}
    @if($activeJobProgressId && !empty($activeJobProgress))
        <div class="p-4 border-t border-gray-700 bg-gray-800/50">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm text-gray-300">
                    Przypisywanie szablonu...
                </span>
                <button wire:click="dismissProgress" class="text-gray-500 hover:text-gray-300">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="w-full bg-gray-700 rounded-full h-2">
                <div
                    class="h-2 rounded-full transition-all duration-300 {{ $activeJobProgress['status'] === 'completed' ? 'bg-green-500' : ($activeJobProgress['status'] === 'failed' ? 'bg-red-500' : 'bg-blue-500') }}"
                    style="width: {{ $activeJobProgress['percentage'] ?? 0 }}%"
                ></div>
            </div>
            <div class="flex items-center justify-between mt-1 text-xs text-gray-500">
                <span>{{ $activeJobProgress['current'] ?? 0 }} / {{ $activeJobProgress['total'] ?? 0 }}</span>
                <span>{{ $activeJobProgress['percentage'] ?? 0 }}%</span>
            </div>
        </div>
    @endif

    {{-- Template Editor Modal --}}
    @if($showTemplateModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div class="bg-gray-800 rounded-xl shadow-xl w-full max-w-2xl max-h-[80vh] overflow-hidden border border-gray-700">
                <div class="flex items-center justify-between p-4 border-b border-gray-700">
                    <h3 class="text-lg font-semibold text-white">
                        {{ $editingTemplateId ? 'Edytuj szablon' : 'Nowy szablon' }}
                    </h3>
                    <button wire:click="closeTemplateModal" class="text-gray-400 hover:text-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="p-4 overflow-y-auto max-h-[60vh]">
                    {{-- Template Name --}}
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-300 mb-1">Nazwa szablonu</label>
                        <input
                            type="text"
                            wire:model="templateName"
                            class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-gray-200 focus:border-amber-500 focus:ring-1 focus:ring-amber-500"
                            placeholder="np. Quad spalinowy 125cc"
                        >
                        @error('templateName')
                            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Features List --}}
                    <div class="mb-4">
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-sm font-medium text-gray-300">Cechy</label>
                            <button
                                wire:click="addFeatureRow"
                                type="button"
                                class="text-sm text-amber-400 hover:text-amber-300"
                            >
                                + Dodaj ceche
                            </button>
                        </div>
                        <div class="space-y-2">
                            @foreach($templateFeatures as $index => $feature)
                                <div class="flex items-start gap-2 p-3 bg-gray-700/50 rounded-lg">
                                    <div class="flex-1 grid grid-cols-2 gap-2">
                                        <input
                                            type="text"
                                            wire:model="templateFeatures.{{ $index }}.name"
                                            class="bg-gray-700 border border-gray-600 rounded px-2 py-1.5 text-sm text-gray-200"
                                            placeholder="Nazwa cechy"
                                        >
                                        <select
                                            wire:model="templateFeatures.{{ $index }}.type"
                                            class="bg-gray-700 border border-gray-600 rounded px-2 py-1.5 text-sm text-gray-200"
                                        >
                                            <option value="text">Tekst</option>
                                            <option value="number">Liczba</option>
                                            <option value="select">Wybor</option>
                                            <option value="boolean">Tak/Nie</option>
                                        </select>
                                        <input
                                            type="text"
                                            wire:model="templateFeatures.{{ $index }}.default"
                                            class="bg-gray-700 border border-gray-600 rounded px-2 py-1.5 text-sm text-gray-200"
                                            placeholder="Wartosc domyslna"
                                        >
                                        <label class="flex items-center gap-2 text-sm text-gray-300">
                                            <input
                                                type="checkbox"
                                                wire:model="templateFeatures.{{ $index }}.required"
                                                class="rounded bg-gray-700 border-gray-600"
                                            >
                                            Wymagane
                                        </label>
                                    </div>
                                    <button
                                        wire:click="removeFeature({{ $index }})"
                                        type="button"
                                        class="p-1 text-red-400 hover:text-red-300"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                        @error('templateFeatures')
                            <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 p-4 border-t border-gray-700">
                    <button
                        wire:click="closeTemplateModal"
                        type="button"
                        class="px-4 py-2 text-gray-400 hover:text-white transition-colors"
                    >
                        Anuluj
                    </button>
                    <button
                        wire:click="saveTemplate"
                        type="button"
                        class="btn-primary"
                    >
                        {{ $editingTemplateId ? 'Zapisz zmiany' : 'Utworz szablon' }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Bulk Assign Modal --}}
    @if($showBulkAssignModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div class="bg-gray-800 rounded-xl shadow-xl w-full max-w-lg overflow-hidden border border-gray-700">
                <div class="flex items-center justify-between p-4 border-b border-gray-700">
                    <h3 class="text-lg font-semibold text-white">Przypisz szablon do produktow</h3>
                    <button wire:click="closeBulkAssignModal" class="text-gray-400 hover:text-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="p-4">
                    {{-- Scope Selection --}}
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-300 mb-2">Zakres</label>
                        <div class="space-y-2">
                            <label class="flex items-center gap-2 p-3 bg-gray-700/50 rounded-lg cursor-pointer hover:bg-gray-700">
                                <input
                                    type="radio"
                                    wire:model.live="bulkAssignScope"
                                    value="all_vehicles"
                                    class="text-amber-500"
                                >
                                <span class="text-gray-200">Wszystkie pojazdy</span>
                            </label>
                            <label class="flex items-center gap-2 p-3 bg-gray-700/50 rounded-lg cursor-pointer hover:bg-gray-700">
                                <input
                                    type="radio"
                                    wire:model.live="bulkAssignScope"
                                    value="by_category"
                                    class="text-amber-500"
                                >
                                <span class="text-gray-200">Tylko kategoria</span>
                            </label>
                        </div>
                    </div>

                    {{-- Action Selection --}}
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-300 mb-2">Akcja</label>
                        <select
                            wire:model="bulkAssignAction"
                            class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-gray-200"
                        >
                            <option value="add_features">Dodaj cechy (zachowaj istniejace)</option>
                            <option value="replace_features">Zastap cechy (usun istniejace)</option>
                        </select>
                    </div>

                    {{-- Products Count --}}
                    <div class="p-3 bg-blue-500/10 border border-blue-500/30 rounded-lg">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span class="text-blue-300">
                                Szablon zostanie zastosowany do <strong>{{ $bulkAssignProductsCount }}</strong> produktow
                            </span>
                        </div>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 p-4 border-t border-gray-700">
                    <button
                        wire:click="closeBulkAssignModal"
                        type="button"
                        class="px-4 py-2 text-gray-400 hover:text-white transition-colors"
                    >
                        Anuluj
                    </button>
                    <button
                        wire:click="bulkAssign"
                        type="button"
                        class="btn-primary"
                    >
                        Zastosuj szablon
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>

<div class="feature-browser">
    {{-- HEADER --}}
    <div class="feature-browser__header">
        <div class="flex items-center gap-3">
            <h3 class="text-lg font-semibold text-white">Szablony Cech</h3>
            <span class="feature-browser__badge">{{ $this->templates->count() }}</span>
        </div>
        <div class="flex items-center gap-2">
            <select wire:model.live="filter" class="form-input-enterprise form-input-sm">
                <option value="all">Wszystkie</option>
                <option value="predefined">Predefiniowane</option>
                <option value="custom">Wlasne</option>
            </select>
            <button wire:click="openTemplateModal" class="btn-enterprise-primary btn-sm">
                + Nowy szablon
            </button>
        </div>
    </div>

    {{-- CARD GRID --}}
    <div class="template-grid">
        @forelse($this->templates as $template)
            <div x-data="{ showPreview: false }"
                 wire:key="template-{{ $template['id'] }}"
                 class="template-card-v2"
                 :class="{ 'template-card-v2--expanded': showPreview }">
                {{-- Card Header --}}
                <div class="template-card-v2__header">
                    <span class="template-card-v2__icon">{{ $template['icon'] }}</span>
                    <div class="template-card-v2__info">
                        <h4 class="template-card-v2__title">{{ $template['name'] }}</h4>
                        <div class="template-card-v2__meta">
                            <span class="template-card-v2__badge {{ $template['is_predefined'] ? 'template-card-v2__badge--predefined' : 'template-card-v2__badge--custom' }}">
                                {{ $template['is_predefined'] ? 'PRE' : 'WLASNY' }}
                            </span>
                            <span>{{ $template['features_count'] }} cech</span>
                            <span>{{ $template['usage_count'] }} uzyc</span>
                        </div>
                    </div>
                </div>

                {{-- Card Actions --}}
                <div class="template-card-v2__actions">
                    <button @click="showPreview = !showPreview" class="btn-enterprise-secondary btn-sm">
                        <span x-show="!showPreview">Podglad</span>
                        <span x-show="showPreview">Schowaj</span>
                    </button>
                    <button wire:click="openBulkAssignModal({{ $template['id'] }})" class="btn-enterprise-primary btn-sm">
                        Przypisz
                    </button>
                    @if(!$template['is_predefined'])
                        <button wire:click="editTemplate({{ $template['id'] }})" class="btn-enterprise-ghost btn-sm">&#9998;</button>
                        <button wire:click="duplicateTemplate({{ $template['id'] }})" class="btn-enterprise-ghost btn-sm">&#128203;</button>
                        <button wire:click="deleteTemplate({{ $template['id'] }})" wire:confirm="Usunac szablon?" class="btn-enterprise-ghost btn-sm text-red-400">&#128465;</button>
                    @else
                        <button wire:click="duplicateTemplate({{ $template['id'] }})" class="btn-enterprise-ghost btn-sm">&#128203;</button>
                    @endif
                </div>

                {{-- Expandable Preview --}}
                <div x-show="showPreview"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 transform -translate-y-2"
                     x-transition:enter-end="opacity-100 transform translate-y-0"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     class="template-card-v2__preview">
                    <div class="template-card-v2__preview-header">Cechy w szablonie:</div>
                    @foreach($template['features'] as $index => $feature)
                        <div class="template-card-v2__preview-item">
                            <span class="template-card-v2__preview-num">{{ $index + 1 }}</span>
                            <span class="template-card-v2__preview-name">{{ $feature['name'] ?? 'Brak nazwy' }}</span>
                            <span class="template-card-v2__preview-type">{{ $feature['type'] ?? 'text' }}</span>
                            @if($feature['required'] ?? false)
                                <span class="template-card-v2__preview-required">wymagane</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="template-grid__empty">
                <svg class="w-12 h-12 mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <p class="text-sm">Brak szablonow</p>
            </div>
        @endforelse
    </div>

    {{-- Job Progress Tracker --}}
    @if($activeJobProgressId)
        <div wire:poll.2s="refreshJobProgress" class="fixed bottom-4 right-4 z-50 w-96">
            <div class="bg-gray-800 rounded-lg shadow-xl border border-gray-700 p-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm text-gray-300">
                        {{ $activeJobProgress['message'] ?? 'Przetwarzanie...' }}
                    </span>
                    <button wire:click="dismissProgress" class="text-gray-500 hover:text-gray-300">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="w-full bg-gray-700 rounded-full h-2">
                    <div
                        class="h-2 rounded-full transition-all duration-300 {{ ($activeJobProgress['status'] ?? '') === 'completed' ? 'bg-green-500' : (($activeJobProgress['status'] ?? '') === 'failed' ? 'bg-red-500' : 'bg-blue-500') }}"
                        style="width: {{ $activeJobProgress['percentage'] ?? 0 }}%"
                    ></div>
                </div>
                <div class="flex items-center justify-between mt-1 text-xs text-gray-500">
                    <span>{{ $activeJobProgress['current'] ?? 0 }} / {{ $activeJobProgress['total'] ?? 0 }}</span>
                    <span>{{ $activeJobProgress['percentage'] ?? 0 }}%</span>
                </div>
            </div>
        </div>
    @endif

    {{-- Template Editor Modal --}}
    @if($showTemplateModal)
        <div class="modal-overlay show" wire:click.self="closeTemplateModal">
            <div class="modal-content max-w-2xl">
                <div class="modal-header">
                    <h3 class="text-h3">
                        {{ $editingTemplateId ? 'Edytuj szablon' : 'Nowy szablon' }}
                    </h3>
                    <button wire:click="closeTemplateModal" class="modal-close">&#10005;</button>
                </div>
                <div class="p-4 overflow-y-auto max-h-[60vh]">
                    {{-- Template Name --}}
                    <div class="mb-4">
                        <label class="form-label">Nazwa szablonu</label>
                        <input
                            type="text"
                            wire:model="templateName"
                            class="form-input"
                            placeholder="np. Quad spalinowy 125cc"
                        >
                        @error('templateName')
                            <p class="error-message">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Features List --}}
                    <div class="mb-4">
                        <div class="flex items-center justify-between mb-2">
                            <label class="form-label">Cechy</label>
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
                                            class="form-input form-input-sm"
                                            placeholder="Nazwa cechy"
                                        >
                                        <select
                                            wire:model="templateFeatures.{{ $index }}.type"
                                            class="form-input form-input-sm"
                                        >
                                            <option value="text">Tekst</option>
                                            <option value="number">Liczba</option>
                                            <option value="select">Wybor</option>
                                            <option value="boolean">Tak/Nie</option>
                                        </select>
                                        <input
                                            type="text"
                                            wire:model="templateFeatures.{{ $index }}.default"
                                            class="form-input form-input-sm"
                                            placeholder="Wartosc domyslna"
                                        >
                                        <label class="flex items-center gap-2 text-sm text-gray-300">
                                            <input
                                                type="checkbox"
                                                wire:model="templateFeatures.{{ $index }}.required"
                                                class="checkbox-enterprise"
                                            >
                                            Wymagane
                                        </label>
                                    </div>
                                    <button
                                        wire:click="removeFeature({{ $index }})"
                                        type="button"
                                        class="p-1 text-red-400 hover:text-red-300"
                                    >
                                        &#10005;
                                    </button>
                                </div>
                            @endforeach
                        </div>
                        @error('templateFeatures')
                            <p class="error-message">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                <div class="modal-actions">
                    <button
                        wire:click="saveTemplate"
                        type="button"
                        class="btn-enterprise-primary"
                    >
                        {{ $editingTemplateId ? 'Zapisz zmiany' : 'Utworz szablon' }}
                    </button>
                    <button
                        wire:click="closeTemplateModal"
                        type="button"
                        class="btn-enterprise-secondary"
                    >
                        Anuluj
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Bulk Assign Modal --}}
    @if($showBulkAssignModal)
        <div class="modal-overlay show" wire:click.self="closeBulkAssignModal">
            <div class="modal-content max-w-lg">
                <div class="modal-header">
                    <h3 class="text-h3">Przypisz szablon do produktow</h3>
                    <button wire:click="closeBulkAssignModal" class="modal-close">&#10005;</button>
                </div>
                <div class="p-4">
                    {{-- Scope Selection --}}
                    <div class="mb-4">
                        <label class="form-label">Zakres</label>
                        <div class="space-y-2">
                            <label class="flex items-center gap-2 p-3 bg-gray-700/50 rounded-lg cursor-pointer hover:bg-gray-700">
                                <input
                                    type="radio"
                                    wire:model.live="bulkAssignScope"
                                    value="all_vehicles"
                                    class="checkbox-enterprise"
                                >
                                <span class="text-gray-200">Wszystkie pojazdy</span>
                            </label>
                            <label class="flex items-center gap-2 p-3 bg-gray-700/50 rounded-lg cursor-pointer hover:bg-gray-700">
                                <input
                                    type="radio"
                                    wire:model.live="bulkAssignScope"
                                    value="by_category"
                                    class="checkbox-enterprise"
                                >
                                <span class="text-gray-200">Tylko kategoria</span>
                            </label>
                        </div>
                    </div>

                    {{-- Action Selection --}}
                    <div class="mb-4">
                        <label class="form-label">Akcja</label>
                        <select
                            wire:model="bulkAssignAction"
                            class="form-input"
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
                <div class="modal-actions">
                    <button
                        wire:click="bulkAssign"
                        type="button"
                        class="btn-enterprise-primary"
                    >
                        Zastosuj szablon
                    </button>
                    <button
                        wire:click="closeBulkAssignModal"
                        type="button"
                        class="btn-enterprise-secondary"
                    >
                        Anuluj
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>

<div class="space-y-6">
    {{-- LIVEWIRE TEST PANEL - Remove after diagnosis --}}
    <div class="enterprise-card bg-yellow-900/20 border-yellow-600">
        <div class="p-4">
            <h3 class="text-yellow-400 font-bold mb-2">🔧 LIVEWIRE DIAGNOSTIC TEST</h3>
            <div class="space-y-2">
                <div>Counter: <span class="text-white font-bold text-xl">{{ $testCounter }}</span></div>
                <div>
                    <button wire:click="incrementTest" class="bg-green-600 hover:bg-green-700 px-4 py-2 rounded">
                        TEST: Increment Counter
                        <span wire:loading wire:target="incrementTest" class="ml-2">⏳</span>
                    </button>
                </div>
                <div>showTemplateEditor: <span class="text-white">{{ $showTemplateEditor ? 'TRUE' : 'FALSE' }}</span></div>
                <div>
                    <button wire:click="openTemplateEditor" class="bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded">
                        TEST: Open Modal (set true)
                        <span wire:loading wire:target="openTemplateEditor" class="ml-2">⏳</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- HEADER --}}
    <div class="enterprise-card">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-h2">Cechy Pojazdow</h2>
                <p class="text-gray-400 mt-1">Zarzadzanie szablonami cech dla produktow typu pojazd</p>
            </div>
            <button wire:click="openTemplateEditor" class="btn-enterprise-primary">
                Dodaj Template
            </button>
        </div>

        {{-- TEMPLATE CARDS GRID --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
            {{-- Predefined Templates (database-backed) --}}
            @foreach($predefinedTemplates as $template)
                <div wire:key="predefined-{{ $template->id }}" class="template-card">
                    <div class="template-icon {{ $template->id == 1 ? 'electric' : 'combustion' }}">
                        {{ $template->id == 1 ? '⚡' : '🚗' }}
                    </div>
                    <h3 class="template-title">{{ $template->name }}</h3>
                    <div class="template-stats">
                        <span class="stat-item">{{ count($template->features) }} cech</span>
                        <span class="stat-item">Uzywany: {{ $template->usage_count ?? 0 }} razy</span>
                    </div>
                    <div class="template-actions">
                        <button wire:click="editTemplate({{ $template->id }})" class="btn-template-action">
                            ⚙ Edit
                        </button>
                        <button wire:click="deleteTemplate({{ $template->id }})" class="btn-template-action delete">
                            🗑 Del
                        </button>
                    </div>
                </div>
            @endforeach

            {{-- Custom Templates (database-backed) --}}
            @foreach($customTemplates as $template)
                <div wire:key="template-{{ $template->id }}" class="template-card">
                    <div class="template-icon custom">&#128220;</div>
                    <h3 class="template-title">{{ $template->name }}</h3>
                    <div class="template-stats">
                        <span class="stat-item">{{ count($template->features) }} cech</span>
                        <span class="stat-item">Uzywany: {{ $template->usage_count ?? 0 }} razy</span>
                    </div>
                    <div class="template-actions">
                        <button wire:click="editTemplate({{ $template->id }})" class="btn-template-action">
                            &#9881; Edit
                        </button>
                        <button wire:click="deleteTemplate({{ $template->id }})" class="btn-template-action delete">
                            &#128465; Del
                        </button>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- BULK ASSIGN BUTTON --}}
        <div class="text-center">
            <button wire:click="openBulkAssignModal" class="btn-enterprise-secondary">
                &#128640; Zastosuj Template do Produktow
            </button>
        </div>
    </div>

    {{-- FEATURE LIBRARY SIDEBAR --}}
    <div x-data="{ showLibrary: true }" class="enterprise-card">
        <button @click="showLibrary = !showLibrary" class="btn-enterprise-secondary mb-4">
            <span x-show="showLibrary">&#9660;</span>
            <span x-show="!showLibrary">&#9654;</span>
            Biblioteka Cech (50+)
        </button>

        <div x-show="showLibrary" x-transition class="feature-library">
            {{-- Search Input --}}
            <input type="text"
                   wire:model.live.debounce.300ms="searchFeature"
                   class="form-input mb-4"
                   placeholder="Szukaj cechy">

            {{-- Grouped Features --}}
            <div class="space-y-4">
                @foreach($this->filteredFeatureLibrary as $group)
                    <div wire:key="group-{{ $group['group'] }}" class="feature-group">
                        <h4 class="feature-group-title">{{ $group['group'] }}</h4>
                        <ul class="feature-list">
                            @foreach($group['features'] as $feature)
                                <li wire:key="feature-{{ $feature['name'] }}"
                                    wire:click="addFeatureToTemplate('{{ $feature['name'] }}')"
                                    class="feature-list-item">
                                    <span class="feature-bullet">&#8226;</span>
                                    <span class="feature-name">{{ $feature['name'] }}</span>
                                    <span class="feature-type-badge">{{ $feature['type'] }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- TEMPLATE EDITOR MODAL --}}
    <div class="modal-overlay {{ $showTemplateEditor ? 'show' : '' }}" wire:click.self="closeTemplateEditor">
        <div class="modal-content max-w-4xl">
            {{-- Modal Header --}}
            <div class="modal-header">
                <h3 class="text-h3">
                    {{ $editingTemplateId ? 'Edytuj' : 'Nowy' }} Template
                </h3>
                <button wire:click="closeTemplateEditor" class="modal-close">&#10005;</button>
            </div>

            {{-- Template Name Input --}}
            <div class="mb-4">
                <label class="form-label">Nazwa template *</label>
                <input type="text"
                       wire:model="templateName"
                       class="form-input"
                       placeholder="np. Pojazdy Elektryczne">
                @error('templateName')
                    <span class="error-message">{{ $message }}</span>
                @enderror
            </div>

            {{-- Features List (Sortable Table) --}}
            <div class="mb-4">
                <div class="flex justify-between items-center mb-2">
                    <h4 class="text-h4">Lista cech (Sortable, Drag &amp; Drop)</h4>
                    <button wire:click="addFeatureRow" class="btn-enterprise-secondary btn-sm">
                        + Dodaj Ceche
                    </button>
                </div>

                <div class="template-features-table">
                    <table class="enterprise-table">
                        <thead>
                            <tr>
                                <th class="w-12">#</th>
                                <th>Nazwa Cechy</th>
                                <th class="w-32">Typ</th>
                                <th class="w-32">Wymagana</th>
                                <th class="w-32">Default</th>
                                <th class="w-20">Akcje</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($templateFeatures as $index => $feature)
                                <tr wire:key="template-feature-{{ $index }}">
                                    <td class="text-center">{{ $index + 1 }}</td>
                                    <td>
                                        <input type="text"
                                               wire:model="templateFeatures.{{ $index }}.name"
                                               class="form-input form-input-sm"
                                               placeholder="Nazwa cechy">
                                    </td>
                                    <td>
                                        <select wire:model="templateFeatures.{{ $index }}.type"
                                                class="form-input form-input-sm">
                                            <option value="text">Text</option>
                                            <option value="number">Number</option>
                                            <option value="bool">Yes/No</option>
                                            <option value="select">Select</option>
                                        </select>
                                    </td>
                                    <td class="text-center">
                                        <input type="checkbox"
                                               wire:model="templateFeatures.{{ $index }}.required"
                                               class="form-checkbox">
                                    </td>
                                    <td>
                                        <input type="text"
                                               wire:model="templateFeatures.{{ $index }}.default"
                                               class="form-input form-input-sm"
                                               placeholder="-">
                                    </td>
                                    <td class="text-center">
                                        <button wire:click="removeFeature({{ $index }})"
                                                class="btn-icon-danger">
                                            &#128465;
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-gray-400 py-8">
                                        Brak cech. Kliknij "Dodaj Ceche" lub wybierz z biblioteki.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Modal Actions --}}
            <div class="modal-actions">
                <button wire:click="saveTemplate"
                        wire:loading.attr="disabled"
                        class="btn-enterprise-primary">
                    <span wire:loading.remove wire:target="saveTemplate">&#128190; Zapisz</span>
                    <span wire:loading wire:target="saveTemplate">Zapisywanie...</span>
                </button>
                <button wire:click="closeTemplateEditor" class="btn-enterprise-secondary">
                    &#10005; Anuluj
                </button>
            </div>
        </div>
    </div>

    {{-- BULK ASSIGN MODAL --}}
    <div class="modal-overlay {{ $showBulkAssignModal ? 'show' : '' }}" wire:click.self="closeBulkAssignModal">
        <div class="modal-content max-w-2xl">
            {{-- Modal Header --}}
            <div class="modal-header">
                <h3 class="text-h3">Zastosuj template do produktow</h3>
                <button wire:click="closeBulkAssignModal" class="modal-close">&#10005;</button>
            </div>

            {{-- Scope Selection --}}
            <div class="mb-4">
                <label class="form-label">Wybierz produkty:</label>

                <div class="space-y-2">
                    <label class="radio-label">
                        <input type="radio"
                               wire:model.live="bulkAssignScope"
                               value="all_vehicles"
                               class="form-radio">
                        <span>Wszystkie pojazdy ({{ $bulkAssignProductsCount }})</span>
                    </label>

                    <label class="radio-label">
                        <input type="radio"
                               wire:model.live="bulkAssignScope"
                               value="by_category"
                               class="form-radio">
                        <span>Pojazdy z kategorii:</span>
                    </label>

                    @if($bulkAssignScope === 'by_category')
                        <select wire:model.live="bulkAssignCategoryId"
                                class="form-input ml-6">
                            <option value="">Wybierz kategorie...</option>
                            {{-- TODO: Load categories dynamically --}}
                            <option value="1">Pojazdy > Motocykle > Elektryczne</option>
                            <option value="2">Pojazdy > Motocykle > Spalinowe</option>
                        </select>
                        <p class="text-sm text-gray-400 ml-6">
                            ({{ $bulkAssignProductsCount }} produktow)
                        </p>
                    @endif
                </div>

                @error('bulkAssignScope')
                    <span class="error-message">{{ $message }}</span>
                @enderror
            </div>

            {{-- Template Selection --}}
            <div class="mb-4">
                <label class="form-label">Wybierz template:</label>
                <select wire:model="selectedTemplateId" class="form-input">
                    <option value="">Wybierz template...</option>
                    <option value="1">Pojazdy Elektryczne</option>
                    <option value="2">Pojazdy Spalinowe</option>
                    @foreach($customTemplates as $template)
                        <option value="{{ $template->id }}">{{ $template->name }}</option>
                    @endforeach
                </select>
                @error('selectedTemplateId')
                    <span class="error-message">{{ $message }}</span>
                @enderror
            </div>

            {{-- Action Selection --}}
            <div class="mb-4">
                <label class="form-label">Akcja:</label>

                <div class="space-y-2">
                    <label class="radio-label">
                        <input type="radio"
                               wire:model="bulkAssignAction"
                               value="add_features"
                               class="form-radio">
                        <span>Dodaj cechy (zachowaj istniejace)</span>
                    </label>

                    <label class="radio-label">
                        <input type="radio"
                               wire:model="bulkAssignAction"
                               value="replace_features"
                               class="form-radio">
                        <span>Zastap cechy (usun istniejace)</span>
                    </label>
                </div>

                @error('bulkAssignAction')
                    <span class="error-message">{{ $message }}</span>
                @enderror
            </div>

            {{-- Modal Actions --}}
            <div class="modal-actions">
                <button wire:click="bulkAssign"
                        wire:loading.attr="disabled"
                        class="btn-enterprise-primary">
                    <span wire:loading.remove wire:target="bulkAssign">&#128640; Zastosuj</span>
                    <span wire:loading wire:target="bulkAssign">Przetwarzanie...</span>
                </button>
                <button wire:click="closeBulkAssignModal" class="btn-enterprise-secondary">
                    &#10005; Anuluj
                </button>
            </div>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if(session()->has('message'))
        <div class="alert alert-success">
            {{ session('message') }}
        </div>
    @endif

    @if($errors->has('general'))
        <div class="alert alert-error">
            {{ $errors->first('general') }}
        </div>
    @endif
</div>

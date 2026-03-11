<div class="space-y-6">
    {{-- HEADER --}}
    <div class="enterprise-card">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-h2">Cechy Pojazdow</h2>
                <p class="text-gray-400 mt-1">Zarzadzanie cechami i szablonami dla produktow</p>
            </div>
        </div>
    </div>

    {{-- TAB NAVIGATION --}}
    <div class="feature-tabs">
        <button wire:click="setTab('library')"
                class="feature-tabs__tab {{ $activeTab === 'library' ? 'active' : '' }}">
            <span class="feature-tabs__tab-icon">📚</span>
            <span>Biblioteka Cech</span>
        </button>
        <button wire:click="setTab('templates')"
                class="feature-tabs__tab {{ $activeTab === 'templates' ? 'active' : '' }}">
            <span class="feature-tabs__tab-icon">📋</span>
            <span>Szablony Cech</span>
        </button>
        <button wire:click="setTab('browser')"
                class="feature-tabs__tab {{ $activeTab === 'browser' ? 'active' : '' }}">
            <span class="feature-tabs__tab-icon">🔍</span>
            <span>Przegladarka Cech</span>
        </button>
    </div>

    {{-- ============================================
        TAB: TEMPLATES (Szablony Cech)
        ============================================ --}}
    @if($activeTab === 'templates')
        <livewire:admin.features.tabs.feature-templates-tab />
    @endif

    {{-- ============================================
        TAB: LIBRARY (Biblioteka Cech)
        ============================================ --}}
    @if($activeTab === 'library')
        <livewire:admin.features.tabs.feature-library-tab />
    @endif

    {{-- ============================================
        TAB: BROWSER (Przegladarka Cech)
        ============================================ --}}
    @if($activeTab === 'browser')
        <livewire:admin.features.tabs.feature-browser-tab />
    @endif

    {{-- ============================================
        MODALS (zawsze renderowane)
        ============================================ --}}

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
                <select wire:model.live="selectedTemplateId" class="form-input">
                    <option value="">Wybierz template...</option>
                    @if($predefinedTemplates->count() > 0)
                        <optgroup label="Predefiniowane">
                            @foreach($predefinedTemplates as $template)
                                <option value="{{ $template->id }}">
                                    {{ $template->name }} ({{ count($template->features ?? []) }} cech)
                                </option>
                            @endforeach
                        </optgroup>
                    @endif
                    @if($customTemplates->count() > 0)
                        <optgroup label="Wlasne szablony">
                            @foreach($customTemplates as $template)
                                <option value="{{ $template->id }}">
                                    {{ $template->name }} ({{ count($template->features ?? []) }} cech)
                                </option>
                            @endforeach
                        </optgroup>
                    @endif
                </select>
                @error('selectedTemplateId')
                    <span class="error-message">{{ $message }}</span>
                @enderror

                {{-- Template Preview --}}
                @if($selectedTemplateId)
                    @php
                        $selectedTemplate = $predefinedTemplates->firstWhere('id', $selectedTemplateId)
                                          ?? $customTemplates->firstWhere('id', $selectedTemplateId);
                    @endphp
                    @if($selectedTemplate)
                        <div class="mt-3 p-3 bg-gray-800 rounded-lg border border-gray-700">
                            <div class="text-sm text-gray-400 mb-2">Cechy w szablonie:</div>
                            <div class="flex flex-wrap gap-2">
                                @foreach(array_slice($selectedTemplate->features ?? [], 0, 10) as $feature)
                                    <span class="text-xs px-2 py-1 bg-gray-700 rounded">
                                        {{ $feature['name'] ?? 'N/A' }}
                                    </span>
                                @endforeach
                                @if(count($selectedTemplate->features ?? []) > 10)
                                    <span class="text-xs px-2 py-1 bg-gray-600 rounded text-gray-400">
                                        +{{ count($selectedTemplate->features) - 10 }} wiecej...
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endif
                @endif
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

    {{-- FEATURE TYPE EDITOR MODAL --}}
    <div class="modal-overlay {{ $showFeatureTypeEditor ? 'show' : '' }}" wire:click.self="closeFeatureTypeEditor">
        <div class="modal-content max-w-xl">
            {{-- Modal Header --}}
            <div class="modal-header">
                <h3 class="text-h3">
                    {{ $editingFeatureTypeId ? 'Edytuj' : 'Nowa' }} Cecha
                </h3>
                <button wire:click="closeFeatureTypeEditor" class="modal-close">&#10005;</button>
            </div>

            {{-- Form --}}
            <div class="space-y-4">
                {{-- Name --}}
                <div>
                    <label class="form-label">Nazwa cechy *</label>
                    <input type="text"
                           wire:model="featureTypeName"
                           class="form-input"
                           placeholder="np. Moc silnika">
                    @error('featureTypeName')
                        <span class="error-message">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Code --}}
                <div>
                    <label class="form-label">Kod (unikatowy) *</label>
                    <input type="text"
                           wire:model="featureTypeCode"
                           class="form-input"
                           placeholder="np. engine_power">
                    @error('featureTypeCode')
                        <span class="error-message">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Value Type --}}
                <div>
                    <label class="form-label">Typ wartosci *</label>
                    <select wire:model="featureTypeValueType" class="form-input">
                        <option value="text">Tekst</option>
                        <option value="number">Liczba</option>
                        <option value="bool">Tak/Nie</option>
                        <option value="select">Lista wyboru</option>
                    </select>
                </div>

                {{-- Unit --}}
                <div>
                    <label class="form-label">Jednostka (opcjonalnie)</label>
                    <input type="text"
                           wire:model="featureTypeUnit"
                           class="form-input"
                           placeholder="np. W, kg, cm">
                </div>

                {{-- Group --}}
                <div>
                    <label class="form-label">Grupa</label>
                    <select wire:model="featureTypeGroupId" class="form-input">
                        <option value="">-- Bez grupy --</option>
                        @foreach($this->featureGroups as $group)
                            <option value="{{ $group->id }}">{{ $group->getDisplayName() }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Placeholder --}}
                <div>
                    <label class="form-label">Placeholder (podpowiedz)</label>
                    <input type="text"
                           wire:model="featureTypePlaceholder"
                           class="form-input"
                           placeholder="np. Wprowadz wartosc...">
                </div>

                {{-- Conditional Group --}}
                <div>
                    <label class="form-label">Warunkowa (typ pojazdu)</label>
                    <select wire:model="featureTypeConditional" class="form-input">
                        <option value="">-- Dla wszystkich --</option>
                        <option value="elektryczne">Tylko elektryczne</option>
                        <option value="spalinowe">Tylko spalinowe</option>
                    </select>
                </div>
            </div>

            {{-- Modal Actions --}}
            <div class="modal-actions mt-6">
                <button wire:click="saveFeatureType"
                        wire:loading.attr="disabled"
                        class="btn-enterprise-primary">
                    <span wire:loading.remove wire:target="saveFeatureType">&#128190; Zapisz</span>
                    <span wire:loading wire:target="saveFeatureType">Zapisywanie...</span>
                </button>
                <button wire:click="closeFeatureTypeEditor" class="btn-enterprise-secondary">
                    &#10005; Anuluj
                </button>
            </div>
        </div>
    </div>

    {{-- FEATURE GROUP EDITOR MODAL --}}
    <div class="modal-overlay {{ $showFeatureGroupEditor ? 'show' : '' }}" wire:click.self="closeFeatureGroupEditor">
        <div class="modal-content max-w-xl">
            {{-- Modal Header --}}
            <div class="modal-header">
                <h3 class="text-h3">
                    {{ $editingFeatureGroupId ? 'Edytuj' : 'Nowa' }} Grupa
                </h3>
                <button wire:click="closeFeatureGroupEditor" class="modal-close">&#10005;</button>
            </div>

            {{-- Form --}}
            <div class="space-y-4">
                {{-- Name --}}
                <div>
                    <label class="form-label">Nazwa grupy *</label>
                    <input type="text"
                           wire:model="featureGroupName"
                           class="form-input"
                           placeholder="np. Silnik">
                    @error('featureGroupName')
                        <span class="error-message">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Code --}}
                <div>
                    <label class="form-label">Kod (unikatowy) *</label>
                    <input type="text"
                           wire:model="featureGroupCode"
                           class="form-input"
                           placeholder="np. engine">
                    @error('featureGroupCode')
                        <span class="error-message">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Icon --}}
                <div>
                    <label class="form-label">Ikona</label>
                    <select wire:model="featureGroupIcon" class="form-input">
                        <option value="">-- Brak --</option>
                        <option value="engine">&#9881; Silnik</option>
                        <option value="ruler">&#128207; Linijka</option>
                        <option value="wheel">&#9899; Kolo</option>
                        <option value="brake">&#128376; Hamulec</option>
                        <option value="suspension">&#8597; Zawieszenie</option>
                        <option value="electric">&#9889; Elektryczny</option>
                        <option value="fuel">&#9981; Paliwo</option>
                        <option value="document">&#128196; Dokument</option>
                        <option value="car">&#128663; Samochod</option>
                        <option value="gear">&#9881; Zebatka</option>
                        <option value="info">&#8505; Info</option>
                    </select>
                </div>

                {{-- Color --}}
                <div>
                    <label class="form-label">Kolor</label>
                    <select wire:model="featureGroupColor" class="form-input">
                        <option value="">-- Domyslny --</option>
                        <option value="orange">Pomaranczowy</option>
                        <option value="blue">Niebieski</option>
                        <option value="green">Zielony</option>
                        <option value="yellow">Zolty</option>
                        <option value="red">Czerwony</option>
                        <option value="purple">Fioletowy</option>
                        <option value="cyan">Turkusowy</option>
                        <option value="gray">Szary</option>
                    </select>
                </div>

                {{-- Sort Order --}}
                <div>
                    <label class="form-label">Kolejnosc *</label>
                    <input type="number"
                           wire:model="featureGroupSortOrder"
                           class="form-input"
                           min="0"
                           placeholder="0">
                    @error('featureGroupSortOrder')
                        <span class="error-message">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Vehicle Type Filter --}}
                <div>
                    <label class="form-label">Filtr typu pojazdu</label>
                    <select wire:model="featureGroupVehicleFilter" class="form-input">
                        <option value="">-- Dla wszystkich --</option>
                        <option value="elektryczne">Tylko elektryczne</option>
                        <option value="spalinowe">Tylko spalinowe</option>
                    </select>
                </div>
            </div>

            {{-- Modal Actions --}}
            <div class="modal-actions mt-6">
                <button wire:click="saveFeatureGroup"
                        wire:loading.attr="disabled"
                        class="btn-enterprise-primary">
                    <span wire:loading.remove wire:target="saveFeatureGroup">&#128190; Zapisz</span>
                    <span wire:loading wire:target="saveFeatureGroup">Zapisywanie...</span>
                </button>
                <button wire:click="closeFeatureGroupEditor" class="btn-enterprise-secondary">
                    &#10005; Anuluj
                </button>
            </div>
        </div>
    </div>

    {{-- JOB PROGRESS BAR --}}
    @if($activeJobProgressId)
        <div wire:poll.2s="refreshJobProgress" class="fixed bottom-4 right-4 z-50 w-96">
            <div class="enterprise-card bg-gray-800 shadow-xl border border-gray-700">
                <div class="flex justify-between items-center mb-2">
                    <span class="text-sm font-medium text-gray-200">
                        Przypisywanie cech...
                    </span>
                    <button wire:click="dismissProgress"
                            class="text-gray-400 hover:text-gray-200 transition-colors">
                        &#10005;
                    </button>
                </div>

                {{-- Progress Message --}}
                <p class="text-xs text-gray-400 mb-2">
                    {{ $activeJobProgress['message'] ?? 'Inicjalizacja...' }}
                </p>

                {{-- Progress Bar --}}
                <div class="w-full bg-gray-700 rounded-full h-2.5 mb-2">
                    <div class="h-2.5 rounded-full transition-all duration-300 {{ ($activeJobProgress['status'] ?? 'pending') === 'completed' ? 'bg-green-500' : (($activeJobProgress['status'] ?? 'pending') === 'failed' ? 'bg-red-500' : 'bg-orange-500') }}"
                         style="width: {{ $activeJobProgress['percentage'] ?? 0 }}%"></div>
                </div>

                {{-- Stats --}}
                <div class="flex justify-between text-xs text-gray-400">
                    <span>{{ $activeJobProgress['current'] ?? 0 }}/{{ $activeJobProgress['total'] ?? 0 }}</span>
                    <span>{{ $activeJobProgress['percentage'] ?? 0 }}%</span>
                </div>

                {{-- Error Count --}}
                @if(($activeJobProgress['errors'] ?? 0) > 0)
                    <p class="text-xs text-red-400 mt-1">
                        &#9888; {{ $activeJobProgress['errors'] }} bledow
                    </p>
                @endif

                {{-- Status Badge --}}
                @if(($activeJobProgress['status'] ?? '') === 'completed')
                    <div class="mt-2 text-center">
                        <span class="text-xs px-2 py-1 bg-green-500/20 text-green-400 rounded">
                            &#10003; Ukonczone
                        </span>
                    </div>
                @elseif(($activeJobProgress['status'] ?? '') === 'failed')
                    <div class="mt-2 text-center">
                        <span class="text-xs px-2 py-1 bg-red-500/20 text-red-400 rounded">
                            &#10005; Blad
                        </span>
                    </div>
                @endif
            </div>
        </div>
    @endif

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

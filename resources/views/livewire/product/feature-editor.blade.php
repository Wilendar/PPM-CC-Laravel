{{--
    FeatureEditor Component Template

    Edit product features (technical specifications) - inline editing, grouped display, bulk save

    LIVEWIRE 3.x COMPLIANCE:
    - wire:key for @foreach (prevent DOM issues)
    - wire:model.blur for text inputs (better performance)
    - wire:model.live for checkboxes (instant feedback)
    - NO inline styles (all styles via CSS classes)

    @version 1.0
    @since ETAP_05a FAZA 4 (2025-10-17)
--}}

<div class="feature-editor-component">
    {{-- Header --}}
    <div class="editor-header">
        <h3>Product Features</h3>
        <button wire:click="toggleEditMode"
                class="btn-toggle-mode"
                type="button">
            {{ $editMode ? 'View Mode' : 'Edit Mode' }}
        </button>
    </div>

    {{-- Error Messages (General) --}}
    @error('general')
        <div class="error-banner" role="alert">
            <strong>Error:</strong> {{ $message }}
        </div>
    @enderror

    {{-- Add Feature Panel (Edit Mode Only) --}}
    @if($editMode)
        <div class="add-feature-panel">
            <label for="newFeatureTypeId" class="sr-only">Select Feature Type</label>
            <select wire:model="newFeatureTypeId"
                    id="newFeatureTypeId"
                    class="feature-type-select"
                    aria-label="Select feature type to add">
                <option value="">Select feature type...</option>
                @foreach($availableFeatureTypes as $type)
                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                @endforeach
            </select>

            <button wire:click="addFeature"
                    class="btn-add-feature"
                    type="button"
                    wire:loading.attr="disabled"
                    wire:target="addFeature">
                <span wire:loading.remove wire:target="addFeature">Add Feature</span>
                <span wire:loading wire:target="addFeature">Adding...</span>
            </button>

            @error('newFeatureTypeId')
                <span class="error-text" role="alert">{{ $message }}</span>
            @enderror
        </div>
    @endif

    {{-- Feature Groups --}}
    @if($this->groupedFeatures->isEmpty())
        <div class="empty-state">
            <p>No features assigned to this product yet.</p>
            @if($editMode)
                <p class="help-text">Use the "Add Feature" panel above to add your first feature.</p>
            @endif
        </div>
    @else
        <div class="feature-groups">
            @foreach($this->groupedFeatures as $groupName => $groupFeatures)
                <div class="feature-group" wire:key="group-product-{{ $product->id }}-{{ Str::slug($groupName) }}">
                    <h4 class="group-title">{{ $groupName }}</h4>

                    <div class="feature-list">
                        @foreach($groupFeatures as $feature)
                            <div class="feature-row" wire:key="feature-{{ $feature->id }}">
                                <label class="feature-label" for="feature-input-{{ $feature->id }}">
                                    {{ $feature->featureType->name }}
                                </label>

                                {{-- VIEW MODE: Display value --}}
                                @if(!$editMode)
                                    <span class="feature-value">{{ $feature->getDisplayValue() }}</span>
                                @else
                                    {{-- EDIT MODE: Input fields based on feature type --}}

                                    {{-- SELECT type (predefined values) --}}
                                    @if($feature->featureType->value_type === 'select' && $feature->featureType->featureValues->isNotEmpty())
                                        <select wire:model.blur="features.{{ $loop->parent->index }}.feature_value_id"
                                                id="feature-input-{{ $feature->id }}"
                                                class="feature-value-select"
                                                aria-label="Select value for {{ $feature->featureType->name }}">
                                            <option value="">Select value...</option>
                                            @foreach($feature->featureType->featureValues as $value)
                                                <option value="{{ $value->id }}">{{ $value->display_value }}</option>
                                            @endforeach
                                        </select>

                                    {{-- BOOL type (checkbox) --}}
                                    @elseif($feature->featureType->value_type === 'bool')
                                        <div class="checkbox-wrapper">
                                            <input type="checkbox"
                                                   wire:model.live="features.{{ $loop->parent->index }}.custom_value"
                                                   id="feature-input-{{ $feature->id }}"
                                                   class="feature-checkbox"
                                                   aria-label="{{ $feature->featureType->name }}">
                                            <label for="feature-input-{{ $feature->id }}" class="checkbox-label">
                                                Yes
                                            </label>
                                        </div>

                                    {{-- NUMBER type (with unit) --}}
                                    @elseif($feature->featureType->value_type === 'number')
                                        <div class="number-input-wrapper">
                                            <input type="number"
                                                   wire:model.blur="features.{{ $loop->parent->index }}.custom_value"
                                                   id="feature-input-{{ $feature->id }}"
                                                   class="feature-number-input"
                                                   step="0.01"
                                                   aria-label="{{ $feature->featureType->name }}">
                                            @if($feature->featureType->unit)
                                                <span class="unit-label" aria-label="Unit">
                                                    {{ $feature->featureType->unit }}
                                                </span>
                                            @endif
                                        </div>

                                    {{-- TEXT type (default) --}}
                                    @else
                                        <input type="text"
                                               wire:model.blur="features.{{ $loop->parent->index }}.custom_value"
                                               id="feature-input-{{ $feature->id }}"
                                               class="feature-text-input"
                                               aria-label="{{ $feature->featureType->name }}">
                                    @endif

                                    {{-- Remove Button (Edit Mode Only) --}}
                                    <button wire:click="removeFeature({{ $feature->id }})"
                                            class="btn-remove-feature"
                                            type="button"
                                            wire:loading.attr="disabled"
                                            wire:target="removeFeature({{ $feature->id }})"
                                            wire:confirm="Are you sure you want to remove this feature?"
                                            aria-label="Remove {{ $feature->featureType->name }}">
                                        <span wire:loading.remove wire:target="removeFeature({{ $feature->id }})">×</span>
                                        <span wire:loading wire:target="removeFeature({{ $feature->id }})">...</span>
                                    </button>
                                @endif

                                {{-- Error Messages (Per Feature) --}}
                                @error("features.{$loop->parent->index}.custom_value")
                                    <span class="error-text" role="alert">{{ $message }}</span>
                                @enderror
                                @error("features.{$loop->parent->index}.feature_value_id")
                                    <span class="error-text" role="alert">{{ $message }}</span>
                                @enderror
                                @error("feature_{$feature->id}")
                                    <span class="error-text" role="alert">{{ $message }}</span>
                                @enderror
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Save Actions (Edit Mode Only) --}}
    @if($editMode && $features->isNotEmpty())
        <div class="editor-actions">
            <button wire:click="saveAll"
                    class="btn-save-all"
                    type="button"
                    wire:loading.attr="disabled"
                    wire:target="saveAll">
                <span wire:loading.remove wire:target="saveAll">Save All Features</span>
                <span wire:loading wire:target="saveAll">Saving...</span>
            </button>
        </div>
    @endif

    {{-- Success Message (Flash) --}}
    @if(session()->has('message'))
        <div class="success-message"
             x-data="{ show: true }"
             x-show="show"
             x-init="setTimeout(() => show = false, 5000)"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform translate-y-2"
             x-transition:enter-end="opacity-100 transform translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             role="alert">
            {{ session('message') }}
        </div>
    @endif

    {{-- Loading Overlay --}}
    <div wire:loading wire:target="saveAll,addFeature,removeFeature,toggleEditMode"
         class="loading-overlay">
        <div class="loading-spinner"></div>
    </div>
</div>

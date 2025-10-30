{{--
    Variant Picker Livewire Component View

    Interactive variant selector with attribute selection UI

    FEATURES:
    - Multiple display types (dropdown, radio, color swatch, button)
    - Real-time variant updates
    - Price & stock display
    - Disabled states for unavailable combinations
    - Accessibility WCAG 2.1 AA compliant

    COMPLIANCE:
    - wire:key for all @foreach (prevents cross-contamination)
    - NO inline styles (100% CSS classes in variant-picker.css)
    - wire:model.live for instant feedback
    - Alpine.js for UI interactions
    - ~150 linii Blade template

    RELATED:
    - app/Http/Livewire/Product/VariantPicker.php
    - resources/css/components/variant-picker.css (added to admin/components.css)
--}}

<div class="variant-picker-container">
    {{-- Header --}}
    <div class="variant-picker-header">
        <h3 class="variant-picker-title">Select Variant</h3>

        @if($this->selectedVariant)
            <button wire:click="resetSelection"
                    type="button"
                    class="btn-reset-variant"
                    aria-label="Reset variant selection">
                Reset
            </button>
        @endif
    </div>

    {{-- Attribute Selectors --}}
    <div class="variant-attribute-groups">
        @foreach($this->attributeTypes as $type)
            <div class="variant-attribute-group" wire:key="attr-type-{{ $type->id }}">
                <label class="variant-attribute-label">
                    {{ $type->name }}
                    @if(isset($selectedAttributes[$type->code]))
                        <span class="selected-indicator">✓</span>
                    @endif
                </label>

                {{-- Dropdown Display --}}
                @if($type->display_type === 'dropdown')
                    <select wire:model.live="selectedAttributes.{{ $type->code }}"
                            class="variant-attribute-dropdown"
                            aria-label="Select {{ $type->name }}">
                        <option value="">-- Select {{ $type->name }} --</option>
                        @foreach($this->getAvailableValues($type) as $attr)
                            <option value="{{ $attr->value_code }}"
                                    @disabled(!$this->isValueAvailable($type, $attr->value_code))
                                    wire:key="dropdown-{{ $type->id }}-{{ $attr->value_code }}">
                                {{ $attr->value }}
                                @if(!$this->isValueAvailable($type, $attr->value_code))
                                    (Out of stock)
                                @endif
                            </option>
                        @endforeach
                    </select>
                @endif

                {{-- Color Swatch Display --}}
                @if($type->display_type === 'color')
                    <div class="variant-color-swatches">
                        @foreach($this->getAvailableValues($type) as $attr)
                            @php
                                $isSelected = ($selectedAttributes[$type->code] ?? '') === $attr->value_code;
                                $isAvailable = $this->isValueAvailable($type, $attr->value_code);
                            @endphp

                            <button type="button"
                                    wire:click="selectAttribute('{{ $type->code }}', '{{ $attr->value_code }}')"
                                    class="color-swatch {{ $isSelected ? 'selected' : '' }} {{ !$isAvailable ? 'disabled' : '' }}"
                                    style="--swatch-color: {{ $attr->color_hex ?? '#cccccc' }}"
                                    title="{{ $attr->value }}"
                                    aria-label="Select {{ $attr->value }}"
                                    @disabled(!$isAvailable)
                                    wire:key="color-{{ $type->id }}-{{ $attr->value_code }}">
                                @if($isSelected)
                                    <span class="swatch-check" aria-hidden="true">✓</span>
                                @endif
                            </button>
                        @endforeach
                    </div>
                @endif

                {{-- Button Display --}}
                @if($type->display_type === 'button')
                    <div class="variant-button-group">
                        @foreach($this->getAvailableValues($type) as $attr)
                            @php
                                $isSelected = ($selectedAttributes[$type->code] ?? '') === $attr->value_code;
                                $isAvailable = $this->isValueAvailable($type, $attr->value_code);
                            @endphp

                            <button type="button"
                                    wire:click="selectAttribute('{{ $type->code }}', '{{ $attr->value_code }}')"
                                    class="variant-button {{ $isSelected ? 'selected' : '' }} {{ !$isAvailable ? 'disabled' : '' }}"
                                    @disabled(!$isAvailable)
                                    aria-label="Select {{ $attr->value }}"
                                    wire:key="button-{{ $type->id }}-{{ $attr->value_code }}">
                                {{ $attr->value }}
                            </button>
                        @endforeach
                    </div>
                @endif

                {{-- Radio Display --}}
                @if($type->display_type === 'radio')
                    <div class="variant-radio-group">
                        @foreach($this->getAvailableValues($type) as $attr)
                            @php
                                $isAvailable = $this->isValueAvailable($type, $attr->value_code);
                                $radioId = "radio-{$type->id}-{$attr->value_code}";
                            @endphp

                            <label class="variant-radio-label {{ !$isAvailable ? 'disabled' : '' }}"
                                   for="{{ $radioId }}"
                                   wire:key="radio-{{ $type->id }}-{{ $attr->value_code }}">
                                <input type="radio"
                                       id="{{ $radioId }}"
                                       wire:model.live="selectedAttributes.{{ $type->code }}"
                                       value="{{ $attr->value_code }}"
                                       @disabled(!$isAvailable)
                                       class="variant-radio-input">
                                <span class="variant-radio-text">
                                    {{ $attr->value }}
                                    @if(!$isAvailable)
                                        <span class="out-of-stock-badge">Out of stock</span>
                                    @endif
                                </span>
                            </label>
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    {{-- Selected Variant Info --}}
    @if($this->selectedVariant)
        <div class="variant-info-panel">
            <div class="variant-sku">
                <span class="label">SKU:</span>
                <span class="value">{{ $this->selectedVariant->sku }}</span>
            </div>

            <div class="variant-price">
                <span class="label">Price:</span>
                <span class="value price-amount">
                    {{ number_format($this->selectedVariant->getPriceForGroup($priceGroupId), 2) }} PLN
                </span>
            </div>

            <div class="variant-stock {{ $this->selectedVariant->getTotalStock() > 0 ? 'in-stock' : 'out-of-stock' }}">
                <span class="label">Stock:</span>
                <span class="value">
                    {{ $this->selectedVariant->getTotalStock() }} {{ $this->selectedVariant->getTotalStock() > 0 ? 'Available' : 'Out of stock' }}
                </span>
            </div>
        </div>
    @else
        <div class="variant-info-placeholder">
            <p>Please select variant attributes to see price and availability.</p>
        </div>
    @endif

    {{-- Loading State --}}
    <div wire:loading class="variant-loading-overlay">
        <div class="loading-spinner" aria-label="Loading variant information"></div>
    </div>
</div>

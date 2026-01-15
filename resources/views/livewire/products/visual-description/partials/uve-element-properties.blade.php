{{-- UVE Element Properties - Panel wlasciwosci elementu (gdy blok odmrozony) --}}
@props(['element'])

@php
    $elementId = $element['id'] ?? '';
    $type = $element['type'] ?? 'container';
    $content = $element['content'] ?? '';
    $styles = $element['styles'] ?? [];
    $classes = $element['classes'] ?? [];
@endphp

<div class="uve-element-properties" x-data="{ activeTab: 'content' }" wire:ignore.self>
    {{-- Element Info Header --}}
    <div class="uve-property-header">
        <span class="uve-property-element-type">{{ ucfirst($type) }}</span>
        <span class="uve-property-element-id">{{ $elementId }}</span>
    </div>

    {{-- Tabs --}}
    <div class="uve-property-tabs">
        <button
            type="button"
            @click="activeTab = 'content'"
            :class="{ 'active': activeTab === 'content' }"
            class="uve-property-tab"
        >
            Tresc
        </button>
        <button
            type="button"
            @click="activeTab = 'style'"
            :class="{ 'active': activeTab === 'style' }"
            class="uve-property-tab"
        >
            Styl
        </button>
        <button
            type="button"
            @click="activeTab = 'spacing'"
            :class="{ 'active': activeTab === 'spacing' }"
            class="uve-property-tab"
        >
            Odstepy
        </button>
    </div>

    {{-- Content Tab --}}
    <div x-show="activeTab === 'content'" class="uve-property-content">
        @if(in_array($type, ['heading', 'text', 'button', 'link']))
            <div class="uve-property-field">
                <label class="uve-property-label">Tekst</label>
                <textarea
                    wire:change="updateElementProperty('{{ $elementId }}', 'content', $event.target.value)"
                    class="uve-input"
                    rows="3"
                >{{ $content }}</textarea>
            </div>
        @endif

        @if(in_array($type, ['button', 'link']))
            <div class="uve-property-field">
                <label class="uve-property-label">URL</label>
                <input
                    type="text"
                    value="{{ $element['href'] ?? '' }}"
                    wire:change="updateElementProperty('{{ $elementId }}', 'href', $event.target.value)"
                    class="uve-input"
                    placeholder="https://..."
                />
            </div>
        @endif

        @if($type === 'image')
            <div class="uve-property-field">
                <label class="uve-property-label">Zrodlo obrazu</label>
                <input
                    type="text"
                    value="{{ $element['src'] ?? '' }}"
                    wire:change="updateElementProperty('{{ $elementId }}', 'src', $event.target.value)"
                    class="uve-input"
                    placeholder="URL obrazu..."
                />
            </div>
            <div class="uve-property-field">
                <label class="uve-property-label">Alt tekst</label>
                <input
                    type="text"
                    value="{{ $element['alt'] ?? '' }}"
                    wire:change="updateElementProperty('{{ $elementId }}', 'alt', $event.target.value)"
                    class="uve-input"
                    placeholder="Opis obrazu..."
                />
            </div>
        @endif

        @if($type === 'heading')
            <div class="uve-property-field">
                <label class="uve-property-label">Poziom</label>
                <select
                    wire:change="updateElementProperty('{{ $elementId }}', 'tag', $event.target.value)"
                    class="uve-select"
                >
                    @foreach(['h1', 'h2', 'h3', 'h4', 'h5', 'h6'] as $h)
                        <option value="{{ $h }}" {{ ($element['tag'] ?? 'h2') === $h ? 'selected' : '' }}>
                            {{ strtoupper($h) }}
                        </option>
                    @endforeach
                </select>
            </div>
        @endif
    </div>

    {{-- Style Tab --}}
    <div x-show="activeTab === 'style'" x-cloak class="uve-property-content">
        <div class="uve-property-field">
            <label class="uve-property-label">Kolor tekstu</label>
            <input
                type="color"
                value="{{ $styles['color'] ?? '#000000' }}"
                wire:change="updateElementProperty('{{ $elementId }}', 'color', $event.target.value)"
                class="uve-color-input"
            />
        </div>

        <div class="uve-property-field">
            <label class="uve-property-label">Kolor tla</label>
            <input
                type="color"
                value="{{ $styles['backgroundColor'] ?? '#ffffff' }}"
                wire:change="updateElementProperty('{{ $elementId }}', 'backgroundColor', $event.target.value)"
                class="uve-color-input"
            />
        </div>

        <div class="uve-property-field">
            <label class="uve-property-label">Rozmiar czcionki</label>
            <select
                wire:change="updateElementProperty('{{ $elementId }}', 'fontSize', $event.target.value)"
                class="uve-select"
            >
                @foreach(['12px', '14px', '16px', '18px', '20px', '24px', '32px', '48px'] as $size)
                    <option value="{{ $size }}" {{ ($styles['fontSize'] ?? '16px') === $size ? 'selected' : '' }}>
                        {{ $size }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="uve-property-field">
            <label class="uve-property-label">Waga czcionki</label>
            <select
                wire:change="updateElementProperty('{{ $elementId }}', 'fontWeight', $event.target.value)"
                class="uve-select"
            >
                @foreach(['300' => 'Light', '400' => 'Normal', '500' => 'Medium', '600' => 'Semi Bold', '700' => 'Bold'] as $weight => $label)
                    <option value="{{ $weight }}" {{ ($styles['fontWeight'] ?? '400') === $weight ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="uve-property-field">
            <label class="uve-property-label">Wyrownanie</label>
            <div class="uve-btn-group-full">
                @foreach(['left' => 'Left', 'center' => 'Center', 'right' => 'Right'] as $align => $label)
                    <button
                        type="button"
                        wire:click="updateElementProperty('{{ $elementId }}', 'textAlign', '{{ $align }}')"
                        class="uve-btn uve-btn-sm {{ ($styles['textAlign'] ?? 'left') === $align ? 'uve-btn-active' : '' }}"
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Spacing Tab --}}
    <div x-show="activeTab === 'spacing'" x-cloak class="uve-property-content">
        <div class="uve-property-field">
            <label class="uve-property-label">Padding</label>
            <input
                type="text"
                value="{{ $styles['padding'] ?? '' }}"
                wire:change="updateElementProperty('{{ $elementId }}', 'padding', $event.target.value)"
                class="uve-input"
                placeholder="np. 1rem lub 10px 20px"
            />
        </div>

        <div class="uve-property-field">
            <label class="uve-property-label">Margin</label>
            <input
                type="text"
                value="{{ $styles['margin'] ?? '' }}"
                wire:change="updateElementProperty('{{ $elementId }}', 'margin', $event.target.value)"
                class="uve-input"
                placeholder="np. 1rem lub 10px 20px"
            />
        </div>

        <div class="uve-property-field">
            <label class="uve-property-label">Border Radius</label>
            <input
                type="text"
                value="{{ $styles['borderRadius'] ?? '' }}"
                wire:change="updateElementProperty('{{ $elementId }}', 'borderRadius', $event.target.value)"
                class="uve-input"
                placeholder="np. 8px"
            />
        </div>

        <div class="uve-property-field">
            <label class="uve-property-label">CSS Classes</label>
            <input
                type="text"
                value="{{ implode(' ', $classes) }}"
                wire:change="updateElementProperty('{{ $elementId }}', 'classes', $event.target.value)"
                class="uve-input"
                placeholder="class1 class2"
            />
        </div>
    </div>

    {{-- Actions --}}
    <div class="uve-property-actions mt-4">
        <button
            type="button"
            wire:click="duplicateElement('{{ $elementId }}')"
            class="uve-btn uve-btn-sm w-full"
        >
            <x-heroicon-o-document-duplicate class="w-4 h-4" />
            Duplikuj
        </button>
        <button
            type="button"
            wire:click="deleteElement('{{ $elementId }}')"
            wire:confirm="Usunac element?"
            class="uve-btn uve-btn-sm uve-btn-danger w-full"
        >
            <x-heroicon-o-trash class="w-4 h-4" />
            Usun
        </button>
    </div>
</div>

<style>
/* UVE Element Properties - Dark Theme */
.uve-element-properties {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.uve-property-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid #334155;
}

.uve-property-element-type {
    font-weight: 600;
    color: #e0ac7e;
    text-transform: uppercase;
    font-size: 0.8rem;
    letter-spacing: 0.05em;
}

.uve-property-element-id {
    font-size: 0.7rem;
    font-family: monospace;
    color: #64748b;
}

.uve-property-tabs {
    display: flex;
    gap: 0.25rem;
    background: #1e293b;
    padding: 0.25rem;
    border-radius: 0.375rem;
}

.uve-property-tab {
    flex: 1;
    padding: 0.375rem 0.5rem;
    font-size: 0.75rem;
    font-weight: 500;
    color: #94a3b8;
    background: transparent;
    border: none;
    border-radius: 0.25rem;
    cursor: pointer;
    transition: all 0.15s;
}

.uve-property-tab:hover {
    color: #e2e8f0;
    background: #334155;
}

.uve-property-tab.active {
    background: #334155;
    color: #e0ac7e;
}

.uve-property-content {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.uve-property-field {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.uve-property-label {
    font-size: 0.75rem;
    font-weight: 500;
    color: #94a3b8;
}

.uve-input,
.uve-select {
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    border: 1px solid #475569;
    border-radius: 0.375rem;
    background: #1e293b;
    color: #e2e8f0;
}

.uve-input:focus,
.uve-select:focus {
    outline: none;
    border-color: #e0ac7e;
    box-shadow: 0 0 0 2px rgba(224, 172, 126, 0.2);
}

.uve-input::placeholder {
    color: #64748b;
}

.uve-select option {
    background: #1e293b;
    color: #e2e8f0;
}

.uve-color-input {
    width: 100%;
    height: 36px;
    padding: 2px;
    border: 1px solid #475569;
    border-radius: 0.375rem;
    cursor: pointer;
    background: #1e293b;
}

.uve-btn-group-full {
    display: flex;
}

.uve-btn-group-full .uve-btn {
    flex: 1;
    border-radius: 0;
    justify-content: center;
    padding: 0.375rem 0.5rem;
    font-size: 0.75rem;
    background: #334155;
    border: 1px solid #475569;
    color: #94a3b8;
    cursor: pointer;
    transition: all 0.15s;
}

.uve-btn-group-full .uve-btn:hover {
    background: #475569;
    color: #e2e8f0;
}

.uve-btn-group-full .uve-btn.uve-btn-active {
    background: #e0ac7e;
    border-color: #e0ac7e;
    color: #0f172a;
}

.uve-btn-group-full .uve-btn:first-child {
    border-radius: 0.375rem 0 0 0.375rem;
}

.uve-btn-group-full .uve-btn:last-child {
    border-radius: 0 0.375rem 0.375rem 0;
}

/* Property Actions */
.uve-property-actions {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    padding-top: 0.75rem;
    border-top: 1px solid #334155;
}

.uve-property-actions .uve-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.375rem;
    padding: 0.5rem 0.75rem;
    font-size: 0.8rem;
    font-weight: 500;
    background: #334155;
    border: 1px solid #475569;
    border-radius: 0.375rem;
    color: #94a3b8;
    cursor: pointer;
    transition: all 0.15s;
}

.uve-property-actions .uve-btn:hover {
    background: #475569;
    color: #e2e8f0;
}

.uve-property-actions .uve-btn-danger:hover {
    background: rgba(248, 113, 113, 0.15);
    border-color: #f87171;
    color: #f87171;
}
</style>

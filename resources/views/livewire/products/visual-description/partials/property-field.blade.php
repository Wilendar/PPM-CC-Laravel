{{-- Dynamic Property Field --}}
@php
    $type = $config['type'] ?? 'text';
    $label = $config['label'] ?? ucfirst($name);
    $description = $config['description'] ?? null;
    $options = $config['options'] ?? [];
    $min = $config['min'] ?? null;
    $max = $config['max'] ?? null;
    $step = $config['step'] ?? null;
    $placeholder = $config['placeholder'] ?? '';
@endphp

<div class="ve-property-field">
    <label class="block text-sm font-medium text-gray-400 mb-1">{{ $label }}</label>

    @switch($type)
        @case('text')
            <input
                type="text"
                class="ve-property-input w-full"
                wire:change="updateBlockProperty({{ $index }}, '{{ $name }}', $event.target.value)"
                value="{{ $value ?? '' }}"
                placeholder="{{ $placeholder }}"
            >
            @break

        @case('textarea')
            <textarea
                rows="{{ $config['rows'] ?? 3 }}"
                class="ve-property-input w-full"
                wire:change="updateBlockProperty({{ $index }}, '{{ $name }}', $event.target.value)"
                placeholder="{{ $placeholder }}"
            >{{ $value ?? '' }}</textarea>
            @break

        @case('richtext')
            <div class="space-y-2">
                {{-- Open VBB for visual editing --}}
                <button
                    wire:click="openBlockInVBB({{ $index }})"
                    class="w-full px-3 py-2 text-sm text-left bg-blue-600/20 border border-blue-500/30 rounded-lg text-blue-400 hover:text-blue-300 hover:bg-blue-600/30 hover:border-blue-500/50 transition flex items-center gap-2"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Edytuj wizualnie (VBB)
                </button>
                {{-- Preview of current content --}}
                @if($value)
                <div class="p-2 bg-gray-900/50 border border-gray-700 rounded text-xs text-gray-500 max-h-20 overflow-hidden">
                    {{ Str::limit(strip_tags($value), 100) }}
                </div>
                @endif
            </div>
            @break

        @case('number')
            <input
                type="number"
                class="ve-property-input w-full"
                wire:change="updateBlockProperty({{ $index }}, '{{ $name }}', $event.target.value)"
                value="{{ $value ?? '' }}"
                @if($min !== null) min="{{ $min }}" @endif
                @if($max !== null) max="{{ $max }}" @endif
                @if($step !== null) step="{{ $step }}" @endif
            >
            @break

        @case('range')
            <div class="flex items-center gap-3">
                <input
                    type="range"
                    class="flex-1"
                    wire:change="updateBlockProperty({{ $index }}, '{{ $name }}', $event.target.value)"
                    value="{{ $value ?? 0 }}"
                    @if($min !== null) min="{{ $min }}" @endif
                    @if($max !== null) max="{{ $max }}" @endif
                    @if($step !== null) step="{{ $step }}" @endif
                >
                <span class="text-sm text-gray-400 w-12 text-right">{{ $value ?? 0 }}</span>
            </div>
            @break

        @case('color')
            <div class="flex items-center gap-2">
                <input
                    type="color"
                    class="w-10 h-10 rounded border border-gray-700 cursor-pointer"
                    wire:change="updateBlockProperty({{ $index }}, '{{ $name }}', $event.target.value)"
                    value="{{ $value ?? '#ffffff' }}"
                >
                <input
                    type="text"
                    class="ve-property-input flex-1"
                    wire:change="updateBlockProperty({{ $index }}, '{{ $name }}', $event.target.value)"
                    value="{{ $value ?? '' }}"
                    placeholder="#000000"
                >
            </div>
            @break

        @case('select')
            <select
                class="ve-property-input w-full"
                wire:change="updateBlockProperty({{ $index }}, '{{ $name }}', $event.target.value)"
            >
                @foreach($options as $optValue => $optLabel)
                    <option value="{{ $optValue }}" {{ $value == $optValue ? 'selected' : '' }}>
                        {{ $optLabel }}
                    </option>
                @endforeach
            </select>
            @break

        @case('checkbox')
            <label class="flex items-center gap-2 cursor-pointer">
                <input
                    type="checkbox"
                    class="checkbox-enterprise"
                    wire:change="updateBlockProperty({{ $index }}, '{{ $name }}', $event.target.checked)"
                    {{ $value ? 'checked' : '' }}
                >
                <span class="text-sm text-gray-300">{{ $config['checkboxLabel'] ?? 'Wlacz' }}</span>
            </label>
            @break

        @case('image')
            <div class="ve-image-field" x-data="{ preview: '{{ $value ?? '' }}' }">
                {{-- Image Preview --}}
                @if($value)
                    <div class="ve-image-field__preview">
                        <img src="{{ $value }}" alt="Preview" class="ve-image-field__image">
                        <div class="ve-image-field__overlay">
                            <button
                                type="button"
                                wire:click="openMediaPicker({{ $index }}, '{{ $name }}', false)"
                                class="ve-image-field__action"
                                title="Zmien obraz"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </button>
                            <button
                                type="button"
                                wire:click="updateBlockProperty({{ $index }}, '{{ $name }}', '')"
                                class="ve-image-field__action ve-image-field__action--danger"
                                title="Usun obraz"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                @else
                    {{-- Empty State --}}
                    <div
                        class="ve-image-field__empty"
                        wire:click="openMediaPicker({{ $index }}, '{{ $name }}', false)"
                    >
                        <svg class="w-8 h-8 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span class="text-sm text-gray-500">Kliknij aby wybrac obraz</span>
                    </div>
                @endif

                {{-- Alt Text Input --}}
                @if($config['showAlt'] ?? true)
                    <div class="ve-image-field__alt">
                        <label class="text-xs text-gray-500">Tekst alternatywny (alt)</label>
                        <input
                            type="text"
                            class="ve-property-input w-full"
                            wire:change="updateBlockProperty({{ $index }}, '{{ $name }}_alt', $event.target.value)"
                            value="{{ $this->blocks[$index]['data'][$name . '_alt'] ?? '' }}"
                            placeholder="Opis obrazu dla czytnikow ekranu"
                        >
                    </div>
                @endif

                {{-- Link Input --}}
                @if($config['showLink'] ?? false)
                    <div class="ve-image-field__link">
                        <label class="text-xs text-gray-500">Link (opcjonalnie)</label>
                        <input
                            type="url"
                            class="ve-property-input w-full"
                            wire:change="updateBlockProperty({{ $index }}, '{{ $name }}_link', $event.target.value)"
                            value="{{ $this->blocks[$index]['data'][$name . '_link'] ?? '' }}"
                            placeholder="https://..."
                        >
                    </div>
                @endif
            </div>
            @break

        @case('video')
            <div class="ve-video-field" x-data="{
                url: '{{ $value ?? '' }}',
                platform: null,
                videoId: null,

                detectPlatform() {
                    const url = this.url.trim();
                    if (!url) {
                        this.platform = null;
                        this.videoId = null;
                        return;
                    }

                    // YouTube
                    const ytMatch = url.match(/(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/);
                    if (ytMatch) {
                        this.platform = 'youtube';
                        this.videoId = ytMatch[1];
                        return;
                    }

                    // Vimeo
                    const vimeoMatch = url.match(/(?:vimeo\.com\/)(\d+)/);
                    if (vimeoMatch) {
                        this.platform = 'vimeo';
                        this.videoId = vimeoMatch[1];
                        return;
                    }

                    this.platform = 'unknown';
                    this.videoId = null;
                },

                getThumbnail() {
                    if (this.platform === 'youtube' && this.videoId) {
                        return `https://img.youtube.com/vi/${this.videoId}/mqdefault.jpg`;
                    }
                    return null;
                }
            }">
                {{-- URL Input --}}
                <div class="ve-video-field__input-wrapper">
                    <input
                        type="url"
                        x-model="url"
                        @input.debounce.300ms="detectPlatform()"
                        @change="$wire.updateBlockProperty({{ $index }}, '{{ $name }}', url)"
                        class="ve-property-input w-full"
                        placeholder="https://youtube.com/watch?v=... lub https://vimeo.com/..."
                    >
                    {{-- Platform indicator --}}
                    <div class="ve-video-field__platform">
                        <template x-if="platform === 'youtube'">
                            <span class="ve-video-field__badge ve-video-field__badge--youtube">
                                <svg class="w-3 h-3" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                                </svg>
                                YouTube
                            </span>
                        </template>
                        <template x-if="platform === 'vimeo'">
                            <span class="ve-video-field__badge ve-video-field__badge--vimeo">
                                <svg class="w-3 h-3" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M23.977 6.416c-.105 2.338-1.739 5.543-4.894 9.609-3.268 4.247-6.026 6.37-8.29 6.37-1.409 0-2.578-1.294-3.553-3.881L5.322 11.4C4.603 8.816 3.834 7.522 3.01 7.522c-.179 0-.806.378-1.881 1.132L0 7.197c1.185-1.044 2.351-2.084 3.501-3.128C5.08 2.701 6.266 1.984 7.055 1.91c1.867-.18 3.016 1.1 3.447 3.838.465 2.953.789 4.789.971 5.507.539 2.45 1.131 3.674 1.776 3.674.502 0 1.256-.796 2.265-2.385 1.004-1.589 1.54-2.797 1.612-3.628.144-1.371-.395-2.061-1.614-2.061-.574 0-1.167.121-1.777.391 1.186-3.868 3.434-5.757 6.762-5.637 2.473.06 3.628 1.664 3.493 4.797l-.013.01z"/>
                                </svg>
                                Vimeo
                            </span>
                        </template>
                        <template x-if="platform === 'unknown'">
                            <span class="ve-video-field__badge ve-video-field__badge--unknown">
                                Nierozpoznano
                            </span>
                        </template>
                    </div>
                </div>

                {{-- Video Preview/Thumbnail --}}
                <template x-if="platform === 'youtube' && videoId">
                    <div class="ve-video-field__preview">
                        <img :src="getThumbnail()" alt="Video thumbnail" class="ve-video-field__thumbnail">
                        <div class="ve-video-field__play-icon">
                            <svg class="w-10 h-10" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M8 5v14l11-7z"/>
                            </svg>
                        </div>
                    </div>
                </template>

                {{-- Lazy facade option --}}
                <label class="ve-video-field__lazy-option">
                    <input
                        type="checkbox"
                        class="checkbox-enterprise"
                        wire:change="updateBlockProperty({{ $index }}, '{{ $name }}_lazy', $event.target.checked)"
                        {{ ($this->blocks[$index]['data'][$name . '_lazy'] ?? true) ? 'checked' : '' }}
                    >
                    <span class="text-sm text-gray-400">Leniwe ladowanie (fasada)</span>
                </label>
            </div>
            @break

        @case('gallery')
            <div class="ve-gallery-field" x-data="{
                images: {{ json_encode($value ?? []) }},
                draggingIndex: null,

                addImages(newImages) {
                    this.images = [...this.images, ...newImages];
                    this.save();
                },

                removeImage(index) {
                    this.images.splice(index, 1);
                    this.save();
                },

                moveImage(fromIndex, toIndex) {
                    const item = this.images.splice(fromIndex, 1)[0];
                    this.images.splice(toIndex, 0, item);
                    this.save();
                },

                save() {
                    $wire.updateBlockProperty({{ $index }}, '{{ $name }}', this.images);
                }
            }">
                {{-- Gallery Grid --}}
                <div class="ve-gallery-field__grid">
                    <template x-for="(image, idx) in images" :key="idx">
                        <div
                            class="ve-gallery-field__item"
                            :class="{ 've-gallery-field__item--dragging': draggingIndex === idx }"
                            draggable="true"
                            @dragstart="draggingIndex = idx"
                            @dragend="draggingIndex = null"
                            @dragover.prevent
                            @drop.prevent="
                                if (draggingIndex !== null && draggingIndex !== idx) {
                                    moveImage(draggingIndex, idx);
                                }
                                draggingIndex = null;
                            "
                        >
                            <img :src="image" alt="Gallery image" class="ve-gallery-field__image">
                            <div class="ve-gallery-field__item-overlay">
                                <button
                                    type="button"
                                    @click="removeImage(idx)"
                                    class="ve-gallery-field__remove"
                                    title="Usun"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                            <span class="ve-gallery-field__order" x-text="idx + 1"></span>
                        </div>
                    </template>

                    {{-- Add Button --}}
                    <div
                        class="ve-gallery-field__add"
                        wire:click="openMediaPicker({{ $index }}, '{{ $name }}', true)"
                    >
                        <svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span class="text-xs text-gray-500">Dodaj</span>
                    </div>
                </div>

                {{-- Gallery Info --}}
                <p class="ve-gallery-field__info">
                    <span x-text="images.length"></span> obrazow | Przeciagnij aby zmienic kolejnosc
                </p>
            </div>
            @break

        @case('icon')
            <div x-data="{ showPicker: false }">
                <button
                    @click="showPicker = !showPicker"
                    class="w-full px-3 py-2 flex items-center justify-between bg-gray-900 border border-gray-700 rounded-lg text-gray-300 hover:border-gray-600 transition"
                >
                    <span class="text-2xl">{{ $value ?? '📦' }}</span>
                    <span class="text-sm text-gray-500">Zmien ikone</span>
                </button>
                {{-- Icon picker would expand here --}}
            </div>
            @break

        @case('alignment')
            <div class="flex items-center gap-1 bg-gray-900 border border-gray-700 rounded-lg p-1">
                @foreach(['left' => 'L', 'center' => 'C', 'right' => 'R'] as $align => $label)
                    <button
                        wire:click="updateBlockProperty({{ $index }}, '{{ $name }}', '{{ $align }}')"
                        class="flex-1 px-3 py-1.5 text-xs font-medium rounded transition
                            {{ $value === $align ? 'bg-blue-500 text-white' : 'text-gray-400 hover:text-gray-200' }}"
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>
            @break

        @case('boolean')
            <label class="flex items-center gap-2 cursor-pointer">
                <input
                    type="checkbox"
                    class="checkbox-enterprise"
                    wire:change="updateBlockProperty({{ $index }}, '{{ $name }}', $event.target.checked)"
                    {{ $value ? 'checked' : '' }}
                >
                <span class="text-sm text-gray-300">{{ $config['checkboxLabel'] ?? $label }}</span>
            </label>
            @if($config['help'] ?? null)
                <p class="mt-1 text-xs text-amber-500">{{ $config['help'] }}</p>
            @endif
            @break

        @case('code')
            @php
                $language = $config['language'] ?? 'html';
                $rows = $config['rows'] ?? 10;
                $languageLabels = [
                    'html' => 'HTML',
                    'css' => 'CSS',
                    'javascript' => 'JavaScript',
                    'js' => 'JavaScript',
                ];
            @endphp
            <div class="ve-code-field">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-xs text-gray-500 uppercase tracking-wider">{{ $languageLabels[$language] ?? strtoupper($language) }}</span>
                    <div class="flex items-center gap-2">
                        @if($language === 'html')
                        <button
                            type="button"
                            wire:click="openBlockInVBB({{ $index }})"
                            class="text-xs text-blue-400 hover:text-blue-300 transition flex items-center gap-1"
                            title="Edytuj wizualnie"
                        >
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            VBB
                        </button>
                        @endif
                        <button
                            type="button"
                            x-data
                            @click="
                                const textarea = $el.closest('.ve-code-field').querySelector('textarea');
                                textarea.select();
                                document.execCommand('copy');
                                $el.textContent = 'Skopiowano!';
                                setTimeout(() => $el.textContent = 'Kopiuj', 1500);
                            "
                            class="text-xs text-gray-500 hover:text-gray-300 transition"
                        >
                            Kopiuj
                        </button>
                    </div>
                </div>
                <textarea
                    rows="{{ $rows }}"
                    class="w-full px-3 py-2 bg-gray-950 border border-gray-700 rounded-lg text-gray-200 text-sm font-mono focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-y"
                    wire:change="updateBlockProperty({{ $index }}, '{{ $name }}', $event.target.value)"
                    placeholder="{{ $placeholder }}"
                    spellcheck="false"
                >{{ $value ?? '' }}</textarea>
            </div>
            @break

        @default
            <input
                type="text"
                class="ve-property-input w-full"
                wire:change="updateBlockProperty({{ $index }}, '{{ $name }}', $event.target.value)"
                value="{{ $value ?? '' }}"
            >
    @endswitch

    @if($description)
        <p class="mt-1 text-xs text-gray-500">{{ $description }}</p>
    @endif
</div>

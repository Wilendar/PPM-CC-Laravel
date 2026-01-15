@php
    $element = $this->selectedElement;
    $elementId = $element['id'] ?? null;
    $elementType = $element['type'] ?? 'unknown';
    $styles = $element['styles'] ?? [];
@endphp

<div class="p-4 space-y-4">
    @if($element)
        {{-- Element Info --}}
        <div class="pb-3 border-b border-gray-700">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-white capitalize">{{ $elementType }}</span>
                <span class="text-xs text-gray-500">{{ $elementId }}</span>
            </div>
        </div>

        {{-- Content Section (for text-based elements) - WYSIWYG Editor --}}
        @if(in_array($elementType, ['heading', 'text', 'button']))
        <div class="space-y-2"
             x-data="wysiwygEditor({
                 content: @js($element['content'] ?? ''),
                 elementId: '{{ $elementId }}',
                 isButton: {{ $elementType === 'button' ? 'true' : 'false' }}
             })"
        >
            <label class="block text-xs font-medium text-gray-400 uppercase tracking-wider">Tresc</label>

            {{-- WYSIWYG Toolbar --}}
            @if($elementType !== 'button')
            <div class="flex flex-wrap items-center gap-1 p-1.5 bg-gray-700 border border-gray-600 rounded-t-lg border-b-0">
                {{-- Bold --}}
                <button
                    type="button"
                    @click="execCommand('bold')"
                    :class="{ 'bg-amber-500/30 text-amber-400': isActive('bold') }"
                    class="p-1.5 text-gray-400 hover:text-white hover:bg-gray-600 rounded transition-colors"
                    title="Pogrubienie (Ctrl+B)"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 4h8a4 4 0 014 4 4 4 0 01-4 4H6z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 12h9a4 4 0 014 4 4 4 0 01-4 4H6z"/>
                    </svg>
                </button>

                {{-- Italic --}}
                <button
                    type="button"
                    @click="execCommand('italic')"
                    :class="{ 'bg-amber-500/30 text-amber-400': isActive('italic') }"
                    class="p-1.5 text-gray-400 hover:text-white hover:bg-gray-600 rounded transition-colors"
                    title="Kursywa (Ctrl+I)"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 4h4m-2 0l-2 16m-4 0h4"/>
                    </svg>
                </button>

                {{-- Underline --}}
                <button
                    type="button"
                    @click="execCommand('underline')"
                    :class="{ 'bg-amber-500/30 text-amber-400': isActive('underline') }"
                    class="p-1.5 text-gray-400 hover:text-white hover:bg-gray-600 rounded transition-colors"
                    title="Podkreslenie (Ctrl+U)"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v7a5 5 0 0010 0V4M5 20h14"/>
                    </svg>
                </button>

                {{-- Strikethrough --}}
                <button
                    type="button"
                    @click="execCommand('strikeThrough')"
                    :class="{ 'bg-amber-500/30 text-amber-400': isActive('strikeThrough') }"
                    class="p-1.5 text-gray-400 hover:text-white hover:bg-gray-600 rounded transition-colors"
                    title="Przekreslenie"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.5 12h-15m11.667-4c-.92-1.38-2.333-2-4.167-2-3.867 0-5.333 3-5.333 4s.533 2 2 2h4m-2.333 6c1.375 1.125 3.167 1.5 4.667 1 1.375-.5 2.5-1.75 2.667-3"/>
                    </svg>
                </button>

                <div class="w-px h-5 bg-gray-600 mx-1"></div>

                {{-- Link --}}
                <button
                    type="button"
                    @click="insertLink()"
                    :class="{ 'bg-amber-500/30 text-amber-400': isActive('createLink') }"
                    class="p-1.5 text-gray-400 hover:text-white hover:bg-gray-600 rounded transition-colors"
                    title="Wstaw link (Ctrl+K)"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                    </svg>
                </button>

                {{-- Remove Link --}}
                <button
                    type="button"
                    @click="execCommand('unlink')"
                    class="p-1.5 text-gray-400 hover:text-white hover:bg-gray-600 rounded transition-colors"
                    title="Usun link"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                    </svg>
                </button>

                <div class="w-px h-5 bg-gray-600 mx-1"></div>

                {{-- Unordered List --}}
                <button
                    type="button"
                    @click="execCommand('insertUnorderedList')"
                    :class="{ 'bg-amber-500/30 text-amber-400': isActive('insertUnorderedList') }"
                    class="p-1.5 text-gray-400 hover:text-white hover:bg-gray-600 rounded transition-colors"
                    title="Lista punktowa"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        <circle cx="2" cy="6" r="1" fill="currentColor"/>
                        <circle cx="2" cy="12" r="1" fill="currentColor"/>
                        <circle cx="2" cy="18" r="1" fill="currentColor"/>
                    </svg>
                </button>

                {{-- Ordered List --}}
                <button
                    type="button"
                    @click="execCommand('insertOrderedList')"
                    :class="{ 'bg-amber-500/30 text-amber-400': isActive('insertOrderedList') }"
                    class="p-1.5 text-gray-400 hover:text-white hover:bg-gray-600 rounded transition-colors"
                    title="Lista numerowana"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 6h13M7 12h13M7 18h13"/>
                        <text x="1" y="7" font-size="6" fill="currentColor">1</text>
                        <text x="1" y="13" font-size="6" fill="currentColor">2</text>
                        <text x="1" y="19" font-size="6" fill="currentColor">3</text>
                    </svg>
                </button>

                <div class="w-px h-5 bg-gray-600 mx-1"></div>

                {{-- Clear Formatting --}}
                <button
                    type="button"
                    @click="execCommand('removeFormat')"
                    class="p-1.5 text-gray-400 hover:text-white hover:bg-gray-600 rounded transition-colors"
                    title="Usun formatowanie"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </button>

                {{-- HTML Mode Toggle --}}
                <button
                    type="button"
                    @click="toggleHtmlMode()"
                    :class="{ 'bg-amber-500/30 text-amber-400': htmlMode }"
                    class="ml-auto p-1.5 text-gray-400 hover:text-white hover:bg-gray-600 rounded transition-colors"
                    title="Tryb HTML"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                    </svg>
                </button>
            </div>
            @endif

            {{-- Editable Area --}}
            <div
                x-show="!htmlMode"
                x-ref="editor"
                contenteditable="true"
                @input="onInput($event)"
                @blur="syncContent()"
                @keydown.ctrl.b.prevent="execCommand('bold')"
                @keydown.ctrl.i.prevent="execCommand('italic')"
                @keydown.ctrl.u.prevent="execCommand('underline')"
                @keydown.ctrl.k.prevent="insertLink()"
                @class([
                    'w-full px-3 py-2 bg-gray-700 border border-gray-600 text-white text-sm focus:ring-amber-500 focus:border-amber-500 focus:outline-none min-h-[80px] max-h-[200px] overflow-y-auto',
                    'rounded-b-lg' => $elementType !== 'button',
                    'rounded-lg' => $elementType === 'button',
                ])
                x-html="content"
            ></div>

            {{-- HTML Source View --}}
            @if($elementType !== 'button')
            <textarea
                x-show="htmlMode"
                x-model="content"
                @blur="syncContent()"
                rows="6"
                class="w-full px-3 py-2 bg-gray-900 border border-gray-600 rounded-b-lg text-green-400 text-sm font-mono focus:ring-amber-500 focus:border-amber-500"
            ></textarea>
            @endif

            {{-- Character count --}}
            <div class="flex justify-between items-center text-xs text-gray-500">
                <span x-text="getPlainText().length + ' znakow'"></span>
                <span x-show="hasHtml()" class="text-amber-500 text-[10px]">Zawiera formatowanie HTML</span>
            </div>
        </div>
        @endif

        {{-- Tag Selection (for heading) --}}
        @if($elementType === 'heading')
        <div class="space-y-2">
            <label class="block text-xs font-medium text-gray-400 uppercase tracking-wider">Poziom naglowka</label>
            <select
                wire:change="updateElementProperty('{{ $elementId }}', 'tag', $event.target.value)"
                class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white text-sm focus:ring-amber-500 focus:border-amber-500"
            >
                @foreach(['h1', 'h2', 'h3', 'h4', 'h5', 'h6'] as $tag)
                    <option value="{{ $tag }}" @selected(($element['tag'] ?? 'h2') === $tag)>{{ strtoupper($tag) }}</option>
                @endforeach
            </select>
        </div>
        @endif

        {{-- Image Source (for image) --}}
        @if($elementType === 'image')
        <div class="space-y-2">
            <label class="block text-xs font-medium text-gray-400 uppercase tracking-wider">URL obrazu</label>
            <input
                type="url"
                wire:change="updateElementProperty('{{ $elementId }}', 'src', $event.target.value)"
                value="{{ $element['src'] ?? '' }}"
                placeholder="https://..."
                class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white text-sm focus:ring-amber-500 focus:border-amber-500"
            >
        </div>
        <div class="space-y-2">
            <label class="block text-xs font-medium text-gray-400 uppercase tracking-wider">Tekst alternatywny</label>
            <input
                type="text"
                wire:change="updateElementProperty('{{ $elementId }}', 'alt', $event.target.value)"
                value="{{ $element['alt'] ?? '' }}"
                placeholder="Opis obrazu"
                class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white text-sm focus:ring-amber-500 focus:border-amber-500"
            >
        </div>

        {{-- Image Fit --}}
        <div class="space-y-2">
            <label class="block text-xs font-medium text-gray-400 uppercase tracking-wider">Dopasowanie</label>
            <select
                wire:change="updateElementProperty('{{ $elementId }}', 'styles.objectFit', $event.target.value)"
                class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white text-sm focus:ring-amber-500 focus:border-amber-500"
            >
                @foreach(['cover' => 'Wypelnij (cover)', 'contain' => 'Dopasuj (contain)', 'fill' => 'Rozciagnij (fill)', 'none' => 'Oryginalny (none)', 'scale-down' => 'Zmniejsz jesli potrzeba'] as $value => $label)
                    <option value="{{ $value }}" @selected(($styles['objectFit'] ?? 'cover') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        {{-- Image Position --}}
        <div class="space-y-2">
            <label class="block text-xs font-medium text-gray-400 uppercase tracking-wider">Pozycja obrazu</label>
            <div class="grid grid-cols-3 gap-1">
                @foreach([
                    'top left' => 'TL', 'top center' => 'TC', 'top right' => 'TR',
                    'center left' => 'CL', 'center center' => 'CC', 'center right' => 'CR',
                    'bottom left' => 'BL', 'bottom center' => 'BC', 'bottom right' => 'BR'
                ] as $value => $label)
                    <button
                        wire:click="updateElementProperty('{{ $elementId }}', 'styles.objectPosition', '{{ $value }}')"
                        @class([
                            'py-1.5 text-xs font-medium rounded transition-colors',
                            'bg-amber-500 text-white' => ($styles['objectPosition'] ?? 'center center') === $value,
                            'bg-gray-700 text-gray-400 hover:bg-gray-600' => ($styles['objectPosition'] ?? 'center center') !== $value,
                        ])
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Image Dimensions --}}
        <div class="grid grid-cols-2 gap-2">
            <div class="space-y-1">
                <label class="text-xs text-gray-500">Szerokosc</label>
                <input
                    type="text"
                    wire:change="updateElementProperty('{{ $elementId }}', 'styles.width', $event.target.value)"
                    value="{{ $styles['width'] ?? '100%' }}"
                    placeholder="100%"
                    class="w-full px-2 py-1.5 bg-gray-700 border border-gray-600 rounded text-white text-sm"
                >
            </div>
            <div class="space-y-1">
                <label class="text-xs text-gray-500">Wysokosc</label>
                <input
                    type="text"
                    wire:change="updateElementProperty('{{ $elementId }}', 'styles.height', $event.target.value)"
                    value="{{ $styles['height'] ?? 'auto' }}"
                    placeholder="auto"
                    class="w-full px-2 py-1.5 bg-gray-700 border border-gray-600 rounded text-white text-sm"
                >
            </div>
        </div>

        {{-- Border Radius for Images --}}
        <div class="space-y-1">
            <label class="text-xs text-gray-500">Zaokraglenie</label>
            <div class="flex gap-1">
                @foreach(['0' => 'Brak', '0.5rem' => 'S', '1rem' => 'M', '1.5rem' => 'L', '50%' => 'Okrag'] as $value => $label)
                    <button
                        wire:click="updateElementProperty('{{ $elementId }}', 'styles.borderRadius', '{{ $value }}')"
                        @class([
                            'flex-1 py-1.5 text-xs font-medium rounded transition-colors',
                            'bg-amber-500 text-white' => ($styles['borderRadius'] ?? '0') === $value,
                            'bg-gray-700 text-gray-400 hover:bg-gray-600' => ($styles['borderRadius'] ?? '0') !== $value,
                        ])
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Button URL (for button) --}}
        @if($elementType === 'button')
        <div class="space-y-2">
            <label class="block text-xs font-medium text-gray-400 uppercase tracking-wider">URL przycisku</label>
            <input
                type="url"
                wire:change="updateElementProperty('{{ $elementId }}', 'href', $event.target.value)"
                value="{{ $element['href'] ?? '#' }}"
                placeholder="https://..."
                class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white text-sm focus:ring-amber-500 focus:border-amber-500"
            >
        </div>

        {{-- Button Style Variants --}}
        <div class="space-y-2">
            <label class="block text-xs font-medium text-gray-400 uppercase tracking-wider">Styl przycisku</label>
            @php
                $buttonVariants = [
                    'primary' => [
                        'label' => 'Glowny',
                        'preview' => 'bg-gradient-to-r from-amber-500 to-amber-600',
                        'styles' => [
                            'backgroundColor' => '#e0ac7e',
                            'color' => '#ffffff',
                            'padding' => '0.75rem 1.5rem',
                            'borderRadius' => '0.5rem',
                        ]
                    ],
                    'secondary' => [
                        'label' => 'Drugorzedny',
                        'preview' => 'bg-gray-600',
                        'styles' => [
                            'backgroundColor' => '#4b5563',
                            'color' => '#ffffff',
                            'padding' => '0.75rem 1.5rem',
                            'borderRadius' => '0.5rem',
                        ]
                    ],
                    'outline' => [
                        'label' => 'Konturowy',
                        'preview' => 'bg-transparent border-2 border-amber-500',
                        'styles' => [
                            'backgroundColor' => 'transparent',
                            'color' => '#e0ac7e',
                            'padding' => '0.75rem 1.5rem',
                            'borderRadius' => '0.5rem',
                            'border' => '2px solid #e0ac7e',
                        ]
                    ],
                    'ghost' => [
                        'label' => 'Duch',
                        'preview' => 'bg-amber-500/10',
                        'styles' => [
                            'backgroundColor' => 'rgba(224, 172, 126, 0.1)',
                            'color' => '#e0ac7e',
                            'padding' => '0.75rem 1.5rem',
                            'borderRadius' => '0.5rem',
                        ]
                    ],
                    'danger' => [
                        'label' => 'Niebezpieczny',
                        'preview' => 'bg-red-600',
                        'styles' => [
                            'backgroundColor' => '#dc2626',
                            'color' => '#ffffff',
                            'padding' => '0.75rem 1.5rem',
                            'borderRadius' => '0.5rem',
                        ]
                    ],
                    'success' => [
                        'label' => 'Sukces',
                        'preview' => 'bg-green-600',
                        'styles' => [
                            'backgroundColor' => '#059669',
                            'color' => '#ffffff',
                            'padding' => '0.75rem 1.5rem',
                            'borderRadius' => '0.5rem',
                        ]
                    ],
                ];
            @endphp
            <div class="grid grid-cols-2 gap-2">
                @foreach($buttonVariants as $variant => $config)
                    <button
                        type="button"
                        wire:click="applyButtonVariant('{{ $elementId }}', '{{ $variant }}')"
                        class="flex flex-col items-center gap-1 p-2 bg-gray-700/50 hover:bg-gray-700 rounded-lg transition-colors"
                    >
                        <span class="w-full h-6 rounded {{ $config['preview'] }}"></span>
                        <span class="text-xs text-gray-400">{{ $config['label'] }}</span>
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Button Size --}}
        <div class="space-y-2">
            <label class="block text-xs font-medium text-gray-400 uppercase tracking-wider">Rozmiar</label>
            <div class="flex gap-1">
                @foreach([
                    'sm' => ['label' => 'S', 'padding' => '0.5rem 1rem', 'fontSize' => '0.875rem'],
                    'md' => ['label' => 'M', 'padding' => '0.75rem 1.5rem', 'fontSize' => '1rem'],
                    'lg' => ['label' => 'L', 'padding' => '1rem 2rem', 'fontSize' => '1.125rem'],
                    'xl' => ['label' => 'XL', 'padding' => '1.25rem 2.5rem', 'fontSize' => '1.25rem'],
                ] as $size => $config)
                    <button
                        wire:click="applyButtonSize('{{ $elementId }}', '{{ $size }}')"
                        @class([
                            'flex-1 py-1.5 text-xs font-medium rounded transition-colors',
                            'bg-amber-500 text-white' => (($styles['padding'] ?? '0.75rem 1.5rem') === $config['padding']),
                            'bg-gray-700 text-gray-400 hover:bg-gray-600' => (($styles['padding'] ?? '0.75rem 1.5rem') !== $config['padding']),
                        ])
                    >
                        {{ $config['label'] }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Button Width --}}
        <div class="space-y-2">
            <label class="block text-xs font-medium text-gray-400 uppercase tracking-wider">Szerokosc</label>
            <div class="flex gap-1">
                @foreach(['auto' => 'Auto', '100%' => 'Pelna'] as $value => $label)
                    <button
                        wire:click="updateElementProperty('{{ $elementId }}', 'styles.width', '{{ $value }}')"
                        @class([
                            'flex-1 py-1.5 text-xs font-medium rounded transition-colors',
                            'bg-amber-500 text-white' => ($styles['width'] ?? 'auto') === $value,
                            'bg-gray-700 text-gray-400 hover:bg-gray-600' => ($styles['width'] ?? 'auto') !== $value,
                        ])
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Icon Picker (for icon) --}}
        @if($elementType === 'icon')
        <div class="space-y-2 relative" x-data="{ showIconPicker: false }">
            <label class="block text-xs font-medium text-gray-400 uppercase tracking-wider">Ikona</label>

            {{-- Current Icon Display --}}
            <button
                type="button"
                @click="showIconPicker = !showIconPicker"
                class="w-full flex items-center justify-between px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white text-sm hover:bg-gray-600 transition-colors"
            >
                <span class="flex items-center gap-2">
                    <span class="{{ $element['iconClass'] ?? 'pd-icon--check' }}"></span>
                    <span>{{ str_replace('pd-icon--', '', $element['iconClass'] ?? 'check') }}</span>
                </span>
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            {{-- Icon Picker Grid --}}
            <div
                x-show="showIconPicker"
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 transform scale-95"
                x-transition:enter-end="opacity-100 transform scale-100"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100 transform scale-100"
                x-transition:leave-end="opacity-0 transform scale-95"
                @click.away="showIconPicker = false"
                class="absolute z-50 left-0 right-0 mt-1 bg-gray-800 border border-gray-600 rounded-lg shadow-xl p-2 max-h-64 overflow-y-auto"
            >
                <div class="grid grid-cols-5 gap-1">
                    @php
                        $icons = [
                            // Produkty i zakupy
                            'wallet', 'cart', 'bag', 'tag', 'gift', 'box', 'package',
                            // Transport
                            'truck', 'delivery', 'shipping', 'plane', 'car',
                            // Czas
                            'clock', 'timer', 'calendar', 'hourglass',
                            // Bezpieczenstwo
                            'shield', 'lock', 'key', 'check', 'verified',
                            // Kontakt
                            'phone', 'email', 'message', 'chat', 'support',
                            // Narzedzia
                            'cog', 'settings', 'wrench', 'tool', 'hammer',
                            // Oceny
                            'star', 'heart', 'like', 'trophy', 'medal',
                            // Informacje
                            'info', 'question', 'alert', 'warning', 'bolt',
                            // Finanse
                            'money', 'credit-card', 'bank', 'percent', 'discount',
                            // Inne
                            'home', 'user', 'users', 'location', 'map',
                            'document', 'file', 'folder', 'download', 'upload',
                            'refresh', 'sync', 'arrow-right', 'arrow-left', 'external',
                        ];
                    @endphp
                    @foreach($icons as $icon)
                        <button
                            type="button"
                            wire:click="updateElementProperty('{{ $elementId }}', 'iconClass', 'pd-icon--{{ $icon }}')"
                            @click="showIconPicker = false"
                            @class([
                                'flex items-center justify-center p-2 rounded hover:bg-gray-700 transition-colors',
                                'bg-amber-500/20 ring-1 ring-amber-500' => ($element['iconClass'] ?? '') === "pd-icon--{$icon}",
                            ])
                            title="{{ $icon }}"
                        >
                            <span class="pd-icon pd-icon--{{ $icon }} text-lg"></span>
                        </button>
                    @endforeach
                </div>

                {{-- Custom Class Input --}}
                <div class="mt-2 pt-2 border-t border-gray-700">
                    <input
                        type="text"
                        wire:change="updateElementProperty('{{ $elementId }}', 'iconClass', $event.target.value)"
                        value="{{ $element['iconClass'] ?? 'pd-icon--check' }}"
                        placeholder="Wlasna klasa ikony..."
                        class="w-full px-2 py-1.5 bg-gray-700 border border-gray-600 rounded text-white text-xs focus:ring-amber-500 focus:border-amber-500"
                    >
                </div>
            </div>

            {{-- Icon Size --}}
            <div class="space-y-1 mt-3">
                <label class="text-xs text-gray-500">Rozmiar</label>
                <select
                    wire:change="updateElementProperty('{{ $elementId }}', 'styles.fontSize', $event.target.value)"
                    class="w-full px-2 py-1.5 bg-gray-700 border border-gray-600 rounded text-white text-sm"
                >
                    @foreach(['1rem' => 'S (16px)', '1.5rem' => 'M (24px)', '2rem' => 'L (32px)', '2.5rem' => 'XL (40px)', '3rem' => '2XL (48px)', '4rem' => '3XL (64px)'] as $value => $label)
                        <option value="{{ $value }}" @selected(($styles['fontSize'] ?? '2rem') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Icon Color --}}
            <div class="space-y-1">
                <label class="text-xs text-gray-500">Kolor</label>
                <div class="flex gap-2">
                    <input
                        type="color"
                        wire:change="updateElementProperty('{{ $elementId }}', 'styles.color', $event.target.value)"
                        value="{{ $styles['color'] ?? '#e0ac7e' }}"
                        class="w-10 h-8 bg-gray-700 border border-gray-600 rounded cursor-pointer"
                    >
                    <input
                        type="text"
                        wire:change="updateElementProperty('{{ $elementId }}', 'styles.color', $event.target.value)"
                        value="{{ $styles['color'] ?? '#e0ac7e' }}"
                        class="flex-1 px-2 py-1.5 bg-gray-700 border border-gray-600 rounded text-white text-sm"
                    >
                </div>
            </div>
        </div>
        @endif

        {{-- Typography Section --}}
        @if(in_array($elementType, ['heading', 'text', 'button']))
        <div class="space-y-3 pt-3 border-t border-gray-700">
            <h4 class="text-xs font-medium text-gray-400 uppercase tracking-wider">Typografia</h4>

            <div class="grid grid-cols-2 gap-2">
                {{-- Font Size --}}
                <div class="space-y-1">
                    <label class="text-xs text-gray-500">Rozmiar</label>
                    <input
                        type="text"
                        wire:change="updateElementProperty('{{ $elementId }}', 'styles.fontSize', $event.target.value)"
                        value="{{ $styles['fontSize'] ?? '1rem' }}"
                        class="w-full px-2 py-1.5 bg-gray-700 border border-gray-600 rounded text-white text-sm"
                    >
                </div>

                {{-- Font Weight --}}
                <div class="space-y-1">
                    <label class="text-xs text-gray-500">Grubosc</label>
                    <select
                        wire:change="updateElementProperty('{{ $elementId }}', 'styles.fontWeight', $event.target.value)"
                        class="w-full px-2 py-1.5 bg-gray-700 border border-gray-600 rounded text-white text-sm"
                    >
                        @foreach(['300' => 'Light', '400' => 'Normal', '500' => 'Medium', '600' => 'Semi Bold', '700' => 'Bold', '800' => 'Extra Bold'] as $value => $label)
                            <option value="{{ $value }}" @selected(($styles['fontWeight'] ?? '400') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Text Align --}}
            <div class="space-y-1">
                <label class="text-xs text-gray-500">Wyrownanie</label>
                <div class="flex gap-1">
                    @foreach(['left' => 'L', 'center' => 'C', 'right' => 'R', 'justify' => 'J'] as $value => $label)
                        <button
                            wire:click="updateElementProperty('{{ $elementId }}', 'styles.textAlign', '{{ $value }}')"
                            @class([
                                'flex-1 py-1.5 text-xs font-medium rounded transition-colors',
                                'bg-amber-500 text-white' => ($styles['textAlign'] ?? 'left') === $value,
                                'bg-gray-700 text-gray-400 hover:bg-gray-600' => ($styles['textAlign'] ?? 'left') !== $value,
                            ])
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Text Color --}}
            <div class="space-y-1">
                <label class="text-xs text-gray-500">Kolor tekstu</label>
                <div class="flex gap-2">
                    <input
                        type="color"
                        wire:change="updateElementProperty('{{ $elementId }}', 'styles.color', $event.target.value)"
                        value="{{ $styles['color'] ?? '#000000' }}"
                        class="w-10 h-8 bg-gray-700 border border-gray-600 rounded cursor-pointer"
                    >
                    <input
                        type="text"
                        wire:change="updateElementProperty('{{ $elementId }}', 'styles.color', $event.target.value)"
                        value="{{ $styles['color'] ?? '#000000' }}"
                        class="flex-1 px-2 py-1.5 bg-gray-700 border border-gray-600 rounded text-white text-sm"
                    >
                </div>
            </div>
        </div>
        @endif

        {{-- Layout Section (for containers) --}}
        @if(in_array($elementType, ['container', 'row', 'column']))
        <div class="space-y-3 pt-3 border-t border-gray-700">
            <h4 class="text-xs font-medium text-gray-400 uppercase tracking-wider">Layout</h4>

            {{-- Flex Direction --}}
            <div class="space-y-1">
                <label class="text-xs text-gray-500">Kierunek</label>
                <div class="flex gap-1">
                    @foreach(['row' => 'Poziomo', 'column' => 'Pionowo'] as $value => $label)
                        <button
                            wire:click="updateElementProperty('{{ $elementId }}', 'styles.flexDirection', '{{ $value }}')"
                            @class([
                                'flex-1 py-1.5 text-xs font-medium rounded transition-colors',
                                'bg-amber-500 text-white' => ($styles['flexDirection'] ?? ($elementType === 'column' ? 'column' : 'row')) === $value,
                                'bg-gray-700 text-gray-400 hover:bg-gray-600' => ($styles['flexDirection'] ?? ($elementType === 'column' ? 'column' : 'row')) !== $value,
                            ])
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Justify Content --}}
            <div class="space-y-1">
                <label class="text-xs text-gray-500">Wyrownanie glowne</label>
                <select
                    wire:change="updateElementProperty('{{ $elementId }}', 'styles.justifyContent', $event.target.value)"
                    class="w-full px-2 py-1.5 bg-gray-700 border border-gray-600 rounded text-white text-sm"
                >
                    @foreach(['flex-start' => 'Start', 'center' => 'Srodek', 'flex-end' => 'Koniec', 'space-between' => 'Rozlozony', 'space-around' => 'Wokol'] as $value => $label)
                        <option value="{{ $value }}" @selected(($styles['justifyContent'] ?? 'flex-start') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Align Items --}}
            <div class="space-y-1">
                <label class="text-xs text-gray-500">Wyrownanie poprzeczne</label>
                <select
                    wire:change="updateElementProperty('{{ $elementId }}', 'styles.alignItems', $event.target.value)"
                    class="w-full px-2 py-1.5 bg-gray-700 border border-gray-600 rounded text-white text-sm"
                >
                    @foreach(['flex-start' => 'Start', 'center' => 'Srodek', 'flex-end' => 'Koniec', 'stretch' => 'Rozciagnij'] as $value => $label)
                        <option value="{{ $value }}" @selected(($styles['alignItems'] ?? ($elementType === 'column' ? 'stretch' : 'center')) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Gap --}}
            <div class="space-y-1">
                <label class="text-xs text-gray-500">Odstep (gap)</label>
                <input
                    type="text"
                    wire:change="updateElementProperty('{{ $elementId }}', 'styles.gap', $event.target.value)"
                    value="{{ $styles['gap'] ?? '1rem' }}"
                    class="w-full px-2 py-1.5 bg-gray-700 border border-gray-600 rounded text-white text-sm"
                >
            </div>

            {{-- Flex Property (for column inside row) --}}
            @if($elementType === 'column')
            <div class="space-y-1">
                <label class="text-xs text-gray-500">Flex (rozciaganie)</label>
                <div class="flex gap-1">
                    @foreach(['1' => 'Rozciagnij', '0 0 auto' => 'Auto', '0 0 50%' => '50%', '0 0 33%' => '33%'] as $value => $label)
                        <button
                            wire:click="updateElementProperty('{{ $elementId }}', 'styles.flex', '{{ $value }}')"
                            @class([
                                'flex-1 py-1.5 text-xs font-medium rounded transition-colors',
                                'bg-amber-500 text-white' => ($styles['flex'] ?? '1') === $value,
                                'bg-gray-700 text-gray-400 hover:bg-gray-600' => ($styles['flex'] ?? '1') !== $value,
                            ])
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
        @endif

        {{-- Grid Layout Section --}}
        @if($elementType === 'grid')
        <div class="space-y-3 pt-3 border-t border-gray-700">
            <h4 class="text-xs font-medium text-gray-400 uppercase tracking-wider">Siatka CSS Grid</h4>

            {{-- Grid Columns --}}
            <div class="space-y-1">
                <label class="text-xs text-gray-500">Liczba kolumn</label>
                <div class="flex gap-1">
                    @foreach([1, 2, 3, 4, 5, 6] as $cols)
                        <button
                            wire:click="applyGridColumns('{{ $elementId }}', {{ $cols }})"
                            @class([
                                'flex-1 py-1.5 text-xs font-medium rounded transition-colors',
                                'bg-amber-500 text-white' => ($element['gridColumns'] ?? 2) == $cols,
                                'bg-gray-700 text-gray-400 hover:bg-gray-600' => ($element['gridColumns'] ?? 2) != $cols,
                            ])
                        >
                            {{ $cols }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Custom Grid Template --}}
            <div class="space-y-1">
                <label class="text-xs text-gray-500">Niestandardowy szablon</label>
                <input
                    type="text"
                    wire:change="updateElementProperty('{{ $elementId }}', 'styles.gridTemplateColumns', $event.target.value)"
                    value="{{ $styles['gridTemplateColumns'] ?? 'repeat(2, 1fr)' }}"
                    placeholder="np. 1fr 2fr 1fr"
                    class="w-full px-2 py-1.5 bg-gray-700 border border-gray-600 rounded text-white text-sm font-mono text-xs"
                >
            </div>

            {{-- Gap --}}
            <div class="space-y-1">
                <label class="text-xs text-gray-500">Odstep (gap)</label>
                <input
                    type="text"
                    wire:change="updateElementProperty('{{ $elementId }}', 'styles.gap', $event.target.value)"
                    value="{{ $styles['gap'] ?? '1rem' }}"
                    class="w-full px-2 py-1.5 bg-gray-700 border border-gray-600 rounded text-white text-sm"
                >
            </div>

            {{-- Align Items in Grid --}}
            <div class="space-y-1">
                <label class="text-xs text-gray-500">Wyrownanie elementow</label>
                <select
                    wire:change="updateElementProperty('{{ $elementId }}', 'styles.alignItems', $event.target.value)"
                    class="w-full px-2 py-1.5 bg-gray-700 border border-gray-600 rounded text-white text-sm"
                >
                    @foreach(['start' => 'Gora', 'center' => 'Srodek', 'end' => 'Dol', 'stretch' => 'Rozciagnij'] as $value => $label)
                        <option value="{{ $value }}" @selected(($styles['alignItems'] ?? 'stretch') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        @endif

        {{-- Background Element Section --}}
        @if($elementType === 'background')
        <div class="space-y-3 pt-3 border-t border-gray-700">
            <h4 class="text-xs font-medium text-gray-400 uppercase tracking-wider">Sekcja z tlem</h4>

            {{-- Background Image URL --}}
            <div class="space-y-1">
                <label class="text-xs text-gray-500">URL obrazu tla</label>
                <input
                    type="url"
                    wire:change="updateBackgroundImage('{{ $elementId }}', $event.target.value)"
                    value="{{ $element['backgroundImage'] ?? '' }}"
                    placeholder="https://..."
                    class="w-full px-2 py-1.5 bg-gray-700 border border-gray-600 rounded text-white text-sm"
                >
            </div>

            {{-- Background Size --}}
            <div class="space-y-1">
                <label class="text-xs text-gray-500">Rozmiar tla</label>
                <div class="flex gap-1">
                    @foreach(['cover' => 'Pokryj', 'contain' => 'Dopasuj', 'auto' => 'Auto'] as $value => $label)
                        <button
                            wire:click="updateElementProperty('{{ $elementId }}', 'styles.backgroundSize', '{{ $value }}')"
                            @class([
                                'flex-1 py-1.5 text-xs font-medium rounded transition-colors',
                                'bg-amber-500 text-white' => ($styles['backgroundSize'] ?? 'cover') === $value,
                                'bg-gray-700 text-gray-400 hover:bg-gray-600' => ($styles['backgroundSize'] ?? 'cover') !== $value,
                            ])
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Background Position --}}
            <div class="space-y-1">
                <label class="text-xs text-gray-500">Pozycja tla</label>
                <div class="grid grid-cols-3 gap-1">
                    @foreach([
                        'top left' => 'TL', 'top center' => 'TC', 'top right' => 'TR',
                        'center left' => 'CL', 'center center' => 'CC', 'center right' => 'CR',
                        'bottom left' => 'BL', 'bottom center' => 'BC', 'bottom right' => 'BR'
                    ] as $value => $label)
                        <button
                            wire:click="updateElementProperty('{{ $elementId }}', 'styles.backgroundPosition', '{{ $value }}')"
                            @class([
                                'py-1.5 text-xs font-medium rounded transition-colors',
                                'bg-amber-500 text-white' => ($styles['backgroundPosition'] ?? 'center') === $value,
                                'bg-gray-700 text-gray-400 hover:bg-gray-600' => ($styles['backgroundPosition'] ?? 'center') !== $value,
                            ])
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Overlay Color --}}
            <div class="space-y-1">
                <label class="text-xs text-gray-500">Kolor nakladki</label>
                <div class="flex gap-2">
                    <input
                        type="color"
                        x-data="{ color: '{{ $element['overlayColor'] ?? '#000000' }}' }"
                        x-model="color"
                        @change="$wire.updateBackgroundOverlay('{{ $elementId }}', color, {{ $element['overlayOpacity'] ?? 0.5 }})"
                        value="{{ $element['overlayColor'] ?? '#000000' }}"
                        class="w-10 h-8 bg-gray-700 border border-gray-600 rounded cursor-pointer"
                    >
                    <input
                        type="text"
                        x-data="{ color: '{{ $element['overlayColor'] ?? '' }}' }"
                        x-model="color"
                        @change="$wire.updateBackgroundOverlay('{{ $elementId }}', color, {{ $element['overlayOpacity'] ?? 0.5 }})"
                        value="{{ $element['overlayColor'] ?? '' }}"
                        placeholder="transparent"
                        class="flex-1 px-2 py-1.5 bg-gray-700 border border-gray-600 rounded text-white text-sm"
                    >
                </div>
            </div>

            {{-- Overlay Opacity --}}
            <div class="space-y-1">
                <label class="text-xs text-gray-500">Przezroczystosc nakladki: {{ ($element['overlayOpacity'] ?? 0.5) * 100 }}%</label>
                <input
                    type="range"
                    min="0"
                    max="1"
                    step="0.1"
                    wire:change="updateBackgroundOverlay('{{ $elementId }}', '{{ $element['overlayColor'] ?? '' }}', $event.target.value)"
                    value="{{ $element['overlayOpacity'] ?? 0.5 }}"
                    class="w-full h-2 bg-gray-700 rounded-lg appearance-none cursor-pointer accent-amber-500"
                >
            </div>

            {{-- Min Height --}}
            <div class="space-y-1">
                <label class="text-xs text-gray-500">Minimalna wysokosc</label>
                <input
                    type="text"
                    wire:change="updateElementProperty('{{ $elementId }}', 'styles.minHeight', $event.target.value)"
                    value="{{ $styles['minHeight'] ?? '200px' }}"
                    placeholder="np. 300px lub 50vh"
                    class="w-full px-2 py-1.5 bg-gray-700 border border-gray-600 rounded text-white text-sm"
                >
            </div>
        </div>
        @endif

        {{-- Repeater Element Section --}}
        @if($elementType === 'repeater')
        <div class="space-y-3 pt-3 border-t border-gray-700">
            <h4 class="text-xs font-medium text-gray-400 uppercase tracking-wider">Lista powtarzalna</h4>

            {{-- Layout Mode --}}
            <div class="space-y-1">
                <label class="text-xs text-gray-500">Uklad listy</label>
                <div class="flex gap-1">
                    @foreach(['list' => 'Lista', 'grid' => 'Siatka', 'carousel' => 'Karuzela'] as $value => $label)
                        <button
                            wire:click="setRepeaterLayout('{{ $elementId }}', '{{ $value }}')"
                            @class([
                                'flex-1 py-1.5 text-xs font-medium rounded transition-colors',
                                'bg-amber-500 text-white' => ($element['itemLayout'] ?? 'list') === $value,
                                'bg-gray-700 text-gray-400 hover:bg-gray-600' => ($element['itemLayout'] ?? 'list') !== $value,
                            ])
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Items Per Row (for grid layout) --}}
            @if(($element['itemLayout'] ?? 'list') === 'grid')
            <div class="space-y-1">
                <label class="text-xs text-gray-500">Elementow w rzedzie</label>
                <div class="flex gap-1">
                    @foreach([1, 2, 3, 4, 5, 6] as $cols)
                        <button
                            wire:click="updateElementProperty('{{ $elementId }}', 'itemsPerRow', {{ $cols }})"
                            @class([
                                'flex-1 py-1.5 text-xs font-medium rounded transition-colors',
                                'bg-amber-500 text-white' => ($element['itemsPerRow'] ?? 1) == $cols,
                                'bg-gray-700 text-gray-400 hover:bg-gray-600' => ($element['itemsPerRow'] ?? 1) != $cols,
                            ])
                        >
                            {{ $cols }}
                        </button>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Gap --}}
            <div class="space-y-1">
                <label class="text-xs text-gray-500">Odstep miedzy elementami</label>
                <input
                    type="text"
                    wire:change="updateElementProperty('{{ $elementId }}', 'styles.gap', $event.target.value)"
                    value="{{ $styles['gap'] ?? '1rem' }}"
                    class="w-full px-2 py-1.5 bg-gray-700 border border-gray-600 rounded text-white text-sm"
                >
            </div>

            {{-- Items List Management --}}
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <label class="text-xs text-gray-500">Elementy listy ({{ count($element['items'] ?? []) }})</label>
                    <button
                        wire:click="addRepeaterItem('{{ $elementId }}')"
                        class="flex items-center gap-1 px-2 py-1 text-xs bg-amber-500 hover:bg-amber-600 text-white rounded transition-colors"
                    >
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Dodaj
                    </button>
                </div>

                {{-- Items preview --}}
                <div class="space-y-1 max-h-32 overflow-y-auto">
                    @foreach($element['items'] ?? [] as $index => $item)
                        <div class="flex items-center justify-between p-2 bg-gray-700/50 rounded text-xs">
                            <span class="text-gray-300">
                                <span class="text-amber-400 font-medium">#{{ $index + 1 }}</span>
                                {{ $item['type'] ?? 'element' }}
                            </span>
                            @if(count($element['items'] ?? []) > 1)
                            <button
                                wire:click="removeRepeaterItem('{{ $elementId }}', {{ $index }})"
                                class="p-1 text-gray-400 hover:text-red-400 transition-colors"
                                title="Usun element"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                            @endif
                        </div>
                    @endforeach
                </div>

                <p class="text-[10px] text-gray-500 italic">
                    Kliknij element na canvas, aby go edytowac
                </p>
            </div>
        </div>
        @endif

        {{-- Slide Element Section --}}
        @if($elementType === 'slide')
        <div class="space-y-3 pt-3 border-t border-gray-700">
            <h4 class="text-xs font-medium text-gray-400 uppercase tracking-wider">Slajd</h4>

            {{-- Slide Index --}}
            <div class="flex items-center gap-2 p-2 bg-gray-700/30 rounded">
                <span class="text-xs text-gray-400">Numer slajdu:</span>
                <span class="text-sm font-medium text-amber-400">#{{ ($element['slideIndex'] ?? 0) + 1 }}</span>
            </div>

            {{-- Background Image URL --}}
            <div class="space-y-1">
                <label class="text-xs text-gray-500">URL obrazu tla</label>
                <input
                    type="url"
                    wire:change="updateSlideBackground('{{ $elementId }}', $event.target.value)"
                    value="{{ $element['backgroundImage'] ?? '' }}"
                    placeholder="https://..."
                    class="w-full px-2 py-1.5 bg-gray-700 border border-gray-600 rounded text-white text-sm"
                >
            </div>

            {{-- Background Size --}}
            <div class="space-y-1">
                <label class="text-xs text-gray-500">Rozmiar tla</label>
                <div class="flex gap-1">
                    @foreach(['cover' => 'Pokryj', 'contain' => 'Dopasuj', 'auto' => 'Auto'] as $value => $label)
                        <button
                            wire:click="updateElementProperty('{{ $elementId }}', 'styles.backgroundSize', '{{ $value }}')"
                            @class([
                                'flex-1 py-1.5 text-xs font-medium rounded transition-colors',
                                'bg-amber-500 text-white' => ($styles['backgroundSize'] ?? 'cover') === $value,
                                'bg-gray-700 text-gray-400 hover:bg-gray-600' => ($styles['backgroundSize'] ?? 'cover') !== $value,
                            ])
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Background Position --}}
            <div class="space-y-1">
                <label class="text-xs text-gray-500">Pozycja tla</label>
                <div class="grid grid-cols-3 gap-1">
                    @foreach([
                        'top left' => 'TL', 'top center' => 'TC', 'top right' => 'TR',
                        'center left' => 'CL', 'center center' => 'CC', 'center right' => 'CR',
                        'bottom left' => 'BL', 'bottom center' => 'BC', 'bottom right' => 'BR'
                    ] as $value => $label)
                        <button
                            wire:click="updateElementProperty('{{ $elementId }}', 'styles.backgroundPosition', '{{ $value }}')"
                            @class([
                                'py-1.5 text-xs font-medium rounded transition-colors',
                                'bg-amber-500 text-white' => ($styles['backgroundPosition'] ?? 'center') === $value,
                                'bg-gray-700 text-gray-400 hover:bg-gray-600' => ($styles['backgroundPosition'] ?? 'center') !== $value,
                            ])
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Overlay Color --}}
            <div class="space-y-1">
                <label class="text-xs text-gray-500">Kolor nakladki</label>
                <div class="flex gap-2">
                    <input
                        type="color"
                        x-data="{ color: '{{ $element['overlayColor'] ?? '#000000' }}' }"
                        x-model="color"
                        @change="$wire.updateSlideOverlay('{{ $elementId }}', color, {{ $element['overlayOpacity'] ?? 0.3 }})"
                        value="{{ $element['overlayColor'] ?? '#000000' }}"
                        class="w-10 h-8 bg-gray-700 border border-gray-600 rounded cursor-pointer"
                    >
                    <input
                        type="text"
                        x-data="{ color: '{{ $element['overlayColor'] ?? '' }}' }"
                        x-model="color"
                        @change="$wire.updateSlideOverlay('{{ $elementId }}', color, {{ $element['overlayOpacity'] ?? 0.3 }})"
                        value="{{ $element['overlayColor'] ?? '' }}"
                        placeholder="transparent"
                        class="flex-1 px-2 py-1.5 bg-gray-700 border border-gray-600 rounded text-white text-sm"
                    >
                </div>
            </div>

            {{-- Overlay Opacity --}}
            <div class="space-y-1">
                <label class="text-xs text-gray-500">Przezroczystosc nakladki: {{ ($element['overlayOpacity'] ?? 0.3) * 100 }}%</label>
                <input
                    type="range"
                    min="0"
                    max="1"
                    step="0.1"
                    wire:change="updateSlideOverlay('{{ $elementId }}', '{{ $element['overlayColor'] ?? '#000000' }}', $event.target.value)"
                    value="{{ $element['overlayOpacity'] ?? 0.3 }}"
                    class="w-full h-2 bg-gray-700 rounded-lg appearance-none cursor-pointer accent-amber-500"
                >
            </div>

            {{-- Min Height --}}
            <div class="space-y-1">
                <label class="text-xs text-gray-500">Minimalna wysokosc</label>
                <input
                    type="text"
                    wire:change="updateElementProperty('{{ $elementId }}', 'styles.minHeight', $event.target.value)"
                    value="{{ $styles['minHeight'] ?? '300px' }}"
                    placeholder="np. 300px lub 50vh"
                    class="w-full px-2 py-1.5 bg-gray-700 border border-gray-600 rounded text-white text-sm"
                >
            </div>

            {{-- Content Alignment --}}
            <div class="space-y-1">
                <label class="text-xs text-gray-500">Wyrownanie tresci</label>
                <div class="grid grid-cols-3 gap-1">
                    @foreach([
                        'flex-start' => 'Gora',
                        'center' => 'Srodek',
                        'flex-end' => 'Dol'
                    ] as $value => $label)
                        <button
                            wire:click="updateElementProperty('{{ $elementId }}', 'styles.justifyContent', '{{ $value }}')"
                            @class([
                                'py-1.5 text-xs font-medium rounded transition-colors',
                                'bg-amber-500 text-white' => ($styles['justifyContent'] ?? 'center') === $value,
                                'bg-gray-700 text-gray-400 hover:bg-gray-600' => ($styles['justifyContent'] ?? 'center') !== $value,
                            ])
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        {{-- Spacing Section with Visual 4-Sided Control --}}
        <div class="space-y-3 pt-3 border-t border-gray-700">
            <h4 class="text-xs font-medium text-gray-400 uppercase tracking-wider">Odstepy</h4>

            @php
                // Parse padding values (can be "10px", "10px 20px", "10px 20px 30px 40px")
                $paddingValue = $styles['padding'] ?? '0';
                $paddingParts = preg_split('/\s+/', trim($paddingValue));
                $paddingTop = $paddingParts[0] ?? '0';
                $paddingRight = $paddingParts[1] ?? $paddingTop;
                $paddingBottom = $paddingParts[2] ?? $paddingTop;
                $paddingLeft = $paddingParts[3] ?? $paddingRight;

                // Parse margin values
                $marginValue = $styles['margin'] ?? '0';
                $marginParts = preg_split('/\s+/', trim($marginValue));
                $marginTop = $marginParts[0] ?? '0';
                $marginRight = $marginParts[1] ?? $marginTop;
                $marginBottom = $marginParts[2] ?? $marginTop;
                $marginLeft = $marginParts[3] ?? $marginRight;
            @endphp

            {{-- Padding Visual Control --}}
            <div class="space-y-2" x-data="{
                linked: true,
                padding: {
                    top: '{{ $paddingTop }}',
                    right: '{{ $paddingRight }}',
                    bottom: '{{ $paddingBottom }}',
                    left: '{{ $paddingLeft }}'
                },
                updatePadding(side, value) {
                    if (this.linked) {
                        this.padding = { top: value, right: value, bottom: value, left: value };
                    } else {
                        this.padding[side] = value;
                    }
                    const cssValue = this.linked ? value : `${this.padding.top} ${this.padding.right} ${this.padding.bottom} ${this.padding.left}`;
                    $wire.updateElementProperty('{{ $elementId }}', 'styles.padding', cssValue);
                }
            }">
                <div class="flex items-center justify-between">
                    <label class="text-xs text-gray-500">Padding</label>
                    <button
                        type="button"
                        @click="linked = !linked"
                        :class="linked ? 'text-amber-400' : 'text-gray-500'"
                        class="p-1 hover:bg-gray-700 rounded transition-colors"
                        :title="linked ? 'Rozlacz wartosci' : 'Polacz wartosci'"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path x-show="linked" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                            <path x-show="!linked" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                        </svg>
                    </button>
                </div>

                {{-- Visual Box --}}
                <div class="relative bg-gray-700/50 rounded-lg p-3">
                    {{-- Top --}}
                    <div class="flex justify-center mb-1">
                        <input
                            type="text"
                            x-model="padding.top"
                            @change="updatePadding('top', $event.target.value)"
                            class="w-14 px-1 py-0.5 text-center text-xs bg-gray-800 border border-gray-600 rounded text-white focus:ring-amber-500 focus:border-amber-500"
                        >
                    </div>
                    {{-- Middle row: Left - Box - Right --}}
                    <div class="flex items-center justify-between">
                        <input
                            type="text"
                            x-model="padding.left"
                            @change="updatePadding('left', $event.target.value)"
                            class="w-14 px-1 py-0.5 text-center text-xs bg-gray-800 border border-gray-600 rounded text-white focus:ring-amber-500 focus:border-amber-500"
                        >
                        <div class="w-12 h-8 bg-gray-600/50 border border-dashed border-gray-500 rounded flex items-center justify-center">
                            <span class="text-[9px] text-gray-500 uppercase">Padding</span>
                        </div>
                        <input
                            type="text"
                            x-model="padding.right"
                            @change="updatePadding('right', $event.target.value)"
                            class="w-14 px-1 py-0.5 text-center text-xs bg-gray-800 border border-gray-600 rounded text-white focus:ring-amber-500 focus:border-amber-500"
                        >
                    </div>
                    {{-- Bottom --}}
                    <div class="flex justify-center mt-1">
                        <input
                            type="text"
                            x-model="padding.bottom"
                            @change="updatePadding('bottom', $event.target.value)"
                            class="w-14 px-1 py-0.5 text-center text-xs bg-gray-800 border border-gray-600 rounded text-white focus:ring-amber-500 focus:border-amber-500"
                        >
                    </div>
                </div>
            </div>

            {{-- Margin Visual Control --}}
            <div class="space-y-2" x-data="{
                linked: true,
                margin: {
                    top: '{{ $marginTop }}',
                    right: '{{ $marginRight }}',
                    bottom: '{{ $marginBottom }}',
                    left: '{{ $marginLeft }}'
                },
                updateMargin(side, value) {
                    if (this.linked) {
                        this.margin = { top: value, right: value, bottom: value, left: value };
                    } else {
                        this.margin[side] = value;
                    }
                    const cssValue = this.linked ? value : `${this.margin.top} ${this.margin.right} ${this.margin.bottom} ${this.margin.left}`;
                    $wire.updateElementProperty('{{ $elementId }}', 'styles.margin', cssValue);
                }
            }">
                <div class="flex items-center justify-between">
                    <label class="text-xs text-gray-500">Margin</label>
                    <button
                        type="button"
                        @click="linked = !linked"
                        :class="linked ? 'text-amber-400' : 'text-gray-500'"
                        class="p-1 hover:bg-gray-700 rounded transition-colors"
                        :title="linked ? 'Rozlacz wartosci' : 'Polacz wartosci'"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path x-show="linked" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                            <path x-show="!linked" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                        </svg>
                    </button>
                </div>

                {{-- Visual Box --}}
                <div class="relative bg-gray-700/50 rounded-lg p-3">
                    {{-- Top --}}
                    <div class="flex justify-center mb-1">
                        <input
                            type="text"
                            x-model="margin.top"
                            @change="updateMargin('top', $event.target.value)"
                            class="w-14 px-1 py-0.5 text-center text-xs bg-gray-800 border border-gray-600 rounded text-white focus:ring-amber-500 focus:border-amber-500"
                        >
                    </div>
                    {{-- Middle row: Left - Box - Right --}}
                    <div class="flex items-center justify-between">
                        <input
                            type="text"
                            x-model="margin.left"
                            @change="updateMargin('left', $event.target.value)"
                            class="w-14 px-1 py-0.5 text-center text-xs bg-gray-800 border border-gray-600 rounded text-white focus:ring-amber-500 focus:border-amber-500"
                        >
                        <div class="w-12 h-8 bg-gray-600/50 border border-dashed border-gray-500 rounded flex items-center justify-center">
                            <span class="text-[9px] text-gray-500 uppercase">Margin</span>
                        </div>
                        <input
                            type="text"
                            x-model="margin.right"
                            @change="updateMargin('right', $event.target.value)"
                            class="w-14 px-1 py-0.5 text-center text-xs bg-gray-800 border border-gray-600 rounded text-white focus:ring-amber-500 focus:border-amber-500"
                        >
                    </div>
                    {{-- Bottom --}}
                    <div class="flex justify-center mt-1">
                        <input
                            type="text"
                            x-model="margin.bottom"
                            @change="updateMargin('bottom', $event.target.value)"
                            class="w-14 px-1 py-0.5 text-center text-xs bg-gray-800 border border-gray-600 rounded text-white focus:ring-amber-500 focus:border-amber-500"
                        >
                    </div>
                </div>
            </div>
        </div>

        {{-- Background Section --}}
        <div class="space-y-3 pt-3 border-t border-gray-700">
            <h4 class="text-xs font-medium text-gray-400 uppercase tracking-wider">Tlo</h4>

            {{-- Background Color --}}
            <div class="space-y-1">
                <label class="text-xs text-gray-500">Kolor tla</label>
                <div class="flex gap-2">
                    <input
                        type="color"
                        wire:change="updateElementProperty('{{ $elementId }}', 'styles.backgroundColor', $event.target.value)"
                        value="{{ $styles['backgroundColor'] ?? '#ffffff' }}"
                        class="w-10 h-8 bg-gray-700 border border-gray-600 rounded cursor-pointer"
                    >
                    <input
                        type="text"
                        wire:change="updateElementProperty('{{ $elementId }}', 'styles.backgroundColor', $event.target.value)"
                        value="{{ $styles['backgroundColor'] ?? '' }}"
                        placeholder="transparent"
                        class="flex-1 px-2 py-1.5 bg-gray-700 border border-gray-600 rounded text-white text-sm"
                    >
                </div>
            </div>
        </div>

        {{-- Size & Position Section --}}
        <div class="space-y-3 pt-3 border-t border-gray-700">
            <h4 class="text-xs font-medium text-gray-400 uppercase tracking-wider">Rozmiar i pozycja</h4>

            {{-- Position Type --}}
            <div class="space-y-1">
                <label class="text-xs text-gray-500">Typ pozycji</label>
                <select
                    wire:change="updateElementProperty('{{ $elementId }}', 'styles.position', $event.target.value)"
                    class="w-full px-2 py-1.5 bg-gray-700 border border-gray-600 rounded text-white text-sm"
                >
                    @foreach(['static' => 'Statyczna (domyslna)', 'relative' => 'Relatywna', 'absolute' => 'Absolutna', 'fixed' => 'Stala'] as $value => $label)
                        <option value="{{ $value }}" @selected(($styles['position'] ?? 'static') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Position Offsets (for absolute/relative/fixed) --}}
            @if(in_array($styles['position'] ?? 'static', ['absolute', 'relative', 'fixed']))
            <div class="space-y-2 p-2 bg-gray-700/30 rounded">
                <label class="text-xs text-gray-500">Przesuniecie</label>
                <div class="grid grid-cols-2 gap-2">
                    <div class="space-y-1">
                        <label class="text-[10px] text-gray-600">Top</label>
                        <input
                            type="text"
                            wire:change="updateElementProperty('{{ $elementId }}', 'styles.top', $event.target.value)"
                            value="{{ $styles['top'] ?? '' }}"
                            placeholder="auto"
                            class="w-full px-2 py-1 bg-gray-800 border border-gray-600 rounded text-white text-xs"
                        >
                    </div>
                    <div class="space-y-1">
                        <label class="text-[10px] text-gray-600">Right</label>
                        <input
                            type="text"
                            wire:change="updateElementProperty('{{ $elementId }}', 'styles.right', $event.target.value)"
                            value="{{ $styles['right'] ?? '' }}"
                            placeholder="auto"
                            class="w-full px-2 py-1 bg-gray-800 border border-gray-600 rounded text-white text-xs"
                        >
                    </div>
                    <div class="space-y-1">
                        <label class="text-[10px] text-gray-600">Bottom</label>
                        <input
                            type="text"
                            wire:change="updateElementProperty('{{ $elementId }}', 'styles.bottom', $event.target.value)"
                            value="{{ $styles['bottom'] ?? '' }}"
                            placeholder="auto"
                            class="w-full px-2 py-1 bg-gray-800 border border-gray-600 rounded text-white text-xs"
                        >
                    </div>
                    <div class="space-y-1">
                        <label class="text-[10px] text-gray-600">Left</label>
                        <input
                            type="text"
                            wire:change="updateElementProperty('{{ $elementId }}', 'styles.left', $event.target.value)"
                            value="{{ $styles['left'] ?? '' }}"
                            placeholder="auto"
                            class="w-full px-2 py-1 bg-gray-800 border border-gray-600 rounded text-white text-xs"
                        >
                    </div>
                </div>
                {{-- Z-Index --}}
                <div class="space-y-1 mt-2">
                    <label class="text-[10px] text-gray-600">Z-Index (warstwa)</label>
                    <input
                        type="number"
                        wire:change="updateElementProperty('{{ $elementId }}', 'styles.zIndex', $event.target.value)"
                        value="{{ $styles['zIndex'] ?? '' }}"
                        placeholder="auto"
                        class="w-full px-2 py-1 bg-gray-800 border border-gray-600 rounded text-white text-xs"
                    >
                </div>
            </div>
            @endif

            {{-- Width & Height with Unit Picker --}}
            <div class="grid grid-cols-2 gap-2">
                {{-- Width --}}
                <div class="space-y-1" x-data="sizePicker({
                    value: '{{ $styles['width'] ?? '' }}',
                    property: 'styles.width',
                    elementId: '{{ $elementId }}'
                })">
                    <label class="text-xs text-gray-500">Szerokosc</label>
                    <div class="flex gap-1">
                        <input
                            type="text"
                            x-model="numValue"
                            @change="updateValue()"
                            placeholder="auto"
                            class="flex-1 w-16 px-2 py-1.5 bg-gray-700 border border-gray-600 rounded text-white text-sm"
                        >
                        <select
                            x-model="unit"
                            @change="updateValue()"
                            class="w-14 px-1 py-1.5 bg-gray-700 border border-gray-600 rounded text-white text-xs"
                        >
                            <option value="px">px</option>
                            <option value="%">%</option>
                            <option value="rem">rem</option>
                            <option value="em">em</option>
                            <option value="vh">vh</option>
                            <option value="vw">vw</option>
                        </select>
                    </div>
                </div>

                {{-- Height --}}
                <div class="space-y-1" x-data="sizePicker({
                    value: '{{ $styles['height'] ?? '' }}',
                    property: 'styles.height',
                    elementId: '{{ $elementId }}'
                })">
                    <label class="text-xs text-gray-500">Wysokosc</label>
                    <div class="flex gap-1">
                        <input
                            type="text"
                            x-model="numValue"
                            @change="updateValue()"
                            placeholder="auto"
                            class="flex-1 w-16 px-2 py-1.5 bg-gray-700 border border-gray-600 rounded text-white text-sm"
                        >
                        <select
                            x-model="unit"
                            @change="updateValue()"
                            class="w-14 px-1 py-1.5 bg-gray-700 border border-gray-600 rounded text-white text-xs"
                        >
                            <option value="px">px</option>
                            <option value="%">%</option>
                            <option value="rem">rem</option>
                            <option value="em">em</option>
                            <option value="vh">vh</option>
                            <option value="vw">vw</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- Min/Max Width/Height --}}
            <div class="grid grid-cols-2 gap-2">
                <div class="space-y-1">
                    <label class="text-[10px] text-gray-500">Min W</label>
                    <input
                        type="text"
                        wire:change="updateElementProperty('{{ $elementId }}', 'styles.minWidth', $event.target.value)"
                        value="{{ $styles['minWidth'] ?? '' }}"
                        placeholder="0"
                        class="w-full px-2 py-1 bg-gray-700 border border-gray-600 rounded text-white text-xs"
                    >
                </div>
                <div class="space-y-1">
                    <label class="text-[10px] text-gray-500">Max W</label>
                    <input
                        type="text"
                        wire:change="updateElementProperty('{{ $elementId }}', 'styles.maxWidth', $event.target.value)"
                        value="{{ $styles['maxWidth'] ?? '' }}"
                        placeholder="none"
                        class="w-full px-2 py-1 bg-gray-700 border border-gray-600 rounded text-white text-xs"
                    >
                </div>
            </div>
        </div>

        {{-- Border Section (Full) --}}
        <div class="space-y-3 pt-3 border-t border-gray-700">
            <h4 class="text-xs font-medium text-gray-400 uppercase tracking-wider">Obramowanie</h4>

            {{-- Border Width --}}
            <div class="space-y-1">
                <label class="text-xs text-gray-500">Grubosc</label>
                <div class="flex gap-1">
                    @foreach(['0' => 'Brak', '1px' => '1px', '2px' => '2px', '3px' => '3px', '4px' => '4px'] as $value => $label)
                        <button
                            wire:click="updateElementProperty('{{ $elementId }}', 'styles.borderWidth', '{{ $value }}')"
                            @class([
                                'flex-1 py-1.5 text-xs font-medium rounded transition-colors',
                                'bg-amber-500 text-white' => ($styles['borderWidth'] ?? '0') === $value,
                                'bg-gray-700 text-gray-400 hover:bg-gray-600' => ($styles['borderWidth'] ?? '0') !== $value,
                            ])
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Border Style --}}
            <div class="space-y-1">
                <label class="text-xs text-gray-500">Styl</label>
                <select
                    wire:change="updateElementProperty('{{ $elementId }}', 'styles.borderStyle', $event.target.value)"
                    class="w-full px-2 py-1.5 bg-gray-700 border border-gray-600 rounded text-white text-sm"
                >
                    @foreach(['none' => 'Brak', 'solid' => 'Ciagla', 'dashed' => 'Przerywana', 'dotted' => 'Kropkowana', 'double' => 'Podwojna'] as $value => $label)
                        <option value="{{ $value }}" @selected(($styles['borderStyle'] ?? 'none') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Border Color --}}
            <div class="space-y-1">
                <label class="text-xs text-gray-500">Kolor obramowania</label>
                <div class="flex gap-2">
                    <input
                        type="color"
                        wire:change="updateElementProperty('{{ $elementId }}', 'styles.borderColor', $event.target.value)"
                        value="{{ $styles['borderColor'] ?? '#e5e5e5' }}"
                        class="w-10 h-8 bg-gray-700 border border-gray-600 rounded cursor-pointer"
                    >
                    <input
                        type="text"
                        wire:change="updateElementProperty('{{ $elementId }}', 'styles.borderColor', $event.target.value)"
                        value="{{ $styles['borderColor'] ?? '' }}"
                        placeholder="#e5e5e5"
                        class="flex-1 px-2 py-1.5 bg-gray-700 border border-gray-600 rounded text-white text-sm"
                    >
                </div>
            </div>

            {{-- Border Radius --}}
            <div class="space-y-1">
                <label class="text-xs text-gray-500">Zaokraglenie</label>
                <div class="flex gap-1">
                    @foreach(['0' => 'Brak', '0.25rem' => 'XS', '0.5rem' => 'S', '0.75rem' => 'M', '1rem' => 'L', '1.5rem' => 'XL', '50%' => 'Okrag'] as $value => $label)
                        <button
                            wire:click="updateElementProperty('{{ $elementId }}', 'styles.borderRadius', '{{ $value }}')"
                            @class([
                                'flex-1 py-1.5 text-[10px] font-medium rounded transition-colors',
                                'bg-amber-500 text-white' => ($styles['borderRadius'] ?? '0') === $value,
                                'bg-gray-700 text-gray-400 hover:bg-gray-600' => ($styles['borderRadius'] ?? '0') !== $value,
                            ])
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
                <input
                    type="text"
                    wire:change="updateElementProperty('{{ $elementId }}', 'styles.borderRadius', $event.target.value)"
                    value="{{ $styles['borderRadius'] ?? '' }}"
                    placeholder="np. 0.5rem 1rem 0 0"
                    class="w-full px-2 py-1.5 bg-gray-700 border border-gray-600 rounded text-white text-xs mt-1"
                >
            </div>
        </div>

        {{-- Effects Section --}}
        <div class="space-y-3 pt-3 border-t border-gray-700">
            <h4 class="text-xs font-medium text-gray-400 uppercase tracking-wider">Efekty</h4>

            {{-- Opacity --}}
            <div class="space-y-1">
                @php $opacityValue = $styles['opacity'] ?? 1; @endphp
                <div class="flex items-center justify-between">
                    <label class="text-xs text-gray-500">Przezroczystosc</label>
                    <span class="text-xs text-amber-400 font-medium">{{ round($opacityValue * 100) }}%</span>
                </div>
                <input
                    type="range"
                    min="0"
                    max="1"
                    step="0.05"
                    wire:change="updateElementProperty('{{ $elementId }}', 'styles.opacity', $event.target.value)"
                    value="{{ $opacityValue }}"
                    class="w-full h-2 bg-gray-700 rounded-lg appearance-none cursor-pointer accent-amber-500"
                >
            </div>

            {{-- Box Shadow Presets --}}
            <div class="space-y-1">
                <label class="text-xs text-gray-500">Cien</label>
                @php
                    $shadowPresets = [
                        'none' => ['label' => 'Brak', 'value' => 'none'],
                        'sm' => ['label' => 'S', 'value' => '0 1px 2px rgba(0,0,0,0.1)'],
                        'md' => ['label' => 'M', 'value' => '0 4px 6px rgba(0,0,0,0.1)'],
                        'lg' => ['label' => 'L', 'value' => '0 10px 15px rgba(0,0,0,0.1)'],
                        'xl' => ['label' => 'XL', 'value' => '0 20px 25px rgba(0,0,0,0.15)'],
                        '2xl' => ['label' => '2XL', 'value' => '0 25px 50px rgba(0,0,0,0.25)'],
                    ];
                @endphp
                <div class="flex gap-1">
                    @foreach($shadowPresets as $key => $preset)
                        <button
                            wire:click="updateElementProperty('{{ $elementId }}', 'styles.boxShadow', '{{ $preset['value'] }}')"
                            @class([
                                'flex-1 py-1.5 text-xs font-medium rounded transition-colors',
                                'bg-amber-500 text-white' => ($styles['boxShadow'] ?? 'none') === $preset['value'],
                                'bg-gray-700 text-gray-400 hover:bg-gray-600' => ($styles['boxShadow'] ?? 'none') !== $preset['value'],
                            ])
                        >
                            {{ $preset['label'] }}
                        </button>
                    @endforeach
                </div>
                {{-- Custom shadow input --}}
                <input
                    type="text"
                    wire:change="updateElementProperty('{{ $elementId }}', 'styles.boxShadow', $event.target.value)"
                    value="{{ $styles['boxShadow'] ?? '' }}"
                    placeholder="np. 0 4px 6px rgba(0,0,0,0.1)"
                    class="w-full px-2 py-1.5 bg-gray-700 border border-gray-600 rounded text-white text-xs mt-1"
                >
            </div>

            {{-- Transform: Rotation --}}
            <div class="space-y-1">
                @php
                    $rotation = 0;
                    if (isset($styles['transform']) && preg_match('/rotate\((\-?\d+)deg\)/', $styles['transform'], $matches)) {
                        $rotation = (int) $matches[1];
                    }
                @endphp
                <div class="flex items-center justify-between">
                    <label class="text-xs text-gray-500">Rotacja</label>
                    <span class="text-xs text-amber-400 font-medium">{{ $rotation }}°</span>
                </div>
                <div class="flex gap-1">
                    @foreach([0, 45, 90, 135, 180, -45, -90] as $deg)
                        <button
                            wire:click="updateElementProperty('{{ $elementId }}', 'styles.transform', 'rotate({{ $deg }}deg)')"
                            @class([
                                'flex-1 py-1 text-[10px] font-medium rounded transition-colors',
                                'bg-amber-500 text-white' => $rotation == $deg,
                                'bg-gray-700 text-gray-400 hover:bg-gray-600' => $rotation != $deg,
                            ])
                        >
                            {{ $deg }}°
                        </button>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Custom CSS Classes Section (ALWAYS VISIBLE for all elements) --}}
        <div class="space-y-3 pt-3 border-t border-gray-700">
            <h4 class="text-xs font-medium text-gray-400 uppercase tracking-wider">Klasy CSS</h4>

            {{-- Current CSS Classes Display --}}
            @php
                $currentClasses = $element['classes'] ?? [];
                $customCssClasses = $element['customCssClasses'] ?? '';
            @endphp

            @if(!empty($currentClasses))
            <div class="space-y-1">
                <label class="text-xs text-gray-500">Aktualne klasy</label>
                <div class="flex flex-wrap gap-1">
                    @foreach($currentClasses as $cssClass)
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs bg-gray-700 border border-gray-600 rounded text-amber-400 font-mono">
                            {{ $cssClass }}
                            <button
                                type="button"
                                wire:click="removeElementClass('{{ $elementId }}', '{{ $cssClass }}')"
                                class="text-gray-500 hover:text-red-400 transition-colors"
                                title="Usun klase"
                            >
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </span>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Add New CSS Class --}}
            <div class="space-y-1">
                <label class="text-xs text-gray-500">Dodaj klase CSS</label>
                <div class="flex gap-1">
                    <input
                        type="text"
                        x-data="{ newClass: '' }"
                        x-model="newClass"
                        @keydown.enter="if(newClass.trim()) { $wire.addElementClass('{{ $elementId }}', newClass.trim()); newClass = ''; }"
                        placeholder="np. pd-intro__heading"
                        class="flex-1 px-2 py-1.5 bg-gray-700 border border-gray-600 rounded text-white text-xs font-mono focus:ring-amber-500 focus:border-amber-500"
                    >
                    <button
                        type="button"
                        x-data="{ }"
                        @click="const input = $el.previousElementSibling; if(input.value.trim()) { $wire.addElementClass('{{ $elementId }}', input.value.trim()); input.value = ''; }"
                        class="px-2 py-1.5 bg-amber-500 hover:bg-amber-600 text-white text-xs rounded transition-colors"
                    >
                        Dodaj
                    </button>
                </div>
            </div>

            {{-- Custom CSS Classes Text Area (for bulk input) --}}
            <div class="space-y-1">
                <label class="text-xs text-gray-500">Wiele klas (oddzielone spacja)</label>
                <textarea
                    wire:change="setElementClasses('{{ $elementId }}', $event.target.value)"
                    placeholder="pd-base-grid pd-intro pd-model..."
                    rows="2"
                    class="w-full px-2 py-1.5 bg-gray-700 border border-gray-600 rounded text-white text-xs font-mono focus:ring-amber-500 focus:border-amber-500 resize-none"
                >{{ implode(' ', $currentClasses) }}</textarea>
            </div>

            {{-- Quick PrestaShop Classes --}}
            <div class="space-y-1">
                <label class="text-xs text-gray-500">Szybki wybor (PrestaShop)</label>
                <div class="flex flex-wrap gap-1">
                    @php
                        $quickClasses = [
                            'pd-base-grid', 'pd-intro', 'pd-intro__heading', 'pd-intro__text',
                            'pd-model', 'pd-model__type', 'pd-model__name',
                            'pd-cover', 'pd-cover__picture', 'grid-row',
                            'pd-asset-list', 'pd-merits', 'pd-specification',
                            'bg-brand', 'bg-neutral-accent', 'text-center'
                        ];
                    @endphp
                    @foreach($quickClasses as $qClass)
                        <button
                            type="button"
                            wire:click="toggleElementClass('{{ $elementId }}', '{{ $qClass }}')"
                            @class([
                                'px-1.5 py-0.5 text-[10px] font-mono rounded transition-colors',
                                'bg-amber-500 text-white' => in_array($qClass, $currentClasses),
                                'bg-gray-700 text-gray-400 hover:bg-gray-600 hover:text-white' => !in_array($qClass, $currentClasses),
                            ])
                        >
                            {{ $qClass }}
                        </button>
                    @endforeach
                </div>
            </div>
        </div>

    @else
        {{-- No Selection --}}
        <div class="flex flex-col items-center justify-center py-12 text-gray-500">
            <svg class="w-12 h-12 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/>
            </svg>
            <p class="text-sm text-center">
                Zaznacz element na canvas,<br>
                aby edytowac jego wlasciwosci
            </p>
        </div>
    @endif
</div>

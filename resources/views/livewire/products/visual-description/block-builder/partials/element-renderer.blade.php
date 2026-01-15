@php
    $element = $element ?? [];
    $elementId = $element['id'] ?? '';
    $elementType = $element['type'] ?? 'unknown';
    $isSelected = ($selectedElementId ?? '') === $elementId;
    $isVisible = $element['visible'] ?? true;
    $isLocked = $element['locked'] ?? false;
    $content = $element['content'] ?? '';
    $children = $element['children'] ?? [];
    $classes = $element['classes'] ?? [];
    $styles = $element['styles'] ?? [];
    $isContainer = in_array($elementType, ['container', 'row', 'column', 'grid', 'background', 'repeater', 'slide']);
    $depth = $depth ?? 0;
@endphp

@if($isVisible)
<div
    wire:key="element-{{ $elementId }}"
    x-on:click.stop="$wire.selectElement('{{ $elementId }}')"
    @class([
        'element-node relative group cursor-pointer transition-all duration-150',
        'ring-2 ring-amber-400 ring-offset-2 ring-offset-white' => $isSelected,
        'hover:ring-1 hover:ring-blue-300 hover:ring-offset-1' => !$isSelected,
        'opacity-50' => !$isVisible,
        'pointer-events-none' => $isLocked,
    ])
    data-element-id="{{ $elementId }}"
    data-element-type="{{ $elementType }}"
    data-is-container="{{ $isContainer ? 'true' : 'false' }}"
    data-depth="{{ $depth }}"
    @if(!$isLocked)
        draggable="true"
        x-on:dragstart.stop="handleElementDragStart($event, '{{ $elementId }}', '{{ $elementType }}')"
        x-on:dragend="handleElementDragEnd($event)"
    @endif
    @if($isContainer)
        x-on:dragover.prevent="handleContainerDragOver($event, '{{ $elementId }}')"
        x-on:dragleave="handleContainerDragLeave($event, '{{ $elementId }}')"
        x-on:drop.prevent.stop="handleContainerDrop($event, '{{ $elementId }}')"
    @endif
>
    {{-- Element Type Badge --}}
    <div
        @class([
            'absolute -top-5 left-0 text-[10px] font-medium px-1.5 py-0.5 rounded transition-opacity z-10',
            'bg-amber-500 text-white opacity-100' => $isSelected,
            'bg-gray-700 text-gray-300 opacity-0 group-hover:opacity-100' => !$isSelected,
        ])
    >
        {{ ucfirst($elementType) }}
    </div>

    {{-- Element Actions (visible on select) --}}
    @if($isSelected && !$isLocked)
    <div class="absolute -top-5 right-0 flex items-center gap-1 z-10">
        <button
            wire:click.stop="moveElementUp('{{ $elementId }}')"
            class="p-1 bg-gray-700 hover:bg-gray-600 text-white rounded text-xs"
            title="Przesun w gore"
        >
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
            </svg>
        </button>
        <button
            wire:click.stop="moveElementDown('{{ $elementId }}')"
            class="p-1 bg-gray-700 hover:bg-gray-600 text-white rounded text-xs"
            title="Przesun w dol"
        >
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>
        <button
            wire:click.stop="duplicateElement('{{ $elementId }}')"
            class="p-1 bg-gray-700 hover:bg-gray-600 text-white rounded text-xs"
            title="Duplikuj"
        >
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
            </svg>
        </button>
        <button
            wire:click.stop="deleteElement('{{ $elementId }}')"
            class="p-1 bg-red-600 hover:bg-red-700 text-white rounded text-xs"
            title="Usun"
        >
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
        </button>
    </div>
    @endif

    {{-- Render Element Content Based on Type --}}
    @switch($elementType)
        @case('heading')
            @php
                // Sanitize HTML content - allow only safe inline tags for rich text
                $safeContent = $content ? strip_tags($content, '<b><strong><i><em><u><s><strike><a><br><span>') : 'Naglowek';
            @endphp
            <{{ $element['tag'] ?? 'h2' }}
                @class(array_merge($classes, ['outline-none wysiwyg-content']))
                style="{{ collect($styles)->map(fn($v, $k) => \Illuminate\Support\Str::kebab($k) . ': ' . $v)->implode('; ') }}"
            >{!! $safeContent !!}</{{ $element['tag'] ?? 'h2' }}>
            @break

        @case('text')
            @php
                // Sanitize HTML content - allow common rich text tags
                $safeContent = $content ? strip_tags($content, '<b><strong><i><em><u><s><strike><a><br><span><ul><ol><li><p>') : 'Tekst';
            @endphp
            <{{ $element['tag'] ?? 'p' }}
                @class(array_merge($classes, ['outline-none wysiwyg-content']))
                style="{{ collect($styles)->map(fn($v, $k) => \Illuminate\Support\Str::kebab($k) . ': ' . $v)->implode('; ') }}"
            >{!! $safeContent !!}</{{ $element['tag'] ?? 'p' }}>
            @break

        @case('image')
            @php
                $src = $element['src'] ?? '';
                $alt = $element['alt'] ?? 'Obraz';
                $srcset = $element['srcset'] ?? '';
                $sizes = $element['sizes'] ?? '';
                $width = $element['width'] ?? '';
                $height = $element['height'] ?? '';
            @endphp
            @if($src)
                <img
                    src="{{ $src }}"
                    alt="{{ $alt }}"
                    @if($srcset) srcset="{{ $srcset }}" @endif
                    @if($sizes) sizes="{{ $sizes }}" @endif
                    @if($width) width="{{ $width }}" @endif
                    @if($height) height="{{ $height }}" @endif
                    @class($classes)
                    style="{{ collect($styles)->map(fn($v, $k) => \Illuminate\Support\Str::kebab($k) . ': ' . $v)->implode('; ') }}"
                >
            @else
                <div
                    @class(array_merge($classes, ['flex items-center justify-center bg-gray-100 border-2 border-dashed border-gray-300 rounded-lg p-8']))
                    style="{{ collect($styles)->map(fn($v, $k) => \Illuminate\Support\Str::kebab($k) . ': ' . $v)->implode('; ') }}; min-height: 150px;"
                >
                    <div class="text-center text-gray-400">
                        <svg class="w-12 h-12 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <p class="text-sm">Kliknij, aby dodac obraz</p>
                    </div>
                </div>
            @endif
            @break

        @case('picture')
            <picture
                @class($classes)
                style="{{ collect($styles)->map(fn($v, $k) => \Illuminate\Support\Str::kebab($k) . ': ' . $v)->implode('; ') }}"
            >
                @foreach($children as $child)
                    @include('livewire.products.visual-description.block-builder.partials.element-renderer', ['element' => $child, 'depth' => $depth + 1])
                @endforeach
            </picture>
            @break

        @case('source')
            @php
                $srcset = $element['srcset'] ?? '';
                $sizes = $element['sizes'] ?? '';
                $media = $element['media'] ?? '';
                $mimeType = $element['mimeType'] ?? '';
            @endphp
            @if($srcset)
                <source
                    srcset="{{ $srcset }}"
                    @if($sizes) sizes="{{ $sizes }}" @endif
                    @if($media) media="{{ $media }}" @endif
                    @if($mimeType) type="{{ $mimeType }}" @endif
                >
            @endif
            @break

        @case('icon')
            @php
                $iconClass = $element['iconClass'] ?? 'pd-icon--check';
            @endphp
            <span
                @class(array_merge($classes, [$iconClass]))
                style="{{ collect($styles)->map(fn($v, $k) => \Illuminate\Support\Str::kebab($k) . ': ' . $v)->implode('; ') }}"
            ></span>
            @break

        @case('button')
            @php
                // Button content - plain text only for simplicity
                $buttonContent = $content ? e(strip_tags($content)) : 'Przycisk';
            @endphp
            <a
                href="{{ $element['href'] ?? '#' }}"
                @class($classes)
                style="{{ collect($styles)->map(fn($v, $k) => \Illuminate\Support\Str::kebab($k) . ': ' . $v)->implode('; ') }}"
                x-on:click.prevent
            >{{ $buttonContent }}</a>
            @break

        @case('separator')
            <hr
                @class($classes)
                style="{{ collect($styles)->map(fn($v, $k) => \Illuminate\Support\Str::kebab($k) . ': ' . $v)->implode('; ') }}"
            >
            @break

        @case('container')
        @case('row')
        @case('column')
            <div
                @class(array_merge($classes, ['drop-container transition-colors duration-200']))
                style="{{ collect($styles)->map(fn($v, $k) => \Illuminate\Support\Str::kebab($k) . ': ' . $v)->implode('; ') }}"
                :class="{ 'ring-2 ring-blue-400 ring-inset bg-blue-50/10': dropTargetId === '{{ $elementId }}' }"
            >
                @if(count($children) > 0)
                    @foreach($children as $index => $child)
                        {{-- Drop zone indicator before each child --}}
                        <div
                            class="drop-zone-indicator h-1 transition-all duration-200 rounded"
                            :class="{ 'h-8 bg-blue-400/30 border-2 border-dashed border-blue-400': dropTargetId === '{{ $elementId }}' && dropPosition === {{ $index }} }"
                            data-drop-position="{{ $index }}"
                            x-on:dragover.prevent="setDropPosition({{ $index }})"
                        ></div>
                        @include('livewire.products.visual-description.block-builder.partials.element-renderer', ['element' => $child, 'depth' => $depth + 1])
                    @endforeach
                    {{-- Drop zone at end --}}
                    <div
                        class="drop-zone-indicator h-1 transition-all duration-200 rounded"
                        :class="{ 'h-8 bg-blue-400/30 border-2 border-dashed border-blue-400': dropTargetId === '{{ $elementId }}' && dropPosition === {{ count($children) }} }"
                        data-drop-position="{{ count($children) }}"
                        x-on:dragover.prevent="setDropPosition({{ count($children) }})"
                    ></div>
                @else
                    <div
                        class="flex items-center justify-center border-2 border-dashed border-gray-200 rounded p-4 text-gray-400 text-sm min-h-[60px] transition-colors"
                        :class="{ 'border-blue-400 bg-blue-50/20 text-blue-400': dropTargetId === '{{ $elementId }}' }"
                    >
                        <span x-text="dropTargetId === '{{ $elementId }}' ? 'Upusc tutaj' : 'Przeciagnij elementy tutaj'">Przeciagnij elementy tutaj</span>
                    </div>
                @endif
            </div>
            @break

        @case('grid')
            @php
                $gridColumns = $element['gridColumns'] ?? 2;
            @endphp
            <div
                @class(array_merge($classes, ['drop-container transition-colors duration-200']))
                style="{{ collect($styles)->map(fn($v, $k) => \Illuminate\Support\Str::kebab($k) . ': ' . $v)->implode('; ') }}"
                :class="{ 'ring-2 ring-blue-400 ring-inset bg-blue-50/10': dropTargetId === '{{ $elementId }}' }"
            >
                @if(count($children) > 0)
                    @foreach($children as $index => $child)
                        <div class="relative">
                            {{-- Drop zone indicator --}}
                            <div
                                class="absolute -top-1 left-0 right-0 h-0.5 transition-all duration-200"
                                :class="{ 'h-2 bg-blue-400': dropTargetId === '{{ $elementId }}' && dropPosition === {{ $index }} }"
                            ></div>
                            @include('livewire.products.visual-description.block-builder.partials.element-renderer', ['element' => $child, 'depth' => $depth + 1])
                        </div>
                    @endforeach
                @else
                    {{-- Show grid placeholders --}}
                    @for($i = 0; $i < $gridColumns; $i++)
                        <div
                            class="flex items-center justify-center border-2 border-dashed border-gray-200 rounded p-4 text-gray-400 text-sm min-h-[80px] transition-colors"
                            :class="{ 'border-blue-400 bg-blue-50/20': dropTargetId === '{{ $elementId }}' }"
                        >
                            <span>Kolumna {{ $i + 1 }}</span>
                        </div>
                    @endfor
                @endif
            </div>
            @break

        @case('background')
            @php
                $bgImage = $element['backgroundImage'] ?? '';
                $overlayColor = $element['overlayColor'] ?? '';
                $overlayOpacity = $element['overlayOpacity'] ?? 0.5;
            @endphp
            <div
                @class(array_merge($classes, ['relative overflow-hidden drop-container transition-colors duration-200']))
                style="{{ collect($styles)->map(fn($v, $k) => \Illuminate\Support\Str::kebab($k) . ': ' . $v)->implode('; ') }}"
                :class="{ 'ring-2 ring-blue-400 ring-inset': dropTargetId === '{{ $elementId }}' }"
            >
                {{-- Overlay layer --}}
                @if($overlayColor)
                    <div
                        class="absolute inset-0 z-0"
                        style="background-color: {{ $overlayColor }}; opacity: {{ $overlayOpacity }};"
                    ></div>
                @endif

                {{-- Content layer --}}
                <div class="relative z-10">
                    @if(count($children) > 0)
                        @foreach($children as $index => $child)
                            {{-- Drop zone indicator --}}
                            <div
                                class="drop-zone-indicator h-1 transition-all duration-200 rounded"
                                :class="{ 'h-6 bg-blue-400/40 border border-dashed border-blue-400': dropTargetId === '{{ $elementId }}' && dropPosition === {{ $index }} }"
                            ></div>
                            @include('livewire.products.visual-description.block-builder.partials.element-renderer', ['element' => $child, 'depth' => $depth + 1])
                        @endforeach
                        <div
                            class="drop-zone-indicator h-1 transition-all duration-200 rounded"
                            :class="{ 'h-6 bg-blue-400/40 border border-dashed border-blue-400': dropTargetId === '{{ $elementId }}' && dropPosition === {{ count($children) }} }"
                        ></div>
                    @else
                        <div
                            class="flex flex-col items-center justify-center border-2 border-dashed border-gray-300 rounded p-8 text-gray-400 text-sm min-h-[150px] transition-colors"
                            :class="{ 'border-blue-400 bg-blue-50/20 text-blue-400': dropTargetId === '{{ $elementId }}' }"
                        >
                            @if(empty($bgImage))
                                <svg class="w-10 h-10 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <span x-text="dropTargetId === '{{ $elementId }}' ? 'Upusc tutaj' : 'Sekcja z tlem'">Sekcja z tlem</span>
                                <span class="text-xs mt-1" x-show="dropTargetId !== '{{ $elementId }}'">Ustaw obraz w panelu wlasciwosci</span>
                            @else
                                <span x-text="dropTargetId === '{{ $elementId }}' ? 'Upusc tutaj' : 'Przeciagnij elementy tutaj'">Przeciagnij elementy tutaj</span>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
            @break

        @case('repeater')
            @php
                $items = $element['items'] ?? [];
                $itemLayout = $element['itemLayout'] ?? 'list';
            @endphp
            <div
                @class($classes)
                style="{{ collect($styles)->map(fn($v, $k) => \Illuminate\Support\Str::kebab($k) . ': ' . $v)->implode('; ') }}"
            >
                @if(count($items) > 0)
                    @foreach($items as $index => $item)
                        <div class="relative group/item">
                            {{-- Item index badge --}}
                            <div class="absolute -top-2 -left-2 w-5 h-5 bg-amber-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center opacity-0 group-hover/item:opacity-100 transition-opacity z-10">
                                {{ $index + 1 }}
                            </div>
                            @include('livewire.products.visual-description.block-builder.partials.element-renderer', ['element' => $item])
                        </div>
                    @endforeach
                @else
                    <div class="flex items-center justify-center border-2 border-dashed border-gray-200 rounded p-4 text-gray-400 text-sm min-h-[60px]">
                        <span>Dodaj elementy do listy</span>
                    </div>
                @endif
            </div>
            @break

        @case('slide')
            @php
                $bgImage = $element['backgroundImage'] ?? '';
                $overlayColor = $element['overlayColor'] ?? '#000000';
                $overlayOpacity = $element['overlayOpacity'] ?? 0.3;
                $slideIndex = $element['slideIndex'] ?? 0;
            @endphp
            <div
                @class(array_merge($classes, ['relative overflow-hidden drop-container transition-colors duration-200']))
                style="{{ collect($styles)->map(fn($v, $k) => \Illuminate\Support\Str::kebab($k) . ': ' . $v)->implode('; ') }}"
                :class="{ 'ring-2 ring-blue-400 ring-inset': dropTargetId === '{{ $elementId }}' }"
            >
                {{-- Slide number indicator --}}
                <div class="absolute top-2 left-2 bg-gray-900/60 text-white text-xs px-2 py-1 rounded z-20">
                    Slajd {{ $slideIndex + 1 }}
                </div>

                {{-- Overlay layer --}}
                @if($overlayColor)
                    <div
                        class="absolute inset-0 z-0"
                        style="background-color: {{ $overlayColor }}; opacity: {{ $overlayOpacity }};"
                    ></div>
                @endif

                {{-- Content layer --}}
                <div class="relative z-10 w-full">
                    @if(count($children) > 0)
                        @foreach($children as $index => $child)
                            {{-- Drop zone indicator --}}
                            <div
                                class="drop-zone-indicator h-1 transition-all duration-200 rounded"
                                :class="{ 'h-6 bg-blue-400/40 border border-dashed border-blue-300': dropTargetId === '{{ $elementId }}' && dropPosition === {{ $index }} }"
                            ></div>
                            @include('livewire.products.visual-description.block-builder.partials.element-renderer', ['element' => $child, 'depth' => $depth + 1])
                        @endforeach
                        <div
                            class="drop-zone-indicator h-1 transition-all duration-200 rounded"
                            :class="{ 'h-6 bg-blue-400/40 border border-dashed border-blue-300': dropTargetId === '{{ $elementId }}' && dropPosition === {{ count($children) }} }"
                        ></div>
                    @else
                        <div
                            class="flex flex-col items-center justify-center border-2 border-dashed border-white/30 rounded p-8 text-white/60 text-sm min-h-[200px] transition-colors"
                            :class="{ 'border-blue-400 bg-blue-500/20 text-blue-300': dropTargetId === '{{ $elementId }}' }"
                        >
                            @if(empty($bgImage))
                                <svg class="w-12 h-12 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z"/>
                                </svg>
                                <span x-text="dropTargetId === '{{ $elementId }}' ? 'Upusc tutaj' : 'Slajd {{ $slideIndex + 1 }}'">Slajd {{ $slideIndex + 1 }}</span>
                                <span class="text-xs mt-1" x-show="dropTargetId !== '{{ $elementId }}'">Dodaj tresc lub ustaw tlo</span>
                            @else
                                <span x-text="dropTargetId === '{{ $elementId }}' ? 'Upusc tutaj' : 'Przeciagnij elementy tutaj'">Przeciagnij elementy tutaj</span>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
            @break

        @case('raw-html')
            <div
                @class($classes)
                style="{{ collect($styles)->map(fn($v, $k) => \Illuminate\Support\Str::kebab($k) . ': ' . $v)->implode('; ') }}"
            >
                {!! $content !!}
            </div>
            @break

        @default
            <div
                @class($classes)
                style="{{ collect($styles)->map(fn($v, $k) => \Illuminate\Support\Str::kebab($k) . ': ' . $v)->implode('; ') }}"
            >
                @if(count($children) > 0)
                    @foreach($children as $child)
                        @include('livewire.products.visual-description.block-builder.partials.element-renderer', ['element' => $child])
                    @endforeach
                @else
                    {{ $content }}
                @endif
            </div>
    @endswitch

    {{-- Resize Handles (visible on selected, resizable elements) --}}
    @if($isSelected && !$isLocked)
    @php
        $resizable = !in_array($elementType, ['row', 'column']); // Rows/columns auto-size
    @endphp
    @if($resizable)
    <div
        class="resize-handles absolute inset-0 pointer-events-none"
        x-data="{ resizing: false, startX: 0, startY: 0, startW: 0, startH: 0, handle: '' }"
    >
        {{-- Right handle (width) --}}
        <div
            class="absolute right-0 top-1/2 -translate-y-1/2 translate-x-1/2 w-3 h-8 bg-amber-500 rounded cursor-e-resize pointer-events-auto hover:bg-amber-400 transition-colors"
            x-on:mousedown.stop.prevent="
                resizing = true;
                handle = 'e';
                startX = $event.clientX;
                startW = $el.closest('.element-node').offsetWidth;
            "
            x-on:touchstart.stop.prevent="
                resizing = true;
                handle = 'e';
                startX = $event.touches[0].clientX;
                startW = $el.closest('.element-node').offsetWidth;
            "
            title="Zmien szerokosc"
        ></div>

        {{-- Bottom handle (height) --}}
        <div
            class="absolute bottom-0 left-1/2 -translate-x-1/2 translate-y-1/2 w-8 h-3 bg-amber-500 rounded cursor-s-resize pointer-events-auto hover:bg-amber-400 transition-colors"
            x-on:mousedown.stop.prevent="
                resizing = true;
                handle = 's';
                startY = $event.clientY;
                startH = $el.closest('.element-node').offsetHeight;
            "
            x-on:touchstart.stop.prevent="
                resizing = true;
                handle = 's';
                startY = $event.touches[0].clientY;
                startH = $el.closest('.element-node').offsetHeight;
            "
            title="Zmien wysokosc"
        ></div>

        {{-- Bottom-right corner handle (both) --}}
        <div
            class="absolute bottom-0 right-0 translate-x-1/2 translate-y-1/2 w-4 h-4 bg-amber-500 rounded cursor-se-resize pointer-events-auto hover:bg-amber-400 transition-colors"
            x-on:mousedown.stop.prevent="
                resizing = true;
                handle = 'se';
                startX = $event.clientX;
                startY = $event.clientY;
                startW = $el.closest('.element-node').offsetWidth;
                startH = $el.closest('.element-node').offsetHeight;
            "
            x-on:touchstart.stop.prevent="
                resizing = true;
                handle = 'se';
                startX = $event.touches[0].clientX;
                startY = $event.touches[0].clientY;
                startW = $el.closest('.element-node').offsetWidth;
                startH = $el.closest('.element-node').offsetHeight;
            "
            title="Zmien rozmiar"
        ></div>

        {{-- Global mouse/touch listeners for resize --}}
        <template x-if="resizing">
            <div
                class="fixed inset-0 z-[9999] pointer-events-auto"
                x-on:mousemove.stop.prevent="
                    if (!resizing) return;
                    const dx = $event.clientX - startX;
                    const dy = $event.clientY - startY;
                    let newW = startW, newH = startH;
                    if (handle === 'e' || handle === 'se') newW = Math.max(50, startW + dx);
                    if (handle === 's' || handle === 'se') newH = Math.max(30, startH + dy);
                    $wire.updateElementSize('{{ $elementId }}', newW, newH, handle);
                "
                x-on:touchmove.stop.prevent="
                    if (!resizing) return;
                    const dx = $event.touches[0].clientX - startX;
                    const dy = $event.touches[0].clientY - startY;
                    let newW = startW, newH = startH;
                    if (handle === 'e' || handle === 'se') newW = Math.max(50, startW + dx);
                    if (handle === 's' || handle === 'se') newH = Math.max(30, startH + dy);
                    $wire.updateElementSize('{{ $elementId }}', newW, newH, handle);
                "
                x-on:mouseup="resizing = false; $wire.commitResize()"
                x-on:touchend="resizing = false; $wire.commitResize()"
                x-on:mouseleave="resizing = false"
                x-bind:style="'cursor: ' + (handle === 'se' ? 'se-resize' : (handle === 's' ? 's-resize' : 'e-resize'))"
            ></div>
        </template>
    </div>
    @endif
    @endif
</div>
@endif

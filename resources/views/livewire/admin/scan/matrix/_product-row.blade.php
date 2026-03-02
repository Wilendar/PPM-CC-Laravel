{{--
    Partial: _product-row.blade.php
    Pojedynczy wiersz produktu w tabeli macierzowej.

    Zmienne:
    - $product: Product model z matrix_cells
    - $sources: array [{type, id, name, icon, color, is_shop}]
    - $selectedProducts: array product IDs
    - $expandedDiffs: array product IDs
--}}

{{-- Product row --}}
<tr wire:key="product-{{ $product->id }}"
    class="odd:bg-gray-800/30 even:bg-gray-900/20 hover:bg-gray-700/40 transition-colors duration-100">

    {{-- Checkbox --}}
    <td class="px-3 py-2 w-10">
        <input type="checkbox"
               wire:key="matrix-select-{{ $product->id }}"
               wire:model.live="selectedProducts"
               value="{{ $product->id }}"
               class="rounded border-gray-600 bg-gray-700 text-orange-500 focus:ring-orange-500 focus:ring-offset-gray-800 cursor-pointer">
    </td>

    {{-- SKU - sticky --}}
    <td class="matrix-sticky-col px-3 py-2">
        <span class="font-mono text-xs text-gray-300 whitespace-nowrap">
            {{ $product->sku }}
        </span>
    </td>

    {{-- Nazwa - dynamiczne obcinanie przez CSS truncate --}}
    <td class="px-3 py-2 matrix-name-col">
        <div class="flex items-center space-x-1 min-w-0">
            <span class="truncate text-gray-200" title="{{ $product->name }}">
                {{ $product->name }}
            </span>
            <button wire:click="toggleDiffViewer({{ $product->id }})"
                    class="flex-shrink-0 text-gray-500 hover:text-gray-300 transition-colors duration-100"
                    title="Podglad roznic">
                <svg class="w-3.5 h-3.5 transition-transform duration-200 {{ in_array($product->id, $expandedDiffs) ? 'rotate-180 text-[#e0ac7e]' : '' }}"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
        </div>
    </td>

    {{-- Marka --}}
    <td class="px-3 py-2">
        <span class="text-xs text-gray-400 whitespace-nowrap">
            {{ $product->manufacturerRelation?->name ?? '—' }}
        </span>
    </td>

    {{-- Source cells with hover actions --}}
    @foreach($sources as $source)
        @php
            $sourceKey = $source['type'] . '_' . $source['id'];
            $cell = $product->matrix_cells[$sourceKey] ?? ['status' => 'unknown'];
            $status = $cell['status'] ?? 'unknown';
            $sourceType = $source['type'];
            $sourceId = $source['id'];
            $hasDirectAction = in_array($status, ['not_linked', 'not_found', 'unknown', 'ignored']);
        @endphp
        @if($this->isSourceVisible($sourceKey))

        <td wire:key="td-{{ $product->id }}-{{ $sourceKey }}"
            class="px-1 py-1.5 text-center matrix-cell matrix-cell--{{ $status }} matrix-cell-hover-parent"
            style="position:relative; min-width:60px; height:36px;">

            {{-- Default icon (hidden via CSS parent:hover for actionable cells) --}}
            <div class="{{ $hasDirectAction ? 'matrix-cell-icon' : 'cursor-pointer' }}"
                 @if(!$hasDirectAction) wire:click="openPopup({{ $product->id }}, '{{ $sourceKey }}')" @endif>
                @switch($status)
                    @case('linked')
                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-green-900/40 text-green-400 mx-auto">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        </span>
                        @break
                    @case('not_linked')
                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-blue-900/40 text-blue-400 mx-auto">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                        </span>
                        @break
                    @case('not_found')
                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-red-900/40 text-red-400 mx-auto">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </span>
                        @break
                    @case('unknown')
                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-gray-700/60 text-gray-400 mx-auto">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </span>
                        @break
                    @case('ignored')
                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-gray-800/60 text-gray-600 mx-auto">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M3 3l18 18"/></svg>
                        </span>
                        @break
                    @case('conflict')
                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-yellow-900/40 text-yellow-400 mx-auto cursor-pointer" wire:click="openPopup({{ $product->id }}, '{{ $sourceKey }}')">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        </span>
                        @break
                    @case('brand_not_allowed')
                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-gray-700 text-gray-500 mx-auto cursor-pointer" wire:click="openPopup({{ $product->id }}, '{{ $sourceKey }}')">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
                        </span>
                        @break
                    @case('pending_sync')
                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-blue-900/40 text-blue-400 mx-auto cursor-pointer" wire:click="openPopup({{ $product->id }}, '{{ $sourceKey }}')">
                            <svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        </span>
                        @break
                @endswitch
            </div>

            {{-- Hover actions overlay (CSS only, no x-show to avoid Livewire morph issues) --}}
            @if($hasDirectAction)
            <div class="matrix-cell-actions absolute inset-0 flex items-center justify-center gap-0.5">

                @if($status === 'not_linked')
                    {{-- Powiaz --}}
                    <button wire:click="cellAction({{ $product->id }}, '{{ $sourceType }}', {{ $sourceId }}, 'link')"
                            class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-blue-500/80 text-white hover:bg-blue-500 transition-colors" title="Powiaz">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                    </button>
                @elseif($status === 'not_found')
                    {{-- Eksportuj --}}
                    <button wire:click="cellAction({{ $product->id }}, '{{ $sourceType }}', {{ $sourceId }}, 'publish')"
                            class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-red-500/80 text-white hover:bg-red-500 transition-colors" title="Eksportuj">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                    </button>
                @elseif($status === 'ignored')
                    {{-- Przywroc --}}
                    <button wire:click="cellAction({{ $product->id }}, '{{ $sourceType }}', {{ $sourceId }}, 'unignore')"
                            class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-amber-500/80 text-white hover:bg-amber-500 transition-colors" title="Przywroc">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    </button>
                @endif

                {{-- Ignoruj (dla not_linked, not_found, unknown) --}}
                @if(in_array($status, ['not_linked', 'not_found', 'unknown']))
                    <button wire:click="cellAction({{ $product->id }}, '{{ $sourceType }}', {{ $sourceId }}, 'ignore')"
                            class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-gray-600/80 text-gray-300 hover:bg-gray-500 transition-colors" title="Ignoruj">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                @endif
            </div>
            @endif

        </td>
        @endif
    @endforeach

</tr>

{{-- Diff viewer expandable row --}}
@if(in_array($product->id, $expandedDiffs))
    <tr wire:key="diff-{{ $product->id }}">
        <td colspan="{{ 4 + count($sources) }}" class="p-0">
            @include('livewire.admin.scan.matrix.diff-viewer', ['product' => $product])
        </td>
    </tr>
@endif

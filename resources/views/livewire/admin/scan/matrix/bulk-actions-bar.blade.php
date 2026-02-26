{{-- Bulk Actions Bar - sticky miedzy toolbar a tabela --}}
@if(count($selectedProducts) > 0)
<div class="sticky top-0 z-10 flex flex-wrap items-center gap-3 px-4 py-2.5 mb-2 bg-gray-800/95 backdrop-blur-sm border border-gray-600 rounded-lg shadow-lg">

    <span class="text-sm text-white font-medium">{{ count($selectedProducts) }} zaznaczonych</span>

    <div class="h-5 border-l border-gray-600"></div>

    {{-- Powiaz niepowiazane --}}
    <div x-data="{ open: false }" class="relative">
        <button @click="open = !open"
                class="flex items-center gap-1.5 px-3 py-1.5 bg-blue-900/30 text-blue-400 text-xs font-medium rounded-lg border border-blue-500/30 hover:border-blue-500/50 transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
            </svg>
            Powiaz
        </button>
        <div x-show="open" @click.away="open = false" x-transition
             class="absolute top-full left-0 mt-1 w-52 bg-gray-800 border border-gray-600 rounded-lg shadow-xl z-20 py-1">
            <button wire:click="bulkAction('link_all')" @click="open = false" class="matrix-popup-action text-blue-400 font-medium">Wszystkie platformy</button>
            @foreach($sources as $source)
                <button wire:click="bulkAction('link_source', '{{ $source['type'] . '_' . $source['id'] }}')" @click="open = false" class="matrix-popup-action">{{ $source['name'] }}</button>
            @endforeach
        </div>
    </div>

    {{-- Eksportuj nie znalezione --}}
    <div x-data="{ open: false }" class="relative">
        <button @click="open = !open"
                class="flex items-center gap-1.5 px-3 py-1.5 bg-red-900/30 text-red-400 text-xs font-medium rounded-lg border border-red-500/30 hover:border-red-500/50 transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
            </svg>
            Eksportuj
        </button>
        <div x-show="open" @click.away="open = false" x-transition
             class="absolute top-full left-0 mt-1 w-52 bg-gray-800 border border-gray-600 rounded-lg shadow-xl z-20 py-1">
            <button wire:click="bulkAction('export_all')" @click="open = false" class="matrix-popup-action text-red-400 font-medium">Wszystkie platformy</button>
            @foreach($sources as $source)
                <button wire:click="bulkAction('export_source', '{{ $source['type'] . '_' . $source['id'] }}')" @click="open = false" class="matrix-popup-action">{{ $source['name'] }}</button>
            @endforeach
        </div>
    </div>

    {{-- Ignoruj --}}
    <div x-data="{ open: false }" class="relative">
        <button @click="open = !open"
                class="flex items-center gap-1.5 px-3 py-1.5 bg-gray-700 text-gray-400 text-xs font-medium rounded-lg border border-gray-600 hover:border-gray-500 transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M3 3l18 18"/>
            </svg>
            Ignoruj
        </button>
        <div x-show="open" @click.away="open = false" x-transition
             class="absolute top-full left-0 mt-1 w-52 bg-gray-800 border border-gray-600 rounded-lg shadow-xl z-20 py-1">
            <button wire:click="bulkAction('ignore_all')" @click="open = false" class="matrix-popup-action text-gray-400 font-medium">Wszystkie platformy</button>
            @foreach($sources as $source)
                <button wire:click="bulkAction('ignore_source', '{{ $source['type'] . '_' . $source['id'] }}')" @click="open = false" class="matrix-popup-action">{{ $source['name'] }}</button>
            @endforeach
        </div>
    </div>

    <div class="flex-1"></div>

    {{-- Export --}}
    <button wire:click="exportMatrix('xlsx')" class="flex items-center gap-1 px-2.5 py-1.5 text-xs text-gray-400 hover:text-white transition-colors">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
        XLSX
    </button>
    <button wire:click="exportMatrix('csv')" class="flex items-center gap-1 px-2.5 py-1.5 text-xs text-gray-400 hover:text-white transition-colors">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
        CSV
    </button>
</div>
@endif

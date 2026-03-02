{{-- Bulk Actions Bar - sticky miedzy toolbar a tabela --}}
@if(count($selectedProducts) > 0 || $selectAllMatching)
<div class="sticky top-0 z-10 mb-2 bg-gray-800/95 backdrop-blur-sm border border-gray-600 rounded-lg shadow-lg">

    {{-- Selection info + selectAllMatching banner --}}
    @if($selectAll && !$selectAllMatching && $totalMatchingCount > count($selectedProducts))
        <div class="flex items-center gap-2 px-4 pt-2.5 pb-1 text-sm">
            <span class="text-gray-300">
                Zaznaczono {{ count($selectedProducts) }} na tej stronie.
            </span>
            <button wire:click="enableSelectAllMatching"
                    class="text-[#e0ac7e] hover:text-[#d1975a] font-medium underline underline-offset-2 transition-colors">
                Zaznacz wszystkie {{ number_format($totalMatchingCount, 0, ',', ' ') }} pasujacych
            </button>
        </div>
    @elseif($selectAllMatching)
        <div class="flex items-center gap-2 px-4 pt-2.5 pb-1 text-sm">
            <svg class="w-4 h-4 text-green-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <span class="text-green-400 font-medium">
                Zaznaczono wszystkie {{ number_format($this->getEffectiveSelectedCount(), 0, ',', ' ') }} pasujacych produktow
            </span>
            @if(count($excludedProducts) > 0)
                <span class="text-gray-500">({{ count($excludedProducts) }} wyklucz.)</span>
            @endif
            <button wire:click="clearSelection"
                    class="ml-1 text-gray-400 hover:text-white text-xs underline transition-colors">
                Wyczysc zaznaczenie
            </button>
        </div>
    @endif

    {{-- Actions row --}}
    <div class="flex flex-wrap items-center gap-3 px-4 py-2.5">

        <span class="text-sm text-white font-medium">
            {{ $selectAllMatching ? number_format($this->getEffectiveSelectedCount(), 0, ',', ' ') : count($selectedProducts) }} zaznaczonych
        </span>

        <div class="h-5 border-l border-gray-600"></div>

        {{-- Powiaz niepowiazane --}}
        @include('livewire.admin.scan.matrix._bulk-action-dropdown', [
            'actionAll' => 'link_all',
            'actionSource' => 'link_source',
            'label' => 'Powiaz',
            'colorBg' => 'bg-blue-900/30',
            'colorText' => 'text-blue-400',
            'colorBorder' => 'border-blue-500/30 hover:border-blue-500/50',
            'colorAccent' => 'text-blue-400',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>',
        ])

        {{-- Eksportuj nie znalezione --}}
        @include('livewire.admin.scan.matrix._bulk-action-dropdown', [
            'actionAll' => 'export_all',
            'actionSource' => 'export_source',
            'label' => 'Eksportuj',
            'colorBg' => 'bg-red-900/30',
            'colorText' => 'text-red-400',
            'colorBorder' => 'border-red-500/30 hover:border-red-500/50',
            'colorAccent' => 'text-red-400',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>',
        ])

        {{-- Ignoruj --}}
        @include('livewire.admin.scan.matrix._bulk-action-dropdown', [
            'actionAll' => 'ignore_all',
            'actionSource' => 'ignore_source',
            'label' => 'Ignoruj',
            'colorBg' => 'bg-gray-700',
            'colorText' => 'text-gray-400',
            'colorBorder' => 'border-gray-600 hover:border-gray-500',
            'colorAccent' => 'text-gray-400',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M3 3l18 18"/>',
        ])

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
</div>
@endif

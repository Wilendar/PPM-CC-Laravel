{{-- resources/views/livewire/products/management/partials/primary-image-preview.blade.php --}}
{{--
    ETAP_07d: Primary Image Preview Section for Basic Tab (LARGE VERSION)
    Shows primary product image with link to Gallery tab
    Position: Right column, full height (same as STAWKA VAT + Status + Harmonogram)
--}}

<div class="h-full enterprise-card p-4 bg-gray-800/50 border border-gray-700 rounded-lg flex flex-col">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-4">
        <h4 class="text-sm font-medium text-gray-200">
            Tu okladka, zdjecie glowne
        </h4>

        {{-- Media count badge --}}
        @php
            $mediaCount = $product->media()->active()->count();
        @endphp
        @if($mediaCount > 0)
            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-900/50 text-blue-300 border border-blue-700/50">
                {{ $mediaCount }} zdjec
            </span>
        @endif
    </div>

    {{-- Primary Image Thumbnail - Large, Centered --}}
    <div class="flex-1 flex items-center justify-center min-h-[200px]">
        @php
            $primaryImageUrl = $product->primary_image ?? null;
            $hasImage = $primaryImageUrl && !str_contains($primaryImageUrl, 'placeholder');
        @endphp

        @if($hasImage)
            <div class="relative group cursor-pointer w-full h-full flex items-center justify-center" wire:click="switchTab('gallery')">
                <img src="{{ $primaryImageUrl }}"
                     alt="{{ $product->display_name }}"
                     class="max-w-full max-h-64 object-contain rounded-lg border border-gray-600 shadow-lg">

                {{-- Overlay on hover --}}
                <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity rounded-lg flex items-center justify-center">
                    <div class="text-center">
                        <svg class="w-10 h-10 text-white mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span class="text-white text-sm font-medium">Zarzadzaj galeria</span>
                    </div>
                </div>
            </div>
        @else
            {{-- Placeholder (large) --}}
            <div class="w-full h-full min-h-[200px] rounded-lg border-2 border-dashed border-gray-600 bg-gray-700/30 flex flex-col items-center justify-center">
                <svg class="w-16 h-16 text-gray-500 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <span class="text-sm text-gray-400 mb-1">Brak zdjecia</span>
                <span class="text-xs text-gray-500">Kliknij aby dodac</span>
            </div>
        @endif
    </div>

    {{-- Action Button - Link to Gallery Tab --}}
    <div class="mt-4">
        <button type="button"
                wire:click="switchTab('gallery')"
                class="w-full inline-flex items-center justify-center px-4 py-2.5 text-sm font-medium rounded-md
                       {{ $hasImage
                          ? 'bg-gray-700 text-gray-200 hover:bg-gray-600 border border-gray-600'
                          : 'bg-blue-600 text-white hover:bg-blue-700' }}
                       transition-colors duration-200">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            {{ $hasImage ? 'Zarzadzaj galeria' : 'Dodaj zdjecia' }}
        </button>
    </div>
</div>

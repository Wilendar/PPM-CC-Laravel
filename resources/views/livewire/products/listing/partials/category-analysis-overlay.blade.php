{{-- ETAP_07 FAZA 3D: Category Analysis Loading Overlay --}}
@if($isAnalyzingCategories)
<div class="fixed inset-0 layer-overlay flex items-center justify-center bg-black/70 backdrop-blur-sm">
    <div class="bg-gradient-to-br from-gray-800 via-gray-900 to-black rounded-2xl shadow-2xl p-8 max-w-md w-full mx-4 border border-gray-700">

        {{-- Header --}}
        <div class="text-center mb-6">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-orange-500/10 mb-4">
                {{-- Animated Spinner SVG --}}
                <svg class="animate-spin h-10 w-10 text-orange-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>

            <h3 class="text-xl font-bold text-white mb-2">
                Analizuję kategorie...
            </h3>

            @if($analyzingShopName)
            <p class="text-sm text-gray-400">
                Sklep: <span class="text-orange-400 font-medium">{{ $analyzingShopName }}</span>
            </p>
            @endif
        </div>

        {{-- Message --}}
        <div class="space-y-3 mb-6">
            <div class="flex items-start space-x-3">
                <svg class="w-5 h-5 text-orange-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-sm text-gray-300">
                    Sprawdzam jakie kategorie muszą zostać utworzone w PPM przed importem produktów
                </p>
            </div>

            <div class="flex items-start space-x-3">
                <svg class="w-5 h-5 text-blue-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-sm text-gray-400">
                    To może potrwać <span class="text-blue-400 font-medium">3-5 sekund</span>
                </p>
            </div>
        </div>

        {{-- Progress Bar --}}
        <div class="relative pt-1">
            <div class="overflow-hidden h-2 text-xs flex rounded-full bg-gray-700">
                <div class="animate-pulse bg-gradient-to-r from-orange-500 to-orange-600" style="width: 100%"></div>
            </div>
        </div>

        {{-- Footer Note --}}
        <p class="text-xs text-gray-500 text-center mt-4">
            Za chwilę otrzymasz podgląd kategorii do utworzenia
        </p>
    </div>
</div>
@endif

{{-- ETAP_07 FAZA 3D: Category Preview Modal --}}
<livewire:components.category-preview-modal />

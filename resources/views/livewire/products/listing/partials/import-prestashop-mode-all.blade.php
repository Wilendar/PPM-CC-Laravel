{{-- MODE: All Products --}}
<div class="p-6 bg-yellow-900/20 rounded-lg">
    <h4 class="font-semibold text-white mb-2">
        <svg class="w-5 h-5 inline-block mr-1 text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
        Import wszystkich produktów
    </h4>
    <p class="text-sm text-gray-400 mb-4">
        Zaimportujesz WSZYSTKIE produkty ze sklepu PrestaShop.
        Operacja może zająć kilka minut w zależności od liczby produktów.
    </p>

    {{-- Variant Import Checkbox --}}
    <div class="mb-4">
        <label class="flex items-center text-sm text-gray-300 cursor-pointer hover:text-white transition-colors">
            <input type="checkbox"
                   wire:model.live="importWithVariants"
                   class="form-checkbox mr-2 text-orange-500 rounded border-gray-500 focus:ring-orange-500">
            <span>Automatycznie importuj brakujace warianty z PrestaShop</span>
        </label>
        <p class="text-xs text-gray-500 mt-1 ml-6">
            Dla produktow z wariantami (combinations) zostana utworzone odpowiednie warianty w PPM
        </p>
    </div>

    <button wire:click="importAllProducts"
            class="btn-enterprise-primary inline-flex items-center">
        <svg class="w-4 h-4 inline-block mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"/></svg>
        Rozpocznij import wszystkich produktów
    </button>
</div>

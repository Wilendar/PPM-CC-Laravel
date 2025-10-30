<div class="enterprise-card">
    {{-- Header with title + actions --}}
    <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-4 mb-6">
        <div>
            <h2 class="text-h2">Zarządzanie Wariantami</h2>
            <p class="text-gray-400 text-sm mt-1">Twórz i zarządzaj wariantami produktów</p>
        </div>
        <div class="flex flex-col sm:flex-row gap-3">
            <button wire:click="openAutoGenerateModal" class="btn-enterprise-primary">
                🔄 Generuj Warianty Automatycznie
            </button>
            <button class="btn-enterprise-secondary">
                📥 Import z CSV
            </button>
        </div>
    </div>

    {{-- Filters --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="md:col-span-2">
            <label class="form-label">Produkt rodzic</label>
            <input type="text"
                   wire:model.live.debounce.300ms="searchParent"
                   class="form-input"
                   placeholder="Szukaj po SKU lub nazwie produktu rodzica">
        </div>
        <div>
            <label class="form-label">Typ atrybutu</label>
            <select wire:model.live="filterAttributeType" class="form-select">
                <option value="">Wszystkie</option>
                @foreach($this->attributeTypes as $type)
                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Reset filters button --}}
    @if($searchParent || $filterAttributeType)
        <div class="mb-4">
            <button wire:click="resetFilters" class="text-sm text-blue-400 hover:text-blue-300">
                ✕ Wyczyść filtry
            </button>
        </div>
    @endif

    {{-- Bulk operations (if selected) --}}
    @if(count($selectedVariants) > 0)
        <div class="bg-blue-500/10 border border-blue-500/30 rounded-lg p-4 mb-6">
            <div class="flex items-center justify-between">
                <span class="text-blue-300">☑️ Zaznaczono {{ count($selectedVariants) }} wariantów</span>
                <div class="flex gap-2">
                    <button wire:click="bulkUpdatePrices"
                            class="btn-enterprise-sm bg-green-500/20 hover:bg-green-500/30 border-green-500/40">
                        💰 Masowa Zmiana Cen
                    </button>
                    <button wire:click="bulkUpdateStock"
                            class="btn-enterprise-sm bg-blue-500/20 hover:bg-blue-500/30 border-blue-500/40">
                        📦 Masowa Zmiana Stanów
                    </button>
                    <button wire:click="bulkAssignImages"
                            class="btn-enterprise-sm bg-purple-500/20 hover:bg-purple-500/30 border-purple-500/40">
                        🖼️ Przypisz Zdjęcia
                    </button>
                    <button wire:click="bulkDelete"
                            wire:confirm="Czy na pewno chcesz usunąć zaznaczone warianty?"
                            class="btn-enterprise-sm bg-red-500/20 hover:bg-red-500/30 border-red-500/40">
                        ❌ Usuń
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Variants table --}}
    <div class="overflow-x-auto">
        <table class="enterprise-table min-w-full">
            <thead>
                <tr>
                    <th class="w-12">
                        <input type="checkbox"
                               wire:model.live="selectAll"
                               aria-label="Zaznacz wszystkie warianty"
                               class="rounded border-gray-600 bg-gray-700 text-blue-500 focus:ring-blue-500">
                    </th>
                    <th wire:click="sortBy('sku')"
                        role="button"
                        aria-label="Sortuj po SKU"
                        tabindex="0"
                        class="cursor-pointer hover:bg-gray-700/50">
                        <div class="flex items-center gap-1">
                            SKU Wariantu
                            @if($sortField === 'sku')
                                <span class="text-blue-400" aria-label="{{ $sortDirection === 'asc' ? 'Sortowanie rosnące' : 'Sortowanie malejące' }}">
                                    {{ $sortDirection === 'asc' ? '↑' : '↓' }}
                                </span>
                            @endif
                        </div>
                    </th>
                    <th>Produkt Rodzic</th>
                    <th>Atrybuty</th>
                    <th wire:click="sortBy('prices')"
                        role="button"
                        aria-label="Sortuj po cenie"
                        tabindex="0"
                        class="cursor-pointer hover:bg-gray-700/50">
                        <div class="flex items-center gap-1">
                            Cena
                            @if($sortField === 'prices')
                                <span class="text-blue-400" aria-label="{{ $sortDirection === 'asc' ? 'Sortowanie rosnące' : 'Sortowanie malejące' }}">
                                    {{ $sortDirection === 'asc' ? '↑' : '↓' }}
                                </span>
                            @endif
                        </div>
                    </th>
                    <th>Stan</th>
                    <th>Zdjęcia</th>
                    <th>Status</th>
                    <th class="text-right">Akcje</th>
                </tr>
            </thead>
            <tbody>
                @forelse($this->variants as $variant)
                    <tr wire:key="variant-{{ $variant->id }}">
                        <td>
                            <input type="checkbox"
                                   wire:model.live="selectedVariants"
                                   value="{{ $variant->id }}"
                                   aria-label="Zaznacz wariant {{ $variant->sku }}"
                                   class="rounded border-gray-600 bg-gray-700 text-blue-500 focus:ring-blue-500">
                        </td>
                        <td class="font-mono text-sm text-blue-400">{{ $variant->sku }}</td>
                        <td>
                            <div class="text-sm">
                                <div class="font-medium text-gray-200">{{ $variant->product->name }}</div>
                                <div class="text-gray-400 text-xs">{{ $variant->product->sku }}</div>
                            </div>
                        </td>
                        <td>
                            <div class="flex flex-wrap gap-1">
                                @foreach($variant->attributes as $attr)
                                    <span class="px-2 py-1 text-xs rounded-full bg-purple-500/20 border border-purple-500/30 text-purple-300">
                                        {{ $attr->attributeType?->name }}: {{ $attr->value }}
                                    </span>
                                @endforeach
                            </div>
                        </td>
                        <td>
                            @php
                                $defaultPrice = $variant->prices->first();
                            @endphp
                            @if($defaultPrice)
                                <div class="text-sm">
                                    <div class="font-medium text-green-400">{{ number_format($defaultPrice->price, 2) }} zł</div>
                                    @if($defaultPrice->price_special)
                                        <div class="text-xs text-gray-400 line-through">{{ number_format($defaultPrice->price_special, 2) }} zł</div>
                                    @endif
                                </div>
                            @else
                                <span class="text-gray-500 text-sm">Brak ceny</span>
                            @endif
                        </td>
                        <td>
                            @php
                                $totalStock = $variant->stock->sum('quantity');
                            @endphp
                            <div class="text-sm">
                                <span class="font-medium {{ $totalStock > 0 ? 'text-green-400' : 'text-red-400' }}">
                                    {{ $totalStock }}
                                </span>
                                <span class="text-gray-400 text-xs">szt.</span>
                            </div>
                        </td>
                        <td>
                            <div class="text-sm text-gray-400">
                                {{ $variant->images->count() }} {{ $variant->images->count() === 1 ? 'zdjęcie' : 'zdjęć' }}
                            </div>
                        </td>
                        <td>
                            @if($variant->is_active)
                                <span class="px-2 py-1 text-xs rounded-full bg-green-500/20 border border-green-500/30 text-green-400">
                                    ● Aktywny
                                </span>
                            @else
                                <span class="px-2 py-1 text-xs rounded-full bg-gray-500/20 border border-gray-500/30 text-gray-400">
                                    ○ Nieaktywny
                                </span>
                            @endif
                            @if($variant->is_default)
                                <span class="px-2 py-1 text-xs rounded-full bg-blue-500/20 border border-blue-500/30 text-blue-400 ml-1">
                                    ⭐ Domyślny
                                </span>
                            @endif
                        </td>
                        <td class="text-right">
                            <div class="flex justify-end gap-2">
                                <button wire:click="$dispatch('edit-variant', { id: {{ $variant->id }} })"
                                        aria-label="Edytuj wariant {{ $variant->sku }}"
                                        class="text-blue-400 hover:text-blue-300 text-sm">
                                    ⚙️ Edytuj
                                </button>
                                <button wire:click="$dispatch('delete-variant', { id: {{ $variant->id }} })"
                                        wire:confirm="Czy na pewno chcesz usunąć ten wariant?"
                                        aria-label="Usuń wariant {{ $variant->sku }}"
                                        class="text-red-400 hover:text-red-300 text-sm">
                                    🗑️ Usuń
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center py-12">
                            <div class="flex flex-col items-center justify-center">
                                <div class="text-6xl mb-4 opacity-50">📦</div>
                                <h3 class="text-xl font-semibold text-gray-300 mb-2">
                                    @if($searchParent || $filterAttributeType)
                                        Brak wariantów spełniających kryteria
                                    @else
                                        Brak wariantów produktów
                                    @endif
                                </h3>
                                <p class="text-gray-400 mb-4 text-sm max-w-md">
                                    @if($searchParent || $filterAttributeType)
                                        Spróbuj zmienić kryteria wyszukiwania lub wyczyść filtry
                                    @else
                                        Rozpocznij od wygenerowania wariantów automatycznie lub zaimportuj je z pliku CSV
                                    @endif
                                </p>
                                @if($searchParent || $filterAttributeType)
                                    <button wire:click="resetFilters" class="btn-enterprise-secondary">
                                        ✕ Wyczyść Filtry
                                    </button>
                                @else
                                    <div class="flex gap-3">
                                        <button wire:click="openAutoGenerateModal" class="btn-enterprise-primary">
                                            🔄 Generuj Warianty
                                        </button>
                                        <button class="btn-enterprise-secondary">
                                            📥 Import z CSV
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div class="mt-6">
        {{ $this->variants->links() }}
    </div>

    {{-- Auto-generate Modal (Alpine.js x-show) --}}
    <div x-data="{ show: @entangle('showAutoGenerateModal') }"
         x-show="show"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         style="display: none;">

        {{-- Overlay --}}
        <div class="fixed inset-0 bg-black/70 backdrop-blur-sm transition-opacity"
             @click="show = false"></div>

        {{-- Modal --}}
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full border border-gray-700"
                 @click.stop>

                {{-- Header --}}
                <div class="px-6 py-4 border-b border-gray-700">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xl font-semibold text-white">Generuj Warianty Automatycznie</h3>
                        <button @click="show = false" class="text-gray-400 hover:text-gray-300">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Body --}}
                <div class="px-6 py-4 space-y-4">

                    {{-- Select parent product --}}
                    <div>
                        <label class="form-label">Wybierz produkt rodzica *</label>
                        <select wire:model.live="selectedParentId" class="form-select">
                            <option value="">Wybierz produkt</option>
                            @foreach($this->products as $product)
                                <option value="{{ $product->id }}">{{ $product->sku }} - {{ $product->name }}</option>
                            @endforeach
                        </select>
                        @error('selectedParentId')
                            <span class="text-red-400 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Select attributes --}}
                    @if($selectedParentId)
                        <div>
                            <label class="form-label">Wybierz atrybuty *</label>
                            <div class="space-y-3 bg-gray-900/50 rounded-lg p-4 border border-gray-700">
                                @foreach($this->attributeTypes as $attrType)
                                    <div wire:key="attr-type-{{ $attrType->id }}">
                                        <label class="block text-sm font-medium text-gray-300 mb-2">
                                            {{ $attrType->name }}
                                        </label>
                                        <div class="flex flex-wrap gap-2">
                                            {{-- Database-backed values from AttributeValue model --}}
                                            @foreach($attrType->values()->where('is_active', true)->orderBy('position')->get() as $value)
                                                <label class="inline-flex items-center" wire:key="value-{{ $value->id }}">
                                                    <input type="checkbox"
                                                           wire:model.live="selectedAutoAttributes.{{ $attrType->id }}"
                                                           value="{{ $value->code }}"
                                                           class="rounded border-gray-600 bg-gray-700 text-blue-500 focus:ring-blue-500">
                                                    <span class="ml-2 text-sm text-gray-300">
                                                        @if($value->color_hex)
                                                            <span class="inline-block w-4 h-4 rounded-full mr-1 border border-gray-600"
                                                                  style="background-color: {{ $value->color_hex }}"></span>
                                                        @endif
                                                        {{ $value->label }}
                                                    </span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            @error('selectedAutoAttributes')
                                <span class="text-red-400 text-sm">{{ $message }}</span>
                            @enderror
                        </div>
                    @endif

                    {{-- SKU pattern preview --}}
                    @if($this->generatedVariantsCount > 0)
                        <div class="bg-blue-500/10 border border-blue-500/30 rounded-lg p-4">
                            <div class="text-sm font-medium text-blue-300 mb-2">
                                Preview: wygeneruje {{ $this->generatedVariantsCount }} wariantów
                            </div>
                            <div class="text-xs text-gray-400 space-y-1">
                                @foreach($this->generatedVariantsPreview as $preview)
                                    <div class="font-mono">
                                        - {{ $this->selectedParent?->sku }}-{{ strtoupper(implode('-', array_column($preview, 'value_code'))) }}
                                    </div>
                                @endforeach
                                @if($this->generatedVariantsCount > 5)
                                    <div class="text-gray-500">... ({{ $this->generatedVariantsCount - 5 }} więcej)</div>
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- Options --}}
                    <div class="space-y-2">
                        <label class="form-label">Opcje</label>
                        <label class="inline-flex items-center">
                            <input type="checkbox"
                                   wire:model="inheritPrices"
                                   class="rounded border-gray-600 bg-gray-700 text-blue-500 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-300">Dziedzicz ceny z produktu rodzica</span>
                        </label>
                        <label class="inline-flex items-center ml-4">
                            <input type="checkbox"
                                   wire:model="inheritStock"
                                   class="rounded border-gray-600 bg-gray-700 text-blue-500 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-300">Dziedzicz stany magazynowe</span>
                        </label>
                    </div>

                    {{-- Error messages --}}
                    @if($errors->has('generate'))
                        <div class="bg-red-500/10 border border-red-500/30 rounded-lg p-3 text-red-400 text-sm">
                            {{ $errors->first('generate') }}
                        </div>
                    @endif
                </div>

                {{-- Footer --}}
                <div class="px-6 py-4 border-t border-gray-700 flex justify-end gap-3">
                    <button @click="show = false"
                            class="btn-enterprise-secondary">
                        ❌ Anuluj
                    </button>
                    <button wire:click="generateVariants"
                            wire:loading.attr="disabled"
                            wire:target="generateVariants"
                            class="btn-enterprise-primary">
                        <span wire:loading.remove wire:target="generateVariants">🚀 Generuj</span>
                        <span wire:loading wire:target="generateVariants">Generowanie...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Flash messages --}}
    @if (session()->has('message'))
        <div x-data="{ show: true }"
             x-show="show"
             x-init="setTimeout(() => show = false, 3000)"
             class="fixed bottom-4 right-4 bg-green-500/20 border border-green-500/30 text-green-400 px-6 py-3 rounded-lg shadow-lg">
            {{ session('message') }}
        </div>
    @endif

    {{-- Bulk Operations Modals (FAZA 3 - 2025-10-24) --}}
    <livewire:admin.variants.bulk-prices-modal />
    <livewire:admin.variants.bulk-stock-modal />
    <livewire:admin.variants.bulk-images-modal />
</div>

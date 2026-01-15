{{-- ETAP_06 REDESIGN: Product row z inline category dropdowns --}}
@php
    $productCats = $product->category_ids ?? [];
    $shopIds = $product->shop_ids ?? [];

    // Get selected categories at each level
    $selectedL3 = \App\Models\Category::whereIn('id', $productCats)->where('level', 2)->first();
    $selectedL4 = $selectedL3
        ? \App\Models\Category::whereIn('id', $productCats)->where('parent_id', $selectedL3->id)->first()
        : null;
    $selectedL5 = $selectedL4
        ? \App\Models\Category::whereIn('id', $productCats)->where('parent_id', $selectedL4->id)->first()
        : null;
    $selectedL6 = $selectedL5
        ? \App\Models\Category::whereIn('id', $productCats)->where('parent_id', $selectedL5->id)->first()
        : null;

    // Check if L6 has children (for showing + button)
    $hasL4Options = $selectedL3 && \App\Models\Category::where('parent_id', $selectedL3->id)->where('is_active', true)->exists();
    $hasL5Options = $selectedL4 && \App\Models\Category::where('parent_id', $selectedL4->id)->where('is_active', true)->exists();
    $hasL6Options = $selectedL5 && \App\Models\Category::where('parent_id', $selectedL5->id)->where('is_active', true)->exists();

    // Status
    $percentage = $product->completion_percentage ?? 0;
    $isReady = $product->is_ready_for_publish ?? false;

    // Show expanded levels based on selection
    $showL4 = $selectedL3 || $hasL4Options;
    $showL5 = $selectedL4 || ($showL4 && $hasL5Options);
    $showL6 = $selectedL5 && $hasL6Options;
@endphp

<tr class="border-b border-gray-700/50 hover:bg-gray-800/30 transition-colors"
    wire:key="pending-product-{{ $product->id }}">

    {{-- Checkbox --}}
    <td class="px-3 py-2">
        <input type="checkbox"
               wire:click="toggleSelection({{ $product->id }})"
               @checked($this->isSelected($product->id))
               class="form-checkbox-dark">
    </td>

    {{-- Miniaturka (klikniecie otwiera modal zdjec) --}}
    <td class="px-3 py-2">
        @php
            $images = $product->temp_media_paths['images'] ?? $product->temp_media_paths ?? [];
            $primaryIndex = $product->primary_media_index ?? 0;
            $hasImages = is_array($images) && count($images) > 0;
            $thumbnailPath = null;
            if ($hasImages && isset($images[$primaryIndex])) {
                $img = $images[$primaryIndex];
                $thumbnailPath = is_array($img) ? ($img['path'] ?? null) : $img;
            }
        @endphp
        @if($hasImages && $thumbnailPath)
            <button wire:click="$dispatch('openImageModal', { productId: {{ $product->id }} })"
                    class="w-12 h-12 rounded bg-gray-700 overflow-hidden cursor-pointer
                           ring-2 ring-transparent hover:ring-pink-500 transition-all"
                    title="Kliknij aby edytowac zdjecia">
                <img src="{{ asset('storage/' . $thumbnailPath) }}"
                     alt="{{ $product->sku }}"
                     class="w-full h-full object-cover">
            </button>
        @else
            <button wire:click="$dispatch('openImageModal', { productId: {{ $product->id }} })"
                    class="w-12 h-12 rounded bg-gray-700 flex items-center justify-center cursor-pointer
                           ring-2 ring-transparent hover:ring-pink-500 hover:bg-gray-600 transition-all"
                    title="Kliknij aby dodac zdjecia">
                <svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </button>
        @endif
    </td>

    {{-- SKU (edytowalny) --}}
    <td class="px-3 py-2">
        @if($editingProductId === $product->id && $editingField === 'sku')
            <input type="text"
                   wire:model="editValue"
                   wire:keydown.enter="saveInlineEdit"
                   wire:keydown.escape="cancelEditing"
                   wire:blur="saveInlineEdit"
                   class="form-input-dark-sm w-full"
                   autofocus>
        @else
            <button wire:click="startEditing({{ $product->id }}, 'sku')"
                    class="text-left text-gray-200 hover:text-white font-mono text-sm hover:underline">
                {{ $product->sku ?? '-' }}
            </button>
        @endif
    </td>

    {{-- Nazwa (edytowalna) --}}
    <td class="px-3 py-2">
        @if($editingProductId === $product->id && $editingField === 'name')
            <input type="text"
                   wire:model="editValue"
                   wire:keydown.enter="saveInlineEdit"
                   wire:keydown.escape="cancelEditing"
                   wire:blur="saveInlineEdit"
                   class="form-input-dark-sm w-full"
                   autofocus>
        @else
            <button wire:click="startEditing({{ $product->id }}, 'name')"
                    class="text-left text-gray-300 hover:text-white text-sm hover:underline truncate max-w-xs block">
                {{ $product->name ?? '(brak nazwy)' }}
            </button>
        @endif
    </td>

    {{-- Typ produktu (dropdown) --}}
    <td class="px-2 py-2">
        <select wire:change="updateProductType({{ $product->id }}, $event.target.value)"
                class="form-select-dark-sm w-full text-xs">
            <option value="">-- typ --</option>
            @foreach($this->productTypes as $type)
                <option value="{{ $type->id }}" @selected($product->product_type_id === $type->id)>
                    {{ $type->name }}
                </option>
            @endforeach
        </select>
    </td>

    {{-- MARKA (manufacturer_id) - DROPDOWN Z MANUFACTURER MODEL --}}
    <td class="px-2 py-2">
        <select wire:change="updateManufacturer({{ $product->id }}, $event.target.value)"
                class="form-select-dark-sm w-full text-xs {{ !$product->manufacturer_id ? 'text-amber-400' : '' }}">
            <option value="">-- marka --</option>
            @foreach($this->manufacturers as $manufacturer)
                <option value="{{ $manufacturer->id }}" @selected($product->manufacturer_id === $manufacturer->id)>
                    {{ $manufacturer->name }}
                </option>
            @endforeach
        </select>
    </td>

    {{-- CENA DETAL (base_price) - NOWA KOLUMNA OPCJONALNA --}}
    <td class="px-2 py-2">
        @if($editingProductId === $product->id && $editingField === 'base_price')
            <div class="flex items-center gap-1">
                <input type="number"
                       wire:model="editValue"
                       wire:keydown.enter="saveInlineEdit"
                       wire:keydown.escape="cancelEditing"
                       wire:blur="saveInlineEdit"
                       class="form-input-dark-sm w-16 text-xs text-right"
                       step="0.01"
                       min="0"
                       placeholder="0.00"
                       autofocus>
                <span class="text-gray-500 text-xs">zl</span>
            </div>
        @else
            <button wire:click="startEditing({{ $product->id }}, 'base_price')"
                    class="text-left text-xs text-gray-400 hover:text-white hover:underline whitespace-nowrap">
                {{ number_format($product->base_price ?? 0, 2, ',', ' ') }} zl
            </button>
        @endif
    </td>

    {{-- KATEGORIE - inline dropdowny --}}
    {{-- L3 (Kategoria glowna) --}}
    <td class="px-2 py-2 relative">
        @include('livewire.products.import.partials.inline-category-select', [
            'product' => $product,
            'level' => 3,
            'disabled' => false,
            'parentCategoryId' => null
        ])
    </td>

    {{-- L4 (Podkategoria) - visible if L3 selected or has options --}}
    <td class="px-2 py-2 relative">
        @include('livewire.products.import.partials.inline-category-select', [
            'product' => $product,
            'level' => 4,
            'disabled' => !$selectedL3,
            'parentCategoryId' => $selectedL3?->id
        ])
    </td>

    {{-- L5 (Szczegolowa) - visible if L4 selected or has options --}}
    <td class="px-2 py-2 relative">
        @include('livewire.products.import.partials.inline-category-select', [
            'product' => $product,
            'level' => 5,
            'disabled' => !$selectedL4,
            'parentCategoryId' => $selectedL4?->id
        ])
    </td>

    {{-- L6 (Dodatkowa) - only if L5 has children, or + button with create form --}}
    <td class="px-2 py-2 relative">
        @if($hasL6Options || $selectedL6)
            @include('livewire.products.import.partials.inline-category-select', [
                'product' => $product,
                'level' => 6,
                'disabled' => !$selectedL5,
                'parentCategoryId' => $selectedL5?->id
            ])
        @else
            {{-- + button with inline create form --}}
            <div x-data="{
                showForm: false,
                newName: '',
                async create() {
                    if (!this.newName.trim()) return;
                    const result = await $wire.createInlineCategory({{ $product->id }}, 6, {{ $selectedL5?->id ?? 'null' }}, this.newName);
                    if (result && result.id) {
                        this.newName = '';
                        this.showForm = false;
                    }
                }
            }" class="relative">
                {{-- Toggle button --}}
                <button type="button"
                        x-show="!showForm"
                        @click="showForm = true; $nextTick(() => $refs.newL6Input?.focus())"
                        class="inline-flex items-center justify-center w-7 h-7 rounded text-gray-500
                               bg-gray-700/30 hover:bg-gray-700/50 hover:text-green-400 transition-colors
                               {{ !$selectedL5 ? 'opacity-30 cursor-not-allowed' : '' }}"
                        @if(!$selectedL5) disabled @endif
                        title="Dodaj podkategorie L6">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                </button>

                {{-- Inline create form --}}
                <div x-show="showForm"
                     x-cloak
                     @click.outside="showForm = false; newName = ''"
                     @keydown.escape.window="if(showForm) { showForm = false; newName = ''; }"
                     class="absolute z-50 left-0 top-0 w-48 bg-gray-800 border border-gray-600 rounded-lg shadow-xl p-2">
                    <div class="flex items-center gap-1">
                        <input type="text"
                               x-ref="newL6Input"
                               x-model="newName"
                               @keydown.enter="create()"
                               placeholder="Nazwa L6..."
                               class="flex-1 px-2 py-1 text-xs bg-gray-700 border border-gray-600 rounded
                                      text-white placeholder-gray-400 focus:border-green-500 focus:ring-1 focus:ring-green-500">
                        <button type="button"
                                @click="create()"
                                :disabled="!newName.trim()"
                                class="p-1 bg-green-600 hover:bg-green-700 text-white rounded transition-colors
                                       disabled:opacity-50 disabled:cursor-not-allowed">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </button>
                        <button type="button"
                                @click="showForm = false; newName = ''"
                                class="p-1 bg-gray-600 hover:bg-gray-500 text-gray-300 rounded transition-colors">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </td>

    {{-- SKLEPY - inline multi-select --}}
    <td class="px-2 py-2 relative">
        @include('livewire.products.import.partials.inline-shop-select', ['product' => $product])
    </td>

    {{-- STATUS gotowosci --}}
    <td class="px-3 py-2 text-center">
        @php
            // Color thresholds - more granular
            $statusColor = match(true) {
                $percentage === 100 => 'bg-green-900/30 text-green-300',      // 100% = zielony
                $percentage >= 90 => 'bg-lime-900/30 text-lime-300',          // 90-99% = limonka
                $percentage >= 80 => 'bg-yellow-900/30 text-yellow-300',      // 80-89% = zolty
                $percentage >= 70 => 'bg-amber-900/30 text-amber-300',        // 70-79% = bursztyn
                $percentage >= 60 => 'bg-orange-900/30 text-orange-300',      // 60-69% = pomaranczowy
                $percentage >= 50 => 'bg-orange-900/40 text-orange-400',      // 50-59% = ciemny pomaranczowy
                $percentage >= 40 => 'bg-red-900/30 text-red-300',            // 40-49% = czerwony
                $percentage >= 25 => 'bg-red-900/40 text-red-400',            // 25-39% = ciemny czerwony
                default => 'bg-red-900/50 text-red-500',                      // <25% = bardzo czerwony
            };
        @endphp
        <span class="inline-flex items-center px-2 py-1 {{ $statusColor }} text-xs rounded font-medium"
              title="{{ $isReady ? 'Gotowe do publikacji' : implode(', ', $product->getPublishValidationErrors() ?? []) }}">
            {{ $percentage }}%
        </span>
    </td>

    {{-- Akcje --}}
    <td class="px-2 py-2 text-right">
        @php
            // Determine which Quick Actions to show based on product type
            $productTypeSlug = $product->productType?->slug ?? null;
            $showFeatures = ($productTypeSlug === 'pojazd');
            $showCompatibility = ($productTypeSlug === 'czesc-zamienna');

            // Check skip flags for color coding
            $skipFeatures = $product->skip_features ?? false;
            $skipCompatibility = $product->skip_compatibility ?? false;
            $skipImages = $product->skip_images ?? false;
            $skipDescriptions = $product->skip_descriptions ?? false;

            // Data counts
            $hasFeatures = !empty($product->feature_data['features'] ?? []);
            $featureCount = count($product->feature_data['features'] ?? []);
            $hasCompatibility = !empty($product->compatibility_data['compatibilities'] ?? []);
            $compatCount = count($product->compatibility_data['compatibilities'] ?? []);
            $hasImages = !empty($product->temp_media_paths['images'] ?? []);
            $imageCount = count($product->temp_media_paths['images'] ?? []);
            $hasVariants = !empty($product->variant_data['variants'] ?? []);
            $variantCount = count($product->variant_data['variants'] ?? []);

            // Descriptions check (short or long)
            $hasDescriptions = !empty($product->short_description) || !empty($product->long_description);
            $descShortLen = strlen($product->short_description ?? '');
            $descLongLen = strlen($product->long_description ?? '');
        @endphp
        <div class="flex items-center justify-end gap-0.5">
            {{-- Warianty (FAZA 5.4) - zawsze widoczne, NIE wplywaja na progress --}}
            <button wire:click="$dispatch('openVariantModal', { productId: {{ $product->id }} })"
                    class="p-1 rounded transition-colors
                           @if($hasVariants)
                               text-cyan-400 hover:text-cyan-300 hover:bg-cyan-900/30
                           @else
                               text-gray-400 hover:text-cyan-400 hover:bg-cyan-900/30
                           @endif"
                    title="{{ $hasVariants ? 'Warianty ('.$variantCount.')' : 'Warianty' }}">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
            </button>

            {{-- Cechy (FAZA 5.5) - tylko dla typu "Pojazd" --}}
            @if($showFeatures)
            <button wire:click="$dispatch('openFeatureModal', { productId: {{ $product->id }} })"
                    class="p-1 rounded transition-colors
                           @if($skipFeatures)
                               text-red-400 hover:text-red-300 hover:bg-red-900/30
                           @elseif($hasFeatures)
                               text-amber-400 hover:text-amber-300 hover:bg-amber-900/30
                           @else
                               text-gray-400 hover:text-amber-400 hover:bg-amber-900/30
                           @endif"
                    title="{{ $skipFeatures ? 'Brak cech (oznaczono)' : ($hasFeatures ? 'Cechy ('.$featureCount.')' : 'Cechy') }}">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                </svg>
            </button>
            @endif

            {{-- Dopasowania (FAZA 5.6) - tylko dla typu "Czesc zamienna" --}}
            @if($showCompatibility)
            <button wire:click="$dispatch('openCompatibilityModal', { productId: {{ $product->id }} })"
                    class="p-1 rounded transition-colors
                           @if($skipCompatibility)
                               text-red-400 hover:text-red-300 hover:bg-red-900/30
                           @elseif($hasCompatibility)
                               text-teal-400 hover:text-teal-300 hover:bg-teal-900/30
                           @else
                               text-gray-400 hover:text-teal-400 hover:bg-teal-900/30
                           @endif"
                    title="{{ $skipCompatibility ? 'Brak dopasowan (oznaczono)' : ($hasCompatibility ? 'Dopasowania ('.$compatCount.')' : 'Dopasowania') }}">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/>
                </svg>
            </button>
            @endif

            {{-- Zdjecia (FAZA 5.7) - zawsze widoczne --}}
            <button wire:click="$dispatch('openImageModal', { productId: {{ $product->id }} })"
                    class="p-1 rounded transition-colors
                           @if($skipImages)
                               text-red-400 hover:text-red-300 hover:bg-red-900/30
                           @elseif($hasImages)
                               text-pink-400 hover:text-pink-300 hover:bg-pink-900/30
                           @else
                               text-gray-400 hover:text-pink-400 hover:bg-pink-900/30
                           @endif"
                    title="{{ $skipImages ? 'Bez zdjec (oznaczono)' : ($hasImages ? 'Zdjecia ('.$imageCount.')' : 'Zdjecia') }}">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </button>

            {{-- Opisy (FAZA 6.5.4) - NOWY modal opisow - PRZED kreska --}}
            <button wire:click="$dispatch('openDescriptionModal', { productId: {{ $product->id }} })"
                    class="p-1 rounded transition-colors
                           @if($skipDescriptions)
                               text-red-400 hover:text-red-300 hover:bg-red-900/30
                           @elseif($hasDescriptions)
                               text-indigo-400 hover:text-indigo-300 hover:bg-indigo-900/30
                           @else
                               text-gray-400 hover:text-indigo-400 hover:bg-indigo-900/30
                           @endif"
                    title="{{ $skipDescriptions ? 'Publikuj bez opisow (oznaczono)' : ($hasDescriptions ? 'Opisy (krotki: '.$descShortLen.', dlugi: '.$descLongLen.')' : 'Opisy produktu') }}">
                {{-- Document text icon - for descriptions --}}
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </button>

            <div class="w-px h-4 bg-gray-700 mx-0.5"></div>

            {{-- Duplikuj --}}
            <button wire:click="duplicateProduct({{ $product->id }})"
                    class="p-1.5 text-gray-400 hover:text-purple-400 hover:bg-purple-900/30 rounded transition-colors"
                    title="Duplikuj">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                </svg>
            </button>

            {{-- Usun --}}
            <button wire:click="deletePendingProduct({{ $product->id }})"
                    wire:confirm="Czy na pewno usunac {{ $product->sku }}?"
                    class="p-1.5 text-gray-400 hover:text-red-400 hover:bg-red-900/30 rounded transition-colors"
                    title="Usun">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
            </button>
        </div>
    </td>
</tr>

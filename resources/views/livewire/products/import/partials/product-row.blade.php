{{-- ETAP_06 REDESIGN: Product row z inline category dropdowns --}}
@php
    $productCats = $product->category_ids ?? [];
    $shopIds = $product->shop_ids ?? [];

    $categoriesForProduct = empty($productCats)
        ? collect()
        : \App\Models\Category::whereIn('id', $productCats)->get();

    // Get selected categories at each level (single branch)
    $selectedL3 = $categoriesForProduct->firstWhere('level', 2);
    $selectedL4 = $selectedL3 ? $categoriesForProduct->firstWhere('parent_id', $selectedL3->id) : null;
    $selectedL5 = $selectedL4 ? $categoriesForProduct->firstWhere('parent_id', $selectedL4->id) : null;
    $selectedL6 = $selectedL5 ? $categoriesForProduct->firstWhere('parent_id', $selectedL5->id) : null;
    $selectedL7 = $selectedL6 ? $categoriesForProduct->firstWhere('parent_id', $selectedL6->id) : null;
    $selectedL8 = $selectedL7 ? $categoriesForProduct->firstWhere('parent_id', $selectedL7->id) : null;

    // Status
    $percentage = $product->completion_percentage ?? 0;
    $isReady = $product->is_ready_for_publish ?? false;

    $effectiveCategoryMaxLevel = $effectiveCategoryMaxLevel ?? ($this->effectiveCategoryMaxLevel ?? 5);
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

    {{-- CENA (FAZA 9.4) - klik otwiera modal cen --}}
    <td class="px-2 py-2">
        <div class="import-price-cell"
             wire:click="openImportPricesModal({{ $product->id }})"
             title="Kliknij aby edytowac ceny">
            @if($product->base_price !== null)
                <span class="import-price-cell-value">
                    {{ number_format((float) $product->base_price, 2, ',', ' ') }} zl
                </span>
            @else
                <span class="import-price-cell-empty">brak</span>
            @endif
        </div>
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

    {{-- L6-L8 - ukryte do czasu klikniecia "+" (dodaje kolumny krokowo) --}}
    <td class="px-2 py-2 relative">
        @if($effectiveCategoryMaxLevel >= 6)
            @include('livewire.products.import.partials.inline-category-select', [
                'product' => $product,
                'level' => 6,
                'disabled' => !$selectedL5,
                'parentCategoryId' => $selectedL5?->id
            ])
        @else
            <button type="button"
                    wire:click="expandCategoryColumns"
                    class="inline-flex items-center justify-center w-7 h-7 rounded text-gray-500
                           bg-gray-700/30 hover:bg-gray-700/50 hover:text-green-400 transition-colors"
                    title="Pokaż KAT L6">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
            </button>
        @endif
    </td>

    @if($effectiveCategoryMaxLevel >= 6)
        <td class="px-2 py-2 relative">
            @if($effectiveCategoryMaxLevel >= 7)
                @include('livewire.products.import.partials.inline-category-select', [
                    'product' => $product,
                    'level' => 7,
                    'disabled' => !$selectedL6,
                    'parentCategoryId' => $selectedL6?->id
                ])
            @else
                <button type="button"
                        wire:click="expandCategoryColumns"
                        class="inline-flex items-center justify-center w-7 h-7 rounded text-gray-500
                               bg-gray-700/30 hover:bg-gray-700/50 hover:text-green-400 transition-colors"
                        title="Pokaż KAT L7">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                </button>
            @endif
        </td>
    @endif

    @if($effectiveCategoryMaxLevel >= 7)
        <td class="px-2 py-2 relative">
            @if($effectiveCategoryMaxLevel >= 8)
                @include('livewire.products.import.partials.inline-category-select', [
                    'product' => $product,
                    'level' => 8,
                    'disabled' => !$selectedL7,
                    'parentCategoryId' => $selectedL7?->id
                ])
            @else
                <button type="button"
                        wire:click="expandCategoryColumns"
                        class="inline-flex items-center justify-center w-7 h-7 rounded text-gray-500
                               bg-gray-700/30 hover:bg-gray-700/50 hover:text-green-400 transition-colors"
                        title="Pokaż KAT L8">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                </button>
            @endif
        </td>
    @endif

    {{-- PUBLIKACJA - interaktywny dropdown ERPConnection + PrestaShop (FAZA 9.3 - zastepuje Sklepy) --}}
    <td class="px-2 py-2 relative">
        @php
            $pubTargets = $product->publication_targets ?? [];
            $erpConnectionIds = $pubTargets['erp_connections'] ?? [];
            $psShops = $pubTargets['prestashop_shops'] ?? [];

            // Get active ERP connections (cached in trait)
            $activeErpConnections = $this->getActiveErpConnections();
            $allShops = $allShops ?? \App\Models\PrestaShopShop::where('is_active', true)->orderBy('name')->get();

            // FIX: ALWAYS ensure default ERP is in the list
            $defaultErpId = null;
            foreach ($activeErpConnections as $conn) {
                if ($conn['is_default']) {
                    $defaultErpId = $conn['id'];
                    break;
                }
            }
            if ($defaultErpId && !in_array($defaultErpId, $erpConnectionIds, true)) {
                $erpConnectionIds[] = $defaultErpId;
            }

            // Prepare shop category counts for Alpine state (badge Kat!/Kat OK)
            $shopCategoryCounts = [];
            foreach ($allShops as $s) {
                $shopCategoryCounts[$s->id] = count($product->shop_categories[(string)$s->id] ?? []);
            }
        @endphp

        <div x-data="{
                open: false,
                 dropdownTop: 0,
                 dropdownLeft: 0,
                 productId: {{ $product->id }},
                 shopCategoryCounts: @js($shopCategoryCounts),
                 openDropdown() {
                     const rect = this.$refs.trigger.getBoundingClientRect();

                     const enterpriseCard = this.$el.closest('.enterprise-card');
                     const cardStyles = enterpriseCard ? getComputedStyle(enterpriseCard) : null;
                     const cardHasBackdrop = !!enterpriseCard && cardStyles && cardStyles.backdropFilter && cardStyles.backdropFilter !== 'none';
                     const cbRect = cardHasBackdrop ? enterpriseCard.getBoundingClientRect() : { top: 0, left: 0, width: window.innerWidth, height: window.innerHeight };

                     const padding = 10;
                     const estimatedMenuWidth = this.$refs.menu?.offsetWidth || 260;
                     const estimatedMenuHeight = this.$refs.menu?.offsetHeight || 320;

                     // NOTE: .enterprise-card uses backdrop-filter which creates a containing block for position:fixed in Chrome.
                     // We must calculate coordinates relative to that element (not the viewport), otherwise dropdown renders off-screen.
                     let left = rect.left - cbRect.left;
                     let top = rect.bottom - cbRect.top + 4;

                     // Open upward if not enough space below
                     if (top + estimatedMenuHeight > cbRect.height - padding) {
                         top = rect.top - cbRect.top - estimatedMenuHeight - 4;
                     }

                     // Clamp inside containing block / viewport
                     left = Math.max(padding, Math.min(left, cbRect.width - estimatedMenuWidth - padding));
                     top = Math.max(padding, Math.min(top, cbRect.height - estimatedMenuHeight - padding));

                     this.dropdownTop = top;
                     this.dropdownLeft = left;
                     this.open = true;

                     // Re-measure after open (real menu size) and clamp again
                     this.$nextTick(() => {
                         const menuW = this.$refs.menu?.offsetWidth || estimatedMenuWidth;
                         const menuH = this.$refs.menu?.offsetHeight || estimatedMenuHeight;

                         let finalLeft = rect.left - cbRect.left;
                         let finalTop = rect.bottom - cbRect.top + 4;
                         if (finalTop + menuH > cbRect.height - padding) {
                             finalTop = rect.top - cbRect.top - menuH - 4;
                         }

                         finalLeft = Math.max(padding, Math.min(finalLeft, cbRect.width - menuW - padding));
                         finalTop = Math.max(padding, Math.min(finalTop, cbRect.height - menuH - padding));

                         this.dropdownLeft = finalLeft;
                         this.dropdownTop = finalTop;
                     });
                 },
                 closeDropdown() {
                     this.open = false;
                 },
                closeIfNotModal(e) {
                    if (e.target.closest('.import-category-picker-modal-overlay')) return;
                    if (e.target.closest('.import-targets-dropdown-menu-fixed')) return;
                    this.open = false;
                },
                hasCats(shopId) {
                    return (this.shopCategoryCounts[shopId] || 0) > 0;
                },
                hasErp(id) { return (Alpine.store('pub_{{ $product->id }}')?.erpIds || []).includes(id); },
                hasPs(id) { return (Alpine.store('pub_{{ $product->id }}')?.psIds || []).includes(id); },
                toggleErp(id) {
                    const s = Alpine.store('pub_{{ $product->id }}');
                    if (!s) return;
                    if (s.erpIds.includes(id)) {
                        s.erpIds = s.erpIds.filter(x => x !== id);
                    } else {
                        s.erpIds = [...s.erpIds, id];
                    }
                },
                togglePs(id) {
                    const s = Alpine.store('pub_{{ $product->id }}');
                    if (!s) return;
                    if (s.psIds.includes(id)) {
                        s.psIds = s.psIds.filter(x => x !== id);
                    } else {
                        s.psIds = [...s.psIds, id];
                    }
                },
                erpCount() { return (Alpine.store('pub_{{ $product->id }}')?.erpIds || []).length; },
                psCount() { return (Alpine.store('pub_{{ $product->id }}')?.psIds || []).length; }
            }"
            x-init="
                Alpine.store('pub_{{ $product->id }}', {
                    erpIds: @js($erpConnectionIds).map(Number),
                    psIds: @js($psShops).map(Number)
                });
            "
            wire:ignore
            class="import-targets-dropdown relative"
            @prestashop-categories-saved.window="
                if ($event.detail && $event.detail.productId == productId) {
                    shopCategoryCounts[$event.detail.shopId] = $event.detail.categoryCount;
                }
            ">
            {{-- Trigger: badges --}}
            <button type="button"
                    x-on:click="open ? closeDropdown() : openDropdown()"
                    x-ref="trigger"
                    class="import-publication-badges-container cursor-pointer hover:opacity-80 transition-opacity">
                {{-- ERP badges --}}
                @foreach($activeErpConnections as $conn)
                    <span x-show="hasErp({{ $conn['id'] }})"
                          x-cloak
                          class="import-publication-badge import-publication-badge-erp">
                        {{ $conn['instance_name'] }}
                        @if($conn['is_default'])
                            <span class="import-publication-badge-default-marker">*</span>
                        @endif
                    </span>
                @endforeach
                <span x-show="erpCount() === 0"
                      x-cloak
                      class="import-publication-badge import-publication-badge-erp opacity-30">
                    +ERP
                </span>

                {{-- PrestaShop badges --}}
                @foreach($allShops as $psBadgeShop)
                    <span x-show="hasPs({{ $psBadgeShop->id }})"
                          x-cloak
                          class="import-publication-badge"
                          :class="hasCats({{ $psBadgeShop->id }}) ? 'import-publication-badge-ps-ok' : 'import-publication-badge-ps-warning'">
                        <span class="text-[10px]" x-text="hasCats({{ $psBadgeShop->id }}) ? '✅' : '⚠️'"></span>
                        {{ Str::limit($psBadgeShop->name, 10) }}
                    </span>
                @endforeach
                <span x-show="psCount() === 0"
                      x-cloak
                      class="import-publication-badge import-publication-badge-prestashop opacity-30">
                    +PS
                </span>
            </button>

            {{-- Dropdown --}}
            {{-- NO x-teleport - breaks Livewire snapshots! Use fixed positioning instead --}}
            <div x-show="open" x-cloak x-ref="menu"
                 x-transition:enter="transition ease-out duration-100"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 class="import-targets-dropdown-menu-fixed"
                 :style="`top: ${dropdownTop}px; left: ${dropdownLeft}px;`"
                 @resize.window="if (open) openDropdown()"
                 @scroll.window="if (open) openDropdown()"
                 @click.outside="closeIfNotModal($event)">

                    {{-- ERP --}}
                    @if(count($activeErpConnections) > 0)
                        <div class="px-3 py-1">
                            <span class="text-xs text-gray-500 uppercase">Systemy ERP</span>
                        </div>
                        @foreach($activeErpConnections as $conn)
                            @php
                                $isDefault = $conn['is_default'];
                            @endphp
                            <label class="import-targets-dropdown-item"
                                   :class="{ 'import-targets-dropdown-item-active': hasErp({{ $conn['id'] }}) }">
                                <input type="checkbox"
                                       :checked="hasErp({{ $conn['id'] }})"
                                       @if($isDefault) disabled @endif
                                       @if(!$isDefault)
                                           @click.stop.prevent="toggleErp({{ $conn['id'] }}); $wire.toggleErpConnection({{ $product->id }}, {{ $conn['id'] }});"
                                       @endif
                                       class="form-checkbox-dark w-3.5 h-3.5 {{ $isDefault ? 'opacity-60 cursor-not-allowed' : '' }}">
                                <span class="flex items-center gap-1">
                                    {{ $conn['instance_name'] }}
                                    @if($isDefault)
                                        <span class="import-publication-badge-default-label">DOMYSLNY</span>
                                    @endif
                                </span>
                            </label>
                        @endforeach
                    @endif

                    {{-- PrestaShop --}}
                    @if($allShops->count() > 0)
                        <div class="border-t border-gray-700 my-1"></div>
                        <div class="px-3 py-1">
                            <span class="text-xs text-gray-500 uppercase">PrestaShop</span>
                        </div>
                        @foreach($allShops as $shop)
                            <div class="import-targets-dropdown-item flex items-center justify-between"
                                 :class="{ 'import-targets-dropdown-item-active': hasPs({{ $shop->id }}) }">
                                <label class="flex items-center gap-2 cursor-pointer flex-1"
                                       @click.stop.prevent="togglePs({{ $shop->id }}); $wire.togglePrestaShopShop({{ $product->id }}, {{ $shop->id }});">
                                    <input type="checkbox"
                                           :checked="hasPs({{ $shop->id }})"
                                           class="form-checkbox-dark w-3.5 h-3.5 pointer-events-none">
                                    <span>{{ $shop->name }}</span>
                                </label>

                                {{-- Category picker button (only if shop selected) --}}
                                <button type="button"
                                        x-show="hasPs({{ $shop->id }})"
                                        x-cloak
                                        @click.stop.prevent="$dispatch('openPrestaShopCategoryPicker', { productId: {{ $product->id }}, shopId: {{ $shop->id }} })"
                                        class="ml-2 px-1.5 py-0.5 rounded text-[10px] font-medium transition-colors"
                                        :class="hasCats({{ $shop->id }})
                                            ? 'bg-green-900/50 text-green-300 border border-green-600'
                                            : 'bg-yellow-900/50 text-yellow-400 border border-yellow-600'"
                                        title="Wybierz kategorie dla {{ $shop->name }}">
                                    <span class="inline-flex items-center gap-0.5"
                                          x-text="hasCats({{ $shop->id }}) ? '✅ Kat OK' : '⚠️ Kat!'"></span>
                                </button>
                            </div>
                        @endforeach
                    @endif

                     <div class="border-t border-gray-700 mt-1 px-3 py-2 flex justify-end">
                         <button x-on:click="closeDropdown()" class="text-xs text-gray-400 hover:text-white">Zamknij</button>
                     </div>
                 </div>
             </div>
         </div>
     </td>

    {{-- DATA PUBLIKACJI (FAZA 9.3) --}}
    <td class="px-2 py-2">
        @php
            $pubStatus = $product->publish_status ?? 'draft';
        @endphp
        @if($pubStatus === 'published')
            <span class="text-xs text-gray-500">
                {{ $product->published_at?->format('d.m H:i') ?? '-' }}
            </span>
        @else
            <input type="datetime-local"
                   wire:key="schedule-{{ $product->id }}-{{ $product->scheduled_publish_at?->timestamp ?? 'none' }}"
                   value="{{ $product->scheduled_publish_at?->format('Y-m-d\\TH:i') }}"
                   wire:change="schedulePublication({{ $product->id }}, $event.target.value)"
                   class="import-schedule-input"
                   title="Zaplanuj date publikacji">
        @endif
    </td>

    {{-- PUBLIKUJ button (FAZA 9.3) --}}
    <td class="px-2 py-2 text-center">
        @php
            $canPublish = ($product->completion_percentage ?? 0) === 100;
            $hasSchedule = !empty($product->scheduled_publish_at);
        @endphp
        @if($pubStatus === 'published')
            <span class="import-publish-btn import-publish-btn-published">Opublikowano</span>
        @elseif($pubStatus === 'publishing')
            <span class="import-publish-btn import-publish-btn-publishing">
                <svg class="animate-spin h-3 w-3 inline mr-1" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                Publikowanie
            </span>
        @elseif($pubStatus === 'failed')
            <button wire:click="publishWithTargets({{ $product->id }})"
                    class="import-publish-btn import-publish-btn-failed" title="Ponow probe">
                Blad - ponow
            </button>
        @elseif($hasSchedule && $pubStatus === 'scheduled')
            <div x-data="{ hovering: false }" class="inline-block"
                 x-on:mouseenter="hovering = true"
                 x-on:mouseleave="hovering = false">
                <span x-show="!hovering"
                      class="import-publish-btn import-publish-btn-scheduled import-countdown"
                      x-data="{ targetTime: '{{ $product->scheduled_publish_at->toIso8601String() }}' }"
                      x-text="(() => {
                          const diff = new Date(targetTime) - new Date();
                          if (diff <= 0) return 'Teraz...';
                          const d = Math.floor(diff/86400000);
                          const h = Math.floor((diff%86400000)/3600000);
                          const m = Math.floor((diff%3600000)/60000);
                          if (d > 0) return d+'d '+h+'h';
                          if (h > 0) return h+'h '+m+'m';
                          return m+'m';
                      })()"
                      title="Zaplanowana publikacja: {{ $product->scheduled_publish_at->format('d.m.Y H:i') }}">
                </span>
                <button x-show="hovering"
                        x-cloak
                        wire:click="cancelScheduledPublication({{ $product->id }})"
                        class="import-publish-btn import-publish-btn-failed"
                        title="Anuluj zaplanowana publikacje">
                    Anuluj
                </button>
            </div>
        @else
            <button wire:click="publishWithTargets({{ $product->id }})"
                    class="import-publish-btn {{ $canPublish ? 'import-publish-btn-ready' : 'import-publish-btn-disabled' }}"
                    @if(!$canPublish) disabled @endif
                    title="{{ $canPublish ? 'Publikuj na wybrane cele' : 'Produkt nie jest gotowy (wymagane 100%)' }}">
                Publikuj
            </button>
        @endif
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

            {{-- Edytuj (FAZA 9.2) --}}
            <button wire:click="openImportModal({{ $product->id }})"
                    class="p-1.5 text-gray-400 hover:text-white hover:bg-gray-700/50 rounded transition-colors"
                    title="Edytuj podstawowe dane">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
            </button>

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

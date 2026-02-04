{{-- resources/views/livewire/products/management/tabs/variants-tab.blade.php --}}
@php
    // PENDING VARIANTS SYSTEM (2025-12-04)
    // Use getAllVariantsForDisplay() to include both existing and pending variants
    $variants = $this->getAllVariantsForDisplay();
    $variantCount = $variants->count();
    $selectedVariants = $selectedVariants ?? [];

    // Count pending operations for badges
    $pendingCreateCount = count($pendingVariantCreates ?? []);
    $pendingUpdateCount = count($pendingVariantUpdates ?? []);
    $pendingDeleteCount = count($pendingVariantDeletes ?? []);
    $totalPendingCount = $pendingCreateCount + $pendingUpdateCount + $pendingDeleteCount;

    // ETAP_05b FAZA 5: Per-shop variant isolation
    $isInShopContext = $activeShopId !== null;
    $shopOverrideCount = $isInShopContext ? ($this->getShopVariantOverrideCount($activeShopId) ?? 0) : 0;
@endphp
<div class="tab-content active space-y-6 relative">
    {{-- ETAP_05c: Sync Overlay - blocks UI during shop variants sync --}}
    @if($shopVariantsSyncing ?? false)
        <div class="absolute inset-0 bg-gray-900/80 backdrop-blur-sm z-50 flex items-center justify-center rounded-lg">
            <div class="text-center p-8">
                <svg class="animate-spin h-12 w-12 text-blue-400 mx-auto mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="text-lg font-medium text-white mb-2">Oczekiwanie na synchronizacje</p>
                <p class="text-sm text-gray-400">Warianty sa synchronizowane z PrestaShop...</p>
            </div>
        </div>
    @endif

    {{-- Header Section --}}
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center space-x-3">
            <h3 class="text-lg font-medium text-white">Warianty produktu</h3>
            @if($variantCount > 0)
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-900/30 text-blue-200 border border-blue-700/50">
                    {{ $variantCount }} {{ $variantCount === 1 ? 'wariant' : 'wariantow' }}
                </span>
            @endif
            {{-- Pending changes indicator --}}
            @if($totalPendingCount > 0)
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-amber-900/30 text-amber-200 border border-amber-700/50 animate-pulse">
                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    {{ $totalPendingCount }} niezapisanych zmian
                </span>
            @endif
        </div>

        {{-- Action Buttons --}}
        <div class="flex items-center space-x-3">
            {{-- Copy Variants Button (context-aware) --}}
            @php
                $availableShops = $this->getAvailableShopsForVariantCopy();
                // In shop context: always show (Dane domyslne option)
                // In default context: show only if shops available
                $showCopyButton = $isInShopContext || $availableShops->isNotEmpty();
            @endphp
            @if($showCopyButton)
                <div x-data="{ showCopyDropdown: false }" class="relative">
                    {{-- Copy Button with Dropdown --}}
                    <button type="button"
                            @click="showCopyDropdown = !showCopyDropdown"
                            class="btn-enterprise-secondary inline-flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                        </svg>
                        <span>Wstaw z</span>
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </button>

                    {{-- Dropdown Menu --}}
                    <div x-show="showCopyDropdown"
                         @click.away="showCopyDropdown = false"
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="transform opacity-0 scale-95"
                         x-transition:enter-end="transform opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="transform opacity-100 scale-100"
                         x-transition:leave-end="transform opacity-0 scale-95"
                         class="absolute right-0 mt-2 w-56 rounded-lg shadow-lg bg-gray-800 border border-gray-700 z-50"
                         style="display: none;">
                        <div class="py-1">
                            {{-- Context-specific options --}}
                            @if($isInShopContext)
                                {{-- IN SHOP CONTEXT: Copy FROM default or other shops --}}
                                <button type="button"
                                        @click="$wire.copyVariantsToShop(null); showCopyDropdown = false"
                                        class="w-full text-left px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 flex items-center space-x-2">
                                    <svg class="w-4 h-4 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd"/>
                                    </svg>
                                    <span>Dane domyslne</span>
                                </button>
                                @foreach($availableShops as $shop)
                                    <button type="button"
                                            @click="$wire.copyVariantsToShop({{ $shop->id }}); showCopyDropdown = false"
                                            class="w-full text-left px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 flex items-center space-x-2">
                                        <svg class="w-4 h-4 text-purple-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 2a4 4 0 00-4 4v1H5a1 1 0 00-.994.89l-1 9A1 1 0 004 18h12a1 1 0 00.994-1.11l-1-9A1 1 0 0015 7h-1V6a4 4 0 00-4-4zm2 5V6a2 2 0 10-4 0v1h4zm-6 3a1 1 0 112 0 1 1 0 01-2 0zm7-1a1 1 0 100 2 1 1 0 000-2z" clip-rule="evenodd"/>
                                        </svg>
                                        <span>{{ $shop->name }}</span>
                                    </button>
                                @endforeach
                            @else
                                {{-- IN DEFAULT CONTEXT: Copy FROM shops --}}
                                @foreach($availableShops as $shop)
                                    <button type="button"
                                            @click="$wire.copyVariantsFromShop({{ $shop->id }}); showCopyDropdown = false"
                                            class="w-full text-left px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 flex items-center space-x-2">
                                        <svg class="w-4 h-4 text-purple-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 2a4 4 0 00-4 4v1H5a1 1 0 00-.994.89l-1 9A1 1 0 004 18h12a1 1 0 00.994-1.11l-1-9A1 1 0 0015 7h-1V6a4 4 0 00-4-4zm2 5V6a2 2 0 10-4 0v1h4zm-6 3a1 1 0 112 0 1 1 0 01-2 0zm7-1a1 1 0 100 2 1 1 0 000-2z" clip-rule="evenodd"/>
                                        </svg>
                                        <span>{{ $shop->name }}</span>
                                    </button>
                                @endforeach
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            {{-- Add Variant Button --}}
            <button type="button"
                    wire:click="openCreateVariantModal"
                    class="btn-enterprise-primary inline-flex items-center space-x-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                <span>Dodaj wariant</span>
            </button>
        </div>
    </div>

    {{-- ETAP_05b FAZA 5: Shop Context Indicator - USUNIETY (legacy element) --}}

    {{-- Variants List or Empty State --}}
    @if($variantCount > 0)
        {{-- Bulk Actions Bar (shown when variants selected) --}}
        @if(count($selectedVariants) > 0)
            <div class="p-4 bg-blue-900/20 border border-blue-700/50 rounded-lg mb-4">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center space-x-2">
                        <svg class="w-5 h-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-sm font-medium text-blue-200">
                            Zaznaczono {{ count($selectedVariants) }} {{ count($selectedVariants) === 1 ? 'wariant' : 'wariantów' }}
                        </span>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <button type="button"
                                wire:click="activateSelected"
                                class="btn-enterprise-secondary text-sm inline-flex items-center">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Aktywuj
                        </button>

                        <button type="button"
                                wire:click="deactivateSelected"
                                class="btn-enterprise-secondary text-sm inline-flex items-center">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Dezaktywuj
                        </button>

                        <button type="button"
                                wire:click="copyPricesFromParent"
                                class="btn-enterprise-secondary text-sm inline-flex items-center">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                            </svg>
                            Kopiuj ceny
                        </button>

                        <button type="button"
                                wire:click="deleteSelected"
                                onclick="return confirm('Czy na pewno chcesz usunąć zaznaczone warianty?')"
                                class="btn-enterprise-danger text-sm inline-flex items-center">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                            Usuń wybrane
                        </button>
                    </div>
                </div>
            </div>
        @endif

        {{-- Variants Table (Responsive) --}}
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-700">
                <thead class="bg-gray-800">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-left">
                            <input type="checkbox"
                                   wire:model.live="selectAll"
                                   class="rounded border-gray-600 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                            Zdjęcie
                        </th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                            SKU
                        </th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                            Nazwa/Atrybuty
                        </th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                            {{-- ETAP_14: Price display mode toggle (Brutto/Netto) --}}
                            <div class="flex items-center space-x-2">
                                <span>Cena</span>
                                <button type="button"
                                        wire:click="toggleVariantPriceDisplayMode"
                                        class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium transition-colors
                                               {{ $variantPriceDisplayMode === 'gross' ? 'bg-blue-900/50 text-blue-300 border border-blue-600' : 'bg-gray-700 text-gray-400 border border-gray-600' }}"
                                        title="Kliknij aby przelaczac Brutto/Netto">
                                    {{ $variantPriceDisplayMode === 'gross' ? 'BRUTTO' : 'NETTO' }}
                                </button>
                            </div>
                        </th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                            Stan
                        </th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                            Status
                        </th>
                        {{-- ETAP_05b FAZA 5: Per-shop context column --}}
                        @if($isInShopContext)
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                                Kontekst
                            </th>
                        @endif
                        <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-300 uppercase tracking-wider">
                            Akcje
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-gray-900 divide-y divide-gray-700">
                    @foreach($variants as $variant)
                        @php
                            // PENDING VARIANTS SYSTEM: Detect pending status
                            $isPendingCreate = $variant->pendingCreate ?? false;
                            $isPendingUpdate = $variant->pendingUpdate ?? false;
                            $isPendingDelete = $variant->pendingDelete ?? false;

                            // PS VARIANTS: Check pending status from pendingPsVariantDeletes/Updates arrays
                            // FIX 2025-12-09: Also check OVERRIDE variants (PPM + prestashop_combination_id)
                            $isPrestaShopVariant = is_string($variant->id) && str_starts_with($variant->id, 'ps_');
                            $hasPrestaShopLinkEarly = isset($variant->prestashop_combination_id) && $variant->prestashop_combination_id > 0;
                            $isLinkedOverrideEarly = !$isPrestaShopVariant && $hasPrestaShopLinkEarly && ($variant->operation_type ?? null) === 'OVERRIDE';
                            $psVariantIdEarly = $isPrestaShopVariant ? $variant->id : ($hasPrestaShopLinkEarly ? 'ps_' . $variant->prestashop_combination_id : null);

                            if ($isPrestaShopVariant || ($isLinkedOverrideEarly && $isInShopContext)) {
                                $checkId = $psVariantIdEarly ?? $variant->id;
                                $isPendingDelete = $isPendingDelete || ($this->isPsVariantPendingDelete($checkId) ?? false);
                                $isPendingUpdate = $isPendingUpdate || ($this->isPsVariantPendingUpdate($checkId) ?? false);
                            }

                            $hasPendingStatus = $isPendingCreate || $isPendingUpdate || $isPendingDelete;

                            // FIX 2025-12-09 BUG#5a: Get pending data - prefer attached data from variant object
                            // (set in getPrestaShopVariantsForDisplay) over getPsVariantPendingData call
                            $pendingData = null;
                            if ($isPendingUpdate) {
                                // First try attached pendingData from variant (most reliable for PS variants)
                                if (isset($variant->pendingData) && is_array($variant->pendingData)) {
                                    $pendingData = $variant->pendingData;
                                } elseif (isset($checkId)) {
                                    // Fallback to method call for override variants
                                    $pendingData = $this->getPsVariantPendingData($checkId);
                                }
                            }

                            // ETAP_05b FAZA 5: Per-shop variant status
                            // FIX 2025-12-09 v3: Check if PS variant has PPM equivalent by SKU
                            $variantShopStatus = 'default';
                            $variantStatusIndicator = ['show' => false, 'text' => '', 'class' => ''];

                            if ($isInShopContext && !$hasPendingStatus) {
                                // Get variant SKU for comparison
                                $variantSku = $variant->sku ?? '';
                                // Strip shop suffix from PS SKU to match PPM (MR-MRF-E-V001-S1 -> MR-MRF-E-V001)
                                $variantSkuBase = preg_replace('/-S\d+$/', '', $variantSku);

                                // Check if this variant has a PPM equivalent (exists in defaultVariantsSnapshot)
                                $hasPpmEquivalent = false;
                                foreach ($this->defaultVariantsSnapshot as $ppmId => $ppmData) {
                                    $ppmSku = $ppmData['sku'] ?? '';
                                    if ($ppmSku === $variantSkuBase || $ppmSku === $variantSku) {
                                        $hasPpmEquivalent = true;
                                        // Use PPM variant ID to get status
                                        $variantShopStatus = $this->getVariantShopStatus($ppmId);
                                        $variantStatusIndicator = $this->getVariantShopStatusIndicator($ppmId);
                                        break;
                                    }
                                }

                                if (!$hasPpmEquivalent) {
                                    // PS-only variant (no PPM equivalent) = "wlasne"
                                    $variantShopStatus = 'different';
                                    $variantStatusIndicator = [
                                        'show' => true,
                                        'text' => 'wlasne',
                                        'class' => 'status-label-different'
                                    ];
                                }
                            }

                            // Row styling based on pending status AND shop context
                            $rowClass = match(true) {
                                $isPendingDelete => 'bg-red-900/20 opacity-60 line-through-row',
                                $isPendingCreate => 'bg-green-900/20 border-l-4 border-green-500',
                                $isPendingUpdate => 'bg-amber-900/20 border-l-4 border-amber-500',
                                $variantShopStatus === 'inherited' => 'bg-purple-900/10 hover:bg-purple-900/20',
                                $variantShopStatus === 'different' => 'bg-orange-900/10 border-l-4 border-orange-500 hover:bg-orange-900/20',
                                $variantShopStatus === 'same' => 'bg-green-900/10 hover:bg-green-900/20',
                                default => 'hover:bg-gray-800'
                            };

                            // Use display_ properties for pending variants
                            $displaySku = $variant->display_sku ?? $variant->sku;
                            $displayName = $variant->display_name ?? $variant->name;
                            $displayIsActive = $variant->display_is_active ?? $variant->is_active;
                        @endphp
                        <tr class="{{ $rowClass }} transition-colors duration-150"
                            wire:key="variant-{{ $variant->id }}">
                            {{-- Checkbox (disabled for pending creates with negative IDs) --}}
                            <td class="px-4 py-4">
                                @if($variant->id > 0)
                                    <input type="checkbox"
                                           wire:model.live="selectedVariants"
                                           value="{{ $variant->id }}"
                                           class="rounded border-gray-600 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                           @if($isPendingDelete) disabled @endif>
                                @else
                                    <span class="text-xs text-gray-500">-</span>
                                @endif
                            </td>

                            {{-- Image Thumbnail with +X indicator (FIX #1, #5 - 2025-12-04) --}}
                            <td class="px-4 py-4">
                                @php
                                    // FIX 2025-12-04: Handle pending creates, updates, AND show image count
                                    $imageUrl = null;
                                    $imageCount = 0;

                                    if ($isPendingCreate) {
                                        // FIX 2025-12-09 BUG#5a: Pending creates - prefer media_ids if available
                                        $overrideMediaIds = $variant->media_ids ?? [];
                                        if (!empty($overrideMediaIds)) {
                                            // Use media_ids from shop override (copied variant)
                                            $imageCount = count($overrideMediaIds);
                                            $firstOverrideMedia = \App\Models\Media::find($overrideMediaIds[0]);
                                            $imageUrl = $firstOverrideMedia?->thumbnail_url ?? $firstOverrideMedia?->url ?? null;
                                        } else {
                                            // Fallback: use Media objects from images collection
                                            $firstMedia = $variant->images->first() ?? null;
                                            $imageUrl = $firstMedia?->thumbnail_url ?? $firstMedia?->url ?? null;
                                            $imageCount = $variant->images->count();
                                        }
                                    } elseif ($isPendingUpdate && $pendingData && isset($pendingData['media_ids'])) {
                                        // FIX 2025-12-09 BUG#5: Use $pendingData (from getPsVariantPendingData) for PS variants
                                        $pendingMediaIds = $pendingData['media_ids'] ?? [];
                                        $imageCount = count($pendingMediaIds);
                                        if (!empty($pendingMediaIds)) {
                                            $firstPendingMedia = \App\Models\Media::find($pendingMediaIds[0]);
                                            $imageUrl = $firstPendingMedia?->thumbnail_url ?? $firstPendingMedia?->url ?? null;
                                        }
                                    } else {
                                        // Check if this is a model with methods or stdClass from API
                                        if (is_object($variant) && method_exists($variant, 'getCoverImage')) {
                                            // Existing variants (ProductVariant model): use getCoverImage() from database
                                            $coverImage = $variant->getCoverImage() ?? null;
                                            $imageUrl = $coverImage?->getUrl() ?? $coverImage?->getThumbUrl() ?? null;
                                            // Count all images for this variant
                                            $imageCount = $variant->images()->count() ?? 0;
                                        } else {
                                            // PrestaShop API variant (stdClass) - use images array
                                            // FIX 2025-12-09 BUG#6: Handle both array and Collection types
                                            $images = $variant->images ?? [];
                                            if ($images instanceof \Illuminate\Support\Collection) {
                                                $imageCount = $images->count();
                                                $firstImage = $images->first();
                                                $imageUrl = $firstImage?->thumbnail_url ?? $firstImage?->url ?? null;
                                            } elseif (is_array($images) && count($images) > 0) {
                                                $imageCount = count($images);
                                                $firstImage = $images[0] ?? null;
                                                // Handle array (from PrestaShop API) - keys are url/thumbnail_url
                                                $imageUrl = $firstImage['url'] ?? $firstImage['thumbnail_url'] ?? null;
                                            } else {
                                                $imageCount = 0;
                                                $imageUrl = null;
                                            }
                                        }
                                    }

                                    $additionalImages = max(0, $imageCount - 1);
                                @endphp
                                @if($imageUrl)
                                    <div class="relative inline-block">
                                        <img src="{{ $imageUrl }}"
                                             alt="{{ $displayName }}"
                                             class="w-16 h-16 object-cover rounded border border-gray-600 {{ $isPendingUpdate ? 'ring-2 ring-amber-500' : '' }}">
                                        {{-- FIX #5: +X indicator for additional images --}}
                                        @if($additionalImages > 0)
                                            <span class="absolute -bottom-1 -right-1 inline-flex items-center justify-center px-1.5 py-0.5 text-xs font-bold leading-none text-white bg-blue-600 rounded-full border border-blue-400">
                                                +{{ $additionalImages }}
                                            </span>
                                        @endif
                                        {{-- Pending update indicator --}}
                                        @if($isPendingUpdate)
                                            <span class="absolute -top-1 -right-1 flex h-3 w-3">
                                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                                                <span class="relative inline-flex rounded-full h-3 w-3 bg-amber-500"></span>
                                            </span>
                                        @endif
                                    </div>
                                @else
                                    <div class="relative w-16 h-16 bg-gray-700 rounded border border-gray-600 flex items-center justify-center {{ $isPendingUpdate ? 'ring-2 ring-amber-500' : '' }}">
                                        @if($isPendingCreate)
                                            <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                            </svg>
                                        @else
                                            <svg class="w-8 h-8 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                        @endif
                                        {{-- Pending update indicator on empty image --}}
                                        @if($isPendingUpdate)
                                            <span class="absolute -top-1 -right-1 flex h-3 w-3">
                                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                                                <span class="relative inline-flex rounded-full h-3 w-3 bg-amber-500"></span>
                                            </span>
                                        @endif
                                    </div>
                                @endif
                            </td>

                            {{-- SKU --}}
                            <td class="px-4 py-4">
                                <div class="flex items-center space-x-2 flex-wrap gap-1">
                                    <code class="text-sm font-mono {{ $isPendingDelete ? 'text-gray-500 line-through' : 'text-blue-400' }}">{{ $displaySku }}</code>
                                    @if($variant->is_default ?? false)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-900/30 text-purple-200 border border-purple-700/50">
                                            Domyslny
                                        </span>
                                    @endif
                                    {{-- Pending status badges --}}
                                    @if($isPendingCreate)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-900/50 text-green-300 border border-green-700/50">
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/></svg>
                                            NOWY
                                        </span>
                                    @elseif($isPendingUpdate)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-900/50 text-amber-300 border border-amber-700/50">
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/></svg>
                                            ZMIENIONY
                                        </span>
                                    @elseif($isPendingDelete)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-900/50 text-red-300 border border-red-700/50">
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                            DO USUNIECIA
                                        </span>
                                    @endif
                                </div>
                            </td>

                            {{-- Name/Attributes --}}
                            <td class="px-4 py-4">
                                <div class="text-sm {{ $isPendingDelete ? 'text-gray-500 line-through' : 'text-gray-300' }}">{{ $displayName }}</div>
                                @php
                                    // Handle both ProductVariant model and stdClass from API
                                    $hasAttributes = false;
                                    $attributeItems = [];
                                    $hasPendingAttributes = false;

                                    // FIX 2025-12-09: BUG #2 - Show pending attributes if available
                                    if ($isPendingUpdate && $pendingData && !empty($pendingData['attributes'])) {
                                        $attributeItems = $this->convertPendingAttributesToDisplay($pendingData['attributes']);
                                        $hasAttributes = !empty($attributeItems);
                                        $hasPendingAttributes = true;
                                    } elseif (!$isPendingCreate && isset($variant->attributes)) {
                                        if (is_object($variant->attributes) && method_exists($variant->attributes, 'count')) {
                                            // Eloquent Collection (ProductVariant model)
                                            $hasAttributes = $variant->attributes->count() > 0;
                                            $attributeItems = $variant->attributes;
                                        } elseif ($variant->attributes instanceof \Illuminate\Support\Collection) {
                                            // Support\Collection (from pending variants)
                                            $hasAttributes = $variant->attributes->count() > 0;
                                            $attributeItems = $variant->attributes;
                                        } elseif (is_array($variant->attributes) && !empty($variant->attributes)) {
                                            // Array from PrestaShop API - format: [['name' => 'Red', 'group_name' => 'Color'], ...]
                                            $hasAttributes = true;
                                            // Convert to displayable format - data already has name and group_name!
                                            foreach ($variant->attributes as $attr) {
                                                $attrArray = is_array($attr) ? $attr : (array) $attr;
                                                $attributeItems[] = (object)[
                                                    'type_name' => $attrArray['group_name'] ?? 'Atrybut',
                                                    'value_label' => $attrArray['name'] ?? '',
                                                    'display_type' => 'dropdown', // PrestaShop doesn't provide this
                                                    'color_hex' => null, // PrestaShop doesn't provide this directly
                                                ];
                                            }
                                        }
                                    }
                                @endphp
                                @if($hasAttributes)
                                    <div class="flex flex-wrap gap-1 mt-1">
                                        @foreach($attributeItems as $attr)
                                            @php
                                                // Handle both Eloquent model and converted stdClass
                                                if (is_object($attr) && method_exists($attr, 'relationLoaded')) {
                                                    // Eloquent VariantAttribute model
                                                    if (!$attr->relationLoaded('attributeType')) {
                                                        $attr->load('attributeType');
                                                    }
                                                    if (!$attr->relationLoaded('attributeValue')) {
                                                        $attr->load('attributeValue');
                                                    }
                                                    $attrType = $attr->attributeType;
                                                    $attrValue = $attr->attributeValue;

                                                    // DEFENSIVE: If relationship returned Collection, get first item
                                                    if ($attrType instanceof \Illuminate\Database\Eloquent\Collection) {
                                                        $attrType = $attrType->first();
                                                    }
                                                    if ($attrValue instanceof \Illuminate\Database\Eloquent\Collection) {
                                                        $attrValue = $attrValue->first();
                                                    }

                                                    $attrLabel = $attrType?->name ?? 'Atrybut';
                                                    $attrValueLabel = $attrValue?->label ?? '';
                                                    $isColor = $attrType?->display_type === 'color';
                                                    $colorHex = $isColor ? ($attrValue?->color_hex ?? null) : null;
                                                } else {
                                                    // Converted stdClass from API
                                                    $attrLabel = $attr->type_name ?? 'Atrybut';
                                                    $attrValueLabel = $attr->value_label ?? '';
                                                    $isColor = ($attr->display_type ?? '') === 'color';
                                                    $colorHex = $isColor ? ($attr->color_hex ?? null) : null;
                                                }
                                            @endphp
                                            @php
                                                // FIX 2025-12-09: BUG #2 - Amber styling for pending attributes
                                                $isPendingAttr = isset($attr->is_pending) && $attr->is_pending;
                                                $badgeClass = $isPendingAttr
                                                    ? 'bg-amber-900/40 text-amber-300 border border-amber-500/50'
                                                    : 'bg-gray-700 text-gray-300';
                                            @endphp
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $badgeClass }}">
                                                @if($colorHex)
                                                    <span class="w-3 h-3 rounded-full mr-1.5 border border-gray-500" style="background-color: {{ $colorHex }}"></span>
                                                @endif
                                                {{ $attrLabel }}: {{ $attrValueLabel }}
                                            </span>
                                        @endforeach
                                    </div>
                                @elseif($isPendingCreate)
                                    <div class="text-xs text-gray-500 italic mt-1">Atrybuty zostana przypisane po zapisie</div>
                                @endif
                            </td>

                            {{-- Price --}}
                            <td class="px-4 py-4">
                                @php
                                    // PENDING VARIANTS: Pending creates don't have prices yet
                                    $priceValueNet = 0;
                                    $priceModifier = 0;
                                    if (!$isPendingCreate) {
                                        // Check if model (has relationLoaded) or stdClass from API
                                        $hasRelationMethod = is_object($variant) && method_exists($variant, 'relationLoaded');
                                        if ($hasRelationMethod && $variant->relationLoaded('prices') && $variant->prices instanceof \Illuminate\Database\Eloquent\Collection) {
                                            $priceValueNet = (float) ($variant->prices->first()?->price ?? 0);
                                        } elseif (is_numeric($variant->price ?? null)) {
                                            $priceValueNet = (float) $variant->price;
                                        }
                                        $priceModifier = is_numeric($variant->price_modifier ?? null) ? (float) $variant->price_modifier : 0;
                                    }

                                    // ETAP_14: Calculate display price based on mode (Brutto/Netto)
                                    $taxRate = $this->tax_rate ?? 23;
                                    $displayPrice = $variantPriceDisplayMode === 'gross'
                                        ? $priceValueNet * (1 + $taxRate / 100)
                                        : $priceValueNet;
                                @endphp
                                @if($isPendingCreate)
                                    <div class="text-xs text-gray-500 italic">Po zapisie</div>
                                @elseif($isPendingDelete)
                                    <div class="text-sm font-medium text-gray-500 line-through">
                                        {{ number_format($displayPrice, 2, ',', ' ') }} PLN
                                    </div>
                                @else
                                    {{-- ETAP_14: Clickable price opens prices modal --}}
                                    <button type="button"
                                            wire:click="openVariantPricesModal({{ $variant->id }})"
                                            class="variant-price-btn text-sm font-medium text-gray-300 hover:text-white"
                                            title="Kliknij aby edytowac ceny wariantu">
                                        {{ number_format($displayPrice, 2, ',', ' ') }} PLN
                                        <svg class="w-3 h-3 ml-1 inline opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                        </svg>
                                    </button>
                                    @if($priceModifier != 0)
                                        <div class="text-xs text-gray-500">
                                            {{ $priceModifier > 0 ? '+' : '' }}{{ number_format($priceModifier, 2, ',', ' ') }} PLN
                                        </div>
                                    @endif
                                @endif
                            </td>

                            {{-- Stock --}}
                            <td class="px-4 py-4">
                                @php
                                    // PENDING VARIANTS: Pending creates don't have stock yet
                                    $stockValue = 0;
                                    if (!$isPendingCreate) {
                                        // Check if model (has relationLoaded) or stdClass from API
                                        $hasRelationMethod = is_object($variant) && method_exists($variant, 'relationLoaded');
                                        // stock() is a relation returning Collection - sum quantity field
                                        if ($hasRelationMethod && $variant->relationLoaded('stock') && $variant->stock instanceof \Illuminate\Database\Eloquent\Collection) {
                                            $stockValue = (int) $variant->stock->sum('quantity');
                                        } elseif (is_numeric($variant->stock ?? null)) {
                                            $stockValue = (int) $variant->stock;
                                        }
                                    }
                                    $stockClass = match(true) {
                                        $stockValue > 10 => 'text-green-400',
                                        $stockValue > 0 => 'text-yellow-400',
                                        default => 'text-red-400'
                                    };
                                @endphp
                                @if($isPendingCreate)
                                    <div class="text-xs text-gray-500 italic">Po zapisie</div>
                                @elseif($isPendingDelete)
                                    <div class="text-sm font-medium text-gray-500 line-through">
                                        {{ $stockValue }} szt.
                                    </div>
                                @else
                                    {{-- ETAP_14: Clickable stock opens stock modal --}}
                                    <button type="button"
                                            wire:click="openVariantStockModal({{ $variant->id }})"
                                            class="variant-stock-btn text-sm font-medium {{ $stockClass }} hover:opacity-80"
                                            title="Kliknij aby edytowac stany magazynowe wariantu">
                                        {{ $stockValue }} szt.
                                        <svg class="w-3 h-3 ml-1 inline opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                        </svg>
                                    </button>
                                @endif
                            </td>

                            {{-- Status --}}
                            <td class="px-4 py-4">
                                @if($isPendingDelete)
                                    {{-- Disabled toggle for pending delete --}}
                                    <div class="relative inline-flex h-6 w-11 items-center rounded-full bg-gray-700 opacity-50 cursor-not-allowed">
                                        <span class="sr-only">Status</span>
                                        <span class="inline-block h-4 w-4 transform rounded-full bg-gray-400 translate-x-1"></span>
                                    </div>
                                @elseif($isPendingCreate)
                                    {{-- Toggle for pending create - uses displayIsActive --}}
                                    <button type="button"
                                            wire:click="togglePendingVariantStatus({{ $variant->id }})"
                                            class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 focus:ring-offset-gray-900 {{ $displayIsActive ? 'bg-green-600' : 'bg-gray-600' }}"
                                            title="Wariant niezapisany">
                                        <span class="sr-only">Toggle status</span>
                                        <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform {{ $displayIsActive ? 'translate-x-6' : 'translate-x-1' }}"></span>
                                    </button>
                                @else
                                    {{-- Normal toggle for existing/updated variants --}}
                                    <button type="button"
                                            wire:click="toggleVariantStatus({{ $variant->id }})"
                                            class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:ring-offset-gray-900 {{ $displayIsActive ? 'bg-blue-600' : 'bg-gray-600' }}">
                                        <span class="sr-only">Toggle status</span>
                                        <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform {{ $displayIsActive ? 'translate-x-6' : 'translate-x-1' }}"></span>
                                    </button>
                                @endif
                            </td>

                            {{-- ETAP_05b FAZA 5: Per-shop Context Column --}}
                            @if($isInShopContext)
                                <td class="px-4 py-4">
                                    @if($hasPendingStatus)
                                        {{-- Pending variants don't show shop status --}}
                                        <span class="text-xs text-gray-500">-</span>
                                    @else
                                        <div class="flex flex-col space-y-1">
                                            {{-- Status badge - uses status-label-* CSS classes --}}
                                            @if($variantStatusIndicator['show'])
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $variantStatusIndicator['class'] }}">
                                                    @if($variantShopStatus === 'inherited')
                                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M12.586 4.586a2 2 0 112.828 2.828l-3 3a2 2 0 01-2.828 0 1 1 0 00-1.414 1.414 4 4 0 005.656 0l3-3a4 4 0 00-5.656-5.656l-1.5 1.5a1 1 0 101.414 1.414l1.5-1.5zm-5 5a2 2 0 012.828 0 1 1 0 101.414-1.414 4 4 0 00-5.656 0l-3 3a4 4 0 105.656 5.656l1.5-1.5a1 1 0 10-1.414-1.414l-1.5 1.5a2 2 0 11-2.828-2.828l3-3z" clip-rule="evenodd"/></svg>
                                                    @elseif($variantShopStatus === 'same')
                                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                                    @elseif($variantShopStatus === 'different')
                                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/></svg>
                                                    @endif
                                                    {{ $variantStatusIndicator['text'] }}
                                                </span>
                                            @endif

                                            {{-- Action buttons (Przywroc only for overridden variants with valid PPM ID) --}}
                                            @php
                                                // Check if variant has valid PPM ID (not ps_xxx format)
                                                $hasValidPpmId = is_numeric($variant->id) && $variant->id > 0;
                                            @endphp
                                            @if($hasValidPpmId && ($variantShopStatus === 'different' || $variantShopStatus === 'same'))
                                                <div class="flex space-x-1 mt-1">
                                                    <button type="button"
                                                            wire:click="removeShopVariantOverride({{ $activeShopId }}, {{ $variant->id }})"
                                                            class="text-xs px-2 py-1 bg-gray-700 text-gray-300 hover:bg-gray-600 rounded border border-gray-600 transition-colors"
                                                            title="Usun wlasna wersje i dziecicz z domyslnych">
                                                        Przywroc
                                                    </button>
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                            @endif

                            {{-- Actions --}}
                            <td class="px-4 py-4 text-right">
                                @php
                                    // Determine variant type for action buttons
                                    // FIX 2025-12-09: Handle OVERRIDE variants (linked PPM+PS) correctly in shop context
                                    $isPrestaShopOnly = is_string($variant->id) && str_starts_with($variant->id, 'ps_');
                                    $hasPrestaShopLink = isset($variant->prestashop_combination_id) && $variant->prestashop_combination_id > 0;
                                    $isLinkedOverride = !$isPrestaShopOnly && $hasPrestaShopLink && ($variant->operation_type ?? null) === 'OVERRIDE';
                                    $canEditInPpm = is_numeric($variant->id) && $variant->id > 0 && !$isInShopContext;
                                    // In shop context, linked variants should use PS methods
                                    $shouldUsePsMethods = $isInShopContext && ($isPrestaShopOnly || $isLinkedOverride);
                                    // For linked variants, use ps_xxx format for PS methods (compute BEFORE using in pending checks)
                                    $psVariantIdForMethods = $isPrestaShopOnly ? $variant->id : ($hasPrestaShopLink ? 'ps_' . $variant->prestashop_combination_id : null);
                                    // Check pending PS variant status (use $psVariantIdForMethods for correct lookup)
                                    $isPsPendingDelete = $shouldUsePsMethods && $psVariantIdForMethods && ($this->isPsVariantPendingDelete($psVariantIdForMethods) ?? false);
                                    $isPsPendingUpdate = $shouldUsePsMethods && $psVariantIdForMethods && ($this->isPsVariantPendingUpdate($psVariantIdForMethods) ?? false);
                                @endphp
                                <div class="flex items-center justify-end space-x-2">
                                    @if($isPendingDelete || $isPsPendingDelete)
                                        {{-- Pending Delete: Only Undo button --}}
                                        @if($shouldUsePsMethods && $psVariantIdForMethods)
                                            <button type="button"
                                                    wire:click="undoPsVariantDelete('{{ $psVariantIdForMethods }}')"
                                                    class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-amber-400 hover:text-amber-300 bg-amber-900/30 hover:bg-amber-900/50 rounded-lg border border-amber-700/50 transition-colors"
                                                    title="Cofnij usuniecie">
                                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path>
                                                </svg>
                                                Cofnij
                                            </button>
                                        @else
                                            <button type="button"
                                                    wire:click="undoDeleteVariant({{ $variant->id }})"
                                                    class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-amber-400 hover:text-amber-300 bg-amber-900/30 hover:bg-amber-900/50 rounded-lg border border-amber-700/50 transition-colors"
                                                    title="Cofnij usuniecie">
                                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path>
                                                </svg>
                                                Cofnij
                                            </button>
                                        @endif
                                    @elseif($isPendingCreate)
                                        {{-- Pending Create: Edit and Remove (from pending) --}}
                                        <button type="button"
                                                wire:click="loadPendingVariantForEdit({{ $variant->id }})"
                                                class="text-green-400 hover:text-green-300"
                                                title="Edytuj nowy wariant">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </button>
                                        <button type="button"
                                                wire:click="removePendingVariant({{ $variant->id }})"
                                                class="text-red-400 hover:text-red-300"
                                                title="Usun z kolejki">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                        </button>
                                    @elseif($shouldUsePsMethods && $psVariantIdForMethods)
                                        {{-- PrestaShop variant (ps_xxx or linked OVERRIDE): Full actions with pending support --}}
                                        @if($isPsPendingUpdate)
                                            {{-- PS Variant with pending update --}}
                                            <button type="button"
                                                    wire:click="loadPsVariantForEdit('{{ $psVariantIdForMethods }}')"
                                                    class="text-amber-400 hover:text-amber-300"
                                                    title="Edytuj (zmiany oczekujace)">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                </svg>
                                            </button>
                                        @else
                                            {{-- Normal PS Variant --}}
                                            <button type="button"
                                                    wire:click="loadPsVariantForEdit('{{ $psVariantIdForMethods }}')"
                                                    class="text-blue-400 hover:text-blue-300"
                                                    title="Edytuj wariant PrestaShop">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                </svg>
                                            </button>
                                        @endif
                                        <button type="button"
                                                wire:click="markPsVariantForDelete('{{ $psVariantIdForMethods }}')"
                                                class="text-red-400 hover:text-red-300"
                                                title="Usun wariant z PrestaShop">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    @else
                                        {{-- Normal/Pending Update: Full actions (only for PPM variants) --}}
                                        @if($canEditInPpm)
                                            <button type="button"
                                                    wire:click="loadVariantForEdit({{ $variant->id }})"
                                                    class="text-blue-400 hover:text-blue-300"
                                                    title="Edytuj">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                </svg>
                                            </button>

                                            @if($isPendingUpdate)
                                                {{-- Undo pending changes --}}
                                                <button type="button"
                                                        wire:click="undoUpdateVariant({{ $variant->id }})"
                                                        class="text-amber-400 hover:text-amber-300"
                                                        title="Cofnij zmiany">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path>
                                                    </svg>
                                                </button>
                                            @else
                                                {{-- Duplicate only for saved variants --}}
                                                <button type="button"
                                                        wire:click="duplicateVariant({{ $variant->id }})"
                                                        class="text-gray-400 hover:text-gray-300"
                                                        title="Duplikuj">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                                    </svg>
                                                </button>
                                            @endif

                                            <button type="button"
                                                    wire:click="deleteVariant({{ $variant->id }})"
                                                    class="text-red-400 hover:text-red-300"
                                                    title="Usun">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                            </button>
                                        @else
                                            {{-- Invalid ID - show nothing --}}
                                            <span class="text-xs text-gray-500">-</span>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        {{-- Empty State --}}
        <div class="bg-gray-800 rounded-lg border border-gray-700 p-12 text-center">
            <div class="flex flex-col items-center space-y-4">
                <svg class="w-16 h-16 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                </svg>
                <div>
                    <h4 class="text-lg font-medium text-gray-300 mb-2">Ten produkt nie ma wariantów</h4>
                    <p class="text-sm text-gray-500 mb-4">
                        Warianty pozwalają na oferowanie produktu w różnych wersjach (kolor, rozmiar, etc.)
                    </p>
                </div>
                <button type="button"
                        wire:click="openCreateVariantModal"
                        class="btn-enterprise-primary inline-flex items-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    <span>Dodaj pierwszy wariant</span>
                </button>
            </div>
        </div>
    @endif

    {{-- Help Text --}}
    <div class="mt-6 p-4 bg-blue-900/10 border border-blue-700/30 rounded-lg">
        <div class="flex items-start space-x-3">
            <svg class="w-5 h-5 text-blue-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
            </svg>
            <div class="flex-1">
                <h5 class="text-sm font-medium text-blue-300 mb-1">Informacje o wariantach</h5>
                <p class="text-xs text-gray-400 leading-relaxed">
                    Warianty dziedziczą podstawowe informacje z produktu głównego (nazwa, opis, kategorie).
                    Możesz dostosować cenę, stan magazynowy oraz zdjęcia dla każdego wariantu osobno.
                    Zaznacz checkbox aby wykonać operacje na wielu wariantach jednocześnie.
                </p>
            </div>
        </div>
    </div>

</div>

{{-- Create/Edit Variant Modal - x-teleport to body for proper positioning --}}
{{-- Uses $wire for Livewire binding (required with x-teleport) --}}
@if($showCreateModal || $showEditModal)
    <template x-teleport="body">
        <div class="fixed inset-0 overflow-y-auto"
             style="z-index: 9999;"
             x-data="{ open: true }"
             x-show="open"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             aria-labelledby="variant-modal-title"
             role="dialog"
             aria-modal="true">
            {{-- Background overlay - uses $wire for Livewire --}}
            <div class="fixed inset-0 bg-black/70 transition-opacity"
                 @click="$wire.closeVariantModal()"></div>

            {{-- Modal panel --}}
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-lg bg-gray-800 rounded-xl shadow-2xl border border-gray-700"
                     @click.stop>
                    <div class="px-6 py-4 border-b border-gray-700">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-white" id="variant-modal-title">
                                {{ $showEditModal ? 'Edytuj wariant' : 'Nowy wariant' }}
                            </h3>
                            <button type="button"
                                    @click="$wire.closeVariantModal()"
                                    class="text-gray-400 hover:text-white">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="px-6 py-4 space-y-4 max-h-[70vh] overflow-y-auto">
                        {{-- ETAP_05f: Auto SKU Checkbox --}}
                        <div class="bg-blue-900/20 border border-blue-700 rounded-lg p-3">
                            <label class="flex items-center space-x-3 cursor-pointer">
                                <input type="checkbox"
                                       wire:model.live="variantData.auto_generate_sku"
                                       class="w-5 h-5 text-blue-500 bg-gray-900 border-gray-600 rounded focus:ring-blue-500 focus:ring-2">
                                <span class="text-sm text-gray-300">
                                    <i class="fas fa-magic text-blue-400 mr-2"></i>
                                    Automatycznie generuj SKU z atrybutow
                                </span>
                            </label>
                            <p class="text-xs text-gray-500 mt-1 ml-8">
                                SKU bedzie skladane z prefix/suffix zdefiniowanych w atrybutach
                            </p>
                        </div>

                        {{-- SKU --}}
                        <div>
                            <label for="variant_sku" class="block text-sm font-medium text-gray-300 mb-1">
                                SKU wariantu *
                                @if($variantData['auto_generate_sku'] ?? false)
                                    <span class="text-xs text-blue-400 ml-2">(generowane automatycznie)</span>
                                @endif
                            </label>
                            <input type="text"
                                   id="variant_sku"
                                   x-model="$wire.variantData.sku"
                                   @if($variantData['auto_generate_sku'] ?? false) readonly @endif
                                   class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent {{ ($variantData['auto_generate_sku'] ?? false) ? 'opacity-75 cursor-not-allowed' : '' }}"
                                   placeholder="np. PROD-001-V001">
                            @error('variantData.sku') <span class="text-red-400 text-xs">{{ $message }}</span> @enderror
                        </div>

                        {{-- Name --}}
                        <div>
                            <label for="variant_name" class="block text-sm font-medium text-gray-300 mb-1">Nazwa wariantu *</label>
                            <input type="text"
                                   id="variant_name"
                                   x-model="$wire.variantData.name"
                                   class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="np. Czarny XL">
                            @error('variantData.name') <span class="text-red-400 text-xs">{{ $message }}</span> @enderror
                        </div>

                        {{-- Position --}}
                        <div>
                            <label for="variant_position" class="block text-sm font-medium text-gray-300 mb-1">Pozycja</label>
                            <input type="number"
                                   id="variant_position"
                                   x-model="$wire.variantData.position"
                                   class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   min="0"
                                   placeholder="0">
                        </div>

                        {{-- Variant Image Selection - Multiple Images --}}
                        @if($product->media && $product->media->count() > 0)
                            <div class="border-t border-gray-700 pt-3 mt-3">
                                <div class="flex items-center justify-between mb-2">
                                    <h4 class="text-sm font-medium text-gray-300">Zdjecia wariantu <span class="text-gray-500 text-xs">(wielokrotny wybor)</span></h4>
                                    @php
                                        $selectedCount = count($variantData['media_ids'] ?? []);
                                    @endphp
                                    @if($selectedCount > 0)
                                        <span class="text-xs text-green-400 flex items-center gap-1">
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                            Wybrano: {{ $selectedCount }}
                                        </span>
                                    @endif
                                </div>
                                {{-- Horizontal scrollable strip --}}
                                <div class="flex gap-2 overflow-x-auto pb-2 scrollbar-thin scrollbar-thumb-gray-600">
                                    @foreach($product->media->take(15) as $mediaItem)
                                        @php
                                            $isSelected = in_array($mediaItem->id, $variantData['media_ids'] ?? []);
                                            $selectedIndex = $isSelected ? array_search($mediaItem->id, $variantData['media_ids'] ?? []) : false;
                                        @endphp
                                        <button type="button"
                                                @click="$wire.toggleVariantImage({{ $mediaItem->id }})"
                                                class="relative flex-shrink-0 w-14 h-14 rounded-md overflow-hidden border-2 transition-all duration-150 {{ $isSelected ? 'border-blue-500 ring-2 ring-blue-500 ring-offset-1 ring-offset-gray-800 scale-105' : 'border-gray-600 hover:border-gray-400' }}">
                                            <img src="{{ $mediaItem->thumbnail_url ?? $mediaItem->url }}"
                                                 alt=""
                                                 class="w-full h-full object-cover">
                                            @if($isSelected)
                                                <div class="absolute inset-0 bg-blue-500/30 flex items-center justify-center">
                                                    @if($selectedIndex === 0)
                                                        <span class="text-[10px] font-bold text-white bg-blue-600 px-1 rounded">1 (Cover)</span>
                                                    @else
                                                        <span class="text-xs font-bold text-white">{{ $selectedIndex + 1 }}</span>
                                                    @endif
                                                </div>
                                            @endif
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- Attributes Section --}}
                        @php
                            $attributeTypes = \App\Models\AttributeType::where('is_active', true)
                                ->whereIn('code', ['size', 'color'])
                                ->orderBy('position')
                                ->get();
                        @endphp
                        @if($attributeTypes->count() > 0)
                            <div class="border-t border-gray-700 pt-4 mt-4">
                                <h4 class="text-sm font-medium text-gray-300 mb-3">Atrybuty wariantu</h4>
                                <div class="grid grid-cols-2 gap-4">
                                    @foreach($attributeTypes as $attrType)
                                        <div>
                                            <label class="block text-sm font-medium text-gray-400 mb-1">{{ $attrType->name }}</label>
                                            @php
                                                $attrValues = \App\Models\AttributeValue::where('attribute_type_id', $attrType->id)
                                                    ->where('is_active', true)
                                                    ->orderBy('position')
                                                    ->get();
                                                $currentValueId = $variantAttributes[$attrType->id] ?? null;
                                                $currentValueLabel = $currentValueId ? $attrValues->firstWhere('id', $currentValueId)?->label : null;
                                            @endphp
                                            @if($attrType->display_type === 'color')
                                                {{-- Color picker --}}
                                                <div class="flex flex-wrap gap-2 items-center">
                                                    @foreach($attrValues as $attrVal)
                                                        @php
                                                            $isColorSelected = (int)$currentValueId === (int)$attrVal->id;
                                                        @endphp
                                                        <button type="button"
                                                                wire:click="setVariantAttribute({{ $attrType->id }}, {{ $attrVal->id }})"
                                                                class="w-8 h-8 rounded-full border-2 transition-all duration-150 {{ $isColorSelected ? 'ring-2 ring-blue-500 ring-offset-2 ring-offset-gray-800 border-blue-500 scale-110' : 'border-gray-600 hover:border-gray-400 hover:scale-105' }}"
                                                                style="background-color: {{ $attrVal->color_hex ?? '#888888' }}"
                                                                title="{{ $attrVal->label }}">
                                                            <span class="sr-only">{{ $attrVal->label }}</span>
                                                        </button>
                                                    @endforeach
                                                    @if($currentValueLabel)
                                                        <span class="ml-2 text-sm text-blue-400 font-medium">{{ $currentValueLabel }}</span>
                                                    @endif
                                                </div>
                                            @else
                                                {{-- Dropdown for size - use wire:change with method call --}}
                                                {{-- wire:model doesn't work properly with numeric array keys --}}
                                                <select wire:change="setVariantAttribute({{ $attrType->id }}, $event.target.value)"
                                                        class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                                    <option value="">-- Wybierz --</option>
                                                    @foreach($attrValues as $attrVal)
                                                        <option value="{{ $attrVal->id }}" {{ ($variantAttributes[$attrType->id] ?? null) == $attrVal->id ? 'selected' : '' }}>{{ $attrVal->label }}</option>
                                                    @endforeach
                                                </select>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- Validation Errors --}}
                        @error('variantData')
                            <div class="mt-4 p-3 bg-red-900/30 border border-red-700 rounded-lg text-red-300 text-sm">
                                {{ $message }}
                            </div>
                        @enderror

                        {{-- Options --}}
                        <div class="flex items-center space-x-6">
                            <label class="flex items-center">
                                <input type="checkbox"
                                       x-model="$wire.variantData.is_active"
                                       class="rounded border-gray-600 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 bg-gray-700">
                                <span class="ml-2 text-sm text-gray-300">Aktywny</span>
                            </label>

                            <label class="flex items-center">
                                <input type="checkbox"
                                       x-model="$wire.variantData.is_default"
                                       class="rounded border-gray-600 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 bg-gray-700">
                                <span class="ml-2 text-sm text-gray-300">Domyslny</span>
                            </label>
                        </div>
                    </div>

                    <div class="px-6 py-4 border-t border-gray-700 flex flex-row-reverse gap-3">
                        @if($showEditModal)
                            <button type="button"
                                    @click="$wire.updateVariant()"
                                    class="inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:text-sm">
                                Zapisz zmiany
                            </button>
                        @else
                            <button type="button"
                                    @click="$wire.createVariant()"
                                    class="inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:text-sm">
                                Utworz wariant
                            </button>
                        @endif
                        <button type="button"
                                @click="$wire.closeVariantModal()"
                                class="inline-flex justify-center rounded-md border border-gray-600 shadow-sm px-4 py-2 bg-gray-700 text-base font-medium text-gray-300 hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 sm:text-sm">
                            Anuluj
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>
@endif

{{-- ETAP_14: Variant Prices Modal --}}
@include('livewire.products.management.partials.variant-prices-modal')

{{-- ETAP_14: Variant Stock Modal --}}
@include('livewire.products.management.partials.variant-stock-modal')

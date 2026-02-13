<?php

namespace App\Http\Livewire\Products\Management\Traits;

use App\DTOs\ShopVariantOverride;
use App\Models\Media;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * VariantShopContextTrait - Per-Shop Variant Isolation
 *
 * Handles shop-specific variant overrides using JSON storage in product_shop_data.attribute_mappings
 * Pattern consistent with ProductFormFeatures (per-shop feature isolation)
 *
 * ARCHITECTURE:
 * - Default variants: stored in product_variants table
 * - Shop overrides: stored in product_shop_data.attribute_mappings['variants']
 * - Inheritance: shops inherit from default unless they have custom overrides
 *
 * STATUS INDICATORS:
 * - 'default': viewing default tab (no shop context)
 * - 'inherited': shop context, using default variant (no override)
 * - 'same': shop context, override identical to default
 * - 'different': shop context, override differs from default
 *
 * @package App\Http\Livewire\Products\Management\Traits
 * @version 1.0
 * @since ETAP_05b FAZA 5
 */
trait VariantShopContextTrait
{
    /*
    |--------------------------------------------------------------------------
    | PROPERTIES
    |--------------------------------------------------------------------------
    */

    /**
     * Snapshot of default variants at mount (for comparison)
     * Format: [variantId => ['sku', 'name', 'is_active', 'is_default', 'attributes' => [], 'position']]
     * @var array
     */
    public array $defaultVariantsSnapshot = [];

    /**
     * Shop variant overrides loaded from product_shop_data
     * Format: [shopId => [variantId => ShopVariantOverride]]
     * @var array
     */
    public array $shopVariantOverrides = [];

    /**
     * UI toggle: show inherited variants in shop context
     * @var bool
     */
    public bool $showInheritedVariants = true;

    /**
     * PrestaShop product images for variant image picker (shop context)
     * Format: [['prestashop_image_id' => int, 'url' => string, 'thumbnail_url' => string], ...]
     * @var array
     */
    public array $psProductImages = [];

    /*
    |--------------------------------------------------------------------------
    | CONTEXT SWITCHING
    |--------------------------------------------------------------------------
    */

    /**
     * Load default variants snapshot (called during mount and selectDefaultTab)
     */
    public function loadDefaultVariantsSnapshot(): void
    {
        if (!$this->product || !$this->product->id) {
            $this->defaultVariantsSnapshot = [];
            return;
        }

        $variants = ProductVariant::where('product_id', $this->product->id)
            ->with(['attributes'])
            ->orderBy('position')
            ->get();

        $this->defaultVariantsSnapshot = $variants->mapWithKeys(function ($variant) {
            // FIX 2026-02-11: Include image count for comparison with PS variants
            $imageCount = $variant->images()->count();
            $imageIds = $variant->images()->orderBy('position')->pluck('id')->toArray();

            return [$variant->id => [
                'id' => $variant->id,
                'sku' => $variant->sku,
                'name' => $variant->name,
                'is_active' => $variant->is_active,
                'is_default' => $variant->is_default,
                'position' => $variant->position,
                'image_count' => $imageCount,
                'image_ids' => $imageIds,
                'attributes' => $variant->attributes->mapWithKeys(
                    fn($attr) => [$attr->attribute_type_id => $attr->attribute_value_id]
                )->toArray(),
            ]];
        })->toArray();

        Log::debug('[VARIANT SHOP CONTEXT] Loaded default variants snapshot', [
            'product_id' => $this->product->id,
            'count' => count($this->defaultVariantsSnapshot),
        ]);
    }

    /**
     * Load shop-specific variant overrides from product_shop_data
     */
    public function loadShopVariantOverrides(int $shopId): void
    {
        if (!$this->product || !$this->product->id) {
            return;
        }

        $shopData = $this->product->shopData()
            ->where('shop_id', $shopId)
            ->first();

        if (!$shopData) {
            Log::debug('[VARIANT SHOP CONTEXT] No shop data found', [
                'product_id' => $this->product->id,
                'shop_id' => $shopId,
            ]);
            $this->shopVariantOverrides[$shopId] = [];
            return;
        }

        $attributeMappings = $shopData->attribute_mappings ?? [];
        $variantMappings = $attributeMappings['variants']['mapping'] ?? [];

        $overrides = [];
        foreach ($variantMappings as $key => $data) {
            $variantId = (int) str_replace('variant-', '', $key);
            $overrides[$variantId] = ShopVariantOverride::fromArray($variantId, $data);
        }

        $this->shopVariantOverrides[$shopId] = $overrides;

        Log::debug('[VARIANT SHOP CONTEXT] Loaded shop variant overrides', [
            'shop_id' => $shopId,
            'override_count' => count($overrides),
        ]);
    }

    /**
     * Switch variant context when shop tab is selected
     * Called from ProductForm::selectShopTab()
     */
    public function switchVariantContextToShop(int $shopId): void
    {
        // Ensure default snapshot is loaded
        if (empty($this->defaultVariantsSnapshot)) {
            $this->loadDefaultVariantsSnapshot();
        }

        // Load shop overrides
        $this->loadShopVariantOverrides($shopId);

        Log::debug('[VARIANT SHOP CONTEXT] Switched to shop context', [
            'shop_id' => $shopId,
            'default_count' => count($this->defaultVariantsSnapshot),
            'override_count' => count($this->shopVariantOverrides[$shopId] ?? []),
        ]);
    }

    /**
     * Restore variant context to default (called from selectDefaultTab)
     */
    public function restoreVariantContextToDefault(): void
    {
        $this->loadDefaultVariantsSnapshot();

        Log::debug('[VARIANT SHOP CONTEXT] Restored to default context');
    }

    /*
    |--------------------------------------------------------------------------
    | STATUS CALCULATION
    |--------------------------------------------------------------------------
    */

    /**
     * Get variant status for comparison UI
     *
     * FIXED 2025-12-08: Now compares PrestaShop API data vs PPM data (not shopVariantOverrides)
     * Pattern consistent with ProductFormFeatures::getFeatureStatus()
     *
     * @param int|string $variantId Default variant ID (may come as string from JSON)
     * @return string 'default'|'inherited'|'same'|'different'
     */
    public function getVariantShopStatus(int|string $variantId): string
    {
        // Cast to int for consistent array key lookup
        $variantId = (int) $variantId;

        // Default context = always 'default'
        if ($this->activeShopId === null) {
            return 'default';
        }

        // Get PPM default variant data
        $ppmVariant = $this->defaultVariantsSnapshot[$variantId] ?? null;

        if (!$ppmVariant) {
            return 'different'; // No PPM data = must be shop-only variant
        }

        // Get PrestaShop variant data (from API pull) by SKU
        $psVariant = $this->findPrestaShopVariantBySku($ppmVariant['sku'] ?? '');

        // No PrestaShop data = inherited (not synced yet)
        if (!$psVariant) {
            return 'inherited';
        }

        // Compare PrestaShop data vs PPM data (by SKU matching)
        $isIdentical = $this->compareVariantData($psVariant, $ppmVariant);

        return $isIdentical ? 'same' : 'different';
    }

    /**
     * Find PrestaShop variant by SKU from pulled data
     *
     * FIX 2025-12-09: Also try matching without shop suffix (-S1, -S2, etc.)
     * PPM may store variants with shop-specific SKU suffixes that don't exist in PrestaShop
     *
     * @param string $sku
     * @return object|null
     */
    protected function findPrestaShopVariantBySku(string $sku): ?object
    {
        if (empty($this->prestaShopVariants['variants'])) {
            return null;
        }

        // Try exact match first
        $exactMatch = $this->prestaShopVariants['variants']
            ->first(fn($v) => ($v->sku ?? '') === $sku);

        if ($exactMatch) {
            return $exactMatch;
        }

        // FIX 2025-12-09: Try matching PS SKU with suffix against PPM SKU without suffix
        // PrestaShop may have SKU like "MR-MRF-E-V001-S1" while PPM has "MR-MRF-E-V001"
        // So we need to find PS variant where stripping suffix from PS SKU matches our PPM SKU
        $matchWithSuffix = $this->prestaShopVariants['variants']
            ->first(function($v) use ($sku) {
                $psSku = $v->sku ?? '';
                $psSkuWithoutSuffix = preg_replace('/-S\d+$/', '', $psSku);
                return $psSkuWithoutSuffix === $sku;
            });

        return $matchWithSuffix;
    }

    /**
     * Compare PrestaShop variant data vs PPM variant data
     *
     * FIX 2026-02-11: Full comparison including images, name, active status, attributes.
     * SKU match is required first, then we compare actual data fields.
     *
     * @param object $psVariant PrestaShop variant data
     * @param array $ppmVariant PPM variant data from defaultVariantsSnapshot
     * @return bool True if variants match (same data)
     */
    protected function compareVariantData(object $psVariant, array $ppmVariant): bool
    {
        // Step 1: SKU must match (exact or base without shop suffix)
        $psSku = $psVariant->sku ?? '';
        $psSkuBase = preg_replace('/-S\d+$/', '', $psSku);
        $ppmSku = $ppmVariant['sku'] ?? '';

        if ($psSku !== $ppmSku && $psSkuBase !== $ppmSku) {
            return false;
        }

        // Step 2: Compare image count (PS images vs PPM images)
        $psImages = $psVariant->images ?? [];
        $psImageCount = is_array($psImages) ? count($psImages)
            : ($psImages instanceof \Illuminate\Support\Collection ? $psImages->count() : 0);
        $ppmImageCount = $ppmVariant['image_count'] ?? 0;

        if ($psImageCount !== $ppmImageCount) {
            return false;
        }

        // Step 3: Compare active status
        $psActive = (bool) ($psVariant->is_active ?? true);
        $ppmActive = (bool) ($ppmVariant['is_active'] ?? true);

        if ($psActive !== $ppmActive) {
            return false;
        }

        // Step 4: Compare name
        $psName = trim($psVariant->name ?? '');
        $ppmName = trim($ppmVariant['name'] ?? '');

        if (!empty($psName) && !empty($ppmName) && $psName !== $ppmName) {
            return false;
        }

        return true;
    }

    /**
     * Get CSS classes for variant row based on status
     * @param int|string $variantId Variant ID (may come as string from JSON)
     */
    public function getVariantShopRowClasses(int|string $variantId): string
    {
        $status = $this->getVariantShopStatus((int) $variantId);

        $baseClasses = 'variant-row transition-all duration-200';

        return match ($status) {
            'default' => $baseClasses . ' variant-row-default',
            'inherited' => $baseClasses . ' variant-row-inherited',
            'same' => $baseClasses . ' variant-row-same',
            'different' => $baseClasses . ' variant-row-different',
            default => $baseClasses,
        };
    }

    /**
     * Get status indicator badge for UI
     *
     * FIXED 2025-12-08: Uses existing CSS classes from components.css consistent with basic-tab.blade.php
     * - 'same' = 'zgodne' (green) - PrestaShop data matches PPM data
     * - 'different' = 'wlasne' (orange) - PrestaShop data differs from PPM
     * - 'inherited' = 'dziedziczone' (purple) - Not synced to PrestaShop yet
     *
     * CSS Classes: status-label-same, status-label-different, status-label-inherited
     * Polish labels consistent with ProductForm other tabs
     *
     * @param int|string $variantId Variant ID (may come as string from JSON)
     */
    public function getVariantShopStatusIndicator(int|string $variantId): array
    {
        // Handle ps_xxx format from PrestaShop variants
        $variantIdInt = is_string($variantId) && str_starts_with($variantId, 'ps_')
            ? 0  // PrestaShop-only variant without local mapping
            : (int) $variantId;

        $status = $this->getVariantShopStatus($variantIdInt);

        return match ($status) {
            'default' => ['show' => false, 'text' => '', 'class' => ''],
            'inherited' => [
                'show' => true,
                'text' => 'dziedziczone',
                'class' => 'status-label-inherited'
            ],
            'same' => [
                'show' => true,
                'text' => 'zgodne',
                'class' => 'status-label-same'
            ],
            'different' => [
                'show' => true,
                'text' => 'wlasne',
                'class' => 'status-label-different'
            ],
            default => ['show' => false, 'text' => '', 'class' => ''],
        };
    }

    /*
    |--------------------------------------------------------------------------
    | SHOP OVERRIDE MANAGEMENT
    |--------------------------------------------------------------------------
    */

    /**
     * Create shop-specific variant override from default variant
     */
    public function createShopVariantOverride(int $shopId, int $defaultVariantId): void
    {
        $defaultVariant = $this->defaultVariantsSnapshot[$defaultVariantId] ?? null;

        if (!$defaultVariant) {
            Log::error('[VARIANT SHOP] Cannot create override - default variant not found', [
                'shop_id' => $shopId,
                'variant_id' => $defaultVariantId,
            ]);
            session()->flash('error', 'Nie znaleziono wariantu domyslnego.');
            return;
        }

        // Create override with shop-specific SKU suffix
        $override = new ShopVariantOverride(
            defaultVariantId: $defaultVariantId,
            sku: $defaultVariant['sku'] . '-S' . $shopId,
            name: $defaultVariant['name'],
            isActive: $defaultVariant['is_active'],
            isDefault: $defaultVariant['is_default'],
            attributes: $defaultVariant['attributes'],
            position: $defaultVariant['position']
        );

        if (!isset($this->shopVariantOverrides[$shopId])) {
            $this->shopVariantOverrides[$shopId] = [];
        }

        $this->shopVariantOverrides[$shopId][$defaultVariantId] = $override;
        $this->hasUnsavedChanges = true;

        Log::info('[VARIANT SHOP] Created shop variant override', [
            'shop_id' => $shopId,
            'default_variant_id' => $defaultVariantId,
            'override_sku' => $override->sku,
        ]);

        session()->flash('message', 'Utworzono wlasny wariant dla sklepu (zapisz aby potwierdzic).');
    }

    /**
     * Remove shop variant override (revert to inherited)
     */
    public function removeShopVariantOverride(int $shopId, int $variantId): void
    {
        if (isset($this->shopVariantOverrides[$shopId][$variantId])) {
            unset($this->shopVariantOverrides[$shopId][$variantId]);
            $this->hasUnsavedChanges = true;

            Log::info('[VARIANT SHOP] Removed shop variant override', [
                'shop_id' => $shopId,
                'variant_id' => $variantId,
            ]);

            session()->flash('message', 'Przywrocono dziedziczenie z domyslnych wariantow.');
        }
    }

    /**
     * Update shop variant override
     */
    public function updateShopVariantOverride(int $shopId, int $variantId, array $updates): void
    {
        if (!isset($this->shopVariantOverrides[$shopId][$variantId])) {
            Log::warning('[VARIANT SHOP] Cannot update - override not found', [
                'shop_id' => $shopId,
                'variant_id' => $variantId,
            ]);
            return;
        }

        $override = $this->shopVariantOverrides[$shopId][$variantId];
        $this->shopVariantOverrides[$shopId][$variantId] = $override->withUpdates($updates);
        $this->hasUnsavedChanges = true;

        Log::info('[VARIANT SHOP] Updated shop variant override', [
            'shop_id' => $shopId,
            'variant_id' => $variantId,
            'updates' => array_keys($updates),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | PRESTASHOP VARIANT ACTIONS (ps_xxx format)
    |--------------------------------------------------------------------------
    | Methods for handling PrestaShop-only variants in shop context.
    | PS variants have string IDs like 'ps_123' from PrestaShop API.
    | These methods allow edit/delete actions that create pending operations.
    */

    /**
     * Pending PS variant deletions (marked for delete on save)
     * Format: ['ps_123' => true, 'ps_456' => true, ...]
     * @var array
     */
    public array $pendingPsVariantDeletes = [];

    /**
     * Mark PrestaShop variant for pending deletion
     *
     * @param string $psVariantId PS variant ID (format: 'ps_123')
     */
    public function markPsVariantForDelete(string $psVariantId): void
    {
        if (!str_starts_with($psVariantId, 'ps_')) {
            Log::warning('[PS VARIANT] Invalid PS variant ID format', ['id' => $psVariantId]);
            return;
        }

        $this->pendingPsVariantDeletes[$psVariantId] = true;
        $this->hasUnsavedChanges = true;

        Log::info('[PS VARIANT] Marked for pending deletion', [
            'ps_variant_id' => $psVariantId,
            'shop_id' => $this->activeShopId,
        ]);

        session()->flash('message', 'Wariant oznaczony do usuniecia (zapisz zmiany aby potwierdzic).');
    }

    /**
     * Undo pending deletion of PrestaShop variant
     *
     * @param string $psVariantId PS variant ID (format: 'ps_123')
     */
    public function undoPsVariantDelete(string $psVariantId): void
    {
        if (isset($this->pendingPsVariantDeletes[$psVariantId])) {
            unset($this->pendingPsVariantDeletes[$psVariantId]);
            $this->hasUnsavedChanges = count($this->pendingPsVariantDeletes) > 0;

            Log::info('[PS VARIANT] Undo pending deletion', [
                'ps_variant_id' => $psVariantId,
                'shop_id' => $this->activeShopId,
            ]);

            session()->flash('message', 'Cofnieto oznaczenie do usuniecia.');
        }
    }

    /**
     * Undo pending update of PrestaShop variant
     * FIX 2026-02-11: Removes pending changes, restoring original PS data
     *
     * @param string $psVariantId PS variant ID (format: 'ps_123')
     */
    public function undoPsVariantUpdate(string $psVariantId): void
    {
        if (isset($this->pendingPsVariantUpdates[$psVariantId])) {
            unset($this->pendingPsVariantUpdates[$psVariantId]);

            Log::info('[PS VARIANT] Undo pending update', [
                'ps_variant_id' => $psVariantId,
                'shop_id' => $this->activeShopId,
                'remaining_pending' => count($this->pendingPsVariantUpdates),
            ]);

            session()->flash('message', 'Cofnieto oczekujace zmiany wariantu.');
        }
    }

    /**
     * Load PrestaShop variant for editing
     * Creates a shop override from PS variant data
     *
     * @param string $psVariantId PS variant ID (format: 'ps_123')
     */
    public function loadPsVariantForEdit(string $psVariantId): void
    {
        if (!str_starts_with($psVariantId, 'ps_')) {
            Log::warning('[PS VARIANT] Invalid PS variant ID format', ['id' => $psVariantId]);
            return;
        }

        // FIX 2025-12-09 BUG#5: Check for pending changes FIRST
        // If user has made local changes (not yet synced), show those instead of PS data
        $pendingData = $this->getPsVariantPendingData($psVariantId);
        if ($pendingData) {
            Log::info('[PS VARIANT] Loading from PENDING data (not synced yet)', [
                'ps_variant_id' => $psVariantId,
                'has_media_ids' => isset($pendingData['media_ids']),
                'has_attributes' => isset($pendingData['attributes']),
            ]);

            // Load pending data into edit form
            $this->variantData = [
                'sku' => $pendingData['sku'] ?? '',
                'name' => $pendingData['name'] ?? '',
                'is_active' => $pendingData['is_active'] ?? true,
                'is_default' => $pendingData['is_default'] ?? false,
                'position' => $pendingData['position'] ?? 0,
                'media_ids' => $pendingData['media_ids'] ?? [],
                'ps_image_ids' => $pendingData['ps_image_ids'] ?? [],
            ];

            $this->editingVariantId = null;
            $this->editingPsVariantId = $psVariantId;
            $this->variantAttributes = $pendingData['attributes'] ?? [];
            $this->initSelectedAttributeTypes();

            Log::info('[PS VARIANT] Loaded PENDING for edit', [
                'ps_variant_id' => $psVariantId,
                'sku' => $this->variantData['sku'],
                'attributes_loaded' => count($this->variantAttributes),
                'media_ids_loaded' => count($this->variantData['media_ids']),
                'ps_image_ids_loaded' => count($this->variantData['ps_image_ids']),
            ]);

            // FIX 2025-12-09 BUG #6: Use correct variable name (showEditModal not showVariantModal)
            $this->showEditModal = true;
            return;
        }

        // No pending data - load from PrestaShop API
        // Extract numeric PrestaShop combination ID from ps_xxx format
        $psCombinationId = (int) str_replace('ps_', '', $psVariantId);

        // Find PS variant in prestaShopVariants collection
        // FIX 2025-12-09: Search by prestashop_combination_id for linked OVERRIDE variants
        // These have numeric ID (local variant) but we need to find by PS combination ID
        $psVariants = $this->prestaShopVariants['variants'] ?? collect();

        // Try by ID first (for ps_xxx variants)
        $psVariant = $psVariants->firstWhere('id', $psVariantId);

        // If not found, try by prestashop_combination_id (for linked OVERRIDE variants)
        if (!$psVariant) {
            $psVariant = $psVariants->first(function ($v) use ($psCombinationId) {
                return ($v->prestashop_combination_id ?? 0) === $psCombinationId;
            });
        }

        if (!$psVariant) {
            Log::warning('[PS VARIANT] Variant not found', [
                'id' => $psVariantId,
                'ps_combination_id' => $psCombinationId,
                'available_ids' => $psVariants->pluck('id')->toArray(),
            ]);
            session()->flash('error', 'Nie znaleziono wariantu PrestaShop.');
            return;
        }

        // FIX 2026-02-11: Load PS variant images directly by prestashop_image_id
        // PS variant images are in $psVariant->images array with format:
        // [{prestashop_image_id: int, url: string, thumbnail_url: string}]
        $psImageIds = [];
        $mediaIds = [];

        if (isset($psVariant->images) && is_array($psVariant->images)) {
            // Direct PS image IDs (for PS product image picker)
            $psImageIds = array_filter(array_column($psVariant->images, 'prestashop_image_id'));

            // Also try to map to PPM Media IDs (legacy compatibility)
            if (!empty($psImageIds)) {
                $shopId = $this->activeShopId;
                $storeKey = "store_{$shopId}";
                $productMedia = $this->product->media ?? collect();

                foreach ($productMedia as $media) {
                    $mapping = $media->prestashop_mapping ?? [];
                    $shopMapping = $mapping[$storeKey] ?? $mapping[$shopId] ?? $mapping["shop_{$shopId}"] ?? null;

                    if ($shopMapping) {
                        $psImageId = (int) ($shopMapping['ps_image_id'] ?? $shopMapping['image_id'] ?? 0);
                        if ($psImageId && in_array($psImageId, $psImageIds)) {
                            $mediaIds[] = $media->id;
                        }
                    }
                }
            }
        }

        // FIX 2026-02-13: Reorder ps_image_ids so the variant's actual cover is first.
        // PrestaShop API returns images sorted by id_image (ascending), so the parent's
        // cover (lower ID) appears first. We resolve the variant's actual cover from
        // VariantImage.is_cover and move its PS image ID to position 0.
        if (!empty($psImageIds) && $this->product) {
            $coverPsImageId = $this->resolveVariantCoverPsImageId(
                $psVariant->sku ?? '',
                $this->activeShopId
            );

            if ($coverPsImageId && in_array($coverPsImageId, $psImageIds)) {
                $psImageIds = array_values(array_diff($psImageIds, [$coverPsImageId]));
                array_unshift($psImageIds, $coverPsImageId);
            }
        }

        // Load PS variant data into edit form
        $this->variantData = [
            'sku' => $psVariant->sku ?? '',
            'name' => $psVariant->name ?? '',
            'is_active' => $psVariant->is_active ?? true,
            'is_default' => $psVariant->is_default ?? false,
            'position' => $psVariant->position ?? 0,
            'media_ids' => $mediaIds,
            'ps_image_ids' => array_values(array_map('intval', $psImageIds)),
        ];

        // Store PS variant ID for reference when saving
        $this->editingVariantId = null; // Not a PPM variant
        $this->editingPsVariantId = $psVariantId;

        // FIX 2025-12-09 BUG #3: Load attributes from PS variant
        // PS returns attributes as: [{prestashop_attribute_id, name, group_name, color}]
        // We need to map to local format: [typeId => valueId]
        $this->variantAttributes = [];
        $psAttributes = $psVariant->attributes ?? [];

        if (!empty($psAttributes) && is_array($psAttributes)) {
            foreach ($psAttributes as $attr) {
                $attrName = $attr['name'] ?? '';
                $groupName = $attr['group_name'] ?? '';

                if (empty($attrName) || empty($groupName)) {
                    continue;
                }

                // Find local AttributeType by name (e.g., "Kolor", "Rozmiar")
                $attributeType = \App\Models\AttributeType::where('name', $groupName)->first();
                if (!$attributeType) {
                    Log::debug('[PS VARIANT] AttributeType not found by name', ['group_name' => $groupName]);
                    continue;
                }

                // Find local AttributeValue by label (e.g., "Czerwony", "M")
                $attributeValue = \App\Models\AttributeValue::where('attribute_type_id', $attributeType->id)
                    ->where('label', $attrName)
                    ->first();

                if ($attributeValue) {
                    $this->variantAttributes[$attributeType->id] = $attributeValue->id;
                    Log::debug('[PS VARIANT] Mapped attribute', [
                        'group' => $groupName,
                        'value' => $attrName,
                        'type_id' => $attributeType->id,
                        'value_id' => $attributeValue->id,
                    ]);
                } else {
                    Log::debug('[PS VARIANT] AttributeValue not found', [
                        'group_name' => $groupName,
                        'attr_name' => $attrName,
                        'type_id' => $attributeType->id,
                    ]);
                }
            }
        }

        $this->initSelectedAttributeTypes();

        Log::info('[PS VARIANT] Loaded for edit', [
            'ps_variant_id' => $psVariantId,
            'sku' => $this->variantData['sku'],
            'shop_id' => $this->activeShopId,
            'attributes_loaded' => count($this->variantAttributes),
            'media_ids_loaded' => count($mediaIds),
        ]);

        $this->showEditModal = true;
    }

    /**
     * Save edited PrestaShop variant as pending update
     * Creates a shop override that will be synced to PrestaShop on save
     */
    public function savePsVariantEdit(): void
    {
        if (empty($this->editingPsVariantId)) {
            Log::warning('[PS VARIANT] No PS variant being edited');
            return;
        }

        $psVariantId = $this->editingPsVariantId;

        // Store pending update (will be synced to PrestaShop on save)
        if (!isset($this->pendingPsVariantUpdates)) {
            $this->pendingPsVariantUpdates = [];
        }

        // FIX 2026-02-11: Include both media_ids and ps_image_ids
        $this->pendingPsVariantUpdates[$psVariantId] = [
            'sku' => $this->variantData['sku'],
            'name' => $this->variantData['name'],
            'is_active' => $this->variantData['is_active'],
            'is_default' => $this->variantData['is_default'],
            'position' => $this->variantData['position'],
            'attributes' => $this->variantAttributes ?? [],
            'media_ids' => $this->variantData['media_ids'] ?? [],
            'ps_image_ids' => $this->variantData['ps_image_ids'] ?? [],
        ];

        $this->hasUnsavedChanges = true;

        Log::info('[PS VARIANT] Saved pending update', [
            'ps_variant_id' => $psVariantId,
            'sku' => $this->variantData['sku'],
            'shop_id' => $this->activeShopId,
            'media_ids_count' => count($this->variantData['media_ids'] ?? []),
        ]);

        $this->showEditModal = false;
        $this->editingPsVariantId = null;
        $this->resetVariantData();

        session()->flash('message', 'Zmiany wariantu zapisane (zapisz formularz aby wyslac do PrestaShop).');
    }

    /**
     * Toggle PrestaShop image selection for variant (shop context)
     * FIX 2026-02-11: Direct PS image ID toggle, bypasses PPM Media mapping
     *
     * Called from blade via wire:click="togglePsVariantImage(psImageId)"
     */
    public function togglePsVariantImage(int $psImageId): void
    {
        $psImageIds = $this->variantData['ps_image_ids'] ?? [];

        if (in_array($psImageId, $psImageIds)) {
            $this->variantData['ps_image_ids'] = array_values(array_diff($psImageIds, [$psImageId]));
        } else {
            $this->variantData['ps_image_ids'][] = $psImageId;
        }
    }

    /**
     * Check if PS variant is marked for pending deletion
     */
    public function isPsVariantPendingDelete(string $psVariantId): bool
    {
        return isset($this->pendingPsVariantDeletes[$psVariantId]);
    }

    /**
     * Check if PS variant has pending updates
     */
    public function isPsVariantPendingUpdate(string $psVariantId): bool
    {
        return isset($this->pendingPsVariantUpdates[$psVariantId]);
    }

    /**
     * Get pending data for PS variant (for display in list)
     * FIX 2025-12-09: BUG #2 - Show pending changes in variant list
     */
    public function getPsVariantPendingData(string $psVariantId): ?array
    {
        return $this->pendingPsVariantUpdates[$psVariantId] ?? null;
    }

    /**
     * Convert pending attributes to displayable format
     * FIX 2025-12-09: BUG #2 - Show pending attribute changes in variant list
     * @param array $pendingAttributes [attrTypeId => valueId]
     * @return array Array of displayable attribute objects
     */
    public function convertPendingAttributesToDisplay(array $pendingAttributes): array
    {
        $result = [];

        foreach ($pendingAttributes as $typeId => $valueId) {
            if (empty($valueId)) continue;

            $type = \App\Models\AttributeType::find($typeId);
            $value = \App\Models\AttributeValue::find($valueId);

            if ($type && $value) {
                $result[] = (object)[
                    'type_name' => $type->name,
                    'value_label' => $value->label,
                    'display_type' => $type->display_type,
                    'color_hex' => $type->display_type === 'color' ? $value->color_hex : null,
                    'is_pending' => true, // Mark as pending for visual distinction
                ];
            }
        }

        return $result;
    }

    /** @var string|null Currently editing PS variant ID */
    public ?string $editingPsVariantId = null;

    /** @var array Pending PS variant updates: [psId => updateData] */
    public array $pendingPsVariantUpdates = [];

    /*
    |--------------------------------------------------------------------------
    | PERSISTENCE
    |--------------------------------------------------------------------------
    */

    /**
     * Save shop variant overrides to database
     * Called from ProductForm::save()
     */
    public function saveShopVariantOverridesToDb(int $shopId): bool
    {
        if (!$this->product || !$this->product->id) {
            Log::warning('[VARIANT SHOP] Cannot save - no product');
            return false;
        }

        try {
            $shopData = $this->product->shopData()
                ->where('shop_id', $shopId)
                ->first();

            if (!$shopData) {
                Log::warning('[VARIANT SHOP] No shop data found', [
                    'product_id' => $this->product->id,
                    'shop_id' => $shopId,
                ]);
                return false;
            }

            $attributeMappings = $shopData->attribute_mappings ?? [];

            // Build variant mappings
            $variantMappings = [];
            $overrides = $this->shopVariantOverrides[$shopId] ?? [];

            foreach ($overrides as $variantId => $override) {
                $key = "variant-{$variantId}";
                $variantMappings[$key] = $override->toArray();
            }

            // Update attribute_mappings.variants section
            $attributeMappings['variants'] = [
                'mapping' => $variantMappings,
                'metadata' => [
                    'last_updated' => now()->toIso8601String(),
                    'source' => 'manual',
                    'version' => '1.0',
                ],
            ];

            $shopData->update([
                'attribute_mappings' => $attributeMappings,
            ]);

            Log::info('[VARIANT SHOP] Saved shop variant overrides', [
                'product_id' => $this->product->id,
                'shop_id' => $shopId,
                'override_count' => count($variantMappings),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('[VARIANT SHOP] Save failed', [
                'product_id' => $this->product->id,
                'shop_id' => $shopId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | PS VARIANT UPDATES COMMIT - FIX 2026-02-11
    |--------------------------------------------------------------------------
    */

    /**
     * Commit pending PrestaShop variant updates to ShopVariant records
     *
     * FIX 2026-02-11: pendingPsVariantUpdates were collected by savePsVariantEdit()
     * but NEVER committed to database. This method creates ShopVariant records
     * with operation_type=OVERRIDE so SyncShopVariantsToPrestaShopJob can process them.
     */
    public function commitPsVariantUpdates(): array
    {
        $stats = ['updated' => 0, 'errors' => []];

        if (empty($this->pendingPsVariantUpdates) || !$this->product || !$this->activeShopId) {
            return $stats;
        }

        Log::info('[PS VARIANT COMMIT] Starting commit of pending PS variant updates', [
            'product_id' => $this->product->id,
            'shop_id' => $this->activeShopId,
            'pending_count' => count($this->pendingPsVariantUpdates),
        ]);

        foreach ($this->pendingPsVariantUpdates as $psVariantId => $data) {
            try {
                // Extract numeric PS combination ID from ps_variant_id (format: "ps_123" or just "123")
                $psCombinationId = is_numeric($psVariantId)
                    ? (int) $psVariantId
                    : (int) str_replace('ps_', '', (string) $psVariantId);

                if ($psCombinationId <= 0) {
                    $stats['errors'][] = "Invalid PS variant ID: {$psVariantId}";
                    continue;
                }

                // Find existing ShopVariant by prestashop_combination_id
                $existingShopVariant = \App\Models\ShopVariant::where('shop_id', $this->activeShopId)
                    ->where('product_id', $this->product->id)
                    ->where('prestashop_combination_id', $psCombinationId)
                    ->first();

                // FIX 2026-02-11: Use ps_image_ids directly if available (shop context)
                // Fallback to media_ids resolution (PPM context)
                $psImageIds = $data['ps_image_ids'] ?? [];
                $mediaIds = $data['media_ids'] ?? [];

                if (!empty($psImageIds)) {
                    $images = array_map(fn($id) => ['prestashop_image_id' => (int) $id], $psImageIds);
                } else {
                    $images = $this->resolveMediaIdsToPrestaShopImages($mediaIds, $this->activeShopId);
                }

                // Build variant_data with images instead of media_ids
                $variantData = [
                    'sku' => $data['sku'] ?? null,
                    'name' => $data['name'] ?? null,
                    'is_active' => $data['is_active'] ?? true,
                    'is_default' => $data['is_default'] ?? false,
                    'position' => $data['position'] ?? 0,
                    'attributes' => $data['attributes'] ?? [],
                    'images' => $images,
                    'media_ids' => $mediaIds, // Keep for reference
                ];

                if ($existingShopVariant) {
                    $existingShopVariant->update([
                        'operation_type' => 'OVERRIDE',
                        'variant_data' => $variantData,
                        'sync_status' => 'pending',
                    ]);
                } else {
                    \App\Models\ShopVariant::create([
                        'shop_id' => $this->activeShopId,
                        'product_id' => $this->product->id,
                        'variant_id' => null,
                        'prestashop_combination_id' => $psCombinationId,
                        'operation_type' => 'OVERRIDE',
                        'variant_data' => $variantData,
                        'sync_status' => 'pending',
                    ]);
                }

                $stats['updated']++;

                Log::info('[PS VARIANT COMMIT] Committed PS variant update', [
                    'ps_variant_id' => $psVariantId,
                    'ps_combination_id' => $psCombinationId,
                    'images_resolved' => count($images),
                    'media_ids_count' => count($mediaIds),
                ]);
            } catch (\Exception $e) {
                $stats['errors'][] = "PS variant {$psVariantId}: {$e->getMessage()}";
                Log::error('[PS VARIANT COMMIT] Failed', [
                    'ps_variant_id' => $psVariantId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Clear pending after commit
        $this->pendingPsVariantUpdates = [];

        Log::info('[PS VARIANT COMMIT] Completed', $stats);

        return $stats;
    }

    /**
     * Resolve PPM media IDs to PrestaShop image IDs for variant sync
     *
     * FIX 2026-02-11: Converts media_ids (PPM Media.id) to images array
     * with prestashop_image_id using Media.prestashop_mapping[store_{shopId}]
     *
     * @param array $mediaIds PPM Media IDs
     * @param int $shopId PrestaShop shop ID
     * @return array Format: [['prestashop_image_id' => int], ...]
     */
    public function resolveMediaIdsToPrestaShopImages(array $mediaIds, int $shopId): array
    {
        if (empty($mediaIds)) {
            return [];
        }

        $images = [];
        $storeKey = "store_{$shopId}";

        $mediaRecords = Media::whereIn('id', $mediaIds)->get();

        foreach ($mediaRecords as $media) {
            $mapping = $media->prestashop_mapping ?? [];
            // Try multiple key formats for compatibility
            $shopMapping = $mapping[$storeKey] ?? $mapping[$shopId] ?? $mapping["shop_{$shopId}"] ?? null;

            if ($shopMapping) {
                $psImageId = (int) ($shopMapping['ps_image_id'] ?? $shopMapping['image_id'] ?? 0);
                if ($psImageId > 0) {
                    $images[] = ['prestashop_image_id' => $psImageId];
                }
            }
        }

        Log::debug('[MEDIA RESOLVE] Resolved media_ids to PS images', [
            'shop_id' => $shopId,
            'media_ids_input' => $mediaIds,
            'images_resolved' => count($images),
            'images' => $images,
        ]);

        return $images;
    }

    /*
    |--------------------------------------------------------------------------
    | DISPLAY HELPERS
    |--------------------------------------------------------------------------
    */

    /**
     * Get all variants for display in current context
     * Merges default variants + shop overrides with inheritance status
     */
    public function getAllVariantsForShopContext(): Collection
    {
        $result = collect();

        // Default context: show default variants only
        if ($this->activeShopId === null) {
            foreach ($this->defaultVariantsSnapshot as $variantId => $data) {
                $variant = (object) array_merge($data, [
                    'status' => 'default',
                    'is_inherited' => false,
                    'is_override' => false,
                    'status_indicator' => $this->getVariantShopStatusIndicator($variantId),
                    'row_classes' => $this->getVariantShopRowClasses($variantId),
                ]);
                $result->push($variant);
            }
            return $result->sortBy('position');
        }

        // Shop context: merge default + overrides
        $shopOverrides = $this->shopVariantOverrides[$this->activeShopId] ?? [];

        foreach ($this->defaultVariantsSnapshot as $variantId => $defaultData) {
            if (isset($shopOverrides[$variantId])) {
                // Shop has override
                $override = $shopOverrides[$variantId];

                $variant = (object) [
                    'id' => $variantId,
                    'sku' => $override->sku,
                    'name' => $override->name,
                    'is_active' => $override->isActive,
                    'is_default' => $override->isDefault,
                    'attributes' => $override->attributes,
                    'position' => $override->position,
                    'status' => $this->getVariantShopStatus($variantId),
                    'is_inherited' => false,
                    'is_override' => true,
                    'status_indicator' => $this->getVariantShopStatusIndicator($variantId),
                    'row_classes' => $this->getVariantShopRowClasses($variantId),
                ];
            } else {
                // Inherited from default
                if (!$this->showInheritedVariants) {
                    continue;
                }

                $variant = (object) array_merge($defaultData, [
                    'status' => 'inherited',
                    'is_inherited' => true,
                    'is_override' => false,
                    'status_indicator' => $this->getVariantShopStatusIndicator($variantId),
                    'row_classes' => $this->getVariantShopRowClasses($variantId),
                ]);
            }

            $result->push($variant);
        }

        return $result->sortBy('position');
    }

    /**
     * Check if shop has any variant overrides
     */
    public function shopHasVariantOverrides(int $shopId): bool
    {
        return !empty($this->shopVariantOverrides[$shopId] ?? []);
    }

    /**
     * Get count of shop variant overrides
     */
    public function getShopVariantOverrideCount(int $shopId): int
    {
        return count($this->shopVariantOverrides[$shopId] ?? []);
    }

    /**
     * Resolve the PrestaShop image ID that is the cover for a variant.
     *
     * FIX 2026-02-13: Uses VariantImage.is_cover → Media.prestashop_mapping
     * to find the correct PS image ID for the variant's cover.
     * Same logic as SyncShopVariantsToPrestaShopJob::resolveCoverImageId().
     */
    protected function resolveVariantCoverPsImageId(string $variantSku, ?int $shopId): ?int
    {
        if (empty($variantSku) || !$shopId || !$this->product) {
            return null;
        }

        // Find PPM variant by SKU
        $variant = $this->product->variants()->where('sku', $variantSku)->first();
        if (!$variant) {
            return null;
        }

        // Find cover image for this variant
        $coverImage = \App\Models\VariantImage::where('variant_id', $variant->id)
            ->where('is_cover', true)
            ->first();

        if (!$coverImage || empty($coverImage->image_path)) {
            return null;
        }

        // Find Media record matching this image_path
        $media = $this->product->media->first(function ($m) use ($coverImage) {
            return $m->file_path === $coverImage->image_path;
        });

        if (!$media) {
            return null;
        }

        // Get PS image ID from prestashop_mapping
        $mapping = $media->prestashop_mapping ?? [];
        $storeKey = "store_{$shopId}";
        $shopMapping = $mapping[$storeKey] ?? $mapping[$shopId] ?? $mapping["shop_{$shopId}"] ?? null;

        if (!$shopMapping) {
            return null;
        }

        $psImageId = (int) ($shopMapping['ps_image_id'] ?? $shopMapping['image_id'] ?? 0);

        if ($psImageId > 0) {
            Log::debug('[PS VARIANT] Resolved cover PS image ID', [
                'variant_sku' => $variantSku,
                'shop_id' => $shopId,
                'cover_image_path' => $coverImage->image_path,
                'ps_image_id' => $psImageId,
            ]);
        }

        return $psImageId > 0 ? $psImageId : null;
    }
}

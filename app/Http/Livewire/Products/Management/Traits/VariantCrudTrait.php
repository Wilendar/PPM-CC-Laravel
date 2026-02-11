<?php

namespace App\Http\Livewire\Products\Management\Traits;

use App\Models\ProductVariant;
use App\Models\VariantAttribute;
use App\Models\VariantImage;
use App\Models\PriceGroup;
use App\Models\Warehouse;
use App\Models\PrestaShopShop;
use App\Services\Product\VariantSkuGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * VariantCrudTrait - CRUD Operations for Product Variants
 *
 * Handles: Create, Update, Delete, Duplicate, Set Default, Generate SKU
 *
 * EXTRACTED FROM: ProductFormVariants.php (1369 lines -> 6 traits)
 * LINE COUNT TARGET: < 300 lines (CLAUDE.md compliance)
 *
 * DEPENDENCIES:
 * - VariantValidation trait (validateVariantCreate, validateVariantUpdate)
 * - Product model ($this->product)
 * - variantPrices, variantStock arrays (initialized here, used by other traits)
 *
 * @package App\Http\Livewire\Products\Management\Traits
 * @version 2.0 (Refactored)
 * @since ETAP_05b FAZA 1
 */
trait VariantCrudTrait
{
    /*
    |--------------------------------------------------------------------------
    | PROPERTIES
    |--------------------------------------------------------------------------
    */

    /** @var array Variant form data */
    public array $variantData = [
        'sku' => '',
        'name' => '',
        'is_active' => true,
        'is_default' => false,
        'position' => 0,
        'media_ids' => [], // Selected product media IDs for variant images (multiple)
        'ps_image_ids' => [], // FIX 2026-02-11: Selected PrestaShop image IDs (shop context)
        'auto_generate_sku' => true, // ETAP_05f: Auto-generate SKU from attributes
    ];

    /** @var int|null Currently editing variant ID */
    public ?int $editingVariantId = null;

    /** @var bool Show create modal flag */
    public bool $showCreateModal = false;

    /** @var bool Show edit modal flag */
    public bool $showEditModal = false;

    /** @var array Selected variant IDs for bulk actions */
    public array $selectedVariants = [];

    /** @var bool Select all checkbox state */
    public bool $selectAll = false;

    /** @var bool Shop variants sync in progress (blocks UI) */
    public bool $shopVariantsSyncing = false;

    /** @var array Shop-specific variant overrides: [shopId => [variantId => overrideData]] */
    public array $shopVariantOverrides = [];

    /** @var string Variant price display mode in list: 'gross' (default) or 'net' */
    public string $variantPriceDisplayMode = 'gross';

    /*
    |--------------------------------------------------------------------------
    | PENDING VARIANTS SYSTEM (2025-12-04)
    |--------------------------------------------------------------------------
    | Variants are NOT saved immediately - only on Save button click!
    | This matches the pendingChanges pattern used by ProductForm.
    */

    /**
     * Pending variant creations (not yet in database)
     * Each entry has a negative tempId for UI tracking
     * Format: [tempId => ['sku', 'name', 'is_active', 'is_default', 'position', 'attributes' => [], 'media_ids' => []]]
     * @var array
     */
    public array $pendingVariantCreates = [];

    /**
     * Pending variant updates (existing variants with uncommitted changes)
     * Format: [variantId => ['sku', 'name', 'is_active', 'is_default', 'position', 'attributes' => []]]
     * @var array
     */
    public array $pendingVariantUpdates = [];

    /**
     * Pending variant deletions (marked for delete on save)
     * Format: [variantId, variantId, ...]
     * @var array
     */
    public array $pendingVariantDeletes = [];

    /**
     * Counter for generating unique negative temp IDs for pending variants
     * @var int
     */
    private int $pendingVariantTempIdCounter = -1;

    /*
    |--------------------------------------------------------------------------
    | PENDING VARIANTS - SESSION PERSISTENCE (FIX FOR LIVEWIRE STATE RESET)
    |--------------------------------------------------------------------------
    | Livewire 3.x resets public properties between requests.
    | We use session storage to persist pending variant data.
    */

    /**
     * Get session key for pending variants storage
     */
    protected function getPendingVariantsSessionKey(): string
    {
        $productId = $this->product->id ?? 'new';
        return "pending_variants_{$productId}";
    }

    /**
     * Save pending variants to session (called after each change)
     * FIX 2025-12-04: Added save() to force session write immediately
     */
    protected function savePendingVariantsToSession(): void
    {
        $key = $this->getPendingVariantsSessionKey();
        session()->put($key, [
            'creates' => $this->pendingVariantCreates,
            'updates' => $this->pendingVariantUpdates,
            'deletes' => $this->pendingVariantDeletes,
            'tempIdCounter' => $this->pendingVariantTempIdCounter,
        ]);

        // FIX 2025-12-04: Force session save to prevent data loss between rapid requests
        session()->save();

        Log::debug('[VARIANT SESSION] Saved to session', [
            'key' => $key,
            'creates_count' => count($this->pendingVariantCreates),
            'updates_count' => count($this->pendingVariantUpdates),
            'deletes_count' => count($this->pendingVariantDeletes),
            'tempIdCounter' => $this->pendingVariantTempIdCounter,
            'creates_keys' => array_keys($this->pendingVariantCreates),
        ]);
    }

    /**
     * Restore pending variants from session (called on hydrate)
     * FIX 2025-12-04: Enhanced logging for debugging multi-variant issue
     */
    protected function restorePendingVariantsFromSession(): void
    {
        $key = $this->getPendingVariantsSessionKey();
        $data = session()->get($key, []);

        if (!empty($data)) {
            // FIX 2025-12-04: Merge with existing data instead of overwrite
            // This prevents data loss if arrays were already populated
            $this->pendingVariantCreates = array_replace(
                $this->pendingVariantCreates,
                $data['creates'] ?? []
            );
            $this->pendingVariantUpdates = array_replace(
                $this->pendingVariantUpdates,
                $data['updates'] ?? []
            );
            $this->pendingVariantDeletes = array_unique(array_merge(
                $this->pendingVariantDeletes,
                $data['deletes'] ?? []
            ));
            $this->pendingVariantTempIdCounter = min(
                $this->pendingVariantTempIdCounter,
                $data['tempIdCounter'] ?? -1
            );

            Log::debug('[VARIANT SESSION] Restored from session', [
                'key' => $key,
                'creates_count' => count($this->pendingVariantCreates),
                'updates_count' => count($this->pendingVariantUpdates),
                'deletes_count' => count($this->pendingVariantDeletes),
                'tempIdCounter' => $this->pendingVariantTempIdCounter,
                'creates_keys' => array_keys($this->pendingVariantCreates),
            ]);
        }
    }

    /**
     * Clear pending variants from session (called after commit)
     */
    protected function clearPendingVariantsSession(): void
    {
        $key = $this->getPendingVariantsSessionKey();
        session()->forget($key);

        Log::debug('[VARIANT SESSION] Cleared session', ['key' => $key]);
    }

    /**
     * Livewire hydrate hook - restore pending variants from session
     * Called BEFORE each Livewire request is processed
     */
    public function hydrateVariantCrudTrait(): void
    {
        $this->restorePendingVariantsFromSession();
    }

    /*
    |--------------------------------------------------------------------------
    | BULK ACTION METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Handle "Select All" checkbox change
     * Livewire lifecycle hook: runs when $selectAll changes
     *
     * FIX 2025-12-08: Use getAllVariantsForDisplay() instead of product->variants
     * to properly handle shop context (PrestaShop API variants)
     */
    public function updatedSelectAll(bool $value): void
    {
        if ($value) {
            // FIX: Get variants from display method (handles both default and shop context)
            $variants = $this->getAllVariantsForDisplay();

            // Filter: only select variants with positive ID (not pending creates)
            // and not marked for deletion (pendingDelete)
            $this->selectedVariants = $variants
                ->filter(function ($variant) {
                    $id = is_object($variant) ? ($variant->id ?? 0) : 0;
                    $isPendingDelete = is_object($variant) ? ($variant->pendingDelete ?? false) : false;
                    return $id > 0 && !$isPendingDelete;
                })
                ->pluck('id')
                ->map(fn($id) => (string) $id)
                ->toArray();
        } else {
            // Deselect all
            $this->selectedVariants = [];
        }
    }

    /**
     * Activate selected variants
     */
    public function activateSelected(): void
    {
        if (empty($this->selectedVariants)) {
            return;
        }

        try {
            ProductVariant::whereIn('id', $this->selectedVariants)
                ->update(['is_active' => true]);

            Log::info('Bulk activate variants', [
                'product_id' => $this->product->id,
                'variant_ids' => $this->selectedVariants,
            ]);

            $this->product->load('variants');
            $this->selectedVariants = [];
            $this->selectAll = false;
            session()->flash('message', 'Wybrane warianty zostaly aktywowane.');
        } catch (\Exception $e) {
            Log::error('Bulk activate failed', ['error' => $e->getMessage()]);
            session()->flash('error', 'Blad podczas aktywacji wariantow.');
        }
    }

    /**
     * Deactivate selected variants
     */
    public function deactivateSelected(): void
    {
        if (empty($this->selectedVariants)) {
            return;
        }

        try {
            ProductVariant::whereIn('id', $this->selectedVariants)
                ->update(['is_active' => false]);

            Log::info('Bulk deactivate variants', [
                'product_id' => $this->product->id,
                'variant_ids' => $this->selectedVariants,
            ]);

            $this->product->load('variants');
            $this->selectedVariants = [];
            $this->selectAll = false;
            session()->flash('message', 'Wybrane warianty zostaly dezaktywowane.');
        } catch (\Exception $e) {
            Log::error('Bulk deactivate failed', ['error' => $e->getMessage()]);
            session()->flash('error', 'Blad podczas dezaktywacji wariantow.');
        }
    }

    /**
     * Delete selected variants
     */
    public function deleteSelected(): void
    {
        if (empty($this->selectedVariants)) {
            return;
        }

        try {
            DB::transaction(function () {
                ProductVariant::whereIn('id', $this->selectedVariants)->delete();

                // Handle default variant removal
                if (in_array($this->product->default_variant_id, $this->selectedVariants)) {
                    $newDefault = $this->product->variants()
                        ->whereNotIn('id', $this->selectedVariants)
                        ->where('is_active', true)
                        ->orderBy('position')
                        ->first();
                    $this->product->update(['default_variant_id' => $newDefault?->id]);
                }

                // Clear is_variant_master if no more variants
                if ($this->product->variants()->count() === 0) {
                    $this->product->update(['is_variant_master' => false]);
                }
            });

            Log::info('Bulk delete variants', [
                'product_id' => $this->product->id,
                'variant_ids' => $this->selectedVariants,
            ]);

            $this->product->load('variants');
            $this->selectedVariants = [];
            $this->selectAll = false;
            session()->flash('message', 'Wybrane warianty zostaly usuniete.');
        } catch (\Exception $e) {
            Log::error('Bulk delete failed', ['error' => $e->getMessage()]);
            session()->flash('error', 'Blad podczas usuwania wariantow.');
        }
    }

    /**
     * Copy prices from parent product to selected variants
     */
    public function copyPricesFromParent(): void
    {
        if (empty($this->selectedVariants)) {
            return;
        }

        try {
            // Get parent product prices
            $productPrices = $this->product->prices ?? collect([]);

            DB::transaction(function () use ($productPrices) {
                foreach ($this->selectedVariants as $variantId) {
                    $variant = ProductVariant::find($variantId);
                    if (!$variant) continue;

                    foreach ($productPrices as $productPrice) {
                        $variant->prices()->updateOrCreate(
                            ['price_group_id' => $productPrice->price_group_id],
                            [
                                'price' => $productPrice->price,
                                'price_special' => $productPrice->price_special,
                            ]
                        );
                    }
                }
            });

            Log::info('Bulk copy prices to variants', [
                'product_id' => $this->product->id,
                'variant_ids' => $this->selectedVariants,
            ]);

            $this->product->load('variants');
            $this->selectedVariants = [];
            $this->selectAll = false;
            session()->flash('message', 'Ceny zostaly skopiowane do wybranych wariantow.');
        } catch (\Exception $e) {
            Log::error('Bulk copy prices failed', ['error' => $e->getMessage()]);
            session()->flash('error', 'Blad podczas kopiowania cen.');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | CRUD METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Open modal to create new variant
     *
     * ETAP_05c: In shop context, creates shop-only variant (ADD operation)
     * Shop-only variants are NOT in product_variants, only in shop_variants
     */
    public function openCreateVariantModal(): void
    {
        // In shop context, we're creating a shop-only variant (ADD operation)
        if ($this->activeShopId !== null) {
            Log::info('[VARIANT MODAL] Opening in shop context - will create shop-only variant', [
                'shop_id' => $this->activeShopId,
            ]);
        }

        $this->resetVariantData();
        $this->variantData['sku'] = $this->generateVariantSKU();
        $this->variantData['name'] = $this->product->name . ' - Wariant';
        $this->showCreateModal = true;
    }

    /**
     * Close variant modal
     */
    public function closeVariantModal(): void
    {
        $this->showCreateModal = false;
        $this->showEditModal = false;
        $this->resetVariantData();
    }

    /**
     * Create new variant - adds to pendingVariantCreates (NOT saved to DB until Save button)
     *
     * PENDING VARIANTS SYSTEM: This method stores variant data in memory.
     * The actual DB insert happens in commitPendingVariants() called from save().
     *
     * ETAP_05c: In shop context, creates shop-only variant (ADD operation in shop_variants)
     * Shop-only variants exist ONLY for this shop, not in base product_variants.
     */
    public function createVariant(): void
    {
        Log::debug('[VARIANT CREATE] Method called (PENDING MODE)', [
            'product_id' => $this->product->id ?? null,
            'variantData' => $this->variantData,
            'activeShopId' => $this->activeShopId ?? null,
        ]);

        // ETAP_05c: Shop context - create shop-only variant (ADD operation)
        if ($this->activeShopId !== null) {
            Log::info('[VARIANT CREATE] Shop context - creating shop-only variant (ADD)', [
                'shop_id' => $this->activeShopId,
            ]);
            $this->createShopOnlyVariant();
            return;
        }

        $validatedData = $this->validateVariantCreate($this->variantData);

        Log::debug('[VARIANT CREATE] Validation passed', [
            'validatedData' => $validatedData,
        ]);

        try {
            // Generate unique negative tempId for this pending variant
            $tempId = $this->pendingVariantTempIdCounter--;

            // Auto-generate position based on existing + pending variants count
            if (empty($validatedData['position'])) {
                $existingCount = $this->product ? $this->product->variants()->count() : 0;
                $pendingCount = count($this->pendingVariantCreates);
                $validatedData['position'] = $existingCount + $pendingCount + 1;
            }

            // FIX 2025-12-08: Detect if we're in shop context - create shop-only variant
            $isShopOnly = $this->activeShopId !== null;

            // Store in pending array (NOT in database)
            $this->pendingVariantCreates[$tempId] = [
                'tempId' => $tempId,
                'sku' => $validatedData['sku'],
                'name' => $validatedData['name'],
                'is_active' => $validatedData['is_active'] ?? true,
                'is_default' => $validatedData['is_default'] ?? false,
                'position' => $validatedData['position'],
                'attributes' => $this->variantAttributes ?? [],
                'media_ids' => $this->variantData['media_ids'] ?? [],
                // Shop-only variant flags
                'is_shop_only' => $isShopOnly,
                'shop_id' => $isShopOnly ? $this->activeShopId : null,
            ];

            Log::info('[VARIANT PENDING] Added to pendingVariantCreates', [
                'product_id' => $this->product->id ?? 'new',
                'tempId' => $tempId,
                'sku' => $validatedData['sku'],
                'is_shop_only' => $isShopOnly,
                'shop_id' => $isShopOnly ? $this->activeShopId : null,
                'pending_count' => count($this->pendingVariantCreates),
            ]);

            // Mark form as having unsaved changes
            $this->hasUnsavedChanges = true;

            // Persist to session (FIX: Livewire state reset between requests)
            $this->savePendingVariantsToSession();

            // Close modal
            $this->showCreateModal = false;
            $this->showEditModal = false;
            $this->resetVariantData();

            $this->dispatch('variant-pending-added');
            $flashMessage = $isShopOnly
                ? 'Wariant dla sklepu dodany (zapisz zmiany aby potwierdzic).'
                : 'Wariant dodany (zapisz zmiany aby potwierdzic).';
            session()->flash('message', $flashMessage);
        } catch (\Exception $e) {
            Log::error('Variant pending creation failed', [
                'product_id' => $this->product->id ?? null,
                'error' => $e->getMessage(),
            ]);
            $this->addError('variantData', 'Blad podczas dodawania wariantu: ' . $e->getMessage());
        }
    }

    /**
     * Update existing variant - adds to pendingVariantUpdates (NOT saved to DB until Save button)
     *
     * PENDING VARIANTS SYSTEM: This method stores update data in memory.
     * The actual DB update happens in commitPendingVariants() called from save().
     *
     * SHOP CONTEXT (ETAP_05b FAZA 5): In shop context, updates go to shopVariantOverrides,
     * not to default variant data.
     */
    public function updateVariant(?int $variantId = null): void
    {
        try {
            // FIX: Check if we're editing a PrestaShop variant (ps_xxxxx ID)
            // In that case, redirect to savePsVariantEdit() which handles PS variants properly
            if (!empty($this->editingPsVariantId)) {
                Log::info('[VARIANT UPDATE] Detected PS variant edit, redirecting to savePsVariantEdit', [
                    'ps_variant_id' => $this->editingPsVariantId,
                    'shop_id' => $this->activeShopId,
                ]);
                $this->savePsVariantEdit();
                return;
            }

            $variantId = $variantId ?? $this->editingVariantId;

            if (!$variantId) {
                $this->addError('variantData', 'Nie wybrano wariantu do aktualizacji.');
                return;
            }

            // SHOP CONTEXT CHECK (ETAP_05b FAZA 5)
            // In shop context, redirect to shop override system
            if ($this->activeShopId !== null && $variantId > 0) {
                Log::info('[VARIANT UPDATE] Shop context detected - using shop override system', [
                    'shop_id' => $this->activeShopId,
                    'variant_id' => $variantId,
                ]);

                // Create or update shop override
                $updates = [
                    'sku' => $this->variantData['sku'],
                    'name' => $this->variantData['name'],
                    'is_active' => $this->variantData['is_active'],
                    'is_default' => $this->variantData['is_default'],
                    'attributes' => $this->variantAttributes ?? [],
                    'position' => $this->variantData['position'] ?? 0,
                ];

                // Check if override exists
                if (isset($this->shopVariantOverrides[$this->activeShopId][$variantId])) {
                    // Update existing override
                    $this->updateShopVariantOverride($this->activeShopId, $variantId, $updates);
                } else {
                    // Create new override from default, then apply updates
                    $this->createShopVariantOverride($this->activeShopId, $variantId);
                    $this->updateShopVariantOverride($this->activeShopId, $variantId, $updates);
                }

                $this->hasUnsavedChanges = true;
                $this->showCreateModal = false;
                $this->showEditModal = false;
                $this->resetVariantData();

                $this->dispatch('variant-pending-updated');
                session()->flash('message', 'Wariant sklepu zaktualizowany (zapisz zmiany aby potwierdzic).');
                return;
            }

            // DEFAULT CONTEXT - original logic below

            // Check if this is a pending variant (negative ID) or existing variant
            if ($variantId < 0) {
                // Update pending variant in pendingVariantCreates
                if (isset($this->pendingVariantCreates[$variantId])) {
                    $this->pendingVariantCreates[$variantId] = array_merge(
                        $this->pendingVariantCreates[$variantId],
                        [
                            'sku' => $this->variantData['sku'],
                            'name' => $this->variantData['name'],
                            'is_active' => $this->variantData['is_active'],
                            'is_default' => $this->variantData['is_default'],
                            'position' => $this->variantData['position'],
                            'attributes' => $this->variantAttributes ?? [],
                            'media_ids' => $this->variantData['media_ids'] ?? [],
                        ]
                    );
                    Log::info('[VARIANT PENDING] Updated pending variant', ['tempId' => $variantId]);

                    // Persist to session (FIX: Livewire state reset between requests)
                    $this->savePendingVariantsToSession();
                }
            } else {
                // Validate existing variant
                $variant = ProductVariant::findOrFail($variantId);
                $this->validateVariantUpdate($variantId, $this->variantData);

                // FIX 2025-12-04: Store in pending updates (NOT in database) - include media_ids for image preview
                $this->pendingVariantUpdates[$variantId] = [
                    'sku' => $this->variantData['sku'],
                    'name' => $this->variantData['name'],
                    'is_active' => $this->variantData['is_active'],
                    'is_default' => $this->variantData['is_default'],
                    'position' => $this->variantData['position'],
                    'attributes' => $this->variantAttributes ?? [],
                    'media_ids' => $this->variantData['media_ids'] ?? [], // FIX #1: Include for image preview
                ];

                Log::info('[VARIANT PENDING] Added to pendingVariantUpdates', [
                    'variant_id' => $variantId,
                    'sku' => $this->variantData['sku'],
                    'media_ids_count' => count($this->variantData['media_ids'] ?? []),
                    'pending_updates_count' => count($this->pendingVariantUpdates),
                ]);
            }

            // Mark form as having unsaved changes
            $this->hasUnsavedChanges = true;

            // Persist to session (FIX: Livewire state reset between requests)
            $this->savePendingVariantsToSession();

            // Close modal
            $this->showCreateModal = false;
            $this->showEditModal = false;
            $this->resetVariantData();

            $this->dispatch('variant-pending-updated');
            session()->flash('message', 'Zmiany w wariancie zapisane (zapisz zmiany aby potwierdzic).');
        } catch (\Exception $e) {
            Log::error('Variant pending update failed', [
                'variant_id' => $variantId,
                'error' => $e->getMessage(),
            ]);
            $this->addError('variantData', 'Blad podczas aktualizacji wariantu: ' . $e->getMessage());
        }
    }

    /**
     * Delete variant - marks for deletion (NOT deleted from DB until Save button)
     *
     * PENDING VARIANTS SYSTEM: This method marks variant for deletion.
     * The actual DB delete happens in commitPendingVariants() called from save().
     *
     * SHOP CONTEXT (ETAP_05b FAZA 5): In shop context, "delete" means remove shop override
     * (revert to inherited from default), NOT delete the actual variant.
     */
    public function deleteVariant(int $variantId): void
    {
        try {
            // SHOP CONTEXT CHECK (ETAP_05c FIX)
            // In shop context, "delete" means HIDE this variant for this shop (DELETE operation)
            // This does NOT delete from product_variants - only creates shop_variants entry with DELETE op
            if ($this->activeShopId !== null && $variantId > 0) {
                Log::info('[VARIANT DELETE] Shop context detected - creating DELETE operation', [
                    'shop_id' => $this->activeShopId,
                    'variant_id' => $variantId,
                ]);

                // ETAP_05c FIX: Always create/update shop override with DELETE operation
                // This hides the variant for this shop without affecting product_variants
                if (!isset($this->shopVariantOverrides[$this->activeShopId])) {
                    $this->shopVariantOverrides[$this->activeShopId] = [];
                }

                // Mark as DELETE operation (variant hidden for this shop)
                $this->shopVariantOverrides[$this->activeShopId][$variantId] = [
                    '_deleted' => true,
                    'operation_type' => 'DELETE',
                ];

                $this->hasUnsavedChanges = true;

                $this->dispatch('variant-pending-deleted');
                session()->flash('message', 'Wariant ukryty dla tego sklepu (zapisz zmiany aby potwierdzic).');
                return;
            }

            // DEFAULT CONTEXT - original logic below (affects product_variants)

            // Check if this is a pending variant (negative ID) or existing variant
            if ($variantId < 0) {
                // Remove from pending creates (never saved to DB, so just remove from array)
                if (isset($this->pendingVariantCreates[$variantId])) {
                    $sku = $this->pendingVariantCreates[$variantId]['sku'] ?? 'unknown';
                    unset($this->pendingVariantCreates[$variantId]);
                    Log::info('[VARIANT PENDING] Removed pending variant', [
                        'tempId' => $variantId,
                        'sku' => $sku,
                    ]);
                    session()->flash('message', 'Wariant usuniety.');
                }
            } else {
                // Mark existing variant for deletion (will be deleted on save)
                $variant = ProductVariant::findOrFail($variantId);

                if (!in_array($variantId, $this->pendingVariantDeletes)) {
                    $this->pendingVariantDeletes[] = $variantId;
                }

                // Also remove from pending updates if exists
                unset($this->pendingVariantUpdates[$variantId]);

                Log::info('[VARIANT PENDING] Marked for deletion', [
                    'variant_id' => $variantId,
                    'sku' => $variant->sku,
                    'pending_deletes_count' => count($this->pendingVariantDeletes),
                ]);

                session()->flash('message', 'Wariant oznaczony do usuniecia (zapisz zmiany aby potwierdzic).');
            }

            // Mark form as having unsaved changes
            $this->hasUnsavedChanges = true;

            // Persist to session (FIX: Livewire state reset between requests)
            $this->savePendingVariantsToSession();

            $this->dispatch('variant-pending-deleted');
        } catch (\Exception $e) {
            Log::error('Variant pending deletion failed', [
                'variant_id' => $variantId,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', 'Blad podczas oznaczania wariantu do usuniecia: ' . $e->getMessage());
        }
    }

    /**
     * Undo pending variant deletion (restore from pendingVariantDeletes)
     */
    public function undoDeleteVariant(int $variantId): void
    {
        $index = array_search($variantId, $this->pendingVariantDeletes);
        if ($index !== false) {
            unset($this->pendingVariantDeletes[$index]);
            $this->pendingVariantDeletes = array_values($this->pendingVariantDeletes); // Re-index

            Log::info('[VARIANT PENDING] Undo delete', ['variant_id' => $variantId]);

            // Persist to session
            $this->savePendingVariantsToSession();

            session()->flash('message', 'Anulowano usuniecie wariantu.');
        }
    }

    /**
     * Undo pending variant update (remove from pendingVariantUpdates)
     */
    public function undoUpdateVariant(int $variantId): void
    {
        if (isset($this->pendingVariantUpdates[$variantId])) {
            unset($this->pendingVariantUpdates[$variantId]);

            Log::info('[VARIANT PENDING] Undo update', ['variant_id' => $variantId]);

            // Persist to session
            $this->savePendingVariantsToSession();

            session()->flash('message', 'Anulowano zmiany w wariancie.');
        }
    }

    /**
     * Remove pending variant from create queue (never saved to DB)
     */
    public function removePendingVariant(int $tempId): void
    {
        if (isset($this->pendingVariantCreates[$tempId])) {
            $sku = $this->pendingVariantCreates[$tempId]['sku'] ?? 'unknown';
            unset($this->pendingVariantCreates[$tempId]);

            Log::info('[VARIANT PENDING] Removed from create queue', [
                'tempId' => $tempId,
                'sku' => $sku,
            ]);

            // Persist to session
            $this->savePendingVariantsToSession();

            session()->flash('message', 'Wariant usuniety z kolejki.');
        }
    }

    /**
     * Toggle is_active for pending create variant (negative ID)
     */
    public function togglePendingVariantStatus(int $tempId): void
    {
        if (isset($this->pendingVariantCreates[$tempId])) {
            $this->pendingVariantCreates[$tempId]['is_active'] =
                !$this->pendingVariantCreates[$tempId]['is_active'];

            Log::info('[VARIANT PENDING] Toggled status', [
                'tempId' => $tempId,
                'is_active' => $this->pendingVariantCreates[$tempId]['is_active'],
            ]);

            // Persist to session
            $this->savePendingVariantsToSession();
        }
    }

    /**
     * Load pending variant data for editing (negative ID)
     */
    public function loadPendingVariantForEdit(int $tempId): void
    {
        if (!isset($this->pendingVariantCreates[$tempId])) {
            Log::warning('[VARIANT PENDING] Cannot load - not found', ['tempId' => $tempId]);
            return;
        }

        $data = $this->pendingVariantCreates[$tempId];

        $this->editingVariantId = $tempId;
        $this->variantData = [
            'sku' => $data['sku'],
            'name' => $data['name'],
            'is_active' => $data['is_active'],
            'is_default' => $data['is_default'],
            'position' => $data['position'],
            'media_ids' => $data['media_ids'] ?? [],
        ];

        // Load attributes
        $this->variantAttributes = $data['attributes'] ?? [];
        $this->initSelectedAttributeTypes();

        $this->showEditModal = true;

        Log::info('[VARIANT PENDING] Loaded for edit', ['tempId' => $tempId]);
    }

    /**
     * Duplicate variant with new SKU
     */
    public function duplicateVariant(int $variantId): void
    {
        // SHOP CONTEXT CHECK (ETAP_05b FAZA 5)
        // Shops cannot create NEW variants - duplication creates new variant
        if ($this->activeShopId !== null) {
            Log::warning('[VARIANT DUPLICATE] Blocked in shop context - shops cannot create variants', [
                'shop_id' => $this->activeShopId,
                'variant_id' => $variantId,
            ]);
            session()->flash('error', 'W kontekscie sklepu nie mozna duplikowac wariantow. Przejdz do "Dane domyslne".');
            return;
        }

        try {
            $original = ProductVariant::with(['attributes', 'prices', 'stock'])->findOrFail($variantId);

            DB::transaction(function () use ($original) {
                $newSku = $this->generateVariantSKU($original->sku);

                $duplicate = ProductVariant::create([
                    'product_id' => $this->product->id,
                    'sku' => $newSku,
                    'name' => $original->name . ' (Kopia)',
                    'is_active' => false,
                    'is_default' => false,
                    'position' => $this->product->variants()->max('position') + 1,
                ]);

                // Copy relations
                foreach ($original->attributes as $attr) {
                    $duplicate->attributes()->create([
                        'attribute_type_id' => $attr->attribute_type_id,
                        'attribute_value_id' => $attr->attribute_value_id,
                    ]);
                }

                foreach ($original->prices as $price) {
                    $duplicate->prices()->create([
                        'price_group_id' => $price->price_group_id,
                        'price' => $price->price,
                        'price_special' => $price->price_special,
                    ]);
                }

                foreach ($original->stock as $stock) {
                    $duplicate->stock()->create([
                        'warehouse_id' => $stock->warehouse_id,
                        'quantity' => 0,
                        'reserved' => 0,
                    ]);
                }

                Log::info('Variant duplicated', [
                    'original_id' => $original->id,
                    'duplicate_id' => $duplicate->id,
                ]);
            });

            $this->product->load('variants');
            $this->dispatch('variant-duplicated');
            session()->flash('message', 'Wariant zostal zduplikowany pomyslnie.');
        } catch (\Exception $e) {
            Log::error('Variant duplication failed', ['error' => $e->getMessage()]);
            session()->flash('error', 'Blad podczas duplikowania wariantu: ' . $e->getMessage());
        }
    }

    /**
     * Set variant as default
     */
    public function setDefaultVariant(int $variantId): void
    {
        // SHOP CONTEXT CHECK (ETAP_05b FAZA 5)
        // In shop context, set default is a shop override
        if ($this->activeShopId !== null) {
            Log::info('[VARIANT SET DEFAULT] Shop context - using shop override', [
                'shop_id' => $this->activeShopId,
                'variant_id' => $variantId,
            ]);

            // Create or update shop override with is_default = true
            if (!isset($this->shopVariantOverrides[$this->activeShopId][$variantId])) {
                $this->createShopVariantOverride($this->activeShopId, $variantId);
            }
            $this->updateShopVariantOverride($this->activeShopId, $variantId, ['is_default' => true]);

            // Unset is_default for other variants in this shop
            if (isset($this->shopVariantOverrides[$this->activeShopId])) {
                foreach ($this->shopVariantOverrides[$this->activeShopId] as $otherVariantId => $override) {
                    if ($otherVariantId !== $variantId && ($override['is_default'] ?? false)) {
                        $this->updateShopVariantOverride($this->activeShopId, $otherVariantId, ['is_default' => false]);
                    }
                }
            }

            $this->hasUnsavedChanges = true;
            $this->dispatch('default-variant-changed');
            session()->flash('message', 'Domyslny wariant sklepu ustawiony (zapisz zmiany aby potwierdzic).');
            return;
        }

        try {
            $variant = ProductVariant::findOrFail($variantId);

            DB::transaction(function () use ($variant) {
                $variant->update(['is_default' => true]);
                $this->product->update(['default_variant_id' => $variant->id]);
                $this->product->variants()
                    ->where('id', '!=', $variant->id)
                    ->update(['is_default' => false]);

                Log::info('Default variant changed', [
                    'product_id' => $this->product->id,
                    'variant_id' => $variant->id,
                ]);
            });

            $this->product->load('variants');
            $this->dispatch('default-variant-changed');
            session()->flash('message', 'Wariant domyslny zostal ustawiony.');
        } catch (\Exception $e) {
            Log::error('Set default variant failed', ['error' => $e->getMessage()]);
            session()->flash('error', 'Blad podczas ustawiania wariantu domyslnego.');
        }
    }

    /**
     * Toggle variant active status
     */
    public function toggleVariantStatus(int $variantId): void
    {
        // SHOP CONTEXT CHECK (ETAP_05b FAZA 5)
        // In shop context, toggle status is a shop override
        if ($this->activeShopId !== null) {
            Log::info('[VARIANT TOGGLE STATUS] Shop context - using shop override', [
                'shop_id' => $this->activeShopId,
                'variant_id' => $variantId,
            ]);

            // Get current status (from override or default)
            $currentStatus = true;
            $existingOverride = $this->shopVariantOverrides[$this->activeShopId][$variantId] ?? null;
            if ($existingOverride !== null) {
                // Handle both ShopVariantOverride DTO objects and arrays
                $currentStatus = $existingOverride instanceof \App\DTOs\ShopVariantOverride
                    ? $existingOverride->isActive
                    : ($existingOverride['is_active'] ?? true);
            } else {
                $variant = ProductVariant::find($variantId);
                if ($variant) {
                    $currentStatus = $variant->is_active;
                }
            }

            // Create or update shop override with toggled status
            if (!isset($this->shopVariantOverrides[$this->activeShopId][$variantId])) {
                $this->createShopVariantOverride($this->activeShopId, $variantId);
            }
            $this->updateShopVariantOverride($this->activeShopId, $variantId, ['is_active' => !$currentStatus]);

            $this->hasUnsavedChanges = true;
            session()->flash('message', 'Status wariantu dla sklepu zmieniony (zapisz zmiany aby potwierdzic).');
            return;
        }

        try {
            $variant = ProductVariant::findOrFail($variantId);
            $variant->update(['is_active' => !$variant->is_active]);

            Log::info('Variant status toggled', [
                'variant_id' => $variant->id,
                'is_active' => $variant->is_active,
            ]);

            $this->product->load('variants');
        } catch (\Exception $e) {
            Log::error('Toggle variant status failed', ['error' => $e->getMessage()]);
            session()->flash('error', 'Blad podczas zmiany statusu wariantu.');
        }
    }

    /**
     * Toggle variant price display mode between 'gross' and 'net'
     * Used by the price column header switch in variants tab
     */
    public function toggleVariantPriceDisplayMode(): void
    {
        $this->variantPriceDisplayMode = $this->variantPriceDisplayMode === 'gross' ? 'net' : 'gross';
    }

    /**
     * Toggle variant image selection (multi-select)
     * Called from blade via wire:click="toggleVariantImage(mediaId)"
     */
    public function toggleVariantImage(int $mediaId): void
    {
        $mediaIds = $this->variantData['media_ids'] ?? [];

        if (in_array($mediaId, $mediaIds)) {
            // Remove from selection
            $this->variantData['media_ids'] = array_values(array_diff($mediaIds, [$mediaId]));
        } else {
            // Add to selection
            $this->variantData['media_ids'][] = $mediaId;
        }
    }

    /**
     * Generate unique SKU for variant (fallback method)
     */
    public function generateVariantSKU(?string $baseSku = null): string
    {
        $baseSku = $baseSku ?? $this->product->sku;
        $counter = 1;

        do {
            $newSku = sprintf('%s-V%03d', $baseSku, $counter);
            $counter++;
            $exists = DB::table('products')->where('sku', $newSku)->exists()
                || DB::table('product_variants')->where('sku', $newSku)->exists();
        } while ($exists && $counter < 1000);

        return $newSku;
    }

    /*
    |--------------------------------------------------------------------------
    | AUTO SKU SYSTEM (ETAP_05f)
    |--------------------------------------------------------------------------
    | Generates SKU automatically from attribute prefix/suffix settings.
    | When auto_generate_sku is enabled, SKU is regenerated on attribute change.
    */

    /**
     * Update variant SKU based on selected attributes
     * Called when attributes change and auto_generate_sku is enabled
     *
     * @since ETAP_05f
     */
    public function updateVariantSku(): void
    {
        // Skip if auto mode is disabled
        if (!($this->variantData['auto_generate_sku'] ?? false)) {
            return;
        }

        // Skip if no product
        if (!$this->product || empty($this->product->sku)) {
            Log::warning('[AUTO SKU] Cannot generate SKU - no product or empty base SKU');
            return;
        }

        // Get variantAttributes (from VariantAttributeTrait)
        $attributes = $this->variantAttributes ?? [];

        if (empty($attributes)) {
            // No attributes selected - use base SKU
            $this->variantData['sku'] = $this->product->sku;
            Log::debug('[AUTO SKU] No attributes selected, using base SKU', [
                'sku' => $this->variantData['sku'],
            ]);
            return;
        }

        // Use VariantSkuGenerator service
        $generator = app(VariantSkuGenerator::class);
        $newSku = $generator->generateSku($this->product, $attributes);

        if (!empty($newSku)) {
            $this->variantData['sku'] = $newSku;
            Log::debug('[AUTO SKU] Generated SKU from attributes', [
                'sku' => $newSku,
                'attributes' => $attributes,
            ]);
        }
    }

    /**
     * Livewire lifecycle hook - called when variantData.auto_generate_sku changes
     * Re-enables auto generation and updates SKU
     *
     * @since ETAP_05f
     */
    public function updatedVariantDataAutoGenerateSku($value): void
    {
        if ($value) {
            // Re-enabled auto mode - regenerate SKU
            $this->updateVariantSku();
            Log::debug('[AUTO SKU] Auto mode re-enabled, regenerating SKU');
        }
    }

    /**
     * Toggle auto SKU generation mode
     * Called from UI when user clicks the checkbox
     *
     * @since ETAP_05f
     */
    public function toggleAutoSkuGeneration(): void
    {
        $this->variantData['auto_generate_sku'] = !($this->variantData['auto_generate_sku'] ?? false);

        if ($this->variantData['auto_generate_sku']) {
            $this->updateVariantSku();
        }
    }

    /**
     * Called when an attribute is changed via setVariantAttribute
     * Triggers SKU regeneration if auto mode is enabled
     *
     * @since ETAP_05f
     */
    public function onVariantAttributeChanged(): void
    {
        $this->updateVariantSku();
    }

    /**
     * Load variant data for editing
     * FIX 2025-12-04: Added eager loading for images and media_ids extraction
     * FIX 2025-12-04 (FAZA 5): Shop context support - load override data if exists
     */
    public function loadVariantForEdit(int $variantId): void
    {
        try {
            $variant = ProductVariant::with(['attributes', 'images'])->findOrFail($variantId);

            $this->editingVariantId = $variant->id;

            // SHOP CONTEXT CHECK (ETAP_05b FAZA 5)
            // In shop context, load shop override data if exists
            if ($this->activeShopId !== null && isset($this->shopVariantOverrides[$this->activeShopId][$variantId])) {
                $override = $this->shopVariantOverrides[$this->activeShopId][$variantId];

                // Handle both ShopVariantOverride DTO and array formats
                $isDto = $override instanceof \App\DTOs\ShopVariantOverride;

                Log::info('[VARIANT EDIT] Loading shop override data', [
                    'shop_id' => $this->activeShopId,
                    'variant_id' => $variantId,
                    'override_type' => $isDto ? 'ShopVariantOverride DTO' : 'array',
                ]);

                // Extract media_ids from variant (default) then override if shop has custom
                $mediaIds = $this->extractMediaIdsFromVariantImages($variant);

                if ($isDto) {
                    // Handle ShopVariantOverride DTO object
                    $this->variantData = [
                        'sku' => $override->sku ?: $variant->sku,
                        'name' => $override->name ?: $variant->name,
                        'is_active' => $override->isActive ?? $variant->is_active,
                        'is_default' => $override->isDefault ?? $variant->is_default,
                        'position' => $override->position ?? $variant->position,
                        'media_ids' => !empty($override->mediaIds) ? $override->mediaIds : $mediaIds,
                    ];
                    $this->variantAttributes = $override->attributes ?? [];
                } else {
                    // Handle array format
                    $this->variantData = [
                        'sku' => $override['sku'] ?? $variant->sku,
                        'name' => $override['name'] ?? $variant->name,
                        'is_active' => $override['is_active'] ?? $variant->is_active,
                        'is_default' => $override['is_default'] ?? $variant->is_default,
                        'position' => $override['position'] ?? $variant->position,
                        'media_ids' => $override['media_ids'] ?? $mediaIds,
                    ];
                    $this->variantAttributes = $override['attributes'] ?? [];
                }

                // Load attributes from default if empty
                if (empty($this->variantAttributes)) {
                    foreach ($variant->attributes as $attr) {
                        $this->variantAttributes[$attr->attribute_type_id] = $attr->value_id;
                    }
                }
                $this->initSelectedAttributeTypes();

                $this->showEditModal = true;
                return;
            }

            // DEFAULT CONTEXT - load from database
            // Extract media_ids by matching variant images to product gallery
            $mediaIds = $this->extractMediaIdsFromVariantImages($variant);

            $this->variantData = [
                'sku' => $variant->sku,
                'name' => $variant->name,
                'is_active' => $variant->is_active,
                'is_default' => $variant->is_default,
                'position' => $variant->position,
                'media_ids' => $mediaIds,
            ];

            // Load attribute value_ids (not text values)
            $this->variantAttributes = [];
            foreach ($variant->attributes as $attr) {
                $this->variantAttributes[$attr->attribute_type_id] = $attr->value_id;
            }
            $this->initSelectedAttributeTypes();

            $this->showEditModal = true;

            Log::debug('[VARIANT EDIT] Loaded variant for edit', [
                'variant_id' => $variantId,
                'media_ids' => $mediaIds,
                'images_count' => $variant->images->count(),
            ]);
        } catch (\Exception $e) {
            Log::error('Load variant for edit failed', ['error' => $e->getMessage()]);
            session()->flash('error', 'Blad podczas ladowania wariantu.');
        }
    }

    /**
     * Extract media IDs from variant images by matching file paths to product gallery
     * FIX 2025-12-04: Since variant_images doesn't have media_id FK, we match by file_path
     */
    protected function extractMediaIdsFromVariantImages(ProductVariant $variant): array
    {
        if (!$this->product || $variant->images->isEmpty()) {
            return [];
        }

        $mediaIds = [];

        // Get product media for matching
        $productMedia = $this->product->media()
            ->active()
            ->get()
            ->keyBy('file_path');

        foreach ($variant->images as $variantImage) {
            // Try to match by file_path
            if (!empty($variantImage->image_path) && isset($productMedia[$variantImage->image_path])) {
                $mediaIds[] = $productMedia[$variantImage->image_path]->id;
            }
        }

        return $mediaIds;
    }

    /**
     * Reset variant form data
     */
    protected function resetVariantData(): void
    {
        $this->variantData = [
            'sku' => '',
            'name' => '',
            'is_active' => true,
            'is_default' => false,
            'position' => 0,
            'media_ids' => [],
            'ps_image_ids' => [],
            'auto_generate_sku' => true,
        ];
        $this->variantAttributes = [];
        $this->selectedAttributeTypeIds = [];
        $this->editingVariantId = null;
        $this->showCreateModal = false;
        $this->showEditModal = false;
    }

    /*
    |--------------------------------------------------------------------------
    | HELPER METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Assign attributes to newly created variant
     * NOTE: $variantAttributes stores value_id (integer FK to attribute_values.id)
     */
    protected function assignVariantAttributes(ProductVariant $variant): void
    {
        if (empty($this->variantAttributes)) {
            return;
        }

        foreach ($this->variantAttributes as $attributeTypeId => $valueId) {
            if (!empty($valueId)) {
                VariantAttribute::create([
                    'variant_id' => $variant->id,
                    'attribute_type_id' => $attributeTypeId,
                    'value_id' => (int) $valueId,
                ]);
            }
        }
    }

    /**
     * Update variant attributes
     * NOTE: $variantAttributes stores value_id (integer FK to attribute_values.id)
     */
    protected function updateVariantAttributes(ProductVariant $variant): void
    {
        if (empty($this->variantAttributes)) {
            return;
        }

        $variant->attributes()->delete();

        foreach ($this->variantAttributes as $attributeTypeId => $valueId) {
            if (!empty($valueId)) {
                VariantAttribute::create([
                    'variant_id' => $variant->id,
                    'attribute_type_id' => $attributeTypeId,
                    'value_id' => (int) $valueId,
                ]);
            }
        }
    }

    /**
     * Assign selected images to variant from product gallery (multiple)
     */
    protected function assignVariantImage(ProductVariant $variant): void
    {
        $mediaIds = $this->variantData['media_ids'] ?? [];

        if (empty($mediaIds)) {
            return;
        }

        // Ensure it's an array
        if (!is_array($mediaIds)) {
            $mediaIds = [$mediaIds];
        }

        $position = 0;
        foreach ($mediaIds as $mediaId) {
            if (empty($mediaId)) {
                continue;
            }

            // Find the media from product gallery
            $media = \App\Models\Media::find($mediaId);

            if (!$media) {
                Log::warning('Selected media not found for variant', [
                    'variant_id' => $variant->id,
                    'media_id' => $mediaId,
                ]);
                continue;
            }

            // Create VariantImage based on product Media
            // First image is cover
            // FIX 2025-12-04: Media model has file_path (not path) and thumbnail_url accessor
            VariantImage::create([
                'variant_id' => $variant->id,
                'image_path' => $media->file_path ?? '',
                'image_thumb_path' => null, // Will be generated on-demand via VariantImage::getThumbPath()
                'image_url' => $media->url ?? null, // Store URL for quick access
                'is_cover' => ($position === 0), // First image is cover
                'position' => $position,
            ]);

            Log::debug('[VARIANT IMAGE] Created from product media', [
                'variant_id' => $variant->id,
                'media_id' => $mediaId,
                'position' => $position,
                'is_cover' => ($position === 0),
            ]);

            $position++;
        }
    }

    /**
     * Initialize price and stock arrays for new variant
     */
    protected function initializeVariantArrays(ProductVariant $variant): void
    {
        $priceGroups = PriceGroup::all();
        foreach ($priceGroups as $priceGroup) {
            $this->variantPrices[$variant->id][$priceGroup->code] = 0;
        }

        $warehouses = Warehouse::orderBy('name')->get();
        foreach ($warehouses as $index => $warehouse) {
            $this->variantStock[$variant->id][$index + 1] = 0;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | PENDING VARIANTS COMMIT METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Commit all pending variant operations to database
     * Called from ProductForm save() method
     *
     * @return array Stats about committed operations
     */
    public function commitPendingVariants(): array
    {
        $stats = [
            'created' => 0,
            'updated' => 0,
            'deleted' => 0,
            'errors' => [],
        ];

        if (!$this->product || !$this->product->id) {
            Log::warning('[VARIANT COMMIT] Cannot commit - no product');
            return $stats;
        }

        Log::info('[VARIANT COMMIT] Starting commit', [
            'product_id' => $this->product->id,
            'pending_creates' => count($this->pendingVariantCreates),
            'pending_updates' => count($this->pendingVariantUpdates),
            'pending_deletes' => count($this->pendingVariantDeletes),
        ]);

        try {
            DB::transaction(function () use (&$stats) {
                // 1. Process DELETES first (avoid conflicts with updates)
                foreach ($this->pendingVariantDeletes as $variantId) {
                    try {
                        $variant = ProductVariant::find($variantId);
                        if ($variant) {
                            $variant->delete();
                            $stats['deleted']++;
                            Log::info('[VARIANT COMMIT] Deleted variant', ['variant_id' => $variantId]);

                            // Handle default variant removal
                            if ($this->product->default_variant_id === $variantId) {
                                $newDefault = $this->product->variants()
                                    ->where('is_active', true)
                                    ->orderBy('position')
                                    ->first();
                                $this->product->update(['default_variant_id' => $newDefault?->id]);
                            }
                        }
                    } catch (\Exception $e) {
                        $stats['errors'][] = "Delete {$variantId}: {$e->getMessage()}";
                        Log::error('[VARIANT COMMIT] Delete failed', ['variant_id' => $variantId, 'error' => $e->getMessage()]);
                    }
                }

                // 2. Process UPDATES
                foreach ($this->pendingVariantUpdates as $variantId => $data) {
                    try {
                        $variant = ProductVariant::find($variantId);
                        if ($variant) {
                            $variant->update([
                                'sku' => $data['sku'],
                                'name' => $data['name'],
                                'is_active' => $data['is_active'],
                                'is_default' => $data['is_default'],
                                'position' => $data['position'],
                            ]);

                            // Update attributes if provided
                            if (!empty($data['attributes'])) {
                                $variant->attributes()->delete();
                                foreach ($data['attributes'] as $attributeTypeId => $valueId) {
                                    if (!empty($valueId)) {
                                        VariantAttribute::create([
                                            'variant_id' => $variant->id,
                                            'attribute_type_id' => $attributeTypeId,
                                            'value_id' => (int) $valueId,
                                        ]);
                                    }
                                }
                            }

                            // Handle default variant change
                            if ($data['is_default']) {
                                $this->product->update(['default_variant_id' => $variant->id]);
                                $this->product->variants()
                                    ->where('id', '!=', $variant->id)
                                    ->update(['is_default' => false]);
                            }

                            // FIX 2025-12-04: Update images if media_ids changed
                            if (isset($data['media_ids'])) {
                                // Clear existing images and reassign
                                $variant->images()->delete();
                                if (!empty($data['media_ids'])) {
                                    $this->assignVariantImagesFromIds($variant, $data['media_ids']);
                                    Log::info('[VARIANT COMMIT] Updated variant images', [
                                        'variant_id' => $variantId,
                                        'media_ids_count' => count($data['media_ids']),
                                    ]);
                                }
                            }

                            $stats['updated']++;
                            Log::info('[VARIANT COMMIT] Updated variant', ['variant_id' => $variantId]);
                        }
                    } catch (\Exception $e) {
                        $stats['errors'][] = "Update {$variantId}: {$e->getMessage()}";
                        Log::error('[VARIANT COMMIT] Update failed', ['variant_id' => $variantId, 'error' => $e->getMessage()]);
                    }
                }

                // 3. Process CREATES (SKIP shop-only variants - they go through commitShopVariants)
                foreach ($this->pendingVariantCreates as $tempId => $data) {
                    // ETAP_05c FIX: Skip shop-only variants - they should NOT be in product_variants
                    if ($data['is_shop_only'] ?? false) {
                        Log::debug('[VARIANT COMMIT] Skipping shop-only variant (goes to shop_variants)', [
                            'tempId' => $tempId,
                            'shop_id' => $data['shop_id'] ?? null,
                        ]);
                        continue;
                    }

                    try {
                        // Create variant via Query Builder
                        $variantId = DB::table('product_variants')->insertGetId([
                            'product_id' => $this->product->id,
                            'sku' => $data['sku'],
                            'name' => $data['name'],
                            'is_active' => $data['is_active'] ?? true,
                            'is_default' => $data['is_default'] ?? false,
                            'position' => $data['position'],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        $variant = ProductVariant::find($variantId);

                        // Assign attributes
                        if (!empty($data['attributes'])) {
                            foreach ($data['attributes'] as $attributeTypeId => $valueId) {
                                if (!empty($valueId)) {
                                    VariantAttribute::create([
                                        'variant_id' => $variant->id,
                                        'attribute_type_id' => $attributeTypeId,
                                        'value_id' => (int) $valueId,
                                    ]);
                                }
                            }
                        }

                        // Assign images from media_ids
                        if (!empty($data['media_ids'])) {
                            $this->assignVariantImagesFromIds($variant, $data['media_ids']);
                        }

                        // Update parent product flags
                        $this->product->update(['is_variant_master' => true]);

                        // Set default if first variant or marked as default
                        if ($data['is_default'] || $this->product->variants()->count() === 1) {
                            $this->product->update(['default_variant_id' => $variant->id]);
                        }

                        // Initialize arrays for new variant
                        $this->initializeVariantArrays($variant);

                        $stats['created']++;
                        Log::info('[VARIANT COMMIT] Created variant', [
                            'tempId' => $tempId,
                            'variant_id' => $variantId,
                            'sku' => $data['sku'],
                        ]);
                    } catch (\Exception $e) {
                        $stats['errors'][] = "Create {$tempId}: {$e->getMessage()}";
                        Log::error('[VARIANT COMMIT] Create failed', ['tempId' => $tempId, 'error' => $e->getMessage()]);
                    }
                }

                // Clear is_variant_master if no more variants
                $remainingVariants = $this->product->variants()->count();
                if ($remainingVariants === 0) {
                    $this->product->update(['is_variant_master' => false]);
                }
            });
        } catch (\Exception $e) {
            $stats['errors'][] = "Transaction: {$e->getMessage()}";
            Log::error('[VARIANT COMMIT] Transaction failed', ['error' => $e->getMessage()]);
        }

        // Clear pending arrays after commit
        // FIX 2025-12-08: KEEP shop-only variants for commitShopVariants() which runs AFTER this method!
        // Only clear non-shop-only variants here; shop-only will be cleared in commitShopVariants()
        $this->pendingVariantCreates = array_filter($this->pendingVariantCreates, function($data) {
            return ($data['is_shop_only'] ?? false); // Keep shop-only variants
        });
        $this->pendingVariantUpdates = [];
        $this->pendingVariantDeletes = [];
        $this->pendingVariantTempIdCounter = -1;

        // Clear session storage as well (FIX: Livewire state)
        $this->clearPendingVariantsSession();

        // Refresh variants
        $this->product->load('variants');

        Log::info('[VARIANT COMMIT] Completed', $stats);

        return $stats;
    }

    /**
     * Assign images to variant from media IDs array
     * Helper method for commitPendingVariants()
     */
    protected function assignVariantImagesFromIds(ProductVariant $variant, array $mediaIds): void
    {
        $position = 0;
        foreach ($mediaIds as $mediaId) {
            if (empty($mediaId)) {
                continue;
            }

            $media = \App\Models\Media::find($mediaId);
            if (!$media) {
                continue;
            }

            // FIX 2025-12-04: Media model has file_path (not path) and thumbnail_url accessor
            VariantImage::create([
                'variant_id' => $variant->id,
                'image_path' => $media->file_path ?? '',
                'image_thumb_path' => null, // Will be generated on-demand via VariantImage::getThumbPath()
                'image_url' => $media->url ?? null, // Store URL for quick access
                'is_cover' => ($position === 0),
                'position' => $position,
            ]);

            $position++;
        }
    }

    /**
     * Check if there are any pending variant changes
     *
     * @return bool
     */
    public function hasPendingVariantChanges(): bool
    {
        return !empty($this->pendingVariantCreates)
            || !empty($this->pendingVariantUpdates)
            || !empty($this->pendingVariantDeletes);
    }

    /**
     * Get all variants including pending ones (for UI display)
     * Returns merged list of existing variants + pending creates
     * Excludes variants marked for deletion
     *
     * ETAP_05c FIX: Keep ProductVariant models for method access (getCoverImage, images, etc.)
     * Instead of using getVariantsForShop() which returns stdClass, we manually filter
     * variants based on shop operations while preserving the original model objects.
     *
     * ETAP_05c SHOP CONTEXT FIX: When in shop context (selectedShopId !== null),
     * variants are pulled LIVE from PrestaShop API via pullShopVariants().
     * Local variants (product_variants table) are NOT shown in shop context.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getAllVariantsForDisplay(): \Illuminate\Support\Collection
    {
        // ETAP_05c SHOP CONTEXT: Return PrestaShop variants when in shop context
        // FIX: Use activeShopId (set by switchToShop) instead of selectedShopId (set by selectShopTab)
        // NOTE: $this->prestaShopVariants is an ARRAY with keys: 'variants' (Collection), 'synced', 'error'
        if ($this->activeShopId !== null && is_array($this->prestaShopVariants) && isset($this->prestaShopVariants['variants'])) {
            $variants = $this->prestaShopVariants['variants'];
            $result = collect();

            // FIX 2025-12-08: ALWAYS include pending shop-only creates for THIS shop
            // This was missing - pending variants were not displayed in shop context!
            foreach ($this->pendingVariantCreates as $tempId => $data) {
                $isShopOnly = $data['is_shop_only'] ?? false;
                $variantShopId = $data['shop_id'] ?? null;

                // Only include shop-only variants for THIS shop
                if ($isShopOnly && $variantShopId === $this->activeShopId) {
                    $mediaIds = $data['media_ids'] ?? [];
                    $images = collect($mediaIds)->map(fn($id) => \App\Models\Media::find($id))->filter();

                    $pendingVariant = (object) [
                        'id' => $tempId,
                        'sku' => $data['sku'],
                        'name' => $data['name'],
                        'is_active' => $data['is_active'] ?? true,
                        'is_default' => $data['is_default'] ?? false,
                        'position' => $data['position'] ?? 0,
                        'pendingCreate' => true,
                        'pendingDelete' => false,
                        'pendingUpdate' => false,
                        'display_sku' => $data['sku'],
                        'display_name' => $data['name'],
                        'display_is_active' => $data['is_active'] ?? true,
                        'attributes' => collect(),
                        'images' => $images,
                        'media_ids' => $mediaIds,
                        'is_shop_only' => true,
                        'is_shop_context' => true,
                        'operation_type' => 'ADD',
                    ];
                    $result->push($pendingVariant);

                    Log::debug('[getAllVariantsForDisplay] Added pending shop-only variant', [
                        'tempId' => $tempId,
                        'sku' => $data['sku'],
                        'shop_id' => $this->activeShopId,
                    ]);
                }
            }

            // If PrestaShop has no combinations, check for pending shop overrides (from copy operation)
            if ($variants->isEmpty()) {
                $shopOverrides = $this->shopVariantOverrides[$this->activeShopId] ?? [];

                // If we have pending shop overrides, display them based on default variants
                if (!empty($shopOverrides)) {
                    // Merge pending creates with shop overrides
                    $overrideVariants = $this->getShopOverridesForDisplay($this->activeShopId, $shopOverrides);
                    return $result->merge($overrideVariants)->sortBy('position');
                }

                // Return pending creates if any, otherwise empty
                if ($result->isNotEmpty()) {
                    Log::debug('[getAllVariantsForDisplay] Returning pending shop-only variants', [
                        'shop_id' => $this->activeShopId,
                        'count' => $result->count(),
                    ]);
                    return $result->sortBy('position');
                }

                Log::debug('[getAllVariantsForDisplay] Shop context - PrestaShop has 0 combinations and no overrides', [
                    'shop_id' => $this->activeShopId,
                ]);
                return collect();
            }

            // Merge pending creates with PrestaShop variants
            $psVariants = $this->getPrestaShopVariantsForDisplay();
            $result = $result->merge($psVariants);

            // FIX 2025-12-08: Also include shop overrides when PrestaShop HAS variants
            // These are PPM variants copied to shop via "Wstaw z" - they need to be created in PS
            $shopOverrides = $this->shopVariantOverrides[$this->activeShopId] ?? [];
            if (!empty($shopOverrides)) {
                // Filter out overrides for variants that already exist in PrestaShop
                $existingPsVariantIds = $psVariants->pluck('id')->filter()->toArray();
                $newOverrides = collect($shopOverrides)->filter(function ($override, $variantId) use ($existingPsVariantIds) {
                    // Include override if variant doesn't exist in PS yet
                    return !in_array($variantId, $existingPsVariantIds) && !str_starts_with((string)$variantId, 'ps_');
                })->toArray();

                if (!empty($newOverrides)) {
                    $overrideVariants = $this->getShopOverridesForDisplay($this->activeShopId, $newOverrides);
                    $result = $result->merge($overrideVariants);

                    Log::debug('[getAllVariantsForDisplay] Added shop overrides (PPM variants to create in PS)', [
                        'shop_id' => $this->activeShopId,
                        'overrides_count' => count($newOverrides),
                    ]);
                }
            }

            return $result->sortBy('position');
        }

        $result = collect();

        if ($this->product) {
            // DEFAULT CONTEXT: Use ProductVariant models from local database
            $baseVariants = $this->product->variants ?? collect();

            // Get shop-specific overrides if in shop context
            $shopOverrides = $this->shopVariantOverrides[$this->activeShopId] ?? [];

            // Get saved shop_variants for this shop (DELETE/OVERRIDE operations already in DB)
            $savedShopVariants = collect();
            if ($this->activeShopId !== null) {
                $savedShopVariants = $this->product->shopVariantsForShop($this->activeShopId)
                    ->get()
                    ->keyBy('variant_id');
            }

            foreach ($baseVariants as $variant) {
                $variantId = $variant->id ?? null;

                // Check for pending deletion in current context
                if ($this->activeShopId !== null) {
                    // Shop context: check both pending overrides and saved shop_variants
                    $savedShopVariant = $savedShopVariants->get($variantId);

                    // Check pending override first (not yet saved)
                    $isDeletedInShop = isset($shopOverrides[$variantId]['_deleted'])
                        || (isset($shopOverrides[$variantId]['operation_type']) && $shopOverrides[$variantId]['operation_type'] === 'DELETE');

                    // Also check saved shop_variants for DELETE operation
                    if (!$isDeletedInShop && $savedShopVariant && $savedShopVariant->isDeleteOperation()) {
                        $isDeletedInShop = true;
                    }

                    if ($isDeletedInShop) {
                        // Mark as pending delete for UI (strikethrough styling)
                        $variant->pendingDelete = true;
                    } else {
                        $variant->pendingDelete = false;
                    }

                    // Determine operation type
                    if ($savedShopVariant && $savedShopVariant->isOverrideOperation()) {
                        $variant->operation_type = 'OVERRIDE';
                    } elseif (isset($shopOverrides[$variantId]) && !($shopOverrides[$variantId]['_deleted'] ?? false)) {
                        $variant->operation_type = 'OVERRIDE';
                    } else {
                        $variant->operation_type = 'INHERIT';
                    }
                    $variant->is_shop_context = true;
                } else {
                    // Default context: use pendingVariantDeletes array
                    if (in_array($variantId, $this->pendingVariantDeletes)) {
                        $variant->pendingDelete = true;
                    } else {
                        $variant->pendingDelete = false;
                    }
                    $variant->is_shop_context = false;
                    $variant->operation_type = null;
                }

                // Check if has pending updates
                $variant->pendingUpdate = isset($this->pendingVariantUpdates[$variantId]);
                if ($variant->pendingUpdate) {
                    // Apply pending updates for display
                    $updates = $this->pendingVariantUpdates[$variantId];
                    $variant->display_sku = $updates['sku'];
                    $variant->display_name = $updates['name'];
                    $variant->display_is_active = $updates['is_active'];
                } else {
                    $variant->display_sku = $variant->sku ?? '';
                    $variant->display_name = $variant->name ?? '';
                    $variant->display_is_active = $variant->is_active ?? true;
                }

                $result->push($variant);
            }
        }

        // Add pending creates (with negative IDs)
        // ETAP_05c FIX: Filter by context - shop-only variants only in shop context
        foreach ($this->pendingVariantCreates as $tempId => $data) {
            $isShopOnly = $data['is_shop_only'] ?? false;
            $variantShopId = $data['shop_id'] ?? null;

            // Filter: In default context, skip shop-only variants
            // In shop context, only show variants for THIS shop (shop-only) or inherited ones
            if ($this->activeShopId === null && $isShopOnly) {
                // Skip shop-only variants in default context
                continue;
            }

            if ($this->activeShopId !== null && $isShopOnly && $variantShopId !== $this->activeShopId) {
                // Skip shop-only variants for OTHER shops
                continue;
            }

            // Convert media_ids to Media objects for thumbnail display
            $mediaIds = $data['media_ids'] ?? [];
            $images = collect($mediaIds)->map(function ($id) {
                return \App\Models\Media::find($id);
            })->filter(); // Remove nulls

            $pendingVariant = (object) [
                'id' => $tempId,
                'sku' => $data['sku'],
                'name' => $data['name'],
                'is_active' => $data['is_active'],
                'is_default' => $data['is_default'],
                'position' => $data['position'],
                'pendingCreate' => true,
                'pendingDelete' => false,
                'pendingUpdate' => false,
                'display_sku' => $data['sku'],
                'display_name' => $data['name'],
                'display_is_active' => $data['is_active'],
                'attributes' => collect(), // Empty collection for pending
                'images' => $images,
                'media_ids' => $mediaIds, // Keep original IDs for reference
                'is_shop_only' => $isShopOnly,
                'is_shop_context' => $this->activeShopId !== null,
                'operation_type' => $isShopOnly ? 'ADD' : 'CREATE',
            ];
            $result->push($pendingVariant);
        }

        return $result->sortBy('position');
    }

    /**
     * Reset all pending variant changes (discard unsaved work)
     */
    public function resetPendingVariants(): void
    {
        $this->pendingVariantCreates = [];
        $this->pendingVariantUpdates = [];
        $this->pendingVariantDeletes = [];
        $this->pendingVariantTempIdCounter = -1;

        Log::info('[VARIANT PENDING] Reset all pending changes');
    }

    /*
    |--------------------------------------------------------------------------
    | SHOP-SPECIFIC VARIANT METHODS (ETAP_05c)
    |--------------------------------------------------------------------------
    | Methods for creating and managing shop-only variants.
    | Shop-only variants exist ONLY in shop_variants table with operation_type=ADD.
    */

    /**
     * Create shop-only variant (ADD operation)
     *
     * Creates a ShopVariant record with operation_type=ADD.
     * This variant exists ONLY for this shop, not in base product_variants.
     */
    protected function createShopOnlyVariant(): void
    {
        try {
            $validatedData = $this->validateVariantCreate($this->variantData);

            // Generate unique negative tempId for pending shop variant
            $tempId = $this->pendingVariantTempIdCounter--;

            // Auto-generate position
            if (empty($validatedData['position'])) {
                $existingCount = $this->product ? $this->product->variants()->count() : 0;
                $shopVariantsCount = $this->product->shopVariantsForShop($this->activeShopId)->count();
                $pendingCount = count($this->pendingVariantCreates);
                $validatedData['position'] = $existingCount + $shopVariantsCount + $pendingCount + 1;
            }

            // Store in pending array with shop context flag
            $this->pendingVariantCreates[$tempId] = [
                'tempId' => $tempId,
                'sku' => $validatedData['sku'],
                'name' => $validatedData['name'],
                'is_active' => $validatedData['is_active'] ?? true,
                'is_default' => $validatedData['is_default'] ?? false,
                'position' => $validatedData['position'],
                'attributes' => $this->variantAttributes ?? [],
                'media_ids' => $this->variantData['media_ids'] ?? [],
                // SHOP CONTEXT FLAGS
                'is_shop_only' => true,
                'shop_id' => $this->activeShopId,
                'operation_type' => 'ADD',
            ];

            Log::info('[VARIANT PENDING] Added shop-only variant (ADD)', [
                'product_id' => $this->product->id ?? 'new',
                'shop_id' => $this->activeShopId,
                'tempId' => $tempId,
                'sku' => $validatedData['sku'],
            ]);

            $this->hasUnsavedChanges = true;
            $this->savePendingVariantsToSession();

            $this->showCreateModal = false;
            $this->showEditModal = false;
            $this->resetVariantData();

            $this->dispatch('variant-pending-added');
            session()->flash('message', 'Wariant dla sklepu dodany (zapisz zmiany aby potwierdzic).');

        } catch (\Exception $e) {
            Log::error('[VARIANT CREATE] Shop-only variant creation failed', [
                'shop_id' => $this->activeShopId,
                'error' => $e->getMessage(),
            ]);
            $this->addError('variantData', 'Blad podczas dodawania wariantu: ' . $e->getMessage());
        }
    }

    /**
     * Commit shop-specific variants to database
     *
     * Called from commitPendingVariants() for shop context.
     * Creates ShopVariant records with appropriate operation_type.
     */
    public function commitShopVariants(): array
    {
        $stats = [
            'created' => 0,
            'updated' => 0,
            'deleted' => 0,
            'errors' => [],
        ];

        if (!$this->product || !$this->product->id || !$this->activeShopId) {
            return $stats;
        }

        Log::info('[SHOP VARIANT COMMIT] Starting commit', [
            'product_id' => $this->product->id,
            'shop_id' => $this->activeShopId,
        ]);

        try {
            DB::transaction(function () use (&$stats) {
                // Process shop-only creates (ADD operations)
                foreach ($this->pendingVariantCreates as $tempId => $data) {
                    // Only process shop-only variants for this shop
                    if (!($data['is_shop_only'] ?? false) || ($data['shop_id'] ?? null) !== $this->activeShopId) {
                        continue;
                    }

                    try {
                        // FIX 2026-02-11: Resolve media_ids to PS images before saving
                        $mediaIds = $data['media_ids'] ?? [];
                        $images = method_exists($this, 'resolveMediaIdsToPrestaShopImages')
                            ? $this->resolveMediaIdsToPrestaShopImages($mediaIds, $this->activeShopId)
                            : [];

                        $shopVariant = \App\Models\ShopVariant::create([
                            'shop_id' => $this->activeShopId,
                            'product_id' => $this->product->id,
                            'variant_id' => null, // Shop-only = no base variant
                            'operation_type' => 'ADD',
                            'variant_data' => [
                                'sku' => $data['sku'],
                                'name' => $data['name'],
                                'is_active' => $data['is_active'] ?? true,
                                'is_default' => $data['is_default'] ?? false,
                                'position' => $data['position'],
                                'attributes' => $data['attributes'] ?? [],
                                'images' => $images,
                                'media_ids' => $mediaIds,
                            ],
                            'sync_status' => 'pending',
                        ]);

                        $stats['created']++;
                        Log::info('[SHOP VARIANT COMMIT] Created shop-only variant', [
                            'shop_variant_id' => $shopVariant->id,
                            'sku' => $data['sku'],
                        ]);

                        // Remove from pending after successful commit
                        unset($this->pendingVariantCreates[$tempId]);

                    } catch (\Exception $e) {
                        $stats['errors'][] = "Create shop variant {$tempId}: {$e->getMessage()}";
                        Log::error('[SHOP VARIANT COMMIT] Create failed', [
                            'tempId' => $tempId,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                // Process shop overrides (updates to existing variants OR new PPM variants copied to shop)
                foreach ($this->shopVariantOverrides[$this->activeShopId] ?? [] as $variantId => $overrideData) {
                    try {
                        // Handle both ShopVariantOverride DTO objects and arrays
                        $isDto = $overrideData instanceof \App\DTOs\ShopVariantOverride;

                        // FIX 2025-12-08: Check if this PPM variant already exists in PrestaShop
                        // PPM variants copied via "Wstaw z" need ADD operation if not yet in PS
                        $isPpmVariant = is_numeric($variantId) && !str_starts_with((string)$variantId, 'ps_');
                        $hasPrestaShopId = false;

                        if ($isPpmVariant) {
                            // Check if existing ShopVariant has prestashop_combination_id
                            $existingShopVariant = \App\Models\ShopVariant::where('shop_id', $this->activeShopId)
                                ->where('product_id', $this->product->id)
                                ->where('variant_id', $variantId)
                                ->whereNotNull('prestashop_combination_id')
                                ->first();

                            $hasPrestaShopId = $existingShopVariant && $existingShopVariant->prestashop_combination_id;
                        }

                        // Determine operation type:
                        // - DELETE: if _deleted flag is set (only for arrays, DTOs are never deleted)
                        // - ADD: if PPM variant doesn't exist in PrestaShop yet
                        // - OVERRIDE: if variant exists in PrestaShop (has prestashop_combination_id)
                        $isDeleted = $isDto ? false : ($overrideData['_deleted'] ?? false);

                        if ($isDeleted) {
                            $operationType = 'DELETE';
                        } elseif ($isPpmVariant && !$hasPrestaShopId) {
                            $operationType = 'ADD';
                        } else {
                            $operationType = 'OVERRIDE';
                        }

                        // Convert DTO to array for storage
                        $variantDataForStorage = null;
                        if ($operationType !== 'DELETE') {
                            $variantDataForStorage = $isDto ? $overrideData->toArray() : $overrideData;

                            // FIX 2026-02-11: Resolve media_ids to PS images
                            if (!empty($variantDataForStorage['media_ids']) && method_exists($this, 'resolveMediaIdsToPrestaShopImages')) {
                                $variantDataForStorage['images'] = $this->resolveMediaIdsToPrestaShopImages(
                                    $variantDataForStorage['media_ids'],
                                    $this->activeShopId
                                );
                            }
                        }

                        \App\Models\ShopVariant::updateOrCreate(
                            [
                                'shop_id' => $this->activeShopId,
                                'product_id' => $this->product->id,
                                'variant_id' => $variantId,
                            ],
                            [
                                'operation_type' => $operationType,
                                'variant_data' => $variantDataForStorage,
                                'sync_status' => 'pending',
                            ]
                        );

                        $stats['updated']++;
                        Log::info('[SHOP VARIANT COMMIT] Updated shop override', [
                            'variant_id' => $variantId,
                            'operation_type' => $operationType,
                            'is_dto' => $isDto,
                            'is_ppm_variant' => $isPpmVariant,
                            'has_prestashop_id' => $hasPrestaShopId,
                        ]);

                    } catch (\Exception $e) {
                        $stats['errors'][] = "Update shop override {$variantId}: {$e->getMessage()}";
                    }
                }
            });

        } catch (\Exception $e) {
            $stats['errors'][] = "Transaction: {$e->getMessage()}";
            Log::error('[SHOP VARIANT COMMIT] Transaction failed', ['error' => $e->getMessage()]);
        }

        // Clear shop-specific pending data
        $this->shopVariantOverrides[$this->activeShopId] = [];

        Log::info('[SHOP VARIANT COMMIT] Completed', $stats);

        // Dispatch sync job if there were any changes
        if ($stats['created'] > 0 || $stats['updated'] > 0 || $stats['deleted'] > 0) {
            $this->dispatchShopVariantsSync();
        }

        return $stats;
    }

    /**
     * Dispatch sync job for shop variants
     *
     * Sets shopVariantsSyncing flag and dispatches SyncShopVariantsToPrestaShopJob
     */
    protected function dispatchShopVariantsSync(): void
    {
        if (!$this->product || !$this->activeShopId) {
            return;
        }

        // Set syncing flag (blocks UI)
        $this->shopVariantsSyncing = true;

        Log::info('[SHOP VARIANT SYNC] Dispatching sync job', [
            'product_id' => $this->product->id,
            'shop_id' => $this->activeShopId,
        ]);

        // Dispatch the job
        \App\Jobs\PrestaShop\SyncShopVariantsToPrestaShopJob::dispatch(
            $this->product->id,
            $this->activeShopId
        );
    }

    /**
     * Handle shop variants sync completed event
     *
     * Called when SyncShopVariantsToPrestaShopJob completes
     */
    public function onShopVariantsSyncCompleted(int $productId, int $shopId, bool $success): void
    {
        if ($productId !== $this->product?->id || $shopId !== $this->activeShopId) {
            return;
        }

        $this->shopVariantsSyncing = false;

        if ($success) {
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Warianty zsynchronizowane z PrestaShop',
            ]);
        } else {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Blad synchronizacji wariantow - sprawdz logi',
            ]);
        }

        // Reload variants to show current state
        $this->loadVariants();
    }

    /**
     * Get variants for display in shop context
     *
     * Returns merged list: base variants (with overrides) + shop-only variants
     */
    public function getShopVariantsForDisplay(): \Illuminate\Support\Collection
    {
        if (!$this->activeShopId) {
            return $this->getAllVariantsForDisplay();
        }

        // Use Product method to get merged variants
        $shopVariants = $this->product->getVariantsForShop($this->activeShopId);

        // Add pending shop-only variants
        foreach ($this->pendingVariantCreates as $tempId => $data) {
            if (($data['is_shop_only'] ?? false) && ($data['shop_id'] ?? null) === $this->activeShopId) {
                // Convert media_ids to Media objects for thumbnail display
                $mediaIds = $data['media_ids'] ?? [];
                $images = collect($mediaIds)->map(function ($id) {
                    return \App\Models\Media::find($id);
                })->filter();

                $pendingVariant = (object) [
                    'id' => $tempId,
                    'sku' => $data['sku'],
                    'name' => $data['name'],
                    'is_active' => $data['is_active'],
                    'is_default' => $data['is_default'],
                    'position' => $data['position'],
                    'pendingCreate' => true,
                    'pendingDelete' => false,
                    'pendingUpdate' => false,
                    'operation_type' => 'ADD',
                    'sync_status' => 'pending',
                    'attributes' => collect(),
                    'images' => $images,
                ];
                $shopVariants->push($pendingVariant);
            }
        }

        return $shopVariants->sortBy('position');
    }

    /**
     * Get PrestaShop variants for display (LIVE data from API)
     *
     * ETAP_05c SHOP CONTEXT: Returns variants pulled from PrestaShop API.
     * This method is called when user is in shop context (selectedShopId !== null)
     * and pullShopVariants() has been called.
     *
     * If product has no combinations in PrestaShop, returns empty collection.
     * Local product_variants table is NOT used in shop context.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getPrestaShopVariantsForDisplay(): \Illuminate\Support\Collection
    {
        // Return cached PrestaShop variants from pullShopVariants()
        $variants = $this->prestaShopVariants['variants'] ?? collect();

        Log::debug('[VariantCrudTrait] getPrestaShopVariantsForDisplay', [
            'shop_id' => $this->selectedShopId,
            'variants_count' => $variants->count(),
            'synced' => $this->prestaShopVariants['synced'] ?? false,
            'error' => $this->prestaShopVariants['error'] ?? null,
        ]);

        // Format for UI display (ensure consistent structure)
        // FIX 2025-12-09 BUG#5a: Check pendingPsVariantUpdates for each variant
        return $variants->map(function ($variant) {
            // If already object (from ShopVariantService), just add UI flags
            if (is_object($variant)) {
                $variant->is_shop_context = true;
                $variant->pendingCreate = false;

                // FIX 2025-12-09 BUG#5a: Check if this PS variant has pending updates
                // pendingPsVariantUpdates uses 'ps_123' format, variant->id may be just '123'
                $rawId = $variant->id ?? null;
                $psVariantId = $rawId;
                // Check both formats: ps_123 and 123
                $psVariantIdWithPrefix = str_starts_with((string)$rawId, 'ps_') ? $rawId : 'ps_' . $rawId;

                $hasPendingUpdate = false;
                $pendingData = null;
                // Check both possible keys
                if ($rawId && isset($this->pendingPsVariantUpdates[$psVariantIdWithPrefix])) {
                    $hasPendingUpdate = true;
                    $pendingData = $this->pendingPsVariantUpdates[$psVariantIdWithPrefix];
                } elseif ($rawId && isset($this->pendingPsVariantUpdates[$rawId])) {
                    $hasPendingUpdate = true;
                    $pendingData = $this->pendingPsVariantUpdates[$rawId];
                }

                if ($pendingData) {
                    // Attach pending data to variant for blade template access
                    $variant->pendingData = $pendingData;
                }
                $variant->pendingUpdate = $hasPendingUpdate;

                $variant->pendingDelete = $variant->operation_type === 'DELETE';
                $variant->display_sku = $variant->sku ?? '';
                $variant->display_name = $variant->name ?? '';
                $variant->display_is_active = $variant->is_active ?? true;
                return $variant;
            }

            // Convert array to object if needed
            // FIX 2025-12-09 BUG#5a: Check if this PS variant has pending updates
            // pendingPsVariantUpdates uses 'ps_123' format, variant['id'] may be just '123'
            $rawId = $variant['id'] ?? null;
            $psVariantId = $rawId;
            // Check both formats: ps_123 and 123
            $psVariantIdWithPrefix = str_starts_with((string)$rawId, 'ps_') ? $rawId : 'ps_' . $rawId;

            $hasPendingUpdate = false;
            $pendingData = null;
            // Check both possible keys
            if ($rawId && isset($this->pendingPsVariantUpdates[$psVariantIdWithPrefix])) {
                $hasPendingUpdate = true;
                $pendingData = $this->pendingPsVariantUpdates[$psVariantIdWithPrefix];
            } elseif ($rawId && isset($this->pendingPsVariantUpdates[$rawId])) {
                $hasPendingUpdate = true;
                $pendingData = $this->pendingPsVariantUpdates[$rawId];
            }

            return (object) array_merge((array) $variant, [
                'is_shop_context' => true,
                'pendingCreate' => false,
                'pendingUpdate' => $hasPendingUpdate,
                'pendingData' => $pendingData,
                'pendingDelete' => ($variant['operation_type'] ?? null) === 'DELETE',
                'display_sku' => $variant['sku'] ?? '',
                'display_name' => $variant['name'] ?? '',
                'display_is_active' => $variant['is_active'] ?? true,
            ]);
        })->sortBy('position');
    }

    /**
     * Get shop variant overrides formatted for display (pending variants from copy operation)
     *
     * Called when PrestaShop has no combinations but we have pending shop overrides.
     * Displays the copied variants based on default variant data with override info.
     *
     * @param int $shopId
     * @param array $shopOverrides
     * @return \Illuminate\Support\Collection
     */
    protected function getShopOverridesForDisplay(int $shopId, array $shopOverrides): \Illuminate\Support\Collection
    {
        $result = collect();

        foreach ($shopOverrides as $variantId => $override) {
            // Handle both ShopVariantOverride DTO objects and arrays (backwards compatibility)
            $isDto = $override instanceof \App\DTOs\ShopVariantOverride;

            // Get values depending on type (DTO uses camelCase properties, arrays use snake_case keys)
            $sku = $isDto ? $override->sku : ($override['sku'] ?? '');
            $name = $isDto ? $override->name : ($override['name'] ?? '');
            $isActive = $isDto ? $override->isActive : ($override['is_active'] ?? true);
            $isDefault = $isDto ? $override->isDefault : ($override['is_default'] ?? false);
            $position = $isDto ? $override->position : ($override['position'] ?? 0);
            $attributes = $isDto ? $override->attributes : ($override['attributes'] ?? []);
            $mediaIds = $isDto ? $override->mediaIds : ($override['media_ids'] ?? []);
            $prices = $isDto ? $override->prices : ($override['prices'] ?? null);
            $stock = $isDto ? $override->stock : ($override['stock'] ?? null);

            // Get base variant data from default context
            $baseVariant = $this->product->variants->firstWhere('id', $variantId);

            // Create display object
            $displayVariant = (object) [
                'id' => $variantId,
                'variant_id' => $variantId,
                'sku' => $sku ?: ($baseVariant->sku ?? ''),
                'name' => $name ?: ($baseVariant->name ?? ''),
                'is_active' => $isActive,
                'is_default' => $isDefault,
                'position' => $position ?: ($baseVariant->position ?? 0),
                'attributes' => $attributes,
                'media_ids' => $mediaIds,

                // Display formatting
                'display_sku' => $sku ?: ($baseVariant->sku ?? ''),
                'display_name' => $name ?: ($baseVariant->name ?? ''),
                'display_is_active' => $isActive,

                // UI flags - these are PENDING (not yet saved)
                'is_shop_context' => true,
                'pendingCreate' => true,  // Mark as pending create (not saved to PrestaShop)
                'pendingUpdate' => false,
                'pendingDelete' => false,
                'operation_type' => 'PENDING_CREATE',

                // FIX 2026-01-29: Use 'price' and 'stock' property names (blade reads these)
                // and fetch from Eloquent relations instead of non-existent direct properties
                'price' => $prices['price'] ?? ($baseVariant ? (float) ($baseVariant->prices->first()?->price ?? 0) : 0),
                'stock' => $stock['quantity'] ?? ($baseVariant ? (int) $baseVariant->stock->sum('quantity') : 0),
            ];

            // Add grouped attributes for display (like default variants)
            if ($baseVariant) {
                $displayVariant->grouped_attributes = $baseVariant->getGroupedAttributes();
                $displayVariant->images = $baseVariant->images ?? collect();
            } else {
                $displayVariant->grouped_attributes = [];
                $displayVariant->images = collect();
            }

            $result->push($displayVariant);

            Log::debug('[getShopOverridesForDisplay] Added variant to display', [
                'variant_id' => $variantId,
                'sku' => $displayVariant->display_sku,
                'is_dto' => $isDto,
            ]);
        }

        Log::info('[getShopOverridesForDisplay] Displaying pending shop overrides', [
            'shop_id' => $shopId,
            'overrides_count' => $result->count(),
        ]);

        return $result->sortBy('position');
    }

    /*
    |--------------------------------------------------------------------------
    | VARIANT COPY OPERATIONS (BETWEEN CONTEXTS)
    |--------------------------------------------------------------------------
    | Methods for copying variants between default context and shop contexts
    */

    /**
     * Copy variants from PrestaShop shop to local PPM variants
     *
     * Creates pending variants in default context (pendingVariantCreates).
     * User must click Save to commit to database.
     *
     * @param int $shopId Shop to copy FROM
     * @return void
     */
    public function copyVariantsFromShop(int $shopId): void
    {
        try {
            // Validate we're in default context (cannot copy TO shop context)
            if ($this->activeShopId !== null) {
                Log::warning('[VARIANT COPY] Cannot copy FROM shop while IN shop context', [
                    'current_shop_id' => $this->activeShopId,
                ]);
                session()->flash('error', 'Przejdz do "Dane domyslne" aby skopiowac warianty z sklepu.');
                return;
            }

            // Get shop variants (from PrestaShop or shop_variants table)
            $shop = PrestaShopShop::findOrFail($shopId);
            $shopVariants = $this->getShopVariantsForCopy($shopId);

            if ($shopVariants->isEmpty()) {
                session()->flash('error', 'Sklep nie ma wariantow do skopiowania.');
                return;
            }

            $copiedCount = 0;
            foreach ($shopVariants as $shopVariant) {
                // Generate unique negative tempId
                $tempId = $this->pendingVariantTempIdCounter--;

                // Generate unique SKU for copied variant
                $copiedSku = $this->generateUniqueSkuForCopy($shopVariant->sku ?? '', $shop->name);

                // Auto-generate position
                $existingCount = $this->product ? $this->product->variants()->count() : 0;
                $pendingCount = count($this->pendingVariantCreates);
                $position = $existingCount + $pendingCount + 1;

                // Create pending variant
                $this->pendingVariantCreates[$tempId] = [
                    'tempId' => $tempId,
                    'sku' => $copiedSku,
                    'name' => ($shopVariant->name ?? '') . ' (z ' . $shop->name . ')',
                    'is_active' => $shopVariant->is_active ?? true,
                    'is_default' => false, // Copied variants are not default
                    'position' => $position,
                    'attributes' => $this->extractAttributesFromShopVariant($shopVariant),
                    'media_ids' => [], // Images not copied (user can assign manually)
                ];

                $copiedCount++;
            }

            // Mark form as having unsaved changes
            $this->hasUnsavedChanges = true;

            // Persist to session
            $this->savePendingVariantsToSession();

            Log::info('[VARIANT COPY] Copied variants from shop to default', [
                'product_id' => $this->product->id ?? null,
                'shop_id' => $shopId,
                'copied_count' => $copiedCount,
            ]);

            $this->dispatch('variant-pending-added');
            session()->flash('message', "Skopiowano {$copiedCount} wariantow z {$shop->name}. Zapisz zmiany aby potwierdzic.");

        } catch (\Exception $e) {
            Log::error('[VARIANT COPY] Failed to copy from shop', [
                'shop_id' => $shopId,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', 'Blad podczas kopiowania wariantow: ' . $e->getMessage());
        }
    }

    /**
     * Copy local PPM variants to shop context (creates shop overrides)
     *
     * In shop context: creates shop variant overrides from default variants.
     * User must click Save to commit to database.
     *
     * @param int|null $sourceShopId Shop to copy FROM (null = default context)
     * @return void
     */
    public function copyVariantsToShop(?int $sourceShopId = null): void
    {
        try {
            // Validate we're in shop context (target)
            if ($this->activeShopId === null) {
                Log::warning('[VARIANT COPY] Cannot copy TO shop - not in shop context');
                session()->flash('error', 'Wybierz sklep (zakładke sklepu) aby skopiowac do niego warianty.');
                return;
            }

            $targetShopId = $this->activeShopId;

            // Get source variants
            if ($sourceShopId === null) {
                // Copy from default context
                $sourceVariants = $this->product->variants ?? collect();
                $sourceName = 'Dane domyslne';
            } else {
                // Copy from another shop
                $sourceShop = PrestaShopShop::findOrFail($sourceShopId);
                $sourceVariants = $this->getShopVariantsForCopy($sourceShopId);
                $sourceName = $sourceShop->name;
            }

            if ($sourceVariants->isEmpty()) {
                session()->flash('error', 'Brak wariantow do skopiowania.');
                return;
            }

            $targetShop = PrestaShopShop::findOrFail($targetShopId);
            $copiedCount = 0;

            foreach ($sourceVariants as $sourceVariant) {
                // For default source: create shop override from ProductVariant model
                if ($sourceShopId === null && $sourceVariant instanceof ProductVariant) {
                    $variantId = $sourceVariant->id;

                    // Check if override already exists
                    if (isset($this->shopVariantOverrides[$targetShopId][$variantId])) {
                        continue; // Skip if already has override
                    }

                    // Create shop override with shop-specific SKU suffix
                    $overrideSku = $sourceVariant->sku . '-S' . $targetShopId;

                    // Use VariantShopContextTrait method to create override
                    $this->createShopVariantOverride($targetShopId, $variantId);

                    // Update the override with source data
                    $this->updateShopVariantOverride($targetShopId, $variantId, [
                        'sku' => $overrideSku,
                        'name' => $sourceVariant->name,
                        'is_active' => $sourceVariant->is_active,
                        'is_default' => $sourceVariant->is_default,
                        'attributes' => $sourceVariant->attributes->mapWithKeys(
                            fn($attr) => [$attr->attribute_type_id => $attr->attribute_value_id]
                        )->toArray(),
                        'position' => $sourceVariant->position,
                    ]);

                    $copiedCount++;
                } else {
                    // For shop source: create as pending variant (ADD operation)
                    // This is handled by copyVariantsFromShop() - redirect there
                    Log::debug('[VARIANT COPY] Shop-to-shop copy via ADD operation', [
                        'source_shop_id' => $sourceShopId,
                        'target_shop_id' => $targetShopId,
                    ]);
                    // Not implemented yet - complex scenario
                }
            }

            // Mark form as having unsaved changes
            $this->hasUnsavedChanges = true;

            Log::info('[VARIANT COPY] Copied variants to shop', [
                'product_id' => $this->product->id ?? null,
                'source' => $sourceShopId ?? 'default',
                'target_shop_id' => $targetShopId,
                'copied_count' => $copiedCount,
            ]);

            $this->dispatch('variant-pending-updated');
            session()->flash('message', "Skopiowano {$copiedCount} wariantow z {$sourceName} do {$targetShop->name}. Zapisz zmiany aby potwierdzic.");

        } catch (\Exception $e) {
            Log::error('[VARIANT COPY] Failed to copy to shop', [
                'source_shop_id' => $sourceShopId,
                'target_shop_id' => $this->activeShopId,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', 'Blad podczas kopiowania wariantow: ' . $e->getMessage());
        }
    }

    /**
     * Get list of available shops for variant copy dropdown
     *
     * Returns shops excluding current shop (if in shop context)
     *
     * @return \Illuminate\Support\Collection
     */
    public function getAvailableShopsForVariantCopy(): \Illuminate\Support\Collection
    {
        if (!$this->product || !$this->product->id) {
            return collect();
        }

        // Get all shops linked to this product via shopData relationship
        $linkedShops = PrestaShopShop::whereIn('id', function($query) {
            $query->select('shop_id')
                  ->from('product_shop_data')
                  ->where('product_id', $this->product->id);
        })->orderBy('name')->get();

        // Filter out current shop if in shop context
        if ($this->activeShopId !== null) {
            $linkedShops = $linkedShops->filter(fn($shop) => $shop->id !== $this->activeShopId);
        }

        return $linkedShops;
    }

    /**
     * Get shop variants for copying (from PrestaShop or shop_variants table)
     *
     * @param int $shopId
     * @return \Illuminate\Support\Collection
     */
    protected function getShopVariantsForCopy(int $shopId): \Illuminate\Support\Collection
    {
        // Option 1: Check if we have PrestaShop variants cached
        if (isset($this->prestaShopVariants['variants']) && !empty($this->prestaShopVariants['variants'])) {
            return $this->prestaShopVariants['variants'];
        }

        // Option 2: Get from shop_variants table
        $shopVariants = \App\Models\ShopVariant::forShop($shopId)
            ->forProduct($this->product->id)
            ->active() // Exclude DELETE operations
            ->get();

        if ($shopVariants->isEmpty()) {
            return collect();
        }

        // Convert to stdClass objects with variant data
        return $shopVariants->map(function ($shopVariant) {
            $data = $shopVariant->getEffectiveVariantData();
            return (object) [
                'id' => $shopVariant->variant_id,
                'sku' => $data['sku'] ?? '',
                'name' => $data['name'] ?? '',
                'is_active' => $data['is_active'] ?? true,
                'is_default' => $data['is_default'] ?? false,
                'attributes' => $data['attributes'] ?? [],
                'position' => $data['position'] ?? 0,
            ];
        });
    }

    /**
     * Generate unique SKU for copied variant
     *
     * @param string $originalSku
     * @param string $shopName
     * @return string
     */
    protected function generateUniqueSkuForCopy(string $originalSku, string $shopName): string
    {
        $baseSku = $originalSku;
        $counter = 1;

        // Remove existing suffixes
        $baseSku = preg_replace('/-S\d+$/', '', $baseSku); // Remove shop suffix
        $baseSku = preg_replace('/-V\d+$/', '', $baseSku); // Remove variant suffix

        do {
            $newSku = sprintf('%s-COPY%02d', $baseSku, $counter);
            $counter++;

            $exists = DB::table('products')->where('sku', $newSku)->exists()
                || DB::table('product_variants')->where('sku', $newSku)->exists()
                || collect($this->pendingVariantCreates)->pluck('sku')->contains($newSku);
        } while ($exists && $counter < 100);

        return $newSku;
    }

    /**
     * Extract attributes from shop variant
     *
     * @param object $shopVariant
     * @return array
     */
    protected function extractAttributesFromShopVariant(object $shopVariant): array
    {
        if (empty($shopVariant->attributes)) {
            return [];
        }

        // If attributes is already array with type_id => value_id mapping
        if (is_array($shopVariant->attributes)) {
            return $shopVariant->attributes;
        }

        // If attributes is collection, convert to array
        if ($shopVariant->attributes instanceof \Illuminate\Support\Collection) {
            return $shopVariant->attributes->mapWithKeys(
                fn($attr) => [$attr->attribute_type_id => $attr->attribute_value_id]
            )->toArray();
        }

        return [];
    }
}

<?php

namespace App\Jobs\PrestaShop;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShopVariant;
use App\Models\PrestaShopShop;
use App\Models\AttributeType;
use App\Models\AttributeValue;
use App\Services\PrestaShop\PrestaShop8Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * SyncShopVariantsToPrestaShopJob
 *
 * ETAP_05c: Synchronizes shop-specific variants to PrestaShop
 *
 * Operation Types handled:
 * - ADD: Creates new combination in PrestaShop
 * - OVERRIDE: Updates existing combination
 * - DELETE: Deletes combination from PrestaShop
 *
 * Flow:
 * 1. User makes changes in UI (shop tab)
 * 2. Save triggers this job
 * 3. UI shows "oczekiwanie na synchronizację" with blocked fields
 * 4. Job processes changes via PrestaShop API
 * 5. Updates sync_status on ShopVariant records
 * 6. Broadcasts completion event to UI
 *
 * @package App\Jobs\PrestaShop
 */
class SyncShopVariantsToPrestaShopJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;
    public int $timeout = 300;

    protected int $productId;
    protected int $shopId;
    protected array $variantIds;

    /**
     * Create a new job instance.
     *
     * @param int $productId
     * @param int $shopId
     * @param array $variantIds ShopVariant IDs to sync (empty = all pending)
     */
    public function __construct(int $productId, int $shopId, array $variantIds = [])
    {
        $this->productId = $productId;
        $this->shopId = $shopId;
        $this->variantIds = $variantIds;
        // FIX 2025-12-05: Removed $this->onQueue('prestashop') - queue 'prestashop' not configured
        // Using default queue instead (database driver with 'default' queue name)
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('[SyncShopVariantsJob] START', [
            'product_id' => $this->productId,
            'shop_id' => $this->shopId,
            'variant_ids' => $this->variantIds,
        ]);

        try {
            $product = Product::find($this->productId);
            $shop = PrestaShopShop::find($this->shopId);

            if (!$product || !$shop) {
                Log::error('[SyncShopVariantsJob] Product or Shop not found');
                return;
            }

            // Get PrestaShop product ID
            $shopData = $product->dataForShop($this->shopId)->first();
            $prestashopProductId = $shopData?->prestashop_product_id;

            if (!$prestashopProductId) {
                Log::warning('[SyncShopVariantsJob] No PrestaShop product ID - skipping', [
                    'product_id' => $this->productId,
                    'shop_id' => $this->shopId,
                ]);
                $this->markAllAsFailed('Product not synced to PrestaShop');
                return;
            }

            // Initialize PrestaShop client
            // FIX 2025-12-05: BasePrestaShopClient expects PrestaShopShop object, not individual properties
            $client = new PrestaShop8Client($shop);

            // Get variants to sync
            $query = ShopVariant::where('shop_id', $this->shopId)
                ->where('product_id', $this->productId)
                ->where('sync_status', 'pending');

            if (!empty($this->variantIds)) {
                $query->whereIn('id', $this->variantIds);
            }

            $shopVariants = $query->get();

            if ($shopVariants->isEmpty()) {
                Log::info('[SyncShopVariantsJob] No pending variants to sync');
                $this->broadcastCompletion(true);
                return;
            }

            // Mark all as in_progress
            $shopVariants->each(fn($sv) => $sv->markAsInProgress());

            $successCount = 0;
            $failCount = 0;

            foreach ($shopVariants as $shopVariant) {
                try {
                    $this->syncShopVariant($client, $shopVariant, $prestashopProductId);
                    $successCount++;
                } catch (\Exception $e) {
                    $shopVariant->markAsFailed($e->getMessage());
                    $failCount++;
                    Log::error('[SyncShopVariantsJob] Failed to sync variant', [
                        'shop_variant_id' => $shopVariant->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            Log::info('[SyncShopVariantsJob] COMPLETE', [
                'product_id' => $this->productId,
                'shop_id' => $this->shopId,
                'success' => $successCount,
                'failed' => $failCount,
            ]);

            $this->broadcastCompletion($failCount === 0);

        } catch (\Exception $e) {
            Log::error('[SyncShopVariantsJob] FATAL ERROR', [
                'product_id' => $this->productId,
                'shop_id' => $this->shopId,
                'error' => $e->getMessage(),
            ]);

            $this->markAllAsFailed($e->getMessage());
            $this->broadcastCompletion(false);

            throw $e;
        }
    }

    /**
     * Sync single ShopVariant to PrestaShop
     */
    protected function syncShopVariant(
        PrestaShop8Client $client,
        ShopVariant $shopVariant,
        int $prestashopProductId
    ): void {
        Log::debug('[SyncShopVariantsJob] Processing variant', [
            'shop_variant_id' => $shopVariant->id,
            'operation_type' => $shopVariant->operation_type,
        ]);

        switch ($shopVariant->operation_type) {
            case 'ADD':
                $this->handleAddOperation($client, $shopVariant, $prestashopProductId);
                break;

            case 'OVERRIDE':
                // FIX 2025-12-05: Check if combination exists in PrestaShop
                // If not, fallback to ADD operation (create combination first)
                $combinationId = $shopVariant->prestashop_combination_id
                    ?? $shopVariant->baseVariant?->prestashop_combination_id;

                if (!$combinationId) {
                    Log::info('[SyncShopVariantsJob] OVERRIDE without combination_id - falling back to ADD', [
                        'shop_variant_id' => $shopVariant->id,
                    ]);
                    $this->handleAddOperation($client, $shopVariant, $prestashopProductId);
                } else {
                    $shopVariant->prestashop_combination_id = $combinationId;
                    // FIX 2026-01-28: Pass prestashopProductId for 404 fallback to ADD
                    $this->handleOverrideOperation($client, $shopVariant, $prestashopProductId);
                }
                break;

            case 'DELETE':
                $this->handleDeleteOperation($client, $shopVariant);
                break;

            case 'INHERIT':
                // Nothing to sync - just mark as synced
                $shopVariant->markAsSynced();
                break;
        }
    }

    /**
     * Handle ADD operation - create new combination in PrestaShop
     */
    protected function handleAddOperation(
        PrestaShop8Client $client,
        ShopVariant $shopVariant,
        int $prestashopProductId
    ): void {
        $variantData = $shopVariant->variant_data ?? [];

        $combinationData = [
            'id_product' => $prestashopProductId,
            'reference' => $variantData['sku'] ?? '',
            'price' => $variantData['price_impact'] ?? 0,
            'weight' => $variantData['weight_impact'] ?? 0,
            'minimal_quantity' => $variantData['minimal_quantity'] ?? 1,
            'default_on' => $variantData['is_default'] ?? false ? 1 : 0,
        ];

        $response = $client->createCombination($prestashopProductId, $combinationData);

        $combinationId = (int) ($response['combination']['id'] ?? 0);

        if ($combinationId > 0) {
            // Set attributes if provided
            // FIX 2025-12-08: Resolve PPM attribute IDs to PrestaShop attribute IDs
            // FIX 2026-01-28: Pass client for auto-create of missing groups/values
            $attributes = $variantData['attributes'] ?? [];
            if (!empty($attributes)) {
                $attributeIds = $this->resolvePrestaShopAttributeIds($attributes, $this->shopId, $client);

                Log::debug('[SyncShopVariantsJob] Resolved attribute IDs for ADD', [
                    'ppm_attributes' => $attributes,
                    'prestashop_attribute_ids' => $attributeIds,
                    'shop_id' => $this->shopId,
                ]);

                if (!empty($attributeIds)) {
                    $client->setCombinationAttributes($combinationId, $attributeIds);
                }
            }

            // Set images if provided
            $imageIds = $this->resolveVariantImageIds($variantData, $shopVariant);
            if (!empty($imageIds)) {
                $client->setCombinationImages($combinationId, $imageIds);
            }

            // Set per-variant cover via PPM module (MUST be after setCombinationImages)
            $coverImageId = $this->resolveCoverImageId($variantData, $shopVariant);
            if ($coverImageId && !empty($imageIds)) {
                $client->setCombinationCovers($prestashopProductId, [
                    $combinationId => $coverImageId,
                ]);
            }

            // FIX 2026-01-29: Sync price and stock from PPM to PrestaShop after creating combination
            $this->syncVariantPrice($client, $shopVariant, $prestashopProductId, $combinationId);
            $this->syncVariantStock($client, $shopVariant, $prestashopProductId, $combinationId);

            $shopVariant->markAsSynced($combinationId);

            Log::info('[SyncShopVariantsJob] ADD successful', [
                'shop_variant_id' => $shopVariant->id,
                'prestashop_combination_id' => $combinationId,
            ]);
        } else {
            throw new \Exception('Failed to create combination - no ID returned');
        }
    }

    /**
     * Handle OVERRIDE operation - update existing combination
     *
     * FIX 2026-01-28: Added $prestashopProductId for 404 fallback to ADD operation
     * When combination doesn't exist in PrestaShop (404), clear ID and create new
     */
    protected function handleOverrideOperation(
        PrestaShop8Client $client,
        ShopVariant $shopVariant,
        int $prestashopProductId
    ): void {
        $combinationId = $shopVariant->prestashop_combination_id;

        if (!$combinationId) {
            throw new \Exception('No PrestaShop combination ID for OVERRIDE operation');
        }

        $variantData = $shopVariant->variant_data ?? [];

        $updates = [];

        if (isset($variantData['sku'])) {
            $updates['reference'] = $variantData['sku'];
        }
        if (isset($variantData['price_impact'])) {
            $updates['price'] = $variantData['price_impact'];
        }
        if (isset($variantData['weight_impact'])) {
            $updates['weight'] = $variantData['weight_impact'];
        }
        if (isset($variantData['minimal_quantity'])) {
            $updates['minimal_quantity'] = $variantData['minimal_quantity'];
        }
        if (isset($variantData['is_default'])) {
            $updates['default_on'] = $variantData['is_default'] ? 1 : 0;
        }
        if (isset($variantData['is_active'])) {
            $updates['active'] = $variantData['is_active'] ? 1 : 0;
        }

        // FIX 2026-01-28: Try to update, if 404 - fallback to ADD operation
        if (!empty($updates)) {
            try {
                $client->updateCombination($combinationId, $updates);
            } catch (\Exception $e) {
                // Check if this is a 404 error (combination doesn't exist)
                if ($this->isCombinationNotFoundError($e)) {
                    Log::warning('[SyncShopVariantsJob] OVERRIDE 404 - combination not found, falling back to ADD', [
                        'shop_variant_id' => $shopVariant->id,
                        'old_combination_id' => $combinationId,
                        'error' => $e->getMessage(),
                    ]);

                    // Clear stale combination ID
                    $shopVariant->prestashop_combination_id = null;
                    $shopVariant->save();

                    // Fallback to ADD operation
                    $this->handleAddOperation($client, $shopVariant, $prestashopProductId);
                    return;
                }

                // Re-throw other errors
                throw $e;
            }
        }

        // Update attributes if provided
        // FIX 2025-12-08: Resolve PPM attribute IDs to PrestaShop attribute IDs
        // FIX 2026-01-28: Pass client for auto-create of missing groups/values
        $attributes = $variantData['attributes'] ?? [];
        if (!empty($attributes)) {
            $attributeIds = $this->resolvePrestaShopAttributeIds($attributes, $this->shopId, $client);

            Log::debug('[SyncShopVariantsJob] Resolved attribute IDs for OVERRIDE', [
                'ppm_attributes' => $attributes,
                'prestashop_attribute_ids' => $attributeIds,
                'shop_id' => $this->shopId,
            ]);

            if (!empty($attributeIds)) {
                try {
                    $client->setCombinationAttributes($combinationId, $attributeIds);
                } catch (\Exception $e) {
                    if ($this->isCombinationNotFoundError($e)) {
                        Log::warning('[SyncShopVariantsJob] setCombinationAttributes 404 - falling back to ADD', [
                            'shop_variant_id' => $shopVariant->id,
                        ]);
                        $shopVariant->prestashop_combination_id = null;
                        $shopVariant->save();
                        $this->handleAddOperation($client, $shopVariant, $prestashopProductId);
                        return;
                    }
                    throw $e;
                }
            }
        }

        // Update images if provided
        $imageIds = $this->resolveVariantImageIds($variantData, $shopVariant);
        if (!empty($imageIds)) {
            try {
                $result = $client->setCombinationImages($combinationId, $imageIds);

                // FIX 2026-02-11: Log warning if setCombinationImages returns false (internal error)
                if (!$result) {
                    Log::warning('[SyncShopVariantsJob] setCombinationImages returned false', [
                        'shop_variant_id' => $shopVariant->id,
                        'combination_id' => $combinationId,
                        'image_ids' => $imageIds,
                    ]);
                } else {
                    Log::info('[SyncShopVariantsJob] setCombinationImages success', [
                        'shop_variant_id' => $shopVariant->id,
                        'combination_id' => $combinationId,
                        'image_ids' => $imageIds,
                    ]);
                }
            } catch (\Exception $e) {
                if ($this->isCombinationNotFoundError($e)) {
                    Log::warning('[SyncShopVariantsJob] setCombinationImages 404 - falling back to ADD', [
                        'shop_variant_id' => $shopVariant->id,
                    ]);
                    $shopVariant->prestashop_combination_id = null;
                    $shopVariant->save();
                    $this->handleAddOperation($client, $shopVariant, $prestashopProductId);
                    return;
                }
                throw $e;
            }
        }

        // Set per-variant cover via PPM module (MUST be after setCombinationImages)
        $coverImageId = $this->resolveCoverImageId($variantData, $shopVariant);
        if ($coverImageId && !empty($imageIds)) {
            $client->setCombinationCovers($prestashopProductId, [
                $combinationId => $coverImageId,
            ]);
        }

        // FIX 2026-01-29: Sync price and stock from PPM to PrestaShop after updating combination
        $this->syncVariantPrice($client, $shopVariant, $prestashopProductId, $combinationId);
        $this->syncVariantStock($client, $shopVariant, $prestashopProductId, $combinationId);

        $shopVariant->markAsSynced();

        Log::info('[SyncShopVariantsJob] OVERRIDE successful', [
            'shop_variant_id' => $shopVariant->id,
            'prestashop_combination_id' => $combinationId,
        ]);
    }

    /**
     * Check if exception is a "combination not found" (404) error
     *
     * FIX 2026-01-28: Helper method for 404 detection
     */
    protected function isCombinationNotFoundError(\Exception $e): bool
    {
        $message = strtolower($e->getMessage());

        // Check for common 404 patterns
        if (str_contains($message, '404')) {
            return true;
        }
        if (str_contains($message, 'not found')) {
            return true;
        }
        if (str_contains($message, 'combination') && str_contains($message, 'does not exist')) {
            return true;
        }

        // Check if it's a GuzzleHttp 404 response
        if ($e instanceof \GuzzleHttp\Exception\ClientException) {
            return $e->getResponse()?->getStatusCode() === 404;
        }

        return false;
    }

    /**
     * Resolve variant image IDs from variant_data
     *
     * FIX 2026-02-11: Supports both formats:
     * - New: variant_data['images'] = [['prestashop_image_id' => int], ...]
     * - Legacy: variant_data['media_ids'] = [int, ...] (PPM Media IDs)
     *
     * For legacy format, looks up Media.prestashop_mapping[store_{shopId}]
     *
     * @param array $variantData The variant_data from ShopVariant
     * @param ShopVariant $shopVariant The ShopVariant model (for shop_id)
     * @return array PrestaShop image IDs ready for setCombinationImages()
     */
    /**
     * Resolve the PrestaShop image ID for a variant's cover image.
     *
     * Looks up VariantImage where is_cover=true, then finds the corresponding
     * Media record and extracts the PS image ID from prestashop_mapping.
     *
     * @param array $variantData Variant data array
     * @param ShopVariant $shopVariant
     * @return int|null PS image ID or null if no cover found
     */
    protected function resolveCoverImageId(array $variantData, ShopVariant $shopVariant): ?int
    {
        $variantId = $shopVariant->variant_id;
        if (!$variantId) {
            return null;
        }

        // Find the cover image for this variant
        $coverImage = \App\Models\VariantImage::where('variant_id', $variantId)
            ->where('is_cover', true)
            ->first();

        if (!$coverImage || empty($coverImage->image_path)) {
            return null;
        }

        // Find corresponding Media record by file_path scoped to this product
        $productId = $shopVariant->product_id;

        $media = \App\Models\Media::where('file_path', $coverImage->image_path)
            ->where(function ($q) use ($productId) {
                // Product-level media (most common)
                $q->where(function ($sub) use ($productId) {
                    $sub->where('mediable_type', 'App\\Models\\Product')
                        ->where('mediable_id', $productId);
                })
                // Variant-level media (media linked to ProductVariant of this product)
                ->orWhere(function ($sub) use ($productId) {
                    $sub->where('mediable_type', 'App\\Models\\ProductVariant')
                        ->whereIn('mediable_id', function ($variantQuery) use ($productId) {
                            $variantQuery->select('id')
                                ->from('product_variants')
                                ->where('product_id', $productId);
                        });
                });
            })
            ->first();

        if (!$media) {
            Log::debug('[SyncShopVariantsJob] No Media record found for variant cover image', [
                'variant_id' => $variantId,
                'image_path' => $coverImage->image_path,
                'product_id' => $productId,
            ]);
            return null;
        }

        // Look up PS image ID from prestashop_mapping using store key pattern
        $mapping = $media->prestashop_mapping ?? [];
        $shopId = $shopVariant->shop_id;
        $shopMapping = $mapping["store_{$shopId}"]
            ?? $mapping[$shopId]
            ?? $mapping["shop_{$shopId}"]
            ?? null;

        if (!$shopMapping) {
            Log::debug('[SyncShopVariantsJob] No PS mapping for cover image media', [
                'media_id' => $media->id,
                'shop_id' => $shopId,
            ]);
            return null;
        }

        $psImageId = (int) ($shopMapping['ps_image_id'] ?? $shopMapping['image_id'] ?? 0);

        if ($psImageId > 0) {
            Log::debug('[SyncShopVariantsJob] Resolved cover image PS ID', [
                'variant_id' => $variantId,
                'media_id' => $media->id,
                'ps_image_id' => $psImageId,
            ]);
            return $psImageId;
        }

        return null;
    }

    protected function resolveVariantImageIds(array $variantData, ShopVariant $shopVariant): array
    {
        // Try new format first: images with prestashop_image_id
        $images = $variantData['images'] ?? [];
        if (!empty($images)) {
            $imageIds = array_column($images, 'prestashop_image_id');
            $imageIds = array_filter($imageIds);
            if (!empty($imageIds)) {
                Log::debug('[SyncShopVariantsJob] Resolved images from new format', [
                    'shop_variant_id' => $shopVariant->id,
                    'image_ids' => $imageIds,
                ]);
                return array_values(array_map('intval', $imageIds));
            }
        }

        // Fallback: resolve media_ids (PPM Media IDs) to PS image IDs
        $mediaIds = $variantData['media_ids'] ?? [];
        if (!empty($mediaIds)) {
            $storeKey = "store_{$shopVariant->shop_id}";
            $resolvedIds = [];

            $mediaRecords = \App\Models\Media::whereIn('id', $mediaIds)->get();
            foreach ($mediaRecords as $media) {
                $mapping = $media->prestashop_mapping ?? [];
                $shopMapping = $mapping[$storeKey]
                    ?? $mapping[$shopVariant->shop_id]
                    ?? $mapping["shop_{$shopVariant->shop_id}"]
                    ?? null;

                if ($shopMapping) {
                    $psImageId = (int) ($shopMapping['ps_image_id'] ?? $shopMapping['image_id'] ?? 0);
                    if ($psImageId > 0) {
                        $resolvedIds[] = $psImageId;
                    }
                }
            }

            if (!empty($resolvedIds)) {
                Log::info('[SyncShopVariantsJob] Resolved images from media_ids fallback', [
                    'shop_variant_id' => $shopVariant->id,
                    'media_ids' => $mediaIds,
                    'resolved_ps_image_ids' => $resolvedIds,
                ]);
                return $resolvedIds;
            }

            Log::warning('[SyncShopVariantsJob] media_ids present but no PS images resolved', [
                'shop_variant_id' => $shopVariant->id,
                'media_ids' => $mediaIds,
                'shop_id' => $shopVariant->shop_id,
            ]);
        }

        return [];
    }

    /**
     * Handle DELETE operation - remove combination from PrestaShop
     */
    protected function handleDeleteOperation(
        PrestaShop8Client $client,
        ShopVariant $shopVariant
    ): void {
        $combinationId = $shopVariant->prestashop_combination_id;

        if (!$combinationId) {
            // No combination to delete - just mark as synced
            $shopVariant->markAsSynced();
            return;
        }

        $deleted = $client->deleteCombination($combinationId);

        if ($deleted) {
            $shopVariant->markAsSynced();
            Log::info('[SyncShopVariantsJob] DELETE successful', [
                'shop_variant_id' => $shopVariant->id,
                'prestashop_combination_id' => $combinationId,
            ]);
        } else {
            throw new \Exception("Failed to delete combination {$combinationId}");
        }
    }

    /**
     * Sync variant price from PPM to PrestaShop as price_impact
     *
     * FIX 2026-01-29: Prices were not synced correctly. PrestaShop uses
     * price_impact (delta) = variant_absolute_price - product_base_price.
     * This method calculates the correct delta and updates the combination.
     *
     * @param PrestaShop8Client $client PrestaShop API client
     * @param ShopVariant $shopVariant Shop variant being synced
     * @param int $prestashopProductId PrestaShop product ID
     * @param int $combinationId PrestaShop combination ID
     */
    protected function syncVariantPrice(
        PrestaShop8Client $client,
        ShopVariant $shopVariant,
        int $prestashopProductId,
        int $combinationId
    ): void {
        try {
            // Load the PPM variant with prices relation
            $localVariant = ProductVariant::with('prices')->find($shopVariant->variant_id);

            if (!$localVariant) {
                Log::warning('[SyncShopVariantsJob] Cannot sync price - local variant not found', [
                    'shop_variant_id' => $shopVariant->id,
                    'variant_id' => $shopVariant->variant_id,
                ]);
                return;
            }

            // Get the PPM variant absolute price (net, first price group)
            $variantPrice = (float) ($localVariant->prices->first()?->price ?? 0);

            if ($variantPrice <= 0) {
                Log::debug('[SyncShopVariantsJob] Variant has no price, skipping price sync', [
                    'variant_id' => $localVariant->id,
                    'variant_sku' => $localVariant->sku,
                ]);
                return;
            }

            // Get the PrestaShop base product price (tax-excluded)
            $psProduct = $client->getProduct($prestashopProductId);
            $basePrice = (float) ($psProduct['product']['price'] ?? 0);

            // Calculate price_impact (delta)
            $priceImpact = round($variantPrice - $basePrice, 6);

            // Update the combination's price field (which is price_impact)
            $client->updateCombination($combinationId, [
                'price' => $priceImpact,
            ]);

            Log::info('[SyncShopVariantsJob] Price synced to PrestaShop', [
                'combination_id' => $combinationId,
                'variant_sku' => $localVariant->sku,
                'variant_absolute_price' => $variantPrice,
                'ps_base_price' => $basePrice,
                'price_impact' => $priceImpact,
            ]);

        } catch (\Exception $e) {
            // Log but don't fail the whole sync - price sync is secondary
            Log::error('[SyncShopVariantsJob] Failed to sync variant price', [
                'combination_id' => $combinationId,
                'prestashop_product_id' => $prestashopProductId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Sync variant stock from PPM to PrestaShop
     *
     * FIX 2026-01-29: Stock was never synced to PrestaShop for combinations.
     * PrestaShop stores variant stock in stock_availables table
     * with id_product_attribute = combination ID.
     *
     * @param PrestaShop8Client $client PrestaShop API client
     * @param ShopVariant $shopVariant Shop variant being synced
     * @param int $prestashopProductId PrestaShop product ID
     * @param int $combinationId PrestaShop combination ID
     */
    protected function syncVariantStock(
        PrestaShop8Client $client,
        ShopVariant $shopVariant,
        int $prestashopProductId,
        int $combinationId
    ): void {
        try {
            // Load the PPM variant with stock relation
            $localVariant = ProductVariant::with('stock')->find($shopVariant->variant_id);

            if (!$localVariant) {
                Log::warning('[SyncShopVariantsJob] Cannot sync stock - local variant not found', [
                    'shop_variant_id' => $shopVariant->id,
                    'variant_id' => $shopVariant->variant_id,
                ]);
                return;
            }

            // Calculate total stock across all warehouses
            $totalStock = (int) $localVariant->stock->sum('quantity');

            // Find the stock_available record in PrestaShop for this combination
            $stockAvailable = $client->getStockForCombination($prestashopProductId, $combinationId);

            if (!$stockAvailable || !isset($stockAvailable['id'])) {
                Log::warning('[SyncShopVariantsJob] No stock_available found in PrestaShop for combination', [
                    'prestashop_product_id' => $prestashopProductId,
                    'combination_id' => $combinationId,
                    'total_stock' => $totalStock,
                ]);
                return;
            }

            $stockAvailableId = (int) $stockAvailable['id'];
            $currentPsQuantity = (int) ($stockAvailable['quantity'] ?? 0);

            // Update stock in PrestaShop (include required id_product and id_product_attribute)
            $client->updateStock($stockAvailableId, $totalStock, $prestashopProductId, $combinationId);

            Log::info('[SyncShopVariantsJob] Stock synced to PrestaShop', [
                'combination_id' => $combinationId,
                'stock_available_id' => $stockAvailableId,
                'old_quantity' => $currentPsQuantity,
                'new_quantity' => $totalStock,
                'variant_sku' => $localVariant->sku,
            ]);

        } catch (\Exception $e) {
            // Log but don't fail the whole sync - stock sync is secondary
            Log::error('[SyncShopVariantsJob] Failed to sync variant stock', [
                'combination_id' => $combinationId,
                'prestashop_product_id' => $prestashopProductId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Mark all pending variants as failed
     */
    protected function markAllAsFailed(string $message): void
    {
        $query = ShopVariant::where('shop_id', $this->shopId)
            ->where('product_id', $this->productId)
            ->whereIn('sync_status', ['pending', 'in_progress']);

        if (!empty($this->variantIds)) {
            $query->whereIn('id', $this->variantIds);
        }

        $query->update([
            'sync_status' => 'failed',
            'sync_error_message' => $message,
        ]);
    }

    /**
     * Resolve PPM attribute IDs to PrestaShop attribute IDs
     *
     * FIX 2025-12-08: PPM stores attributes as [attribute_type_id => value_id]
     * but PrestaShop needs ps_attribute.id_attribute from prestashop_attribute_value_mapping
     *
     * FIX 2026-01-28: AUTO-CREATE missing attribute groups and values in PrestaShop
     * If group "Rozmiar" doesn't exist → create it via API and save mapping
     *
     * SUPPORTS MULTIPLE FORMATS:
     * 1. PPM format: [attribute_type_id => value_id] (from UI input)
     * 2. PrestaShop format: [['prestashop_attribute_id' => X]] (already resolved)
     * 3. Mixed format: [['id' => X, 'value_id' => Y]] (from pull operations)
     *
     * @param array $attributes PPM attributes array
     * @param int $shopId PrestaShop shop ID
     * @param PrestaShop8Client|null $client Optional client for auto-create
     * @return array PrestaShop attribute IDs
     */
    protected function resolvePrestaShopAttributeIds(array $attributes, int $shopId, ?PrestaShop8Client $client = null): array
    {
        $prestashopAttributeIds = [];

        // Case 1: Already resolved format [['prestashop_attribute_id' => X]]
        if (isset($attributes[0]) && is_array($attributes[0]) && isset($attributes[0]['prestashop_attribute_id'])) {
            Log::debug('[SyncShopVariantsJob] Attributes already in PrestaShop format');
            return array_filter(array_column($attributes, 'prestashop_attribute_id'));
        }

        // Case 2: PPM format [attribute_type_id => value_id]
        foreach ($attributes as $key => $value) {
            // Handle array format [['attribute_type_id' => X, 'value_id' => Y]]
            if (is_array($value)) {
                if (isset($value['prestashop_attribute_id'])) {
                    $prestashopAttributeIds[] = (int) $value['prestashop_attribute_id'];
                } elseif (isset($value['value_id'])) {
                    $psId = $this->lookupPrestaShopAttributeId((int) $value['value_id'], $shopId);
                    if ($psId) {
                        $prestashopAttributeIds[] = $psId;
                    } elseif ($client !== null) {
                        // FIX BUG#12-v3: Auto-create missing attribute for array format
                        Log::info('[SyncShopVariantsJob] Auto-create for array-format attribute', [
                            'attribute_type_id' => $value['attribute_type_id'] ?? null,
                            'attribute_value_id' => (int) $value['value_id'],
                            'shop_id' => $shopId,
                        ]);
                        $psId = $this->ensureAttributeValueMapped($client, (int) $value['value_id'], $shopId);
                        if ($psId) {
                            $prestashopAttributeIds[] = $psId;
                        } else {
                            Log::error('[SyncShopVariantsJob] Failed auto-create attribute (array format)', [
                                'attribute_value_id' => (int) $value['value_id'],
                                'shop_id' => $shopId,
                            ]);
                        }
                    } else {
                        Log::warning('[SyncShopVariantsJob] No PS mapping for attribute (no client)', [
                            'attribute_value_id' => (int) $value['value_id'],
                            'shop_id' => $shopId,
                        ]);
                    }
                }
                continue;
            }

            // PPM format: $key = attribute_type_id, $value = attribute_value_id
            $attributeValueId = (int) $value;
            $attributeTypeId = (int) $key;

            if ($attributeValueId <= 0) {
                Log::warning('[SyncShopVariantsJob] Invalid attribute_value_id', [
                    'attribute_type_id' => $attributeTypeId,
                    'value' => $value,
                ]);
                continue;
            }

            $psId = $this->lookupPrestaShopAttributeId($attributeValueId, $shopId);

            if ($psId) {
                $prestashopAttributeIds[] = $psId;
            } else {
                // FIX 2026-01-28: AUTO-CREATE missing attribute group and value
                if ($client !== null) {
                    Log::info('[SyncShopVariantsJob] Attempting auto-create for missing attribute', [
                        'attribute_type_id' => $attributeTypeId,
                        'attribute_value_id' => $attributeValueId,
                        'shop_id' => $shopId,
                    ]);

                    $psId = $this->ensureAttributeValueMapped($client, $attributeValueId, $shopId);

                    if ($psId) {
                        $prestashopAttributeIds[] = $psId;
                        Log::info('[SyncShopVariantsJob] Auto-created attribute mapping', [
                            'attribute_value_id' => $attributeValueId,
                            'prestashop_attribute_id' => $psId,
                            'shop_id' => $shopId,
                        ]);
                    } else {
                        Log::error('[SyncShopVariantsJob] Failed to auto-create attribute mapping', [
                            'attribute_type_id' => $attributeTypeId,
                            'attribute_value_id' => $attributeValueId,
                            'shop_id' => $shopId,
                        ]);
                    }
                } else {
                    Log::warning('[SyncShopVariantsJob] No PrestaShop mapping found for attribute (no client for auto-create)', [
                        'attribute_type_id' => $attributeTypeId,
                        'attribute_value_id' => $attributeValueId,
                        'shop_id' => $shopId,
                        'hint' => 'Run attribute sync first to create mappings',
                    ]);
                }
            }
        }

        return array_values(array_filter($prestashopAttributeIds));
    }

    /**
     * Lookup PrestaShop attribute ID from mapping table
     *
     * @param int $attributeValueId PPM AttributeValue ID
     * @param int $shopId PrestaShop shop ID
     * @return int|null PrestaShop ps_attribute.id_attribute
     */
    protected function lookupPrestaShopAttributeId(int $attributeValueId, int $shopId): ?int
    {
        $mapping = DB::table('prestashop_attribute_value_mapping')
            ->where('attribute_value_id', $attributeValueId)
            ->where('prestashop_shop_id', $shopId)
            ->where('is_synced', true)
            ->first();

        if ($mapping && $mapping->prestashop_attribute_id) {
            return (int) $mapping->prestashop_attribute_id;
        }

        return null;
    }

    /**
     * Broadcast completion event to Livewire
     */
    protected function broadcastCompletion(bool $success): void
    {
        // Broadcast to Livewire component
        event(new \App\Events\ShopVariantsSyncCompleted(
            $this->productId,
            $this->shopId,
            $success
        ));
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('[SyncShopVariantsJob] JOB FAILED', [
            'product_id' => $this->productId,
            'shop_id' => $this->shopId,
            'error' => $exception->getMessage(),
        ]);

        $this->markAllAsFailed($exception->getMessage());
        $this->broadcastCompletion(false);
    }

    /*
    |--------------------------------------------------------------------------
    | AUTO-CREATE ATTRIBUTE GROUPS & VALUES (FIX 2026-01-28)
    |--------------------------------------------------------------------------
    |
    | When syncing variants to PrestaShop and attribute group "Rozmiar" doesn't
    | exist in PrestaShop, automatically create it via API and save mapping.
    |
    */

    /**
     * Ensure AttributeValue has mapping to PrestaShop, create if missing
     *
     * FIX 2026-01-28: Auto-creates missing attribute groups and values in PrestaShop
     *
     * @param PrestaShop8Client $client PrestaShop API client
     * @param int $attributeValueId PPM AttributeValue ID
     * @param int $shopId PrestaShop shop ID
     * @return int|null PrestaShop ps_attribute.id_attribute or null on failure
     */
    protected function ensureAttributeValueMapped(
        PrestaShop8Client $client,
        int $attributeValueId,
        int $shopId
    ): ?int {
        try {
            // Get AttributeValue with its type
            $attributeValue = AttributeValue::with('attributeType')->find($attributeValueId);

            if (!$attributeValue || !$attributeValue->attributeType) {
                Log::error('[SyncShopVariantsJob] AttributeValue or AttributeType not found', [
                    'attribute_value_id' => $attributeValueId,
                ]);
                return null;
            }

            $attributeType = $attributeValue->attributeType;

            // 1. Ensure attribute group exists in PrestaShop
            $psGroupId = $this->ensureAttributeGroupMapped($client, $attributeType, $shopId);

            if (!$psGroupId) {
                Log::error('[SyncShopVariantsJob] Failed to ensure attribute group mapping', [
                    'attribute_type_id' => $attributeType->id,
                    'attribute_type_name' => $attributeType->name,
                    'shop_id' => $shopId,
                ]);
                return null;
            }

            // 2. Check if value already exists in PrestaShop
            $existingValueId = $this->findPrestaShopAttributeValue($client, $psGroupId, $attributeValue->label);

            if ($existingValueId) {
                // Save mapping and return
                $this->saveAttributeValueMapping($attributeValueId, $shopId, $existingValueId);
                return $existingValueId;
            }

            // 3. Create new attribute value in PrestaShop
            $psValueId = $this->createPrestaShopAttributeValue($client, $psGroupId, $attributeValue);

            if ($psValueId) {
                $this->saveAttributeValueMapping($attributeValueId, $shopId, $psValueId);
                return $psValueId;
            }

            return null;

        } catch (\Exception $e) {
            Log::error('[SyncShopVariantsJob] ensureAttributeValueMapped failed', [
                'attribute_value_id' => $attributeValueId,
                'shop_id' => $shopId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Ensure AttributeType has mapping to PrestaShop attribute group
     *
     * @param PrestaShop8Client $client PrestaShop API client
     * @param AttributeType $attributeType PPM AttributeType model
     * @param int $shopId PrestaShop shop ID
     * @return int|null PrestaShop ps_attribute_group.id_attribute_group or null
     */
    protected function ensureAttributeGroupMapped(
        PrestaShop8Client $client,
        AttributeType $attributeType,
        int $shopId
    ): ?int {
        try {
            // Check existing mapping
            $existingMapping = DB::table('prestashop_attribute_group_mapping')
                ->where('attribute_type_id', $attributeType->id)
                ->where('prestashop_shop_id', $shopId)
                ->where('is_synced', true)
                ->first();

            if ($existingMapping && $existingMapping->prestashop_attribute_group_id) {
                return (int) $existingMapping->prestashop_attribute_group_id;
            }

            // Search for existing group in PrestaShop by name
            $existingGroupId = $this->findPrestaShopAttributeGroup($client, $attributeType->name);

            if ($existingGroupId) {
                // Save mapping
                $this->saveAttributeGroupMapping($attributeType->id, $shopId, $existingGroupId);
                return $existingGroupId;
            }

            // Create new group in PrestaShop
            $newGroupId = $this->createPrestaShopAttributeGroup($client, $attributeType);

            if ($newGroupId) {
                $this->saveAttributeGroupMapping($attributeType->id, $shopId, $newGroupId);

                Log::info('[SyncShopVariantsJob] Created new attribute group in PrestaShop', [
                    'attribute_type_name' => $attributeType->name,
                    'prestashop_group_id' => $newGroupId,
                    'shop_id' => $shopId,
                ]);

                return $newGroupId;
            }

            return null;

        } catch (\Exception $e) {
            Log::error('[SyncShopVariantsJob] ensureAttributeGroupMapped failed', [
                'attribute_type_id' => $attributeType->id,
                'shop_id' => $shopId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Find existing attribute group in PrestaShop by name
     *
     * @param PrestaShop8Client $client
     * @param string $name Group name to search
     * @return int|null PrestaShop group ID or null
     */
    protected function findPrestaShopAttributeGroup(PrestaShop8Client $client, string $name): ?int
    {
        try {
            $response = $client->getAttributeGroups([
                'display' => 'full',
                'filter[name]' => "[{$name}]",
            ]);

            if (!isset($response['product_options']) || empty($response['product_options'])) {
                return null;
            }

            $groups = $response['product_options'];

            // Handle single result vs array
            if (isset($groups['id'])) {
                return (int) $groups['id'];
            }

            if (isset($groups[0]['id'])) {
                return (int) $groups[0]['id'];
            }

            return null;

        } catch (\Exception $e) {
            Log::warning('[SyncShopVariantsJob] findPrestaShopAttributeGroup failed', [
                'name' => $name,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Create new attribute group in PrestaShop
     *
     * @param PrestaShop8Client $client
     * @param AttributeType $attributeType
     * @return int|null New PrestaShop group ID
     */
    protected function createPrestaShopAttributeGroup(PrestaShop8Client $client, AttributeType $attributeType): ?int
    {
        try {
            $groupType = $attributeType->display_type === 'color' ? 'color' : 'select';
            $isColorGroup = $attributeType->display_type === 'color' ? '1' : '0';

            $groupData = [
                'is_color_group' => $isColorGroup,
                'group_type' => $groupType,
                'name' => [
                    ['id' => 1, 'value' => $attributeType->name],
                ],
                'public_name' => [
                    ['id' => 1, 'value' => $attributeType->name],
                ],
            ];

            $response = $client->createAttributeGroup($groupData);

            if (isset($response['product_option']['id'])) {
                return (int) $response['product_option']['id'];
            }

            return null;

        } catch (\Exception $e) {
            Log::error('[SyncShopVariantsJob] createPrestaShopAttributeGroup failed', [
                'attribute_type_name' => $attributeType->name,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Find existing attribute value in PrestaShop by group ID and label
     *
     * @param PrestaShop8Client $client
     * @param int $groupId PrestaShop attribute group ID
     * @param string $label Value label to search
     * @return int|null PrestaShop attribute value ID or null
     */
    protected function findPrestaShopAttributeValue(PrestaShop8Client $client, int $groupId, string $label): ?int
    {
        try {
            $response = $client->getAttributeValues([
                'display' => 'full',
                'filter[id_attribute_group]' => $groupId,
                'filter[name]' => "[{$label}]",
            ]);

            if (!isset($response['product_option_values']) || empty($response['product_option_values'])) {
                return null;
            }

            $values = $response['product_option_values'];

            // Handle single result vs array
            if (isset($values['id'])) {
                return (int) $values['id'];
            }

            if (isset($values[0]['id'])) {
                return (int) $values[0]['id'];
            }

            return null;

        } catch (\Exception $e) {
            Log::warning('[SyncShopVariantsJob] findPrestaShopAttributeValue failed', [
                'group_id' => $groupId,
                'label' => $label,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Create new attribute value in PrestaShop
     *
     * @param PrestaShop8Client $client
     * @param int $groupId PrestaShop attribute group ID
     * @param AttributeValue $attributeValue PPM AttributeValue
     * @return int|null New PrestaShop attribute value ID
     */
    protected function createPrestaShopAttributeValue(
        PrestaShop8Client $client,
        int $groupId,
        AttributeValue $attributeValue
    ): ?int {
        try {
            $valueData = [
                'id_attribute_group' => $groupId,
                'name' => [
                    ['id' => 1, 'value' => $attributeValue->label],
                ],
            ];

            // Add color if this is a color attribute
            if (!empty($attributeValue->color_hex)) {
                $valueData['color'] = $attributeValue->color_hex;
            }

            $response = $client->createAttributeValue($valueData);

            if (isset($response['product_option_value']['id'])) {
                Log::info('[SyncShopVariantsJob] Created new attribute value in PrestaShop', [
                    'attribute_value_label' => $attributeValue->label,
                    'prestashop_value_id' => $response['product_option_value']['id'],
                    'group_id' => $groupId,
                ]);

                return (int) $response['product_option_value']['id'];
            }

            return null;

        } catch (\Exception $e) {
            Log::error('[SyncShopVariantsJob] createPrestaShopAttributeValue failed', [
                'attribute_value_label' => $attributeValue->label,
                'group_id' => $groupId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Save attribute group mapping to database
     */
    protected function saveAttributeGroupMapping(int $attributeTypeId, int $shopId, int $psGroupId): void
    {
        DB::table('prestashop_attribute_group_mapping')->updateOrInsert(
            [
                'attribute_type_id' => $attributeTypeId,
                'prestashop_shop_id' => $shopId,
            ],
            [
                'prestashop_attribute_group_id' => $psGroupId,
                'prestashop_label' => null, // Will be filled on next sync
                'sync_status' => 'synced',
                'sync_notes' => 'Auto-created during variant sync',
                'is_synced' => true,
                'last_synced_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    /**
     * Save attribute value mapping to database
     */
    protected function saveAttributeValueMapping(int $attributeValueId, int $shopId, int $psAttributeId): void
    {
        DB::table('prestashop_attribute_value_mapping')->updateOrInsert(
            [
                'attribute_value_id' => $attributeValueId,
                'prestashop_shop_id' => $shopId,
            ],
            [
                'prestashop_attribute_id' => $psAttributeId,
                'prestashop_label' => null, // Will be filled on next sync
                'prestashop_color' => null,
                'sync_status' => 'synced',
                'sync_notes' => 'Auto-created during variant sync',
                'is_synced' => true,
                'last_synced_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}

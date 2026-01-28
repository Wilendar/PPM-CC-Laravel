<?php

namespace App\Jobs\PrestaShop;

use App\Models\Product;
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
                    $this->handleOverrideOperation($client, $shopVariant);
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
            $images = $variantData['images'] ?? [];
            if (!empty($images)) {
                $imageIds = array_column($images, 'prestashop_image_id');
                $imageIds = array_filter($imageIds);
                if (!empty($imageIds)) {
                    $client->setCombinationImages($combinationId, $imageIds);
                }
            }

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
     */
    protected function handleOverrideOperation(
        PrestaShop8Client $client,
        ShopVariant $shopVariant
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

        if (!empty($updates)) {
            $client->updateCombination($combinationId, $updates);
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
                $client->setCombinationAttributes($combinationId, $attributeIds);
            }
        }

        // Update images if provided
        $images = $variantData['images'] ?? [];
        if (!empty($images)) {
            $imageIds = array_column($images, 'prestashop_image_id');
            $imageIds = array_filter($imageIds);
            if (!empty($imageIds)) {
                $client->setCombinationImages($combinationId, $imageIds);
            }
        }

        $shopVariant->markAsSynced();

        Log::info('[SyncShopVariantsJob] OVERRIDE successful', [
            'shop_variant_id' => $shopVariant->id,
            'prestashop_combination_id' => $combinationId,
        ]);
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
            // Skip if value is an array (not PPM format)
            if (is_array($value)) {
                // Maybe it's [['id' => X, 'value_id' => Y]] format from pull
                if (isset($value['prestashop_attribute_id'])) {
                    $prestashopAttributeIds[] = (int) $value['prestashop_attribute_id'];
                } elseif (isset($value['value_id'])) {
                    // Lookup from value_id
                    $psId = $this->lookupPrestaShopAttributeId((int) $value['value_id'], $shopId);
                    if ($psId) {
                        $prestashopAttributeIds[] = $psId;
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
}

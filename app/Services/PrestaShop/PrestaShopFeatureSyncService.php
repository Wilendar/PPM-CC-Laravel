<?php

namespace App\Services\PrestaShop;

use App\Models\FeatureType;
use App\Models\Product;
use App\Models\PrestashopFeatureMapping;
use App\Models\PrestaShopShop;
use App\Services\PrestaShop\Mappers\FeatureValueMapper;
use App\Services\PrestaShop\Transformers\FeatureTransformer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PrestaShopFeatureSyncService
 *
 * ETAP_07e FAZA 4.1.3 - Core service for feature synchronization
 *
 * Coordinates all feature sync operations between PPM and PrestaShop:
 * - Feature types sync (PPM FeatureType <-> PS product_features)
 * - Product features sync (PPM ProductFeature -> PS product associations)
 * - Feature import from PrestaShop to PPM
 * - Conflict resolution for name collisions
 *
 * @package App\Services\PrestaShop
 * @version 1.0
 * @since 2025-12-03
 */
class PrestaShopFeatureSyncService
{
    /**
     * PrestaShop API client
     */
    protected PrestaShop8Client $client;

    /**
     * Feature transformer
     */
    protected FeatureTransformer $transformer;

    /**
     * Feature value mapper
     */
    protected FeatureValueMapper $valueMapper;

    /**
     * Create service instance
     *
     * @param PrestaShop8Client $client
     * @param FeatureTransformer $transformer
     */
    public function __construct(
        PrestaShop8Client $client,
        FeatureTransformer $transformer
    ) {
        $this->client = $client;
        $this->transformer = $transformer;
        $this->valueMapper = new FeatureValueMapper($client);
    }

    /*
    |--------------------------------------------------------------------------
    | FEATURE TYPES SYNC (PPM -> PrestaShop)
    |--------------------------------------------------------------------------
    */

    /**
     * Sync feature types from PPM to PrestaShop
     *
     * Creates/updates ps_feature entries based on FeatureType model.
     * Creates PrestashopFeatureMapping for new features.
     *
     * @param PrestaShopShop $shop Target shop
     * @param array|null $featureTypeIds Optional filter (null = all active mapped)
     * @return array Stats: ['created' => int, 'updated' => int, 'skipped' => int, 'errors' => array]
     */
    public function syncFeatureTypes(PrestaShopShop $shop, ?array $featureTypeIds = null): array
    {
        $stats = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        Log::info('[FEATURE SYNC] Starting feature types sync', [
            'shop_id' => $shop->id,
            'shop_name' => $shop->name,
            'filter_ids' => $featureTypeIds,
        ]);

        // Get feature types to sync
        $query = FeatureType::active();
        if ($featureTypeIds !== null) {
            $query->whereIn('id', $featureTypeIds);
        }
        $featureTypes = $query->get();

        foreach ($featureTypes as $featureType) {
            try {
                $result = $this->syncSingleFeatureType($featureType, $shop);
                $stats[$result['action']]++;
            } catch (\Exception $e) {
                $stats['errors'][] = [
                    'feature_type_id' => $featureType->id,
                    'name' => $featureType->name,
                    'error' => $e->getMessage(),
                ];

                Log::error('[FEATURE SYNC] Feature type sync failed', [
                    'feature_type_id' => $featureType->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('[FEATURE SYNC] Feature types sync completed', [
            'shop_id' => $shop->id,
            'created' => $stats['created'],
            'updated' => $stats['updated'],
            'skipped' => $stats['skipped'],
            'errors' => count($stats['errors']),
        ]);

        return $stats;
    }

    /**
     * Sync single feature type to PrestaShop
     *
     * @param FeatureType $featureType
     * @param PrestaShopShop $shop
     * @return array ['action' => 'created'|'updated'|'skipped', 'ps_feature_id' => int|null]
     */
    protected function syncSingleFeatureType(FeatureType $featureType, PrestaShopShop $shop): array
    {
        // Check existing mapping
        $mapping = PrestashopFeatureMapping::where('feature_type_id', $featureType->id)
            ->where('shop_id', $shop->id)
            ->first();

        // Transform to PS format
        $psData = $this->transformer->transformFeatureTypeToPS($featureType);

        if ($mapping && $mapping->prestashop_feature_id) {
            // Mapping exists - check sync direction
            if (!$mapping->canPushToPrestaShop()) {
                Log::debug('[FEATURE SYNC] Skipping - sync direction does not allow push', [
                    'feature_type_id' => $featureType->id,
                    'sync_direction' => $mapping->sync_direction,
                ]);
                return ['action' => 'skipped', 'ps_feature_id' => $mapping->prestashop_feature_id];
            }

            // Update existing feature in PS
            try {
                $this->client->updateProductFeature($mapping->prestashop_feature_id, $psData);
                $mapping->markSynced();

                Log::debug('[FEATURE SYNC] Updated feature in PS', [
                    'feature_type_id' => $featureType->id,
                    'ps_feature_id' => $mapping->prestashop_feature_id,
                ]);

                // Sync predefined values if applicable
                if ($featureType->requiresValues() && $mapping->auto_create_values) {
                    $this->valueMapper->syncFeatureValues($featureType, $mapping->prestashop_feature_id, $shop);
                }

                return ['action' => 'updated', 'ps_feature_id' => $mapping->prestashop_feature_id];
            } catch (\Exception $e) {
                $mapping->markSyncError($e->getMessage());
                throw $e;
            }
        } else {
            // No mapping - create new feature in PS
            $result = $this->client->createProductFeature($psData);
            $psFeatureId = $result['id'] ?? null;

            if (!$psFeatureId) {
                throw new \Exception('PrestaShop did not return feature ID');
            }

            // Create or update mapping
            if ($mapping) {
                $mapping->update([
                    'prestashop_feature_id' => $psFeatureId,
                    'prestashop_feature_name' => $featureType->name,
                    'last_synced_at' => now(),
                    'last_sync_error' => null,
                ]);
            } else {
                $mapping = PrestashopFeatureMapping::create([
                    'feature_type_id' => $featureType->id,
                    'shop_id' => $shop->id,
                    'prestashop_feature_id' => $psFeatureId,
                    'prestashop_feature_name' => $featureType->name,
                    'sync_direction' => PrestashopFeatureMapping::SYNC_BOTH,
                    'auto_create_values' => true,
                    'is_active' => true,
                    'last_synced_at' => now(),
                ]);
            }

            Log::debug('[FEATURE SYNC] Created feature in PS', [
                'feature_type_id' => $featureType->id,
                'ps_feature_id' => $psFeatureId,
            ]);

            // Sync predefined values if applicable
            if ($featureType->requiresValues()) {
                $this->valueMapper->syncFeatureValues($featureType, $psFeatureId, $shop);
            }

            return ['action' => 'created', 'ps_feature_id' => $psFeatureId];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | PRODUCT FEATURES SYNC (PPM Product -> PrestaShop)
    |--------------------------------------------------------------------------
    */

    /**
     * Sync product features to PrestaShop product
     *
     * Updates associations.product_features in PrestaShop.
     * Creates feature values as needed.
     *
     * @param Product $product PPM Product with features
     * @param PrestaShopShop $shop Target shop
     * @param int $psProductId PrestaShop product ID
     * @return array Stats: ['synced' => int, 'skipped' => int, 'errors' => array]
     */
    public function syncProductFeatures(
        Product $product,
        PrestaShopShop $shop,
        int $psProductId
    ): array {
        $stats = [
            'synced' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        Log::info('[FEATURE SYNC] Starting product features sync', [
            'product_id' => $product->id,
            'sku' => $product->sku,
            'shop_id' => $shop->id,
            'ps_product_id' => $psProductId,
        ]);

        try {
            // Build associations using transformer
            $associations = $this->transformer->buildProductFeaturesAssociations(
                $product->id,
                $shop,
                $this->client
            );

            if (empty($associations)) {
                Log::debug('[FEATURE SYNC] No features to sync for product', [
                    'product_id' => $product->id,
                ]);
                return $stats;
            }

            // Get current product data from PS (for GET-MODIFY-PUT pattern)
            // CRITICAL: PrestaShop PUT requires all required fields (price, etc.)
            $psProduct = $this->client->getProduct($psProductId);

            if (!$psProduct || !isset($psProduct['product'])) {
                throw new \Exception("Product not found in PrestaShop: {$psProductId}");
            }

            // Extract product data from response
            $existingProductData = $psProduct['product'];

            // ETAP_07e FAZA 4.5 - Use SAME structure as GET response for PUT
            // KEY INSIGHT: PrestaShop GET returns product_features in specific format
            // We MUST use EXACTLY the same format for PUT, not double-nest it

            // Log GET structure for debugging
            Log::debug('[FEATURE SYNC] Existing product_features structure from GET', [
                'ps_product_id' => $psProductId,
                'existing_features' => $existingProductData['associations']['product_features'] ?? 'NO FEATURES IN GET',
            ]);

            // ETAP_07e FAZA 4.6 - CRITICAL FIX: Use FLAT indexed array for product_features
            //
            // buildXmlFromArray() handles indexed arrays by:
            // 1. Creating container element with original key (product_features)
            // 2. Singularizing key for child elements (product_features → product_feature)
            // 3. Each array item becomes <product_feature><id>X</id><id_feature_value>Y</id_feature_value></product_feature>
            //
            // CORRECT structure for PHP array:
            // 'product_features' => [
            //     ['id' => 1, 'id_feature_value' => 5],
            //     ['id' => 2, 'id_feature_value' => 10],
            // ]
            //
            // This will produce XML:
            // <product_features>
            //   <product_feature><id>1</id><id_feature_value>5</id_feature_value></product_feature>
            //   <product_feature><id>2</id><id_feature_value>10</id_feature_value></product_feature>
            // </product_features>
            //
            // WRONG structure was: 'product_features' => ['product_feature' => [...]]
            // This creates double nesting!

            Log::debug('[FEATURE SYNC] Product features structure for PUT (FLAT indexed array)', [
                'ps_product_id' => $psProductId,
                'associations_count' => count($associations),
                'first_association' => $associations[0] ?? 'EMPTY',
                'associations_sample' => array_slice($associations, 0, 3),
            ]);

            // ETAP_07e FAZA 4.6 - CRITICAL FIX: PrestaShop PUT replaces ENTIRE product
            // We MUST preserve ALL existing fields, not just a few!
            // Previous "minimal" approach caused products to be wiped (reference, name, etc. became empty)
            //
            // SOLUTION: Use GET-MODIFY-PUT pattern - start with ALL existing data, only modify associations

            // Start with ALL existing product data to preserve everything
            $updateData = $existingProductData;

            // Only override the associations we want to update (product_features)
            // CRITICAL: product_features must be FLAT indexed array, NOT nested with 'product_feature' key!
            if (!isset($updateData['associations'])) {
                $updateData['associations'] = [];
            }
            $updateData['associations']['product_features'] = $associations;

            // Remove read-only and problematic fields that PrestaShop doesn't accept in PUT
            $readOnlyFields = [
                'manufacturer_name',
                'quantity',
                'type',
                'id_shop_default',
                'position_in_category',
                'date_add',
                'date_upd',
                'pack_stock_type',
            ];
            foreach ($readOnlyFields as $field) {
                unset($updateData[$field]);
            }

            // Clean up multilang fields - remove 'language' wrapper if present (PS returns it but doesn't accept it back)
            $multilangFields = [
                'name', 'description', 'description_short', 'link_rewrite',
                'meta_title', 'meta_description', 'meta_keywords',
                'available_now', 'available_later', 'delivery_in_stock', 'delivery_out_stock',
            ];
            foreach ($multilangFields as $field) {
                if (isset($updateData[$field]['language'])) {
                    $updateData[$field] = $updateData[$field]['language'];
                }
            }

            // Ensure ID is set
            $updateData['id'] = $psProductId;

            Log::debug('[FEATURE SYNC] Full update data structure (GET-MODIFY-PUT pattern)', [
                'ps_product_id' => $psProductId,
                'update_data_keys' => array_keys($updateData),
                'associations_keys' => array_keys($updateData['associations'] ?? []),
                'reference_preserved' => $updateData['reference'] ?? 'MISSING!',
                'name_preserved' => isset($updateData['name']) ? 'YES' : 'MISSING!',
            ]);

            // Update product in PS with FULL data + updated features
            $this->client->updateProduct($psProductId, $updateData);

            $stats['synced'] = count($associations);

            Log::info('[FEATURE SYNC] Product features synced', [
                'product_id' => $product->id,
                'ps_product_id' => $psProductId,
                'features_synced' => $stats['synced'],
            ]);

        } catch (\Exception $e) {
            $stats['errors'][] = [
                'product_id' => $product->id,
                'error' => $e->getMessage(),
            ];

            Log::error('[FEATURE SYNC] Product features sync failed', [
                'product_id' => $product->id,
                'ps_product_id' => $psProductId,
                'error' => $e->getMessage(),
            ]);
        }

        return $stats;
    }

    /**
     * Batch sync product features for multiple products
     *
     * @param array $productIds PPM Product IDs
     * @param PrestaShopShop $shop Target shop
     * @param callable|null $onProgress Progress callback (int $current, int $total)
     * @return array Aggregated stats
     */
    public function syncProductFeaturesBatch(
        array $productIds,
        PrestaShopShop $shop,
        ?callable $onProgress = null
    ): array {
        $totalStats = [
            'total' => count($productIds),
            'processed' => 0,
            'synced' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        foreach ($productIds as $index => $productId) {
            $product = Product::find($productId);
            if (!$product) {
                $totalStats['skipped']++;
                continue;
            }

            // Get PS product ID from shop data
            $shopData = $product->shopData()->where('shop_id', $shop->id)->first();
            if (!$shopData || !$shopData->external_id) {
                $totalStats['skipped']++;
                Log::debug('[FEATURE SYNC] No external ID for product', [
                    'product_id' => $productId,
                    'shop_id' => $shop->id,
                ]);
                continue;
            }

            $result = $this->syncProductFeatures($product, $shop, (int) $shopData->external_id);

            $totalStats['processed']++;
            $totalStats['synced'] += $result['synced'];
            $totalStats['errors'] = array_merge($totalStats['errors'], $result['errors']);

            if ($onProgress) {
                $onProgress($index + 1, count($productIds));
            }

            // Rate limiting - small delay between products
            usleep(200000); // 200ms
        }

        return $totalStats;
    }

    /*
    |--------------------------------------------------------------------------
    | IMPORT FROM PRESTASHOP
    |--------------------------------------------------------------------------
    */

    /**
     * Import features from PrestaShop to PPM
     *
     * Creates FeatureType + PrestashopFeatureMapping for unmapped PS features.
     *
     * @param PrestaShopShop $shop Source shop
     * @param bool $overwriteExisting Update existing PPM features?
     * @return array Stats: ['imported' => int, 'updated' => int, 'skipped' => int, 'errors' => array]
     */
    public function importFeaturesFromPrestaShop(
        PrestaShopShop $shop,
        bool $overwriteExisting = false
    ): array {
        $stats = [
            'imported' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        Log::info('[FEATURE SYNC] Starting import from PrestaShop', [
            'shop_id' => $shop->id,
            'shop_name' => $shop->name,
            'overwrite' => $overwriteExisting,
        ]);

        try {
            // Fetch all features from PS
            $psFeatures = $this->client->getProductFeatures(['display' => 'full']);

            Log::debug('[FEATURE SYNC] Fetched PS features', [
                'count' => count($psFeatures),
            ]);

            // Get existing mappings for this shop
            $existingMappings = PrestashopFeatureMapping::where('shop_id', $shop->id)
                ->pluck('feature_type_id', 'prestashop_feature_id')
                ->toArray();

            foreach ($psFeatures as $psFeature) {
                try {
                    $result = $this->importSingleFeature($psFeature, $shop, $existingMappings, $overwriteExisting);
                    $stats[$result]++;
                } catch (\Exception $e) {
                    $stats['errors'][] = [
                        'ps_feature_id' => $psFeature['id'] ?? 'unknown',
                        'error' => $e->getMessage(),
                    ];

                    Log::error('[FEATURE SYNC] Feature import failed', [
                        'ps_feature_id' => $psFeature['id'] ?? 'unknown',
                        'error' => $e->getMessage(),
                    ]);
                }
            }

        } catch (\Exception $e) {
            $stats['errors'][] = [
                'error' => 'Failed to fetch features: ' . $e->getMessage(),
            ];

            Log::error('[FEATURE SYNC] Import failed', [
                'shop_id' => $shop->id,
                'error' => $e->getMessage(),
            ]);
        }

        Log::info('[FEATURE SYNC] Import from PrestaShop completed', [
            'shop_id' => $shop->id,
            'imported' => $stats['imported'],
            'updated' => $stats['updated'],
            'skipped' => $stats['skipped'],
            'errors' => count($stats['errors']),
        ]);

        return $stats;
    }

    /**
     * Import single feature from PrestaShop
     *
     * @param array $psFeature PS feature data
     * @param PrestaShopShop $shop
     * @param array $existingMappings Map of ps_id => feature_type_id
     * @param bool $overwriteExisting
     * @return string Action taken: 'imported', 'updated', 'skipped'
     */
    protected function importSingleFeature(
        array $psFeature,
        PrestaShopShop $shop,
        array $existingMappings,
        bool $overwriteExisting
    ): string {
        $psFeatureId = (int) ($psFeature['id'] ?? 0);
        if (!$psFeatureId) {
            return 'skipped';
        }

        // Check if already mapped
        if (isset($existingMappings[$psFeatureId])) {
            if (!$overwriteExisting) {
                return 'skipped';
            }

            // Update existing feature type
            $featureType = FeatureType::find($existingMappings[$psFeatureId]);
            if ($featureType) {
                $featureData = $this->transformer->transformPSToFeatureType($psFeature);
                $featureType->update([
                    'prestashop_name' => $featureData['prestashop_name'],
                    // Only update name if different
                    'name' => $featureType->name !== $featureData['prestashop_name']
                        ? $featureData['name']
                        : $featureType->name,
                ]);

                // Import feature values
                $this->valueMapper->importFeatureValuesFromPS($psFeatureId, $featureType, $shop);

                return 'updated';
            }
        }

        // Create new feature type
        $featureData = $this->transformer->transformPSToFeatureType($psFeature);

        // Check for name collision
        $existingByCode = FeatureType::where('code', $featureData['code'])->first();
        if ($existingByCode) {
            // Generate unique code
            $featureData['code'] = $featureData['code'] . '_' . $shop->id;
        }

        DB::beginTransaction();
        try {
            $featureType = FeatureType::create($featureData);

            // Create mapping
            PrestashopFeatureMapping::create([
                'feature_type_id' => $featureType->id,
                'shop_id' => $shop->id,
                'prestashop_feature_id' => $psFeatureId,
                'prestashop_feature_name' => $featureData['prestashop_name'],
                'sync_direction' => PrestashopFeatureMapping::SYNC_PS_TO_PPM,
                'auto_create_values' => true,
                'is_active' => true,
                'last_synced_at' => now(),
            ]);

            // Import feature values
            $this->valueMapper->importFeatureValuesFromPS($psFeatureId, $featureType, $shop);

            DB::commit();

            Log::debug('[FEATURE SYNC] Imported feature from PS', [
                'ps_feature_id' => $psFeatureId,
                'feature_type_id' => $featureType->id,
                'name' => $featureType->name,
            ]);

            return 'imported';

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | CONFLICT RESOLUTION
    |--------------------------------------------------------------------------
    */

    /**
     * Resolve conflicts when same feature name exists in PPM and PS
     *
     * Strategies:
     * - auto_merge: Merge to first found
     * - manual_mapping: Skip, require user action
     * - create_new: Create new feature in PPM
     *
     * @param array $conflicts Array of conflict descriptors
     * @param string $strategy Conflict resolution strategy
     * @return array Resolution results
     */
    public function resolveConflicts(array $conflicts, string $strategy = 'auto_merge'): array
    {
        $results = [
            'resolved' => 0,
            'pending' => 0,
            'details' => [],
        ];

        foreach ($conflicts as $conflict) {
            $result = match ($strategy) {
                'auto_merge' => $this->resolveByMerge($conflict),
                'manual_mapping' => $this->markForManualResolution($conflict),
                'create_new' => $this->resolveByCreatingNew($conflict),
                default => ['status' => 'skipped', 'reason' => 'Unknown strategy'],
            };

            $results['details'][] = array_merge($conflict, $result);

            if ($result['status'] === 'resolved') {
                $results['resolved']++;
            } else {
                $results['pending']++;
            }
        }

        return $results;
    }

    /**
     * Resolve conflict by merging to existing feature
     */
    protected function resolveByMerge(array $conflict): array
    {
        $featureTypeId = $conflict['feature_type_id'] ?? null;
        $psFeatureId = $conflict['ps_feature_id'] ?? null;
        $shopId = $conflict['shop_id'] ?? null;

        if (!$featureTypeId || !$psFeatureId || !$shopId) {
            return ['status' => 'error', 'reason' => 'Missing required conflict data'];
        }

        try {
            PrestashopFeatureMapping::updateOrCreate(
                [
                    'feature_type_id' => $featureTypeId,
                    'shop_id' => $shopId,
                ],
                [
                    'prestashop_feature_id' => $psFeatureId,
                    'sync_direction' => PrestashopFeatureMapping::SYNC_BOTH,
                    'is_active' => true,
                    'last_synced_at' => now(),
                ]
            );

            return ['status' => 'resolved', 'action' => 'merged'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'reason' => $e->getMessage()];
        }
    }

    /**
     * Mark conflict for manual resolution
     */
    protected function markForManualResolution(array $conflict): array
    {
        // In real implementation, this could create a notification
        // or add to a conflicts queue for admin review
        return ['status' => 'pending', 'action' => 'marked_for_manual'];
    }

    /**
     * Resolve conflict by creating new feature type
     */
    protected function resolveByCreatingNew(array $conflict): array
    {
        $psFeature = $conflict['ps_feature'] ?? null;
        $shopId = $conflict['shop_id'] ?? null;

        if (!$psFeature || !$shopId) {
            return ['status' => 'error', 'reason' => 'Missing PS feature data'];
        }

        $shop = PrestaShopShop::find($shopId);
        if (!$shop) {
            return ['status' => 'error', 'reason' => 'Shop not found'];
        }

        try {
            $featureData = $this->transformer->transformPSToFeatureType($psFeature);

            // Ensure unique code
            $baseCode = $featureData['code'];
            $counter = 1;
            while (FeatureType::where('code', $featureData['code'])->exists()) {
                $featureData['code'] = $baseCode . '_' . $counter++;
            }

            $featureType = FeatureType::create($featureData);

            PrestashopFeatureMapping::create([
                'feature_type_id' => $featureType->id,
                'shop_id' => $shopId,
                'prestashop_feature_id' => (int) $psFeature['id'],
                'prestashop_feature_name' => $featureData['prestashop_name'],
                'sync_direction' => PrestashopFeatureMapping::SYNC_PS_TO_PPM,
                'is_active' => true,
                'last_synced_at' => now(),
            ]);

            return [
                'status' => 'resolved',
                'action' => 'created_new',
                'feature_type_id' => $featureType->id,
            ];
        } catch (\Exception $e) {
            return ['status' => 'error', 'reason' => $e->getMessage()];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | HELPER METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Get sync status for a feature type and shop
     *
     * @param FeatureType $featureType
     * @param PrestaShopShop $shop
     * @return array Status info
     */
    public function getFeatureSyncStatus(FeatureType $featureType, PrestaShopShop $shop): array
    {
        $mapping = PrestashopFeatureMapping::where('feature_type_id', $featureType->id)
            ->where('shop_id', $shop->id)
            ->first();

        if (!$mapping) {
            return [
                'status' => 'unmapped',
                'label' => 'Nie zmapowane',
                'ps_feature_id' => null,
                'last_synced' => null,
            ];
        }

        return array_merge(
            $mapping->getSyncStatus(),
            [
                'ps_feature_id' => $mapping->prestashop_feature_id,
                'last_synced' => $mapping->last_synced_at?->format('Y-m-d H:i'),
                'sync_direction' => $mapping->getSyncDirectionLabel(),
            ]
        );
    }

    /**
     * Get all unmapped features for a shop
     *
     * @param PrestaShopShop $shop
     * @return array ['ppm_unmapped' => [], 'ps_unmapped' => []]
     */
    public function getUnmappedFeatures(PrestaShopShop $shop): array
    {
        // Get mapped PPM feature type IDs
        $mappedPpmIds = PrestashopFeatureMapping::where('shop_id', $shop->id)
            ->where('is_active', true)
            ->pluck('feature_type_id')
            ->toArray();

        // Get unmapped PPM features
        $ppmUnmapped = FeatureType::active()
            ->whereNotIn('id', $mappedPpmIds)
            ->get(['id', 'code', 'name', 'value_type'])
            ->toArray();

        // Get mapped PS feature IDs
        $mappedPsIds = PrestashopFeatureMapping::where('shop_id', $shop->id)
            ->where('is_active', true)
            ->pluck('prestashop_feature_id')
            ->toArray();

        // Get PS features and filter unmapped
        $psUnmapped = [];
        try {
            $psFeatures = $this->client->getProductFeatures(['display' => 'full']);
            foreach ($psFeatures as $psFeature) {
                $psId = (int) ($psFeature['id'] ?? 0);
                if ($psId && !in_array($psId, $mappedPsIds)) {
                    $psUnmapped[] = [
                        'id' => $psId,
                        'name' => $this->transformer->extractMultilangValue($psFeature['name'] ?? []),
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::error('[FEATURE SYNC] Failed to fetch PS features for unmapped check', [
                'error' => $e->getMessage(),
            ]);
        }

        return [
            'ppm_unmapped' => $ppmUnmapped,
            'ps_unmapped' => $psUnmapped,
        ];
    }
}

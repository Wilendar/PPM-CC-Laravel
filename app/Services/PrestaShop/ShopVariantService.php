<?php

namespace App\Services\PrestaShop;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShopVariant;
use App\Models\PrestaShopShop;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ShopVariantService - Per-Shop Variant Management
 *
 * ETAP_05c: Manages shop-specific variants with 4 operation types:
 * - ADD: New variant ONLY for this shop (variant_id = null)
 * - OVERRIDE: Modified existing variant for this shop
 * - DELETE: Hidden variant in this shop
 * - INHERIT: Use default from product_variants (no override)
 *
 * @package App\Services\PrestaShop
 */
class ShopVariantService
{
    public function __construct(
        protected PrestaShop8Client $prestaShopClient
    ) {}

    /**
     * Get all variants for specific shop (base + shop-specific merged)
     *
     * @param Product $product
     * @param int $shopId
     * @return Collection
     */
    public function getVariantsForShop(Product $product, int $shopId): Collection
    {
        return $product->getVariantsForShop($shopId);
    }

    /**
     * Pull variants LIVE from PrestaShop API
     *
     * Called when entering shop tab to get current state from PrestaShop
     *
     * @param Product $product
     * @param int $shopId
     * @return array ['variants' => Collection, 'synced' => bool, 'error' => ?string]
     */
    public function pullShopVariants(Product $product, int $shopId): array
    {
        Log::info('[ShopVariantService] pullShopVariants START', [
            'product_id' => $product->id,
            'shop_id' => $shopId,
        ]);

        try {
            $shop = PrestaShopShop::find($shopId);
            if (!$shop) {
                return [
                    'variants' => collect(),
                    'synced' => false,
                    'error' => 'Shop not found',
                ];
            }

            // Get PrestaShop product ID from shop data
            $shopData = $product->dataForShop($shopId)->first();
            $prestashopProductId = $shopData?->prestashop_product_id;

            if (!$prestashopProductId) {
                Log::info('[ShopVariantService] No PrestaShop product ID - returning empty', [
                    'product_id' => $product->id,
                    'shop_id' => $shopId,
                    'note' => 'Product not synced to this shop yet',
                ]);

                // ETAP_05c: Return EMPTY when product not synced to PrestaShop
                // User needs to sync product first before seeing variants
                return [
                    'variants' => collect(),
                    'synced' => false,
                    'error' => 'Produkt nie jest jeszcze zsynchronizowany z tym sklepem',
                ];
            }

            // Initialize client for this shop
            $client = $this->getClientForShop($shop);

            // Pull combinations from PrestaShop API
            $combinations = $client->getCombinations($prestashopProductId);

            if (empty($combinations)) {
                Log::info('[ShopVariantService] No combinations in PrestaShop - returning empty', [
                    'prestashop_product_id' => $prestashopProductId,
                    'note' => 'Shop TAB shows ONLY PrestaShop variants, not local PPM variants',
                ]);

                // ETAP_05c: Return EMPTY when PrestaShop has no combinations
                // Shop TAB shows ONLY PrestaShop variants, not local PPM variants
                return [
                    'variants' => collect(),
                    'synced' => true,
                    'error' => null,
                ];
            }

            // FIX 2025-12-08: Fetch attribute names for all combinations
            $attributeNamesMap = $this->fetchAttributeNamesForCombinations($client, $combinations);

            // FIX 2025-12-08: Fetch product images as fallback for combinations without images
            $productImages = [];
            try {
                $productImages = $client->getProductImages($prestashopProductId);
            } catch (\Exception $e) {
                Log::debug('[ShopVariantService] Could not fetch product images', ['error' => $e->getMessage()]);
            }

            // Map PrestaShop combinations to our variant structure
            // FIX 2025-12-08: Pass shop URL for image URL building + product images as fallback
            $mappedVariants = $this->mapCombinationsToVariants(
                $product,
                $shopId,
                $combinations,
                $attributeNamesMap,
                $shop->url,
                $prestashopProductId,
                $productImages
            );

            // Update ShopVariant records based on pulled data (with attribute names)
            $this->syncShopVariantsFromPull($product, $shopId, $combinations, $attributeNamesMap);

            Log::info('[ShopVariantService] pullShopVariants SUCCESS', [
                'product_id' => $product->id,
                'shop_id' => $shopId,
                'variants_count' => $mappedVariants->count(),
            ]);

            return [
                'variants' => $mappedVariants,
                'synced' => true,
                'error' => null,
            ];

        } catch (\Exception $e) {
            Log::error('[ShopVariantService] pullShopVariants ERROR', [
                'product_id' => $product->id,
                'shop_id' => $shopId,
                'error' => $e->getMessage(),
            ]);

            return [
                'variants' => $this->getVariantsForShop($product, $shopId),
                'synced' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create shop-only variant (ADD operation)
     *
     * @param Product $product
     * @param int $shopId
     * @param array $data
     * @return ShopVariant
     */
    public function createShopOnlyVariant(Product $product, int $shopId, array $data): ShopVariant
    {
        Log::info('[ShopVariantService] createShopOnlyVariant', [
            'product_id' => $product->id,
            'shop_id' => $shopId,
            'sku' => $data['sku'] ?? null,
        ]);

        return ShopVariant::create([
            'shop_id' => $shopId,
            'product_id' => $product->id,
            'variant_id' => null, // NULL = shop-only variant
            'operation_type' => 'ADD',
            'variant_data' => $this->normalizeVariantData($data),
            'sync_status' => 'pending',
        ]);
    }

    /**
     * Override existing variant for shop (OVERRIDE operation)
     *
     * @param Product $product
     * @param int $shopId
     * @param int $variantId
     * @param array $overrides
     * @return ShopVariant
     */
    public function overrideVariant(
        Product $product,
        int $shopId,
        int $variantId,
        array $overrides
    ): ShopVariant {
        Log::info('[ShopVariantService] overrideVariant', [
            'product_id' => $product->id,
            'shop_id' => $shopId,
            'variant_id' => $variantId,
        ]);

        // Check if override already exists
        $existing = ShopVariant::where('shop_id', $shopId)
            ->where('product_id', $product->id)
            ->where('variant_id', $variantId)
            ->first();

        if ($existing) {
            // Update existing override
            $existingData = $existing->variant_data ?? [];
            $existing->update([
                'operation_type' => 'OVERRIDE',
                'variant_data' => array_merge($existingData, $overrides),
                'sync_status' => 'pending',
            ]);
            return $existing->fresh();
        }

        // Create new override
        return ShopVariant::create([
            'shop_id' => $shopId,
            'product_id' => $product->id,
            'variant_id' => $variantId,
            'operation_type' => 'OVERRIDE',
            'variant_data' => $this->normalizeVariantData($overrides),
            'sync_status' => 'pending',
        ]);
    }

    /**
     * Hide variant in shop (DELETE operation)
     *
     * @param Product $product
     * @param int $shopId
     * @param int $variantId
     * @return ShopVariant
     */
    public function hideVariantInShop(Product $product, int $shopId, int $variantId): ShopVariant
    {
        Log::info('[ShopVariantService] hideVariantInShop', [
            'product_id' => $product->id,
            'shop_id' => $shopId,
            'variant_id' => $variantId,
        ]);

        return ShopVariant::updateOrCreate(
            [
                'shop_id' => $shopId,
                'product_id' => $product->id,
                'variant_id' => $variantId,
            ],
            [
                'operation_type' => 'DELETE',
                'variant_data' => null,
                'sync_status' => 'pending',
            ]
        );
    }

    /**
     * Unhide variant in shop (revert to INHERIT)
     *
     * @param Product $product
     * @param int $shopId
     * @param int $variantId
     * @return bool
     */
    public function unhideVariantInShop(Product $product, int $shopId, int $variantId): bool
    {
        Log::info('[ShopVariantService] unhideVariantInShop', [
            'product_id' => $product->id,
            'shop_id' => $shopId,
            'variant_id' => $variantId,
        ]);

        $shopVariant = ShopVariant::where('shop_id', $shopId)
            ->where('product_id', $product->id)
            ->where('variant_id', $variantId)
            ->first();

        if ($shopVariant) {
            // If it was just a DELETE, we can remove it (back to INHERIT)
            // If it has prestashop_combination_id, mark as INHERIT
            if ($shopVariant->prestashop_combination_id) {
                $shopVariant->update([
                    'operation_type' => 'INHERIT',
                    'sync_status' => 'synced',
                ]);
            } else {
                $shopVariant->delete();
            }
            return true;
        }

        return false;
    }

    /**
     * Commit all pending variant changes for shop
     *
     * @param Product $product
     * @param int $shopId
     * @param array $creates Array of variants to create (ADD)
     * @param array $updates Array of variants to update (OVERRIDE)
     * @param array $deletes Array of variant IDs to delete (DELETE)
     * @return array ['success' => bool, 'shopVariants' => Collection]
     */
    public function commitShopVariants(
        Product $product,
        int $shopId,
        array $creates = [],
        array $updates = [],
        array $deletes = []
    ): array {
        Log::info('[ShopVariantService] commitShopVariants START', [
            'product_id' => $product->id,
            'shop_id' => $shopId,
            'creates_count' => count($creates),
            'updates_count' => count($updates),
            'deletes_count' => count($deletes),
        ]);

        $createdVariants = collect();

        DB::beginTransaction();
        try {
            // 1. Process CREATES (ADD operations)
            foreach ($creates as $createData) {
                $shopVariant = $this->createShopOnlyVariant($product, $shopId, $createData);
                $createdVariants->push($shopVariant);
            }

            // 2. Process UPDATES (OVERRIDE operations)
            foreach ($updates as $variantId => $updateData) {
                $shopVariant = $this->overrideVariant($product, $shopId, $variantId, $updateData);
                $createdVariants->push($shopVariant);
            }

            // 3. Process DELETES (DELETE operations)
            foreach ($deletes as $variantId) {
                $shopVariant = $this->hideVariantInShop($product, $shopId, $variantId);
                $createdVariants->push($shopVariant);
            }

            DB::commit();

            Log::info('[ShopVariantService] commitShopVariants SUCCESS', [
                'product_id' => $product->id,
                'shop_id' => $shopId,
                'total_committed' => $createdVariants->count(),
            ]);

            return [
                'success' => true,
                'shopVariants' => $createdVariants,
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('[ShopVariantService] commitShopVariants ERROR', [
                'product_id' => $product->id,
                'shop_id' => $shopId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'shopVariants' => collect(),
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get pending shop variants count for shop
     *
     * @param Product $product
     * @param int $shopId
     * @return int
     */
    public function getPendingCount(Product $product, int $shopId): int
    {
        return ShopVariant::where('shop_id', $shopId)
            ->where('product_id', $product->id)
            ->where('sync_status', 'pending')
            ->count();
    }

    /**
     * Check if shop has any variant overrides
     *
     * @param Product $product
     * @param int $shopId
     * @return bool
     */
    public function hasOverrides(Product $product, int $shopId): bool
    {
        return ShopVariant::where('shop_id', $shopId)
            ->where('product_id', $product->id)
            ->where('operation_type', '!=', 'INHERIT')
            ->exists();
    }

    /*
    |--------------------------------------------------------------------------
    | ATTRIBUTE NAME FETCHING (FIX 2025-12-08)
    |--------------------------------------------------------------------------
    */

    /**
     * Fetch attribute names for all combinations from PrestaShop API
     *
     * FIX 2025-12-08: PrestaShop combinations API returns only attribute IDs,
     * not names. This method fetches names separately.
     *
     * @param PrestaShop8Client $client
     * @param array $combinations
     * @return array Map of attribute_id => ['name' => string, 'group_name' => string]
     */
    protected function fetchAttributeNamesForCombinations(PrestaShop8Client $client, array $combinations): array
    {
        // Collect all unique attribute IDs from combinations
        $attributeIds = [];
        foreach ($combinations as $combination) {
            $attrs = $combination['associations']['product_option_values'] ?? [];
            foreach ($attrs as $attr) {
                $attrId = (int) ($attr['id'] ?? 0);
                if ($attrId > 0) {
                    $attributeIds[$attrId] = true;
                }
            }
        }

        if (empty($attributeIds)) {
            return [];
        }

        $attributeIds = array_keys($attributeIds);
        $namesMap = [];

        Log::debug('[ShopVariantService] Fetching attribute names', [
            'attribute_ids' => $attributeIds,
        ]);

        // Fetch each attribute individually (PrestaShop API doesn't support display=full with filter)
        foreach ($attributeIds as $attrId) {
            try {
                $response = $client->getAttributeValue($attrId);
                $data = $response['product_option_value'] ?? [];

                if (!empty($data)) {
                    // Also fetch group name
                    $groupId = (int) ($data['id_attribute_group'] ?? 0);
                    $groupName = '';

                    if ($groupId > 0) {
                        try {
                            $groupResponse = $client->getAttributeGroup($groupId);
                            $groupData = $groupResponse['product_option'] ?? [];
                            $groupName = $groupData['name'] ?? '';
                        } catch (\Exception $e) {
                            // Ignore group fetch errors
                        }
                    }

                    $namesMap[$attrId] = [
                        'name' => $data['name'] ?? '',
                        'group_name' => $groupName,
                        'color' => $data['color'] ?? null,
                    ];
                }
            } catch (\Exception $e) {
                Log::warning('[ShopVariantService] Failed to fetch attribute', [
                    'attribute_id' => $attrId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::debug('[ShopVariantService] Attribute names fetched', [
            'count' => count($namesMap),
            'map' => $namesMap,
        ]);

        return $namesMap;
    }

    /*
    |--------------------------------------------------------------------------
    | PRIVATE HELPERS
    |--------------------------------------------------------------------------
    */

    /**
     * Get PrestaShop client for specific shop
     */
    protected function getClientForShop(PrestaShopShop $shop): PrestaShop8Client
    {
        // Configure client with shop instance
        return new PrestaShop8Client($shop);
    }

    /**
     * Get base variants formatted as INHERIT
     */
    protected function getBaseVariantsAsInherit(Product $product): Collection
    {
        $baseVariants = $product->variants ?? collect();

        return $baseVariants->map(function ($variant) {
            return (object) [
                'id' => $variant->id,
                'sku' => $variant->sku,
                'name' => $variant->name,
                'is_active' => $variant->is_active,
                'is_default' => $variant->is_default,
                'position' => $variant->position,
                'attributes' => $variant->attributes,
                'images' => $variant->images,
                'operation_type' => 'INHERIT',
                'sync_status' => 'synced',
            ];
        });
    }

    /**
     * Map PrestaShop combinations to variant structure
     *
     * FIX 2025-12-08: Now accepts $attributeNamesMap for proper attribute name display
     * FIX 2025-12-08: Now accepts $shopUrl and $prestashopProductId for image URLs
     * FIX 2025-12-08: Now accepts $productImages as fallback when combination has no images
     *
     * @param Product $product
     * @param int $shopId
     * @param array $combinations
     * @param array $attributeNamesMap Map of attribute_id => ['name' => string, 'group_name' => string]
     * @param string|null $shopUrl Shop base URL for image URLs
     * @param int|null $prestashopProductId PrestaShop product ID for image URLs
     * @param array $productImages Product images as fallback
     * @return Collection
     */
    protected function mapCombinationsToVariants(
        Product $product,
        int $shopId,
        array $combinations,
        array $attributeNamesMap = [],
        ?string $shopUrl = null,
        ?int $prestashopProductId = null,
        array $productImages = []
    ): Collection {
        $result = collect();

        // Get existing shop overrides
        $shopOverrides = ShopVariant::where('shop_id', $shopId)
            ->where('product_id', $product->id)
            ->get()
            ->keyBy('prestashop_combination_id');

        foreach ($combinations as $combination) {
            $psId = (int) ($combination['id'] ?? $combination['id_product_attribute'] ?? 0);

            // Check if we have local override
            $override = $shopOverrides->get($psId);

            // FIX 2025-12-09: For OVERRIDE variants (linked to local PPM variant),
            // fallback to local variant SKU if PrestaShop reference is empty
            $psSku = $combination['reference'] ?? '';
            $localVariantId = $override?->variant_id;

            // If SKU is empty and we have a local variant, use local variant's SKU
            if (empty($psSku) && $localVariantId) {
                $localVariant = $product->variants->firstWhere('id', $localVariantId);
                if ($localVariant) {
                    $psSku = $localVariant->sku ?? '';
                }
            }

            $variantData = [
                'id' => $override?->variant_id ?? 'ps_' . $psId,
                'shop_variant_id' => $override?->id,
                'prestashop_combination_id' => $psId,
                'sku' => $psSku,
                'name' => $this->buildCombinationName($combination, $attributeNamesMap),
                'is_active' => (bool) ($combination['active'] ?? true),
                'is_default' => (bool) ($combination['default_on'] ?? false),
                'position' => (int) ($combination['position'] ?? 0),
                'price_impact' => (float) ($combination['price'] ?? 0),
                'weight_impact' => (float) ($combination['weight'] ?? 0),
                'quantity' => (int) ($combination['quantity'] ?? 0),
                'minimal_quantity' => (int) ($combination['minimal_quantity'] ?? 1),
                'attributes' => $this->extractCombinationAttributes($combination, $attributeNamesMap),
                'images' => $this->extractCombinationImages($combination, $shopUrl, $prestashopProductId, $productImages),
                'operation_type' => $override?->operation_type ?? 'INHERIT',
                'sync_status' => $override?->sync_status ?? 'synced',
            ];

            $result->push((object) $variantData);
        }

        return $result->sortBy('position');
    }

    /**
     * Sync ShopVariant records based on pulled PrestaShop data
     *
     * FIX 2025-12-08: Now updates variant_data with proper attribute names
     *
     * @param Product $product
     * @param int $shopId
     * @param array $combinations
     * @param array $attributeNamesMap Map of attribute_id => ['name' => string, 'group_name' => string]
     */
    protected function syncShopVariantsFromPull(
        Product $product,
        int $shopId,
        array $combinations,
        array $attributeNamesMap = []
    ): void {
        $existingShopVariants = ShopVariant::where('shop_id', $shopId)
            ->where('product_id', $product->id)
            ->get()
            ->keyBy('prestashop_combination_id');

        $existingIds = $existingShopVariants->pluck('prestashop_combination_id')
            ->filter()
            ->toArray();

        $pulledIds = array_map(function ($c) {
            return (int) ($c['id'] ?? $c['id_product_attribute'] ?? 0);
        }, $combinations);

        // Mark non-existing combinations as needing sync (might have been deleted in PS)
        $deletedInPs = array_diff($existingIds, $pulledIds);

        if (!empty($deletedInPs)) {
            ShopVariant::where('shop_id', $shopId)
                ->where('product_id', $product->id)
                ->whereIn('prestashop_combination_id', $deletedInPs)
                ->update(['sync_status' => 'pending']);

            Log::info('[ShopVariantService] Found deleted combinations in PrestaShop', [
                'deleted_count' => count($deletedInPs),
            ]);
        }

        // FIX 2025-12-08: Update existing ShopVariant records with proper attribute names
        foreach ($combinations as $combination) {
            $psId = (int) ($combination['id'] ?? $combination['id_product_attribute'] ?? 0);
            $shopVariant = $existingShopVariants->get($psId);

            if ($shopVariant) {
                // Build updated variant_data with proper attribute names
                $variantData = $shopVariant->variant_data ?? [];
                $variantData['name'] = $this->buildCombinationName($combination, $attributeNamesMap);
                $variantData['attributes'] = $this->extractCombinationAttributes($combination, $attributeNamesMap);
                $variantData['sku'] = $combination['reference'] ?? $variantData['sku'] ?? '';

                $shopVariant->update([
                    'variant_data' => $variantData,
                ]);
            }
        }

        Log::debug('[ShopVariantService] syncShopVariantsFromPull updated variant_data', [
            'product_id' => $product->id,
            'shop_id' => $shopId,
            'updated_count' => count($combinations),
        ]);
    }

    /**
     * Build combination name from attributes
     *
     * FIX 2025-12-08: Uses $attributeNamesMap for proper names
     *
     * @param array $combination
     * @param array $attributeNamesMap Map of attribute_id => ['name' => string, 'group_name' => string]
     * @return string
     */
    protected function buildCombinationName(array $combination, array $attributeNamesMap = []): string
    {
        $attributes = $combination['associations']['product_option_values'] ?? [];

        if (empty($attributes)) {
            return $combination['reference'] ?? 'Variant';
        }

        $names = [];
        foreach ($attributes as $attr) {
            $attrId = (int) ($attr['id'] ?? 0);

            // FIX 2025-12-08: Use fetched name from map, fallback to API data, then ID
            if (isset($attributeNamesMap[$attrId]['name'])) {
                $names[] = $attributeNamesMap[$attrId]['name'];
            } elseif (!empty($attr['name'])) {
                $names[] = $attr['name'];
            } else {
                $names[] = (string) $attrId;
            }
        }

        return implode(' - ', $names);
    }

    /**
     * Extract attributes from PrestaShop combination
     *
     * FIX 2025-12-08: Uses $attributeNamesMap for proper names and group names
     *
     * @param array $combination
     * @param array $attributeNamesMap Map of attribute_id => ['name' => string, 'group_name' => string, 'color' => ?string]
     * @return array
     */
    protected function extractCombinationAttributes(array $combination, array $attributeNamesMap = []): array
    {
        $attributes = $combination['associations']['product_option_values'] ?? [];

        return array_map(function ($attr) use ($attributeNamesMap) {
            $attrId = (int) ($attr['id'] ?? 0);
            $mappedData = $attributeNamesMap[$attrId] ?? [];

            return [
                'prestashop_attribute_id' => $attrId,
                'prestashop_group_id' => (int) ($attr['id_attribute_group'] ?? 0),
                // FIX 2025-12-08: Use fetched names from map
                'name' => $mappedData['name'] ?? $attr['name'] ?? '',
                'group_name' => $mappedData['group_name'] ?? $attr['group_name'] ?? '',
                'color' => $mappedData['color'] ?? null,
            ];
        }, $attributes);
    }

    /**
     * Extract images from PrestaShop combination
     *
     * FIX 2025-12-08: Now builds full image URLs for display
     * FIX 2025-12-08: Uses product images as fallback when combination has no images
     * FIX 2025-12-09: CORRECT PrestaShop image URL format: /img/p/{split_id}/{id}-{size}.jpg
     *                 Example: image ID 1234 → /img/p/1/2/3/4/1234-small_default.jpg
     *
     * @param array $combination
     * @param string|null $shopUrl Shop base URL
     * @param int|null $prestashopProductId PrestaShop product ID
     * @param array $productImages Product images as fallback
     * @return array
     */
    protected function extractCombinationImages(
        array $combination,
        ?string $shopUrl = null,
        ?int $prestashopProductId = null,
        array $productImages = []
    ): array {
        $images = $combination['associations']['images'] ?? [];

        // FIX 2025-12-08: If combination has no images, use first product image as fallback
        if (empty($images) && !empty($productImages)) {
            $firstProductImage = $productImages[0] ?? null;
            if ($firstProductImage && $shopUrl) {
                $imageId = (int) ($firstProductImage['id'] ?? 0);
                if ($imageId > 0) {
                    $imageUrl = $this->buildPrestaShopImageUrl($shopUrl, $imageId, 'small_default');
                    return [[
                        'prestashop_image_id' => $imageId,
                        'url' => $imageUrl,
                        'thumbnail_url' => $imageUrl,
                        'is_product_fallback' => true, // Mark as fallback
                    ]];
                }
            }
        }

        return array_map(function ($img) use ($shopUrl) {
            $imageId = (int) ($img['id'] ?? 0);

            // Build image URL if we have shop URL and image ID
            $imageUrl = null;
            if ($shopUrl && $imageId) {
                // FIX 2025-12-09: Use CORRECT PrestaShop image URL format
                $imageUrl = $this->buildPrestaShopImageUrl($shopUrl, $imageId, 'small_default');
            }

            // DEBUG 2025-12-09: Log image URL building
            Log::debug('[ShopVariantService] extractCombinationImages - Image URL', [
                'imageId' => $imageId,
                'shopUrl' => $shopUrl,
                'imageUrl' => $imageUrl,
                'hasShopUrl' => !empty($shopUrl),
            ]);

            return [
                'prestashop_image_id' => $imageId,
                'url' => $imageUrl,
                'thumbnail_url' => $imageUrl, // Same for now, could use smaller type
            ];
        }, $images);
    }

    /**
     * Build PrestaShop image URL with correct folder structure
     *
     * PrestaShop stores images in /img/p/{split_id}/{id}-{size}.jpg format
     * Example: image ID 1234 → /img/p/1/2/3/4/1234-small_default.jpg
     *
     * @param string $shopUrl Shop base URL
     * @param int $imageId PrestaShop image ID
     * @param string $size Image size (e.g., 'small_default', 'medium_default', 'home_default')
     * @return string Full image URL
     */
    protected function buildPrestaShopImageUrl(string $shopUrl, int $imageId, string $size = 'small_default'): string
    {
        $baseUrl = rtrim($shopUrl, '/');

        // Split image ID into folder path: 1234 → 1/2/3/4
        $idString = (string) $imageId;
        $folderPath = implode('/', str_split($idString));

        // Build URL: {baseUrl}/img/p/{folderPath}/{id}-{size}.jpg
        return "{$baseUrl}/img/p/{$folderPath}/{$imageId}-{$size}.jpg";
    }

    /**
     * Normalize variant data for storage
     */
    protected function normalizeVariantData(array $data): array
    {
        return [
            'sku' => $data['sku'] ?? '',
            'name' => $data['name'] ?? '',
            'is_active' => $data['is_active'] ?? true,
            'is_default' => $data['is_default'] ?? false,
            'position' => $data['position'] ?? 0,
            'attributes' => $data['attributes'] ?? [],
            'prices' => $data['prices'] ?? [],
            'stock' => $data['stock'] ?? [],
            'images' => $data['images'] ?? [],
        ];
    }
}

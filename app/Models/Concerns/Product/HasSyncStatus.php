<?php

namespace App\Models\Concerns\Product;

use App\Models\PrestaShopShop;
use App\Models\ProductShopData;
use App\Models\IntegrationMapping;
use App\Services\PrestaShop\PrestaShopImportService;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Builder;

/**
 * HasSyncStatus Trait - Integration Sync Status Management
 *
 * Responsibility: PrestaShop/ERP sync status tracking i import/export
 *
 * Features:
 * - Integration mappings (PrestaShop, Baselinker, Subiekt GT, Dynamics)
 * - Sync status per integration system
 * - Import from PrestaShop (static factory method)
 * - Sync errors tracking
 * - Integration data formatted accessor
 *
 * Performance: Optimized dla sync operations
 * Integration: Multi-system support (PrestaShop, ERP)
 *
 * @package App\Models\Concerns\Product
 * @version 1.0
 * @since ETAP_05a SEKCJA 0 - Product.php Refactoring
 */
trait HasSyncStatus
{
    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS - Integration Relations
    |--------------------------------------------------------------------------
    */

    /**
     * Product integration mappings polymorphic relationship (1:many) - FAZA C ✅ IMPLEMENTED
     *
     * Universal mapping: PrestaShop, Baselinker, Subiekt GT, etc.
     * Performance: Optimized dla sync operations
     * Multi-store: Support dla różnych sklepów PrestaShop
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function integrationMappings(): MorphMany
    {
        return $this->morphMany(IntegrationMapping::class, 'mappable')
                    ->orderBy('integration_type', 'asc')
                    ->orderBy('integration_identifier', 'asc');
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS - Computed Integration Attributes
    |--------------------------------------------------------------------------
    */

    /**
     * Get integration data for all systems - FAZA C ✅ IMPLEMENTED
     *
     * Business Logic: Sync status monitoring
     * Performance: Cached integration summary
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function integrationData(): Attribute
    {
        return Attribute::make(
            get: function (): array {
                $data = [];

                $mappings = $this->integrationMappings()->get();

                foreach ($mappings as $mapping) {
                    $key = $mapping->integration_type . '_' . $mapping->integration_identifier;
                    $data[$key] = [
                        'type' => $mapping->integration_type,
                        'identifier' => $mapping->integration_identifier,
                        'external_id' => $mapping->external_id,
                        'status' => $mapping->sync_status,
                        'last_sync' => $mapping->last_sync_at?->format('Y-m-d H:i:s'),
                        'needs_sync' => $mapping->needs_sync,
                    ];
                }

                return $data;
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | BUSINESS METHODS - Sync Operations
    |--------------------------------------------------------------------------
    */

    /**
     * Get sync status for specific PrestaShop shop (CONSOLIDATED 2025-10-13)
     *
     * Usage: $syncStatus = $product->getSyncStatus($shop);
     * Returns: ProductShopData instance or null
     * Performance: Single query with shop_id filter
     * UPDATED: Now uses shopData() relation instead of deprecated syncStatuses()
     *
     * @param \App\Models\PrestaShopShop $shop
     * @return \App\Models\ProductShopData|null
     */
    public function getShopSyncStatus(PrestaShopShop $shop): ?ProductShopData
    {
        return $this->shopData()
            ->where('shop_id', $shop->id)
            ->first();
    }

    /**
     * Get sync status for specific shop by ID (CONSOLIDATED 2025-10-13)
     *
     * ETAP_07 FAZA 3: Helper method for ProductForm integration
     * Usage: $syncStatus = $product->syncStatusForShop($shopId);
     * Returns: ProductShopData instance or null
     * Performance: Single query with shop_id filter
     * UPDATED: Now uses shopData() relation instead of deprecated syncStatuses()
     *
     * @param int $shopId
     * @return \App\Models\ProductShopData|null
     */
    public function syncStatusForShop(int $shopId): ?ProductShopData
    {
        return $this->shopData()
            ->where('shop_id', $shopId)
            ->first();
    }

    /**
     * Get PrestaShop product ID for specific shop (if synced)
     *
     * Usage: $psProductId = $product->getPrestashopProductId($shop);
     * Returns: PrestaShop product ID or null if not synced
     * Business Logic: Convenience method for sync operations
     *
     * @param \App\Models\PrestaShopShop $shop
     * @return int|null
     */
    public function getPrestashopProductId(PrestaShopShop $shop): ?int
    {
        $syncStatus = $this->getShopSyncStatus($shop);
        return $syncStatus?->prestashop_product_id;
    }

    /**
     * Import this product's data from PrestaShop shop
     *
     * Usage: $product = Product::importFromPrestaShop(123, $shop);
     * Business Logic: Static factory method for PrestaShop imports
     * Performance: Delegates to PrestaShopImportService
     * Integration: ETAP_07 FAZA 2A.1 reverse transformation
     *
     * @param int $prestashopProductId PrestaShop product ID
     * @param \App\Models\PrestaShopShop $shop Shop to import from
     * @return self Imported Product instance
     */
    public static function importFromPrestaShop(
        int $prestashopProductId,
        PrestaShopShop $shop
    ): self
    {
        $importService = app(PrestaShopImportService::class);
        return $importService->importProductFromPrestaShop($prestashopProductId, $shop);
    }

    /**
     * Scope: Products imported from specific PrestaShop shop (CONSOLIDATED 2025-10-13)
     *
     * Usage: $products = Product::importedFrom($shop->id)->get();
     * Business Logic: Filter products by import source
     * Performance: Optimized subquery with sync_direction filter
     * UPDATED: Now uses shopData() relation instead of deprecated syncStatuses()
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $shopId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeImportedFrom(Builder $query, int $shopId): Builder
    {
        return $query->whereHas('shopData', function($q) use ($shopId) {
            $q->where('shop_id', $shopId)
              ->where('sync_direction', 'ps_to_ppm');
        });
    }

    /**
     * Sync product to specific integration system
     *
     * @param string $integrationType
     * @param string $integrationIdentifier
     * @param array $options
     * @return \App\Models\IntegrationMapping
     */
    public function syncToIntegration(string $integrationType, string $integrationIdentifier, array $options = []): IntegrationMapping
    {
        // Find or create integration mapping
        $mapping = $this->integrationMappings()
            ->where('integration_type', $integrationType)
            ->where('integration_identifier', $integrationIdentifier)
            ->first();

        if (!$mapping) {
            $mapping = new IntegrationMapping();
            $mapping->integration_type = $integrationType;
            $mapping->integration_identifier = $integrationIdentifier;
            $mapping->sync_status = 'pending';
            $mapping->sync_direction = $options['sync_direction'] ?? 'both';

            $this->integrationMappings()->save($mapping);
        }

        // Update sync status to pending if not already syncing
        if (!in_array($mapping->sync_status, ['pending', 'syncing'])) {
            $mapping->sync_status = 'pending';
            $mapping->next_sync_at = now();
            $mapping->save();
        }

        return $mapping;
    }

    /**
     * Get sync status for all integration systems
     *
     * @return array
     */
    public function getSyncStatus(): array
    {
        $status = [];

        $mappings = $this->integrationMappings()->get();

        foreach ($mappings as $mapping) {
            $status[$mapping->integration_type][$mapping->integration_identifier] = [
                'status' => $mapping->sync_status,
                'last_sync' => $mapping->last_sync_at,
                'needs_sync' => $mapping->needs_sync,
                'has_error' => $mapping->has_error,
                'error_count' => $mapping->error_count,
            ];
        }

        return $status;
    }
}

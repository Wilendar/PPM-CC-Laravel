<?php

declare(strict_types=1);

namespace App\Services\Import;

use App\Models\ERPConnection;
use App\Models\PendingProduct;
use App\Models\Product;
use App\Models\ProductErpData;
use App\Models\PrestaShopShop;
use App\Jobs\PrestaShop\SyncProductToPrestaShop;
use App\Jobs\ERP\SyncProductToERP;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * PublicationTargetService - FAZA 9.4 / GRUPA D
 *
 * Resolves publication targets from PendingProduct config
 * and dispatches sync jobs to ERP and PrestaShop systems.
 *
 * GRUPA D: Now uses ERPConnection model instead of config for ERP targets.
 *
 * Target structure (stored in PendingProduct.publication_targets):
 * {
 *   "erp_connections": [5, 8],
 *   "prestashop_shops": [1, 3]
 * }
 *
 * Backward compatibility: old format {"erp_primary": true} is still supported
 * and automatically mapped to default ERPConnection.
 *
 * @package App\Services\Import
 */
class PublicationTargetService
{
    /**
     * Get all available publication targets for UI display
     *
     * Returns active ERPConnection instances + all active PrestaShop shops.
     *
     * @return array ['erp_connections' => [...], 'prestashop_shops' => [...]]
     */
    public function getAvailableTargets(): array
    {
        $targets = [];

        // ERP Connections from database (replaces config-based erp_primary)
        $targets['erp_connections'] = ERPConnection::active()
            ->orderByDesc('is_default')
            ->orderBy('priority')
            ->get(['id', 'instance_name', 'erp_type', 'is_default', 'priority'])
            ->map(fn(ERPConnection $conn) => [
                'id' => $conn->id,
                'name' => $conn->instance_name,
                'erp_type' => $conn->erp_type,
                'is_default' => $conn->is_default,
                'priority' => $conn->priority,
            ])
            ->toArray();

        // PrestaShop shops from DB
        $targets['prestashop_shops'] = [];
        if (config('import.publication_targets.prestashop_enabled', true)) {
            $shops = PrestaShopShop::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'url']);

            foreach ($shops as $shop) {
                $targets['prestashop_shops'][] = [
                    'id' => $shop->id,
                    'name' => $shop->name,
                    'url' => $shop->url,
                ];
            }
        }

        return $targets;
    }

    /**
     * Get the default ERP connection
     *
     * @return ERPConnection|null
     */
    public function getDefaultErpConnection(): ?ERPConnection
    {
        return ERPConnection::default()->active()->first();
    }

    /**
     * Get default ERP connection ID
     *
     * @return int|null
     */
    public function getDefaultErpConnectionId(): ?int
    {
        return ERPConnection::default()->active()->value('id');
    }

    /**
     * Resolve targets from PendingProduct to actionable data.
     * Supports both new format (erp_connections) and legacy format (erp_primary).
     *
     * @param array|null $targets Raw targets from PendingProduct
     * @return array ['erp_connection_ids' => int[], 'prestashop_shop_ids' => int[]]
     */
    public function resolveTargets(?array $targets): array
    {
        if (empty($targets)) {
            // Default: include default ERP only
            $defaultId = $this->getDefaultErpConnectionId();
            return [
                'erp_connection_ids' => $defaultId ? [$defaultId] : [],
                'prestashop_shop_ids' => [],
            ];
        }

        // New format: erp_connections array of IDs
        if (isset($targets['erp_connections']) && !empty($targets['erp_connections'])) {
            return [
                'erp_connection_ids' => $targets['erp_connections'],
                'prestashop_shop_ids' => $targets['prestashop_shops'] ?? [],
            ];
        }

        // erp_primary flag OR empty erp_connections with erp_primary=true
        if (!empty($targets['erp_primary'])) {
            $defaultId = $this->getDefaultErpConnectionId();
            return [
                'erp_connection_ids' => $defaultId ? [$defaultId] : [],
                'prestashop_shop_ids' => $targets['prestashop_shops'] ?? [],
            ];
        }

        // Targets exist but no ERP selection - include default ERP as fallback
        $defaultId = $this->getDefaultErpConnectionId();
        return [
            'erp_connection_ids' => $defaultId ? [$defaultId] : [],
            'prestashop_shop_ids' => $targets['prestashop_shops'] ?? [],
        ];
    }

    /**
     * Dispatch sync jobs based on resolved targets.
     * Uses the SAME dispatch patterns as ProductForm:
     * - PrestaShop: dispatch() on default queue (processed by CRON)
     * - ERP: dispatchSync() for immediate execution (Hostido has no daemon)
     *
     * @param Product $product Published product
     * @param array $resolvedTargets Output from resolveTargets()
     */
    public function dispatchSyncJobs(Product $product, array $resolvedTargets): void
    {
        $userId = Auth::id();

        // Dispatch PrestaShop sync jobs (same pattern as ProductForm::dispatchSyncJobsForAllShops)
        $shopIds = $resolvedTargets['prestashop_shop_ids'] ?? [];

        // Build pendingMediaChanges: mark ALL product media as "sync" for each shop
        // This is needed because SmartMediaSyncService filters out media without PS mapping
        // unless explicitly marked in pendingMediaChanges
        $pendingMediaChanges = $this->buildPendingMediaChanges($product, $shopIds);

        foreach ($shopIds as $shopId) {
            try {
                $shop = PrestaShopShop::find($shopId);
                if (!$shop) {
                    Log::warning('PublicationTargetService: Shop not found', [
                        'product_id' => $product->id,
                        'shop_id' => $shopId,
                    ]);
                    continue;
                }

                // FIX 2026-02-10: Use default queue, pass userId + pendingMediaChanges
                // Same pattern as ProductForm::dispatchSyncJobsForAllShops
                SyncProductToPrestaShop::dispatch(
                    $product,
                    $shop,
                    $userId,
                    $pendingMediaChanges
                );

                Log::info('PublicationTargetService: PrestaShop sync dispatched', [
                    'product_id' => $product->id,
                    'shop_id' => $shopId,
                    'user_id' => $userId,
                ]);
            } catch (\Throwable $e) {
                Log::error('PublicationTargetService: PrestaShop sync failed', [
                    'product_id' => $product->id,
                    'shop_id' => $shopId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Dispatch ERP sync jobs (same pattern as ProductForm::dispatchSyncJobsForAllErp)
        $erpConnectionIds = $resolvedTargets['erp_connection_ids'] ?? [];
        foreach ($erpConnectionIds as $connectionId) {
            $this->dispatchErpJobByConnection($product, $connectionId);
        }

        Log::info('PublicationTargetService: All sync jobs dispatched', [
            'product_id' => $product->id,
            'prestashop_shops' => count($shopIds),
            'erp_connections' => count($erpConnectionIds),
        ]);
    }

    /**
     * Ensure ProductErpData record exists before ERP dispatch.
     * The SyncProductToERP job needs this record for status tracking.
     */
    public function ensureProductErpData(Product $product, int $connectionId): ProductErpData
    {
        return ProductErpData::firstOrCreate(
            [
                'product_id' => $product->id,
                'erp_connection_id' => $connectionId,
            ],
            [
                'sync_status' => ProductErpData::STATUS_PENDING,
                'sync_direction' => ProductErpData::DIRECTION_BIDIRECTIONAL,
            ]
        );
    }

    /**
     * Dispatch ERP sync job for a specific ERPConnection.
     * Uses dispatchSync() for immediate execution (same as ProductForm).
     *
     * @param Product $product
     * @param int $connectionId ERPConnection ID
     */
    protected function dispatchErpJobByConnection(Product $product, int $connectionId): void
    {
        try {
            $connection = ERPConnection::find($connectionId);
            if (!$connection || !$connection->is_active) {
                Log::warning('PublicationTargetService: ERP connection inactive or not found', [
                    'connection_id' => $connectionId,
                ]);
                return;
            }

            // Ensure ProductErpData record exists for tracking
            $erpData = $this->ensureProductErpData($product, $connectionId);
            $erpData->update(['sync_status' => ProductErpData::STATUS_PENDING]);

            // FIX 2026-02-10: Use dispatchSync() like ProductForm does
            // Hostido shared hosting has no queue daemon, CRON processes --once per minute
            // dispatchSync = immediate execution = reliable for publication
            SyncProductToERP::dispatchSync(
                $product,
                $connection,
                null, // syncJob - will be created by job
                ['sync_prices' => true, 'sync_stock' => true]
            );

            Log::info('PublicationTargetService: ERP sync dispatched (sync)', [
                'product_id' => $product->id,
                'connection_id' => $connectionId,
                'erp_type' => $connection->erp_type,
            ]);
        } catch (\Throwable $e) {
            Log::error('PublicationTargetService: ERP sync dispatch failed', [
                'product_id' => $product->id,
                'connection_id' => $connectionId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Build pendingMediaChanges array for publication.
     * Marks all active product media as "sync" for each target shop.
     * Format: ['mediaId:shopId' => 'sync']
     */
    protected function buildPendingMediaChanges(Product $product, array $shopIds): array
    {
        $media = \App\Models\Media::where('mediable_type', Product::class)
            ->where('mediable_id', $product->id)
            ->where('is_active', true)
            ->orderBy('is_primary', 'desc')
            ->orderBy('sort_order', 'asc')
            ->get();

        $changes = [];
        foreach ($media as $m) {
            foreach ($shopIds as $shopId) {
                $changes["{$m->id}:{$shopId}"] = 'sync';
            }
        }

        Log::debug('PublicationTargetService: Built pendingMediaChanges', [
            'product_id' => $product->id,
            'media_count' => $media->count(),
            'shops' => $shopIds,
            'changes_count' => count($changes),
        ]);

        return $changes;
    }

    /**
     * Get default targets for new products.
     * Includes default ERP connection automatically.
     */
    public function getDefaultTargets(): array
    {
        $defaultId = $this->getDefaultErpConnectionId();

        return [
            'erp_connections' => $defaultId ? [$defaultId] : [],
            'prestashop_shops' => [],
        ];
    }

    /**
     * Validate targets array structure
     *
     * @param array $targets
     * @return array ['valid' => bool, 'errors' => string[]]
     */
    public function validateTargets(array $targets): array
    {
        $errors = [];

        // Must have at least one target
        $hasErpConnections = !empty($targets['erp_connections']);
        $hasShops = !empty($targets['prestashop_shops']);

        // Legacy check
        $hasLegacyErp = !empty($targets['erp_primary']);

        if (!$hasErpConnections && !$hasShops && !$hasLegacyErp) {
            $errors[] = 'Musisz wybrac przynajmniej jeden cel publikacji';
        }

        // Validate ERP connection IDs exist
        if ($hasErpConnections) {
            $connectionIds = $targets['erp_connections'];
            $existingCount = ERPConnection::whereIn('id', $connectionIds)
                ->where('is_active', true)
                ->count();

            if ($existingCount !== count($connectionIds)) {
                $errors[] = 'Niektore wybrane polaczenia ERP nie istnieja lub sa nieaktywne';
            }
        }

        // Validate shop IDs exist
        if ($hasShops) {
            $shopIds = $targets['prestashop_shops'];
            $existingCount = PrestaShopShop::whereIn('id', $shopIds)
                ->where('is_active', true)
                ->count();

            if ($existingCount !== count($shopIds)) {
                $errors[] = 'Niektore wybrane sklepy nie istnieja lub sa nieaktywne';
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }
}

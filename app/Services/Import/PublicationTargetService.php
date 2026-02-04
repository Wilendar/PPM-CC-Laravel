<?php

declare(strict_types=1);

namespace App\Services\Import;

use App\Models\ERPConnection;
use App\Models\PendingProduct;
use App\Models\Product;
use App\Models\PrestaShopShop;
use App\Jobs\PrestaShop\SyncProductToPrestaShop;
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
        if (isset($targets['erp_connections'])) {
            return [
                'erp_connection_ids' => $targets['erp_connections'] ?? [],
                'prestashop_shop_ids' => $targets['prestashop_shops'] ?? [],
            ];
        }

        // Legacy format: erp_primary boolean
        if (isset($targets['erp_primary'])) {
            $erpIds = [];
            if ($targets['erp_primary']) {
                $defaultId = $this->getDefaultErpConnectionId();
                if ($defaultId) {
                    $erpIds[] = $defaultId;
                }
            }

            return [
                'erp_connection_ids' => $erpIds,
                'prestashop_shop_ids' => $targets['prestashop_shops'] ?? [],
            ];
        }

        return [
            'erp_connection_ids' => [],
            'prestashop_shop_ids' => $targets['prestashop_shops'] ?? [],
        ];
    }

    /**
     * Dispatch sync jobs based on resolved targets
     *
     * @param Product $product Published product
     * @param array $resolvedTargets Output from resolveTargets()
     */
    public function dispatchSyncJobs(Product $product, array $resolvedTargets): void
    {
        // Dispatch PrestaShop sync jobs
        $shopIds = $resolvedTargets['prestashop_shop_ids'] ?? [];
        foreach ($shopIds as $shopId) {
            try {
                SyncProductToPrestaShop::dispatch($product->id, $shopId)
                    ->onQueue('prestashop-sync');

                Log::info('PublicationTargetService: PrestaShop sync dispatched', [
                    'product_id' => $product->id,
                    'shop_id' => $shopId,
                ]);
            } catch (\Exception $e) {
                Log::error('PublicationTargetService: PrestaShop sync failed', [
                    'product_id' => $product->id,
                    'shop_id' => $shopId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Dispatch ERP sync jobs for each connection
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
     * Dispatch ERP sync job for a specific ERPConnection
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

            $jobClass = match ($connection->erp_type) {
                'subiekt_gt' => \App\Jobs\ERP\SyncProductToSubiektJob::class,
                'baselinker' => \App\Jobs\ERP\SyncProductToBaselinkerJob::class,
                'dynamics' => \App\Jobs\ERP\SyncProductToDynamicsJob::class,
                default => null,
            };

            if ($jobClass && class_exists($jobClass)) {
                $jobClass::dispatch($product->id, $connectionId)->onQueue('erp-sync');

                Log::info('PublicationTargetService: ERP sync dispatched', [
                    'product_id' => $product->id,
                    'connection_id' => $connectionId,
                    'erp_type' => $connection->erp_type,
                    'job_class' => $jobClass,
                ]);
            } else {
                Log::warning('PublicationTargetService: ERP job class not found', [
                    'connection_id' => $connectionId,
                    'erp_type' => $connection->erp_type,
                    'expected_class' => $jobClass,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('PublicationTargetService: ERP sync dispatch failed', [
                'product_id' => $product->id,
                'connection_id' => $connectionId,
                'error' => $e->getMessage(),
            ]);
        }
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

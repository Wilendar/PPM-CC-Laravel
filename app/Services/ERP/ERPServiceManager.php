<?php

namespace App\Services\ERP;

use App\Models\ERPConnection;
use App\Models\Product;
use App\Services\ERP\Contracts\ERPSyncServiceInterface;
use Illuminate\Support\Facades\Log;

/**
 * ERPServiceManager
 *
 * ETAP_08: BaseLinker ERP Integration
 *
 * Factory pattern dla roznych systemow ERP.
 * Zwraca odpowiedni serwis na podstawie typu polaczenia.
 *
 * Supported ERP types:
 * - baselinker (PRIORYTET #1)
 * - subiekt_gt (placeholder)
 * - dynamics (placeholder)
 */
class ERPServiceManager
{
    /**
     * Cached service instances per connection.
     *
     * @var array<int, ERPSyncServiceInterface>
     */
    protected array $services = [];

    /**
     * Get ERP service for given connection.
     *
     * @param ERPConnection $connection ERP connection
     * @return ERPSyncServiceInterface
     * @throws \InvalidArgumentException When ERP type is not supported
     */
    public function getService(ERPConnection $connection): ERPSyncServiceInterface
    {
        $cacheKey = $connection->id;

        if (isset($this->services[$cacheKey])) {
            return $this->services[$cacheKey];
        }

        $service = $this->createService($connection->erp_type);
        $this->services[$cacheKey] = $service;

        return $service;
    }

    /**
     * Create service instance based on ERP type.
     *
     * @param string $erpType ERP type identifier
     * @return ERPSyncServiceInterface
     * @throws \InvalidArgumentException
     */
    protected function createService(string $erpType): ERPSyncServiceInterface
    {
        return match ($erpType) {
            ERPConnection::ERP_BASELINKER => app(BaselinkerService::class),
            ERPConnection::ERP_SUBIEKT_GT => app(SubiektGTService::class),
            ERPConnection::ERP_DYNAMICS => app(DynamicsService::class),
            default => throw new \InvalidArgumentException("Unsupported ERP type: {$erpType}"),
        };
    }

    /**
     * Sync product to ALL active ERP connections.
     *
     * @param Product $product Product to sync
     * @param array $filters Optional filters (erp_types, connection_ids)
     * @return array<string, array> Results per connection
     */
    public function syncProductToAllERP(Product $product, array $filters = []): array
    {
        $results = [];

        $query = ERPConnection::active()
            ->where('auto_sync_products', true)
            ->orderBy('priority');

        // Filter by ERP types
        if (!empty($filters['erp_types'])) {
            $query->whereIn('erp_type', $filters['erp_types']);
        }

        // Filter by connection IDs
        if (!empty($filters['connection_ids'])) {
            $query->whereIn('id', $filters['connection_ids']);
        }

        $connections = $query->get();

        foreach ($connections as $connection) {
            try {
                $service = $this->getService($connection);
                $result = $service->syncProductToERP($connection, $product);

                $results[$connection->instance_name] = [
                    'success' => $result['success'],
                    'message' => $result['message'],
                    'external_id' => $result['external_id'] ?? null,
                    'erp_type' => $connection->erp_type,
                ];

                // Update connection health
                if ($result['success']) {
                    $connection->updateConnectionHealth(
                        ERPConnection::CONNECTION_CONNECTED,
                        $result['response_time'] ?? null
                    );
                } else {
                    $connection->updateConnectionHealth(
                        ERPConnection::CONNECTION_ERROR,
                        null,
                        $result['message']
                    );
                }

            } catch (\Exception $e) {
                Log::error("ERPServiceManager: Failed to sync product to {$connection->instance_name}", [
                    'product_id' => $product->id,
                    'product_sku' => $product->sku,
                    'connection_id' => $connection->id,
                    'error' => $e->getMessage(),
                ]);

                $results[$connection->instance_name] = [
                    'success' => false,
                    'message' => 'Exception: ' . $e->getMessage(),
                    'external_id' => null,
                    'erp_type' => $connection->erp_type,
                ];

                $connection->updateConnectionHealth(
                    ERPConnection::CONNECTION_ERROR,
                    null,
                    $e->getMessage()
                );
            }
        }

        return $results;
    }

    /**
     * Test connection for specific ERP.
     *
     * @param ERPConnection $connection ERP connection to test
     * @return array Test result
     */
    public function testConnection(ERPConnection $connection): array
    {
        try {
            $service = $this->getService($connection);
            $result = $service->testConnection($connection->connection_config);

            // Update connection health based on result
            $connection->updateConnectionHealth(
                $result['success'] ? ERPConnection::CONNECTION_CONNECTED : ERPConnection::CONNECTION_ERROR,
                $result['response_time'] ?? null,
                $result['success'] ? null : $result['message']
            );

            if ($result['success']) {
                $connection->updateAuthStatus(ERPConnection::AUTH_AUTHENTICATED);
            }

            return $result;

        } catch (\Exception $e) {
            $connection->updateConnectionHealth(
                ERPConnection::CONNECTION_ERROR,
                null,
                $e->getMessage()
            );

            return [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage(),
                'response_time' => 0,
                'details' => [],
            ];
        }
    }

    /**
     * Get supported features for ERP type.
     *
     * @param string $erpType ERP type identifier
     * @return array Supported features
     */
    public function getSupportedFeatures(string $erpType): array
    {
        try {
            $service = $this->createService($erpType);
            return $service->getSupportedFeatures();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Clear cached services.
     *
     * @return void
     */
    public function clearCache(): void
    {
        $this->services = [];
    }

    /**
     * Get all supported ERP types.
     *
     * @return array<string, string> ERP types with labels
     */
    public static function getSupportedERPTypes(): array
    {
        return [
            ERPConnection::ERP_BASELINKER => 'BaseLinker',
            ERPConnection::ERP_SUBIEKT_GT => 'Subiekt GT',
            ERPConnection::ERP_DYNAMICS => 'Microsoft Dynamics',
        ];
    }
}

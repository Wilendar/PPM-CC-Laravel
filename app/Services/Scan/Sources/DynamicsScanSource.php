<?php

namespace App\Services\Scan\Sources;

use App\Models\ERPConnection;
use App\Services\ERP\DynamicsService;
use App\Services\Scan\Contracts\ScanSourceInterface;
use App\Exceptions\ScanSourceException;
use Illuminate\Support\Facades\Log;

/**
 * DynamicsScanSource
 *
 * Adapter for scanning products from Microsoft Dynamics 365 ERP system.
 * Currently a placeholder implementation as Dynamics integration is not yet complete.
 *
 * Status: NOT IMPLEMENTED (placeholder only)
 * Requirements: Azure AD App Registration, OAuth2 credentials, OData endpoint
 *
 * Usage:
 * ```php
 * $connection = ERPConnection::where('erp_type', 'dynamics')->first();
 * $source = new DynamicsScanSource($connection);
 * // Currently returns empty results / throws not implemented exception
 * ```
 *
 * @package App\Services\Scan\Sources
 * @version 1.0.0
 */
class DynamicsScanSource implements ScanSourceInterface
{
    protected ERPConnection $connection;
    protected DynamicsService $service;

    /**
     * Constructor
     *
     * @param ERPConnection $connection Dynamics ERP connection
     * @throws ScanSourceException When connection is invalid or inactive
     */
    public function __construct(ERPConnection $connection)
    {
        $this->validateConnection($connection);
        $this->connection = $connection;
        $this->service = new DynamicsService();
    }

    /**
     * Validate the ERP connection.
     *
     * @param ERPConnection $connection
     * @throws ScanSourceException
     */
    protected function validateConnection(ERPConnection $connection): void
    {
        if ($connection->erp_type !== ERPConnection::ERP_DYNAMICS) {
            throw new ScanSourceException(
                "Invalid ERP type: expected 'dynamics', got '{$connection->erp_type}'"
            );
        }

        if (!$connection->is_active) {
            throw new ScanSourceException(
                "ERP connection '{$connection->instance_name}' is not active"
            );
        }
    }

    /**
     * {@inheritdoc}
     *
     * @throws ScanSourceException Always - Dynamics not implemented
     */
    public function getAllSkus(): array
    {
        Log::warning('DynamicsScanSource::getAllSkus - Not implemented', [
            'connection_id' => $this->connection->id,
            'connection_name' => $this->connection->instance_name,
        ]);

        throw new ScanSourceException(
            "Microsoft Dynamics integration is not yet implemented. " .
            "Requires Azure AD App Registration, OAuth2 credentials, and OData endpoint configuration.",
            501, // HTTP 501 Not Implemented
            null,
            ['status' => 'not_implemented']
        );
    }

    /**
     * {@inheritdoc}
     *
     * @throws ScanSourceException Always - Dynamics not implemented
     */
    public function getProductBySku(string $sku): ?array
    {
        Log::warning('DynamicsScanSource::getProductBySku - Not implemented', [
            'connection_id' => $this->connection->id,
            'sku' => $sku,
        ]);

        throw new ScanSourceException(
            "Microsoft Dynamics product lookup is not yet implemented.",
            501,
            null,
            ['status' => 'not_implemented', 'sku' => $sku]
        );
    }

    /**
     * {@inheritdoc}
     *
     * @throws ScanSourceException Always - Dynamics not implemented
     */
    public function getProductsBatch(int $page, int $perPage = 100): array
    {
        Log::warning('DynamicsScanSource::getProductsBatch - Not implemented', [
            'connection_id' => $this->connection->id,
            'page' => $page,
            'per_page' => $perPage,
        ]);

        throw new ScanSourceException(
            "Microsoft Dynamics batch product fetch is not yet implemented.",
            501,
            null,
            ['status' => 'not_implemented', 'page' => $page]
        );
    }

    /**
     * {@inheritdoc}
     *
     * @throws ScanSourceException Always - Dynamics not implemented
     */
    public function getProductCount(): int
    {
        Log::warning('DynamicsScanSource::getProductCount - Not implemented', [
            'connection_id' => $this->connection->id,
        ]);

        throw new ScanSourceException(
            "Microsoft Dynamics product count is not yet implemented.",
            501,
            null,
            ['status' => 'not_implemented']
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getSourceType(): string
    {
        return ERPConnection::ERP_DYNAMICS;
    }

    /**
     * {@inheritdoc}
     */
    public function getSourceId(): ?int
    {
        return $this->connection->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getSourceName(): string
    {
        return "Microsoft Dynamics - {$this->connection->instance_name}";
    }

    /**
     * {@inheritdoc}
     */
    public function testConnection(): array
    {
        $result = $this->service->testConnection($this->connection->connection_config);

        return [
            'success' => $result['success'] ?? false,
            'message' => $result['message'] ?? 'Not implemented',
            'response_time_ms' => $result['response_time'] ?? null,
        ];
    }

    /**
     * {@inheritdoc}
     *
     * Placeholder implementation - returns empty normalized structure.
     */
    public function normalizeProduct(array $rawProduct): array
    {
        // Placeholder implementation
        // When Dynamics is implemented, this will map:
        // - ItemNumber -> sku
        // - ProductName -> name
        // - Description -> description
        // - SalesPrice -> price_net
        // - InventoryOnHand -> stock
        // etc.

        return [
            'external_id' => (string) ($rawProduct['ItemNumber'] ?? $rawProduct['id'] ?? ''),
            'sku' => (string) ($rawProduct['ItemNumber'] ?? $rawProduct['sku'] ?? ''),
            'name' => (string) ($rawProduct['ProductName'] ?? $rawProduct['name'] ?? ''),
            'description' => $rawProduct['Description'] ?? $rawProduct['description'] ?? null,
            'ean' => $rawProduct['EAN'] ?? $rawProduct['ean'] ?? null,
            'price_net' => isset($rawProduct['SalesPrice'])
                ? (float) $rawProduct['SalesPrice']
                : null,
            'price_gross' => null,
            'stock' => isset($rawProduct['InventoryOnHand'])
                ? (float) $rawProduct['InventoryOnHand']
                : null,
            'unit' => $rawProduct['UnitOfMeasure'] ?? null,
            'weight' => isset($rawProduct['NetWeight'])
                ? (float) $rawProduct['NetWeight']
                : null,
            'vat_rate' => null,
            'is_active' => (bool) ($rawProduct['IsActive'] ?? true),
            'manufacturer' => $rawProduct['VendorAccount'] ?? null,
            'group' => $rawProduct['ProductCategory'] ?? null,
            'source_type' => $this->getSourceType(),
            'source_id' => $this->getSourceId(),
            'raw_data' => $rawProduct,
        ];
    }

    /**
     * Get implementation status and requirements.
     *
     * @return array Status information
     */
    public function getImplementationStatus(): array
    {
        return [
            'status' => 'not_implemented',
            'requirements' => [
                'Azure AD App Registration',
                'OAuth2 Client ID/Secret',
                'OData API endpoint',
                'Dynamics 365 Finance & Operations license',
            ],
            'planned_features' => [
                'products' => 'Product catalog synchronization',
                'stock' => 'Real-time inventory levels',
                'prices' => 'Price list management',
                'orders' => 'Sales order integration',
            ],
            'estimated_implementation' => 'Future release',
        ];
    }

    /**
     * Get the underlying service.
     *
     * @return DynamicsService
     */
    public function getService(): DynamicsService
    {
        return $this->service;
    }

    /**
     * Get the ERP connection model.
     *
     * @return ERPConnection
     */
    public function getConnection(): ERPConnection
    {
        return $this->connection;
    }
}

<?php

namespace App\Services\ERP\Contracts;

use App\Models\ERPConnection;
use App\Models\Product;

/**
 * ERPSyncServiceInterface
 *
 * ETAP_08: BaseLinker ERP Integration
 *
 * Interfejs definiujący kontrakt dla wszystkich serwisów ERP.
 * Implementowany przez: BaselinkerService, SubiektGTService, DynamicsService
 *
 * Pattern: Strategy Pattern dla różnych systemów ERP
 */
interface ERPSyncServiceInterface
{
    /**
     * Test connection to ERP system.
     *
     * @param array $config Connection configuration
     * @return array{success: bool, message: string, response_time: float, details: array}
     */
    public function testConnection(array $config): array;

    /**
     * Test authentication with ERP system.
     *
     * @param array $config Connection configuration
     * @return array{success: bool, message: string, response_time: float, details: array, supported_features: array}
     */
    public function testAuthentication(array $config): array;

    /**
     * Sync single product TO external ERP system (PUSH).
     *
     * @param ERPConnection $connection Active ERP connection
     * @param Product $product Product to sync
     * @return array{success: bool, message: string, external_id: ?string}
     */
    public function syncProductToERP(ERPConnection $connection, Product $product): array;

    /**
     * Sync single product FROM external ERP system (PULL).
     *
     * @param ERPConnection $connection Active ERP connection
     * @param string $erpProductId External product ID
     * @return array{success: bool, message: string, product: ?Product}
     */
    public function syncProductFromERP(ERPConnection $connection, string $erpProductId): array;

    /**
     * Sync all products to ERP system (batch PUSH).
     *
     * @param ERPConnection $connection Active ERP connection
     * @param array $filters Optional filters (product_ids, categories, etc.)
     * @return array{success: bool, total: int, synced: int, failed: int, errors: array}
     */
    public function syncAllProducts(ERPConnection $connection, array $filters = []): array;

    /**
     * Pull all products from ERP system (batch PULL).
     *
     * @param ERPConnection $connection Active ERP connection
     * @param array $filters Optional filters
     * @return array{success: bool, total: int, imported: int, skipped: int, errors: array}
     */
    public function pullAllProducts(ERPConnection $connection, array $filters = []): array;

    /**
     * Sync product stock to ERP system.
     *
     * @param ERPConnection $connection Active ERP connection
     * @param Product $product Product to sync stock for
     * @return array{success: bool, message: string}
     */
    public function syncStock(ERPConnection $connection, Product $product): array;

    /**
     * Sync product prices to ERP system.
     *
     * @param ERPConnection $connection Active ERP connection
     * @param Product $product Product to sync prices for
     * @return array{success: bool, message: string}
     */
    public function syncPrices(ERPConnection $connection, Product $product): array;

    /**
     * Get ERP type identifier.
     *
     * @return string ERP type (baselinker, subiekt_gt, dynamics)
     */
    public function getERPType(): string;

    /**
     * Get supported features for this ERP.
     *
     * @return array List of supported features
     */
    public function getSupportedFeatures(): array;

    /**
     * Find product in ERP by SKU.
     *
     * @param ERPConnection $connection Active ERP connection
     * @param string $sku Product SKU to search for
     * @return array{success: bool, found: bool, external_id: ?string, data: ?array, message: string}
     */
    public function findProductBySku(ERPConnection $connection, string $sku): array;
}

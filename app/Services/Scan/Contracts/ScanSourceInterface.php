<?php

namespace App\Services\Scan\Contracts;

/**
 * ScanSourceInterface
 *
 * Defines the contract for product scan source adapters.
 * Each adapter provides access to an external system (ERP, PrestaShop)
 * for product scanning and comparison operations.
 *
 * Implementations:
 * - SubiektGTScanSource
 * - PrestaShopScanSource
 * - BaselinkerScanSource
 * - DynamicsScanSource
 *
 * @package App\Services\Scan\Contracts
 * @version 1.0.0
 */
interface ScanSourceInterface
{
    /**
     * Get all SKUs from the source system.
     *
     * Returns a flat array of all SKUs available in the external system.
     * Used for comparing against PPM product SKUs.
     *
     * @return array<string> Array of SKU strings
     * @throws \App\Exceptions\ScanSourceException When connection fails
     */
    public function getAllSkus(): array;

    /**
     * Get a single product by SKU.
     *
     * Returns normalized product data or null if not found.
     *
     * @param string $sku Product SKU to search for
     * @return array|null Normalized product data or null if not found
     * @throws \App\Exceptions\ScanSourceException When connection fails
     */
    public function getProductBySku(string $sku): ?array;

    /**
     * Get products in batches with pagination.
     *
     * Returns a paginated result with normalized product data.
     *
     * @param int $page Page number (1-based)
     * @param int $perPage Number of products per page (default: 100)
     * @return array{
     *     data: array<int, array>,
     *     total: int,
     *     page: int,
     *     per_page: int,
     *     has_more: bool
     * }
     * @throws \App\Exceptions\ScanSourceException When connection fails
     */
    public function getProductsBatch(int $page, int $perPage = 100): array;

    /**
     * Get the total number of products in the source.
     *
     * @return int Total product count
     * @throws \App\Exceptions\ScanSourceException When connection fails
     */
    public function getProductCount(): int;

    /**
     * Get the source type identifier.
     *
     * Returns a string identifying the source type (e.g., 'subiekt_gt', 'prestashop', 'baselinker').
     *
     * @return string Source type identifier
     */
    public function getSourceType(): string;

    /**
     * Get the source ID (if applicable).
     *
     * Returns the ID of the specific connection/shop instance.
     * For ERPConnection this is erp_connection_id, for PrestaShop this is shop_id.
     *
     * @return int|null Source instance ID or null for global sources
     */
    public function getSourceId(): ?int;

    /**
     * Get the human-readable source name.
     *
     * Returns a display name for the source (e.g., "Subiekt GT - MPP TRADE", "PrestaShop - Pitbike.pl").
     *
     * @return string Human-readable source name
     */
    public function getSourceName(): string;

    /**
     * Test connection to the source.
     *
     * Verifies that the source is accessible and properly configured.
     *
     * @return array{success: bool, message: string, response_time_ms?: float}
     */
    public function testConnection(): array;

    /**
     * Get normalized product data structure.
     *
     * Returns product data in a standardized format for comparison.
     * Normalizes field names and data types across different sources.
     *
     * @param array $rawProduct Raw product data from the source
     * @return array Normalized product data with standard field names
     */
    public function normalizeProduct(array $rawProduct): array;
}

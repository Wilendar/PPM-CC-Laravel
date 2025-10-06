<?php

namespace App\Services\PrestaShop\Sync;

use Illuminate\Database\Eloquent\Model;
use App\Services\PrestaShop\BasePrestaShopClient;
use App\Models\PrestaShopShop;

/**
 * Sync Strategy Interface
 *
 * Defines contract dla synchronizacji entities PPM → PrestaShop
 *
 * Pattern: Strategy Pattern dla różnych typów synchronizacji
 * Usage: Implemented by ProductSyncStrategy, CategorySyncStrategy, etc.
 *
 * @package App\Services\PrestaShop\Sync
 */
interface ISyncStrategy
{
    /**
     * Synchronize model to PrestaShop
     *
     * Main method performing full sync operation:
     * - Validate model data
     * - Transform to PrestaShop format
     * - Execute API call
     * - Update sync status
     * - Log operation
     *
     * @param Model $model Laravel Eloquent model to sync
     * @param BasePrestaShopClient $client PrestaShop API client (v8 or v9)
     * @param PrestaShopShop $shop Target shop configuration
     *
     * @return array Sync result with keys: success, external_id, message, checksum
     *
     * @throws \App\Exceptions\PrestaShopAPIException On API errors
     * @throws \InvalidArgumentException On validation errors
     */
    public function syncToPrestaShop(
        Model $model,
        BasePrestaShopClient $client,
        PrestaShopShop $shop
    ): array;

    /**
     * Calculate checksum for change detection
     *
     * Generates SHA256 hash z model data dla:
     * - Skip synchronization jeśli data unchanged
     * - Detect conflicts z remote data
     * - Track last synced state
     *
     * @param Model $model Laravel Eloquent model
     * @param PrestaShopShop $shop Shop dla shop-specific data
     *
     * @return string SHA256 hash (64 chars)
     */
    public function calculateChecksum(Model $model, PrestaShopShop $shop): string;

    /**
     * Handle sync error with logging and status update
     *
     * Centralizes error handling:
     * - Log error z full context
     * - Update sync status to 'error'
     * - Increment retry counter
     * - Store error message
     *
     * @param \Exception $exception Original exception
     * @param Model $model Model that failed to sync
     * @param PrestaShopShop $shop Target shop
     *
     * @return void
     */
    public function handleSyncError(
        \Exception $exception,
        Model $model,
        PrestaShopShop $shop
    ): void;

    /**
     * Validate model before sync
     *
     * Business rules validation:
     * - Required fields presence
     * - Data format correctness
     * - Business constraints
     *
     * @param Model $model Model to validate
     * @param PrestaShopShop $shop Target shop
     *
     * @return array Empty array jeśli valid, array of error messages otherwise
     */
    public function validateBeforeSync(Model $model, PrestaShopShop $shop): array;

    /**
     * Check if model needs sync
     *
     * Determines sync necessity based on:
     * - Checksum comparison
     * - Sync status
     * - Last sync timestamp
     *
     * @param Model $model Model to check
     * @param PrestaShopShop $shop Target shop
     *
     * @return bool True jeśli needs sync, false otherwise
     */
    public function needsSync(Model $model, PrestaShopShop $shop): bool;
}

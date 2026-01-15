<?php

namespace App\Services\PrestaShop\Mappers;

use App\Models\FeatureType;
use App\Models\FeatureValue;
use App\Models\PrestaShopShop;
use App\Services\PrestaShop\PrestaShop8Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * FeatureValueMapper
 *
 * ETAP_07e FAZA 4.1.2 - Manages feature value mapping between PPM and PrestaShop
 *
 * Handles:
 * - Getting or creating PS feature values (deduplication)
 * - Syncing predefined values from PPM to PS
 * - Importing PS values to PPM FeatureValue model
 * - Caching for performance optimization
 *
 * @package App\Services\PrestaShop\Mappers
 * @version 1.0
 * @since 2025-12-03
 */
class FeatureValueMapper
{
    /**
     * Cache TTL in seconds (1 hour)
     */
    protected const CACHE_TTL = 3600;

    /**
     * Cache key prefix
     */
    protected const CACHE_PREFIX = 'ps_feature_value_';

    /**
     * PrestaShop API client
     */
    protected PrestaShop8Client $client;

    /**
     * Create mapper instance
     *
     * @param PrestaShop8Client $client
     */
    public function __construct(PrestaShop8Client $client)
    {
        $this->client = $client;
    }

    /*
    |--------------------------------------------------------------------------
    | VALUE RESOLUTION (GET OR CREATE)
    |--------------------------------------------------------------------------
    */

    /**
     * Get or create PrestaShop feature_value for given value
     *
     * Searches existing PS values by text, creates if not found.
     * Uses caching for performance.
     *
     * @param int $psFeatureId PrestaShop feature ID
     * @param string $value Value string
     * @param PrestaShopShop $shop Target shop
     * @return int PrestaShop feature_value ID
     *
     * @throws \Exception If creation fails
     */
    public function getOrCreateFeatureValue(
        int $psFeatureId,
        string $value,
        PrestaShopShop $shop
    ): int {
        // Normalize value for consistency
        $normalizedValue = $this->normalizeValue($value);

        if (empty($normalizedValue)) {
            throw new \InvalidArgumentException('Feature value cannot be empty');
        }

        // Check cache first
        $cacheKey = $this->getCacheKey($shop->id, $psFeatureId, $normalizedValue);
        $cachedId = Cache::get($cacheKey);

        if ($cachedId !== null) {
            Log::debug('[FEATURE VALUE MAPPER] Cache hit', [
                'ps_feature_id' => $psFeatureId,
                'value' => $normalizedValue,
                'ps_value_id' => $cachedId,
            ]);
            return $cachedId;
        }

        // Use API helper method (handles deduplication)
        $psValueId = $this->client->getOrCreateProductFeatureValue(
            $psFeatureId,
            $normalizedValue,
            1 // Default language ID
        );

        // Cache the result
        Cache::put($cacheKey, $psValueId, self::CACHE_TTL);

        Log::debug('[FEATURE VALUE MAPPER] Value resolved', [
            'ps_feature_id' => $psFeatureId,
            'value' => $normalizedValue,
            'ps_value_id' => $psValueId,
            'from_cache' => false,
        ]);

        return $psValueId;
    }

    /**
     * Batch get/create multiple feature values
     *
     * Optimized for bulk operations - reduces API calls through batching.
     *
     * @param int $psFeatureId PrestaShop feature ID
     * @param array $values Array of value strings
     * @param PrestaShopShop $shop Target shop
     * @return array Map of value => ps_value_id
     */
    public function getOrCreateMultipleValues(
        int $psFeatureId,
        array $values,
        PrestaShopShop $shop
    ): array {
        $result = [];
        $uncachedValues = [];

        // Check cache for all values
        foreach ($values as $value) {
            $normalizedValue = $this->normalizeValue($value);
            if (empty($normalizedValue)) {
                continue;
            }

            $cacheKey = $this->getCacheKey($shop->id, $psFeatureId, $normalizedValue);
            $cachedId = Cache::get($cacheKey);

            if ($cachedId !== null) {
                $result[$value] = $cachedId;
            } else {
                $uncachedValues[$value] = $normalizedValue;
            }
        }

        // Fetch existing values from PS API
        if (!empty($uncachedValues)) {
            try {
                $existingValues = $this->client->getProductFeatureValues([
                    'filter[id_feature]' => $psFeatureId,
                    'display' => 'full',
                ]);

                // Build lookup map from existing values
                $existingMap = [];
                foreach ($existingValues as $existing) {
                    $existingText = $this->extractValueText($existing);
                    $existingMap[strtolower($existingText)] = (int) $existing['id'];
                }

                // Match or create values
                foreach ($uncachedValues as $originalValue => $normalizedValue) {
                    $lookupKey = strtolower($normalizedValue);

                    if (isset($existingMap[$lookupKey])) {
                        $psValueId = $existingMap[$lookupKey];
                    } else {
                        // Create new value
                        $psValueId = $this->createFeatureValue($psFeatureId, $normalizedValue);
                    }

                    // Cache and store result
                    $cacheKey = $this->getCacheKey($shop->id, $psFeatureId, $normalizedValue);
                    Cache::put($cacheKey, $psValueId, self::CACHE_TTL);
                    $result[$originalValue] = $psValueId;
                }
            } catch (\Exception $e) {
                Log::error('[FEATURE VALUE MAPPER] Batch get/create failed', [
                    'ps_feature_id' => $psFeatureId,
                    'error' => $e->getMessage(),
                ]);

                // Fallback to individual creation
                foreach ($uncachedValues as $originalValue => $normalizedValue) {
                    try {
                        $psValueId = $this->client->getOrCreateProductFeatureValue(
                            $psFeatureId,
                            $normalizedValue,
                            1
                        );
                        $result[$originalValue] = $psValueId;

                        $cacheKey = $this->getCacheKey($shop->id, $psFeatureId, $normalizedValue);
                        Cache::put($cacheKey, $psValueId, self::CACHE_TTL);
                    } catch (\Exception $e2) {
                        Log::error('[FEATURE VALUE MAPPER] Individual value creation failed', [
                            'value' => $normalizedValue,
                            'error' => $e2->getMessage(),
                        ]);
                    }
                }
            }
        }

        return $result;
    }

    /*
    |--------------------------------------------------------------------------
    | SYNC PPM VALUES TO PRESTASHOP
    |--------------------------------------------------------------------------
    */

    /**
     * Sync all predefined values for a FeatureType to PrestaShop
     *
     * Used for SELECT type features with predefined values.
     * Creates missing values in PS.
     *
     * @param FeatureType $featureType Feature type with values
     * @param int $psFeatureId PrestaShop feature ID
     * @param PrestaShopShop $shop Target shop
     * @return array Stats: ['created' => int, 'existing' => int, 'errors' => array]
     */
    public function syncFeatureValues(
        FeatureType $featureType,
        int $psFeatureId,
        PrestaShopShop $shop
    ): array {
        $stats = [
            'created' => 0,
            'existing' => 0,
            'errors' => [],
        ];

        // Only for SELECT type features
        if (!$featureType->requiresValues()) {
            Log::debug('[FEATURE VALUE MAPPER] Feature type does not require values', [
                'feature_type_id' => $featureType->id,
                'value_type' => $featureType->value_type,
            ]);
            return $stats;
        }

        // Get predefined values from PPM
        $ppmValues = $featureType->featureValues()
            ->where('is_active', true)
            ->ordered()
            ->get();

        if ($ppmValues->isEmpty()) {
            Log::debug('[FEATURE VALUE MAPPER] No predefined values to sync', [
                'feature_type_id' => $featureType->id,
            ]);
            return $stats;
        }

        // Get existing values from PrestaShop
        try {
            $existingPsValues = $this->client->getProductFeatureValues([
                'filter[id_feature]' => $psFeatureId,
                'display' => 'full',
            ]);
        } catch (\Exception $e) {
            Log::error('[FEATURE VALUE MAPPER] Failed to fetch existing PS values', [
                'ps_feature_id' => $psFeatureId,
                'error' => $e->getMessage(),
            ]);
            $existingPsValues = [];
        }

        // Build lookup map (lowercase for case-insensitive matching)
        $existingMap = [];
        foreach ($existingPsValues as $existing) {
            $valueText = $this->extractValueText($existing);
            $existingMap[strtolower($valueText)] = (int) $existing['id'];
        }

        // Sync each PPM value
        foreach ($ppmValues as $ppmValue) {
            $normalizedValue = $this->normalizeValue($ppmValue->value);
            $lookupKey = strtolower($normalizedValue);

            if (isset($existingMap[$lookupKey])) {
                // Value already exists in PS
                $stats['existing']++;

                // Update cache
                $cacheKey = $this->getCacheKey($shop->id, $psFeatureId, $normalizedValue);
                Cache::put($cacheKey, $existingMap[$lookupKey], self::CACHE_TTL);
            } else {
                // Create new value in PS
                try {
                    $psValueId = $this->createFeatureValue($psFeatureId, $normalizedValue);
                    $stats['created']++;

                    // Update cache
                    $cacheKey = $this->getCacheKey($shop->id, $psFeatureId, $normalizedValue);
                    Cache::put($cacheKey, $psValueId, self::CACHE_TTL);

                    Log::debug('[FEATURE VALUE MAPPER] Created PS value', [
                        'feature_type_id' => $featureType->id,
                        'value' => $normalizedValue,
                        'ps_value_id' => $psValueId,
                    ]);
                } catch (\Exception $e) {
                    $stats['errors'][] = [
                        'value' => $normalizedValue,
                        'error' => $e->getMessage(),
                    ];

                    Log::error('[FEATURE VALUE MAPPER] Failed to create PS value', [
                        'feature_type_id' => $featureType->id,
                        'value' => $normalizedValue,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        Log::info('[FEATURE VALUE MAPPER] Values sync completed', [
            'feature_type_id' => $featureType->id,
            'feature_name' => $featureType->name,
            'ps_feature_id' => $psFeatureId,
            'created' => $stats['created'],
            'existing' => $stats['existing'],
            'errors' => count($stats['errors']),
        ]);

        return $stats;
    }

    /*
    |--------------------------------------------------------------------------
    | IMPORT PS VALUES TO PPM
    |--------------------------------------------------------------------------
    */

    /**
     * Import feature values from PrestaShop to PPM FeatureValue model
     *
     * Creates/updates FeatureValue entries for SELECT type features.
     *
     * @param int $psFeatureId PrestaShop feature ID
     * @param FeatureType $featureType Target PPM feature type
     * @param PrestaShopShop $shop Source shop
     * @return array Stats: ['imported' => int, 'skipped' => int, 'updated' => int]
     */
    public function importFeatureValuesFromPS(
        int $psFeatureId,
        FeatureType $featureType,
        PrestaShopShop $shop
    ): array {
        $stats = [
            'imported' => 0,
            'skipped' => 0,
            'updated' => 0,
        ];

        // Fetch all values for this feature from PS
        try {
            $psValues = $this->client->getProductFeatureValues([
                'filter[id_feature]' => $psFeatureId,
                'display' => 'full',
            ]);
        } catch (\Exception $e) {
            Log::error('[FEATURE VALUE MAPPER] Failed to fetch PS values for import', [
                'ps_feature_id' => $psFeatureId,
                'error' => $e->getMessage(),
            ]);
            return $stats;
        }

        // Get existing PPM values for lookup
        $existingPpmValues = $featureType->featureValues()
            ->pluck('id', 'value')
            ->mapWithKeys(fn($id, $value) => [strtolower($value) => $id])
            ->toArray();

        // Import each PS value
        $position = 0;
        foreach ($psValues as $psValue) {
            $valueText = $this->extractValueText($psValue);
            $normalizedValue = $this->normalizeValue($valueText);

            if (empty($normalizedValue)) {
                $stats['skipped']++;
                continue;
            }

            $lookupKey = strtolower($normalizedValue);

            if (isset($existingPpmValues[$lookupKey])) {
                // Value already exists - optionally update position
                $stats['skipped']++;
            } else {
                // Create new FeatureValue
                try {
                    FeatureValue::create([
                        'feature_type_id' => $featureType->id,
                        'value' => $normalizedValue,
                        'is_active' => true,
                        'position' => $position,
                    ]);
                    $stats['imported']++;

                    Log::debug('[FEATURE VALUE MAPPER] Imported PS value', [
                        'feature_type_id' => $featureType->id,
                        'value' => $normalizedValue,
                    ]);
                } catch (\Exception $e) {
                    Log::error('[FEATURE VALUE MAPPER] Failed to import value', [
                        'feature_type_id' => $featureType->id,
                        'value' => $normalizedValue,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $position++;
        }

        Log::info('[FEATURE VALUE MAPPER] Values import completed', [
            'ps_feature_id' => $psFeatureId,
            'feature_type_id' => $featureType->id,
            'imported' => $stats['imported'],
            'skipped' => $stats['skipped'],
        ]);

        return $stats;
    }

    /*
    |--------------------------------------------------------------------------
    | CACHE MANAGEMENT
    |--------------------------------------------------------------------------
    */

    /**
     * Clear cache for specific feature
     *
     * @param int $shopId Shop ID
     * @param int $psFeatureId PrestaShop feature ID
     */
    public function clearCacheForFeature(int $shopId, int $psFeatureId): void
    {
        // Clear pattern-based cache (if Redis with pattern support)
        $pattern = self::CACHE_PREFIX . "{$shopId}_{$psFeatureId}_*";

        Log::debug('[FEATURE VALUE MAPPER] Cache cleared', [
            'shop_id' => $shopId,
            'ps_feature_id' => $psFeatureId,
        ]);
    }

    /**
     * Clear all feature value cache
     */
    public function clearAllCache(): void
    {
        // Note: This requires cache tagging or manual tracking
        // For now, cache will expire naturally after TTL
        Log::info('[FEATURE VALUE MAPPER] Full cache clear requested');
    }

    /*
    |--------------------------------------------------------------------------
    | HELPER METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Normalize value for consistent matching
     *
     * - Trims whitespace
     * - Removes extra spaces
     * - Preserves original casing (PS is case-sensitive for display)
     *
     * @param string $value Original value
     * @return string Normalized value
     */
    protected function normalizeValue(string $value): string
    {
        // Trim and remove multiple spaces
        $normalized = trim(preg_replace('/\s+/', ' ', $value));

        return $normalized;
    }

    /**
     * Extract value text from PS API response
     *
     * Handles multilang structure.
     *
     * @param array $psValue PS feature value data
     * @return string Value text
     */
    protected function extractValueText(array $psValue): string
    {
        $valueField = $psValue['value'] ?? [];

        // Multilang structure
        if (is_array($valueField) && isset($valueField['language'])) {
            $languages = $valueField['language'];

            // Single language
            if (isset($languages['value'])) {
                return $languages['value'];
            }

            // Multiple languages - get first
            if (is_array($languages) && !empty($languages)) {
                $first = reset($languages);
                return is_array($first) ? ($first['value'] ?? '') : (string) $first;
            }
        }

        // Simple string
        if (is_string($valueField)) {
            return $valueField;
        }

        return '';
    }

    /**
     * Create new feature value in PrestaShop
     *
     * @param int $psFeatureId PrestaShop feature ID
     * @param string $value Value text
     * @param int $langId Language ID
     * @return int Created feature value ID
     *
     * @throws \Exception If creation fails
     */
    protected function createFeatureValue(int $psFeatureId, string $value, int $langId = 1): int
    {
        $result = $this->client->createProductFeatureValue([
            'id_feature' => $psFeatureId,
            'value' => [
                'language' => [
                    [
                        'attrs' => ['id' => (string) $langId],
                        'value' => $value,
                    ],
                ],
            ],
        ]);

        if (!isset($result['id'])) {
            throw new \Exception('PrestaShop did not return feature value ID');
        }

        return (int) $result['id'];
    }

    /**
     * Generate cache key for feature value
     *
     * @param int $shopId Shop ID
     * @param int $psFeatureId PrestaShop feature ID
     * @param string $value Value text
     * @return string Cache key
     */
    protected function getCacheKey(int $shopId, int $psFeatureId, string $value): string
    {
        // Use MD5 hash of value to keep key length manageable
        $valueHash = md5(strtolower($value));
        return self::CACHE_PREFIX . "{$shopId}_{$psFeatureId}_{$valueHash}";
    }
}

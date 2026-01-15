<?php

namespace App\Services\PrestaShop;

use App\Models\FeatureType;
use App\Models\PrestashopFeatureMapping;
use App\Models\PrestaShopShop;
use App\Services\PrestaShop\Transformers\FeatureTransformer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * FeatureMappingManager
 *
 * ETAP_07e FAZA 4.2 - Business logic for feature mapping UI
 *
 * Handles:
 * - Getting mapping status with suggestions
 * - Auto-matching by name similarity
 * - Manual mapping creation
 * - Creating missing features in PrestaShop
 *
 * @package App\Services\PrestaShop
 * @version 1.0
 * @since 2025-12-03
 */
class FeatureMappingManager
{
    /**
     * PrestaShop API client
     */
    protected PrestaShop8Client $client;

    /**
     * Feature transformer
     */
    protected FeatureTransformer $transformer;

    /**
     * Default similarity threshold for auto-match (0.0 - 1.0)
     */
    protected const DEFAULT_SIMILARITY_THRESHOLD = 0.8;

    /**
     * Create manager instance
     *
     * @param PrestaShop8Client $client
     * @param FeatureTransformer $transformer
     */
    public function __construct(
        PrestaShop8Client $client,
        FeatureTransformer $transformer
    ) {
        $this->client = $client;
        $this->transformer = $transformer;
    }

    /*
    |--------------------------------------------------------------------------
    | MAPPING STATUS & SUGGESTIONS
    |--------------------------------------------------------------------------
    */

    /**
     * Get all mappings for shop with match suggestions
     *
     * Returns complete mapping status including:
     * - Currently mapped features
     * - Unmapped PPM features
     * - Unmapped PS features
     * - Auto-match suggestions
     *
     * @param PrestaShopShop $shop
     * @return array ['mapped' => [], 'unmapped_ppm' => [], 'unmapped_ps' => [], 'suggestions' => []]
     */
    public function getMappingsWithSuggestions(PrestaShopShop $shop): array
    {
        Log::debug('[FEATURE MAPPING] Getting mappings with suggestions', [
            'shop_id' => $shop->id,
        ]);

        // Get existing mappings
        $mappings = PrestashopFeatureMapping::where('shop_id', $shop->id)
            ->with('featureType')
            ->get();

        $mappedPpmIds = $mappings->pluck('feature_type_id')->toArray();
        $mappedPsIds = $mappings->pluck('prestashop_feature_id')->toArray();

        // Get unmapped PPM features
        $unmappedPpm = FeatureType::active()
            ->whereNotIn('id', $mappedPpmIds)
            ->get();

        // Get PS features
        $psFeatures = [];
        try {
            $psFeatures = $this->client->getProductFeatures(['display' => 'full']);
        } catch (\Exception $e) {
            Log::error('[FEATURE MAPPING] Failed to fetch PS features', [
                'error' => $e->getMessage(),
            ]);
        }

        // Build PS features lookup and find unmapped
        $unmappedPs = [];
        $psFeaturesMap = [];
        foreach ($psFeatures as $psFeature) {
            $psId = (int) ($psFeature['id'] ?? 0);
            $psName = $this->transformer->extractMultilangValue($psFeature['name'] ?? []);
            $psFeaturesMap[$psId] = $psName;

            if (!in_array($psId, $mappedPsIds)) {
                $unmappedPs[] = [
                    'id' => $psId,
                    'name' => $psName,
                ];
            }
        }

        // Generate suggestions for unmapped features
        $suggestions = $this->generateSuggestions($unmappedPpm, $unmappedPs);

        // Format mapped features for response
        $mappedFormatted = $mappings->map(function ($mapping) use ($psFeaturesMap) {
            return [
                'id' => $mapping->id,
                'feature_type_id' => $mapping->feature_type_id,
                'ppm_name' => $mapping->featureType?->name ?? 'Unknown',
                'ppm_code' => $mapping->featureType?->code ?? '',
                'ps_feature_id' => $mapping->prestashop_feature_id,
                'ps_name' => $psFeaturesMap[$mapping->prestashop_feature_id] ?? $mapping->prestashop_feature_name,
                'sync_direction' => $mapping->sync_direction,
                'sync_status' => $mapping->getSyncStatus(),
                'last_synced' => $mapping->last_synced_at?->format('Y-m-d H:i'),
                'is_active' => $mapping->is_active,
            ];
        })->toArray();

        // Format unmapped PPM
        $unmappedPpmFormatted = $unmappedPpm->map(function ($featureType) {
            return [
                'id' => $featureType->id,
                'code' => $featureType->code,
                'name' => $featureType->name,
                'value_type' => $featureType->value_type,
                'group' => $featureType->getGroupDisplayName(),
            ];
        })->toArray();

        return [
            'mapped' => $mappedFormatted,
            'unmapped_ppm' => $unmappedPpmFormatted,
            'unmapped_ps' => $unmappedPs,
            'suggestions' => $suggestions,
            'stats' => [
                'total_ppm' => FeatureType::active()->count(),
                'total_ps' => count($psFeatures),
                'mapped_count' => count($mappedFormatted),
                'unmapped_ppm_count' => count($unmappedPpmFormatted),
                'unmapped_ps_count' => count($unmappedPs),
                'suggestions_count' => count($suggestions),
            ],
        ];
    }

    /**
     * Generate match suggestions based on name similarity
     *
     * @param \Illuminate\Support\Collection $unmappedPpm
     * @param array $unmappedPs
     * @return array Suggestions with confidence scores
     */
    protected function generateSuggestions($unmappedPpm, array $unmappedPs): array
    {
        $suggestions = [];

        foreach ($unmappedPpm as $ppmFeature) {
            $bestMatch = null;
            $bestScore = 0;

            foreach ($unmappedPs as $psFeature) {
                $score = $this->calculateSimilarity(
                    $ppmFeature->name,
                    $psFeature['name']
                );

                // Also check prestashop_name if set
                if ($ppmFeature->prestashop_name) {
                    $altScore = $this->calculateSimilarity(
                        $ppmFeature->prestashop_name,
                        $psFeature['name']
                    );
                    $score = max($score, $altScore);
                }

                if ($score > $bestScore && $score >= self::DEFAULT_SIMILARITY_THRESHOLD) {
                    $bestScore = $score;
                    $bestMatch = $psFeature;
                }
            }

            if ($bestMatch) {
                $suggestions[] = [
                    'ppm_feature' => [
                        'id' => $ppmFeature->id,
                        'name' => $ppmFeature->name,
                        'code' => $ppmFeature->code,
                    ],
                    'ps_feature' => $bestMatch,
                    'confidence' => round($bestScore * 100),
                    'match_type' => $bestScore >= 0.95 ? 'exact' : 'similar',
                ];
            }
        }

        // Sort by confidence (highest first)
        usort($suggestions, fn($a, $b) => $b['confidence'] <=> $a['confidence']);

        return $suggestions;
    }

    /*
    |--------------------------------------------------------------------------
    | AUTO-MATCH
    |--------------------------------------------------------------------------
    */

    /**
     * Auto-match features by name similarity
     *
     * Uses Levenshtein distance for fuzzy matching.
     * Creates mappings for matches above threshold.
     *
     * @param PrestaShopShop $shop
     * @param float $threshold Similarity threshold (0.0-1.0, default: 0.8)
     * @return array Stats: ['matched' => int, 'skipped' => int, 'suggestions' => array]
     */
    public function autoMatchByName(PrestaShopShop $shop, float $threshold = 0.8): array
    {
        $stats = [
            'matched' => 0,
            'skipped' => 0,
            'suggestions' => [],
        ];

        Log::info('[FEATURE MAPPING] Starting auto-match', [
            'shop_id' => $shop->id,
            'threshold' => $threshold,
        ]);

        // Get current mapping status
        $status = $this->getMappingsWithSuggestions($shop);

        foreach ($status['suggestions'] as $suggestion) {
            $confidence = $suggestion['confidence'] / 100;

            if ($confidence >= $threshold) {
                // Auto-create mapping
                try {
                    $this->createMapping(
                        $suggestion['ppm_feature']['id'],
                        $suggestion['ps_feature']['id'],
                        $shop
                    );
                    $stats['matched']++;

                    Log::debug('[FEATURE MAPPING] Auto-matched', [
                        'ppm_feature_id' => $suggestion['ppm_feature']['id'],
                        'ps_feature_id' => $suggestion['ps_feature']['id'],
                        'confidence' => $confidence,
                    ]);
                } catch (\Exception $e) {
                    $stats['skipped']++;
                    $stats['suggestions'][] = $suggestion;

                    Log::error('[FEATURE MAPPING] Auto-match failed', [
                        'ppm_feature_id' => $suggestion['ppm_feature']['id'],
                        'error' => $e->getMessage(),
                    ]);
                }
            } else {
                // Below threshold - add to suggestions for manual review
                $stats['suggestions'][] = $suggestion;
                $stats['skipped']++;
            }
        }

        Log::info('[FEATURE MAPPING] Auto-match completed', [
            'shop_id' => $shop->id,
            'matched' => $stats['matched'],
            'skipped' => $stats['skipped'],
        ]);

        return $stats;
    }

    /*
    |--------------------------------------------------------------------------
    | MANUAL MAPPING
    |--------------------------------------------------------------------------
    */

    /**
     * Create manual mapping between PPM feature type and PS feature
     *
     * @param int $featureTypeId PPM FeatureType ID
     * @param int $psFeatureId PrestaShop feature ID
     * @param PrestaShopShop $shop
     * @param string $syncDirection Sync direction (default: both)
     * @return PrestashopFeatureMapping
     *
     * @throws \Exception If mapping already exists or validation fails
     */
    public function createMapping(
        int $featureTypeId,
        int $psFeatureId,
        PrestaShopShop $shop,
        string $syncDirection = PrestashopFeatureMapping::SYNC_BOTH
    ): PrestashopFeatureMapping {
        // Validate feature type exists
        $featureType = FeatureType::findOrFail($featureTypeId);

        // Check if mapping already exists
        $existingMapping = PrestashopFeatureMapping::where('feature_type_id', $featureTypeId)
            ->where('shop_id', $shop->id)
            ->first();

        if ($existingMapping) {
            // Update existing mapping
            $existingMapping->update([
                'prestashop_feature_id' => $psFeatureId,
                'sync_direction' => $syncDirection,
                'is_active' => true,
                'last_sync_error' => null,
            ]);

            Log::info('[FEATURE MAPPING] Updated existing mapping', [
                'mapping_id' => $existingMapping->id,
                'feature_type_id' => $featureTypeId,
                'ps_feature_id' => $psFeatureId,
            ]);

            return $existingMapping;
        }

        // Get PS feature name for reference
        $psFeatureName = null;
        try {
            $psFeature = $this->client->getProductFeature($psFeatureId);
            $psFeatureName = $this->transformer->extractMultilangValue($psFeature['name'] ?? []);
        } catch (\Exception $e) {
            Log::warning('[FEATURE MAPPING] Could not fetch PS feature name', [
                'ps_feature_id' => $psFeatureId,
                'error' => $e->getMessage(),
            ]);
        }

        // Create new mapping
        $mapping = PrestashopFeatureMapping::create([
            'feature_type_id' => $featureTypeId,
            'shop_id' => $shop->id,
            'prestashop_feature_id' => $psFeatureId,
            'prestashop_feature_name' => $psFeatureName ?? "PS Feature #{$psFeatureId}",
            'sync_direction' => $syncDirection,
            'auto_create_values' => true,
            'is_active' => true,
        ]);

        Log::info('[FEATURE MAPPING] Created mapping', [
            'mapping_id' => $mapping->id,
            'feature_type_id' => $featureTypeId,
            'ppm_name' => $featureType->name,
            'ps_feature_id' => $psFeatureId,
            'ps_name' => $psFeatureName,
        ]);

        return $mapping;
    }

    /**
     * Delete mapping
     *
     * @param int $mappingId
     * @return bool
     */
    public function deleteMapping(int $mappingId): bool
    {
        $mapping = PrestashopFeatureMapping::find($mappingId);
        if (!$mapping) {
            return false;
        }

        Log::info('[FEATURE MAPPING] Deleted mapping', [
            'mapping_id' => $mappingId,
            'feature_type_id' => $mapping->feature_type_id,
            'ps_feature_id' => $mapping->prestashop_feature_id,
        ]);

        return $mapping->delete();
    }

    /**
     * Deactivate mapping (soft disable)
     *
     * @param int $mappingId
     * @return bool
     */
    public function deactivateMapping(int $mappingId): bool
    {
        $mapping = PrestashopFeatureMapping::find($mappingId);
        if (!$mapping) {
            return false;
        }

        $mapping->update(['is_active' => false]);

        Log::info('[FEATURE MAPPING] Deactivated mapping', [
            'mapping_id' => $mappingId,
        ]);

        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | CREATE MISSING IN PRESTASHOP
    |--------------------------------------------------------------------------
    */

    /**
     * Create missing features in PrestaShop
     *
     * For unmapped PPM features, creates them in PS and auto-maps.
     *
     * @param array $featureTypeIds PPM FeatureType IDs to create
     * @param PrestaShopShop $shop
     * @return array Stats: ['created' => int, 'errors' => array]
     */
    public function createMissingInPrestaShop(
        array $featureTypeIds,
        PrestaShopShop $shop
    ): array {
        $stats = [
            'created' => 0,
            'errors' => [],
        ];

        Log::info('[FEATURE MAPPING] Creating missing features in PS', [
            'shop_id' => $shop->id,
            'feature_count' => count($featureTypeIds),
        ]);

        foreach ($featureTypeIds as $featureTypeId) {
            $featureType = FeatureType::find($featureTypeId);
            if (!$featureType) {
                $stats['errors'][] = [
                    'feature_type_id' => $featureTypeId,
                    'error' => 'FeatureType not found',
                ];
                continue;
            }

            // Check if already mapped
            $existingMapping = PrestashopFeatureMapping::where('feature_type_id', $featureTypeId)
                ->where('shop_id', $shop->id)
                ->where('is_active', true)
                ->first();

            if ($existingMapping) {
                Log::debug('[FEATURE MAPPING] Feature already mapped, skipping', [
                    'feature_type_id' => $featureTypeId,
                ]);
                continue;
            }

            try {
                // Transform to PS format
                $psData = $this->transformer->transformFeatureTypeToPS($featureType);

                // Create in PrestaShop
                $result = $this->client->createProductFeature($psData);
                $psFeatureId = $result['id'] ?? null;

                if (!$psFeatureId) {
                    throw new \Exception('PrestaShop did not return feature ID');
                }

                // Create mapping
                $this->createMapping($featureTypeId, (int) $psFeatureId, $shop);

                $stats['created']++;

                Log::info('[FEATURE MAPPING] Created feature in PS', [
                    'feature_type_id' => $featureTypeId,
                    'ppm_name' => $featureType->name,
                    'ps_feature_id' => $psFeatureId,
                ]);

            } catch (\Exception $e) {
                $stats['errors'][] = [
                    'feature_type_id' => $featureTypeId,
                    'name' => $featureType->name,
                    'error' => $e->getMessage(),
                ];

                Log::error('[FEATURE MAPPING] Failed to create feature in PS', [
                    'feature_type_id' => $featureTypeId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('[FEATURE MAPPING] Create missing completed', [
            'shop_id' => $shop->id,
            'created' => $stats['created'],
            'errors' => count($stats['errors']),
        ]);

        return $stats;
    }

    /*
    |--------------------------------------------------------------------------
    | SIMILARITY CALCULATION
    |--------------------------------------------------------------------------
    */

    /**
     * Calculate similarity between two strings
     *
     * Uses combination of:
     * - Levenshtein distance (normalized)
     * - Similar text percentage
     * - Word overlap
     *
     * @param string $string1
     * @param string $string2
     * @return float Similarity score (0.0 - 1.0)
     */
    protected function calculateSimilarity(string $string1, string $string2): float
    {
        // Normalize strings
        $s1 = $this->normalizeForComparison($string1);
        $s2 = $this->normalizeForComparison($string2);

        // Exact match after normalization
        if ($s1 === $s2) {
            return 1.0;
        }

        // Empty string handling
        if (empty($s1) || empty($s2)) {
            return 0.0;
        }

        // Calculate multiple similarity metrics

        // 1. Levenshtein distance (normalized)
        $maxLen = max(strlen($s1), strlen($s2));
        $levenshtein = levenshtein($s1, $s2);
        $levenshteinScore = 1 - ($levenshtein / $maxLen);

        // 2. Similar text percentage
        $similarTextScore = 0;
        similar_text($s1, $s2, $similarTextScore);
        $similarTextScore /= 100;

        // 3. Word overlap
        $words1 = array_filter(explode(' ', $s1));
        $words2 = array_filter(explode(' ', $s2));

        if (!empty($words1) && !empty($words2)) {
            $commonWords = count(array_intersect($words1, $words2));
            $totalWords = count(array_unique(array_merge($words1, $words2)));
            $wordOverlapScore = $commonWords / $totalWords;
        } else {
            $wordOverlapScore = 0;
        }

        // Weighted average of scores
        $weights = [
            'levenshtein' => 0.4,
            'similar_text' => 0.3,
            'word_overlap' => 0.3,
        ];

        $finalScore =
            ($levenshteinScore * $weights['levenshtein']) +
            ($similarTextScore * $weights['similar_text']) +
            ($wordOverlapScore * $weights['word_overlap']);

        return round($finalScore, 4);
    }

    /**
     * Normalize string for comparison
     *
     * - Lowercase
     * - Remove accents
     * - Remove special characters
     * - Trim and collapse spaces
     *
     * @param string $string
     * @return string
     */
    protected function normalizeForComparison(string $string): string
    {
        // Lowercase
        $normalized = mb_strtolower($string, 'UTF-8');

        // Polish characters mapping
        $polishMap = [
            'ą' => 'a', 'ć' => 'c', 'ę' => 'e', 'ł' => 'l',
            'ń' => 'n', 'ó' => 'o', 'ś' => 's', 'ź' => 'z', 'ż' => 'z',
        ];
        $normalized = strtr($normalized, $polishMap);

        // Remove content in parentheses (units like "(W)", "(kg)")
        $normalized = preg_replace('/\s*\([^)]*\)\s*/', ' ', $normalized);

        // Remove special characters, keep alphanumeric and spaces
        $normalized = preg_replace('/[^a-z0-9\s]/', '', $normalized);

        // Collapse multiple spaces
        $normalized = preg_replace('/\s+/', ' ', $normalized);

        return trim($normalized);
    }

    /*
    |--------------------------------------------------------------------------
    | BULK OPERATIONS
    |--------------------------------------------------------------------------
    */

    /**
     * Apply multiple suggested mappings
     *
     * @param array $suggestions Array of suggestions to apply
     * @param PrestaShopShop $shop
     * @return array Stats: ['applied' => int, 'errors' => array]
     */
    public function applySuggestions(array $suggestions, PrestaShopShop $shop): array
    {
        $stats = [
            'applied' => 0,
            'errors' => [],
        ];

        foreach ($suggestions as $suggestion) {
            $ppmId = $suggestion['ppm_feature']['id'] ?? null;
            $psId = $suggestion['ps_feature']['id'] ?? null;

            if (!$ppmId || !$psId) {
                continue;
            }

            try {
                $this->createMapping($ppmId, $psId, $shop);
                $stats['applied']++;
            } catch (\Exception $e) {
                $stats['errors'][] = [
                    'ppm_id' => $ppmId,
                    'ps_id' => $psId,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $stats;
    }

    /**
     * Sync all mappings for a shop
     *
     * Triggers sync for all active mappings.
     *
     * @param PrestaShopShop $shop
     * @return array Stats
     */
    public function syncAllMappings(PrestaShopShop $shop): array
    {
        $stats = [
            'total' => 0,
            'synced' => 0,
            'errors' => [],
        ];

        $mappings = PrestashopFeatureMapping::where('shop_id', $shop->id)
            ->where('is_active', true)
            ->with('featureType')
            ->get();

        $stats['total'] = $mappings->count();

        foreach ($mappings as $mapping) {
            try {
                $mapping->markSynced();
                $stats['synced']++;
            } catch (\Exception $e) {
                $mapping->markSyncError($e->getMessage());
                $stats['errors'][] = [
                    'mapping_id' => $mapping->id,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $stats;
    }
}

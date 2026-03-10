<?php

namespace App\Services\Location;

use App\Models\Location;
use App\Models\Product;
use App\Models\ProductStock;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * LocationSuggestionEngine - Silnik sugestii lokalizacji magazynowych
 *
 * Dwa tryby pracy:
 * - SMART: Sugestia na podstawie kategorii/marki produktu (pusty input)
 * - PREFIX: Sugestia na podstawie wpisanego prefiksu kodu lokalizacji
 *
 * Scoring Algorithm (SMART mode):
 * - categoryScore  (0.40) - ile produktow tej samej kategorii jest na lokalizacji
 * - brandScore     (0.30) - ile produktow tego samego manufacturer jest na lokalizacji
 * - overlapScore   (0.20) - ile produktow ma OBE cechy (kategoria + marka)
 * - popularityScore(0.10) - popularnosc lokalizacji vs max
 *
 * Threshold: score >= 0.85 i top-2 diff >= 0.10 (conservative)
 *
 * @package App\Services\Location
 * @version 1.0
 * @since ETAP_08
 */
class LocationSuggestionEngine
{
    /**
     * Minimum score threshold for SMART suggestion.
     * Below this value, no suggestion is returned (prefer no suggestion over wrong one).
     */
    private const SCORE_THRESHOLD = 0.85;

    /**
     * Minimum difference between top-1 and top-2 scores.
     * If the difference is smaller, the result is too ambiguous.
     */
    private const AMBIGUITY_THRESHOLD = 0.10;

    /**
     * Cache TTL for location index (in seconds).
     */
    private const CACHE_TTL_SECONDS = 600; // 10 minutes

    /**
     * Maximum number of prefix suggestions returned.
     */
    private const PREFIX_LIMIT = 8;

    /**
     * Scoring weights for SMART mode.
     */
    private const WEIGHT_CATEGORY = 0.40;
    private const WEIGHT_BRAND = 0.30;
    private const WEIGHT_OVERLAP = 0.20;
    private const WEIGHT_POPULARITY = 0.10;

    /**
     * SMART suggestion for empty input based on product context.
     *
     * Analyzes which locations contain products with similar category/manufacturer
     * and suggests the most statistically likely location.
     *
     * @param int $productId Product ID to analyze
     * @param int $warehouseId Warehouse to suggest within
     * @return array|null Suggestion array or null if no confident suggestion
     */
    public function suggestSmart(int $productId, int $warehouseId): ?array
    {
        $product = Product::with(['categories' => function ($query) {
            $query->wherePivot('is_primary', true);
        }, 'manufacturerRelation'])->find($productId);

        if (!$product) {
            return null;
        }

        $primaryCategory = $product->categories->first();
        $categoryId = $primaryCategory?->id;
        $manufacturerId = $product->manufacturer_id;

        if ($categoryId === null && $manufacturerId === null) {
            return null;
        }

        $index = $this->getLocationIndex($warehouseId);

        if (empty($index['locations'])) {
            return null;
        }

        $maxProductCount = $index['max_product_count'] ?? 1;
        $scored = [];

        foreach ($index['locations'] as $code => $locData) {
            $totalProducts = $locData['product_count'];

            if ($totalProducts === 0) {
                continue;
            }

            // Category score: fraction of products at this location in the same category
            $categoryScore = 0.0;
            if ($categoryId !== null && isset($locData['categories'][$categoryId])) {
                $categoryScore = $locData['categories'][$categoryId] / $totalProducts;
            }

            // Brand score: fraction of products at this location with the same manufacturer
            $brandScore = 0.0;
            if ($manufacturerId !== null && isset($locData['manufacturers'][$manufacturerId])) {
                $brandScore = $locData['manufacturers'][$manufacturerId] / $totalProducts;
            }

            // Overlap score: fraction of products with BOTH category + brand match
            $overlapScore = 0.0;
            if ($categoryId !== null && $manufacturerId !== null) {
                $pairKey = $categoryId . '_' . $manufacturerId;
                if (isset($locData['category_brand_pairs'][$pairKey])) {
                    $overlapScore = $locData['category_brand_pairs'][$pairKey] / $totalProducts;
                }
            }

            // Popularity score: how full is this location relative to the busiest one
            $popularityScore = $totalProducts / max($maxProductCount, 1);

            $totalScore = ($categoryScore * self::WEIGHT_CATEGORY)
                        + ($brandScore * self::WEIGHT_BRAND)
                        + ($overlapScore * self::WEIGHT_OVERLAP)
                        + ($popularityScore * self::WEIGHT_POPULARITY);

            $scored[] = [
                'code' => $code,
                'score' => $totalScore,
                'category_score' => $categoryScore,
                'brand_score' => $brandScore,
                'overlap_score' => $overlapScore,
                'popularity_score' => $popularityScore,
            ];
        }

        if (empty($scored)) {
            return null;
        }

        // Sort by score DESC
        usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);

        $top = $scored[0];

        // Threshold check: score must be high enough
        if ($top['score'] < self::SCORE_THRESHOLD) {
            return null;
        }

        // Ambiguity check: top-2 diff must be significant
        if (count($scored) >= 2) {
            $secondScore = $scored[1]['score'];
            if (($top['score'] - $secondScore) < self::AMBIGUITY_THRESHOLD) {
                return null;
            }
        }

        // Build human-readable reason
        $reason = $this->buildReason(
            $primaryCategory?->name,
            $top['category_score'],
            $product->manufacturerRelation?->name ?? $product->manufacturer,
            $top['brand_score']
        );

        return [
            'ghost' => $top['code'],
            'score' => round($top['score'], 2),
            'reason' => $reason,
        ];
    }

    /**
     * PREFIX suggestion based on typed prefix.
     *
     * Returns matching locations ordered by product count (most popular first),
     * with the top result as ghost text and all matches as alternatives.
     *
     * @param string $prefix User-typed prefix (e.g. "CR_", "A-01")
     * @param int $warehouseId Warehouse to search within
     * @return array Suggestion array with ghost + alternatives, or empty array
     */
    public function suggestByPrefix(string $prefix, int $warehouseId): array
    {
        $prefix = trim($prefix);

        if ($prefix === '') {
            return [];
        }

        $locations = Location::where('warehouse_id', $warehouseId)
            ->where('code', 'LIKE', $prefix . '%')
            ->where('is_active', true)
            ->orderByDesc('product_count')
            ->orderBy('code')
            ->limit(self::PREFIX_LIMIT)
            ->get(['id', 'code', 'product_count', 'description']);

        if ($locations->isEmpty()) {
            return [];
        }

        $alternatives = $locations->map(fn (Location $loc) => [
            'code' => $loc->code,
            'count' => $loc->product_count,
            'description' => $loc->description,
        ])->all();

        return [
            'ghost' => $locations->first()->code,
            'alternatives' => $alternatives,
        ];
    }

    /**
     * Get cached location index for SMART scoring.
     *
     * Builds a denormalized index of all locations in a warehouse with:
     * - Product counts per category
     * - Product counts per manufacturer
     * - Product counts per category+brand pair
     *
     * @param int $warehouseId Warehouse ID
     * @return array Index structure with 'locations' and 'max_product_count'
     */
    public function getLocationIndex(int $warehouseId): array
    {
        $cacheKey = "location_smart_index_{$warehouseId}";

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($warehouseId) {
            return $this->buildLocationIndex($warehouseId);
        });
    }

    /**
     * Clear suggestion cache.
     *
     * @param int|null $warehouseId Specific warehouse or null for all
     * @return void
     */
    public function clearCache(?int $warehouseId = null): void
    {
        if ($warehouseId !== null) {
            Cache::forget("location_smart_index_{$warehouseId}");
            return;
        }

        // Clear all warehouse caches - get all warehouse IDs
        $warehouseIds = DB::table('warehouses')->pluck('id');

        foreach ($warehouseIds as $id) {
            Cache::forget("location_smart_index_{$id}");
        }
    }

    /**
     * Build the location index from database.
     *
     * Joins product_stock -> products -> product_categories to gather
     * category/manufacturer distribution per location code.
     *
     * @param int $warehouseId
     * @return array
     */
    private function buildLocationIndex(int $warehouseId): array
    {
        // Get all active locations for this warehouse
        $locations = Location::where('warehouse_id', $warehouseId)
            ->where('is_active', true)
            ->get(['id', 'code', 'product_count']);

        if ($locations->isEmpty()) {
            return ['locations' => [], 'max_product_count' => 0];
        }

        $locationCodes = $locations->pluck('code')->all();

        // Get product_stock records for this warehouse that have a location value
        // matching any known location code. ProductStock.location is comma-separated.
        $stockRows = DB::table('product_stock as ps')
            ->join('products as p', 'ps.product_id', '=', 'p.id')
            ->leftJoin('product_categories as pc', function ($join) {
                $join->on('p.id', '=', 'pc.product_id')
                     ->where('pc.is_primary', '=', true)
                     ->whereNull('pc.shop_id');
            })
            ->where('ps.warehouse_id', $warehouseId)
            ->whereNotNull('ps.location')
            ->where('ps.location', '!=', '')
            ->select([
                'ps.location',
                'pc.category_id',
                'p.manufacturer_id',
                'p.id as product_id',
            ])
            ->get();

        // Initialize index from Location records
        $index = [];
        foreach ($locations as $loc) {
            $index[$loc->code] = [
                'location_id' => $loc->id,
                'code' => $loc->code,
                'product_count' => $loc->product_count,
                'categories' => [],
                'manufacturers' => [],
                'category_brand_pairs' => [],
            ];
        }

        // Build a set for fast lookup
        $locationCodeSet = array_flip($locationCodes);

        // Process each stock row - split comma-separated locations
        foreach ($stockRows as $row) {
            $codes = array_map('trim', explode(',', $row->location));

            foreach ($codes as $code) {
                if ($code === '' || !isset($locationCodeSet[$code])) {
                    continue;
                }

                if (!isset($index[$code])) {
                    continue;
                }

                $catId = $row->category_id;
                $mfgId = $row->manufacturer_id;

                if ($catId !== null) {
                    $catId = (int) $catId;
                    $index[$code]['categories'][$catId] = ($index[$code]['categories'][$catId] ?? 0) + 1;
                }

                if ($mfgId !== null) {
                    $mfgId = (int) $mfgId;
                    $index[$code]['manufacturers'][$mfgId] = ($index[$code]['manufacturers'][$mfgId] ?? 0) + 1;
                }

                if ($catId !== null && $mfgId !== null) {
                    $pairKey = $catId . '_' . $mfgId;
                    $index[$code]['category_brand_pairs'][$pairKey] =
                        ($index[$code]['category_brand_pairs'][$pairKey] ?? 0) + 1;
                }
            }
        }

        $maxProductCount = $locations->max('product_count') ?: 1;

        return [
            'locations' => $index,
            'max_product_count' => $maxProductCount,
        ];
    }

    /**
     * Build human-readable reason string for SMART suggestion.
     *
     * @param string|null $categoryName
     * @param float $categoryScore
     * @param string|null $brandName
     * @param float $brandScore
     * @return string
     */
    private function buildReason(
        ?string $categoryName,
        float $categoryScore,
        ?string $brandName,
        float $brandScore
    ): string {
        $parts = [];

        if ($categoryName !== null && $categoryScore > 0) {
            $parts[] = 'Kategoria: ' . $categoryName . ' (' . round($categoryScore * 100) . '%)';
        }

        if ($brandName !== null && $brandScore > 0) {
            $parts[] = 'Marka: ' . $brandName . ' (' . round($brandScore * 100) . '%)';
        }

        if (empty($parts)) {
            return 'Na podstawie analizy lokalizacji';
        }

        return implode(', ', $parts);
    }
}

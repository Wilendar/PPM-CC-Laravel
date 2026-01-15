<?php

namespace App\Services\Compatibility;

use App\Models\CompatibilitySuggestion;
use App\Models\PrestaShopShop;
use App\Models\Product;
use App\Models\VehicleModel;
use App\Models\VehicleCompatibility;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * SmartSuggestionEngine Service
 *
 * ETAP_05d FAZA 2.1 - AI-powered compatibility suggestion engine
 *
 * PURPOSE:
 * - Generate compatibility suggestions based on text matching
 * - Per-shop suggestions (different brands per shop)
 * - Confidence scoring (0.00 - 1.00)
 * - TTL-based caching (24h default)
 *
 * SCORING ALGORITHM:
 * - Brand match: product.manufacturer == vehicle.brand → +0.50 (highest weight)
 * - Name match: product.name CONTAINS vehicle.model → +0.30
 * - Description match: product.description CONTAINS vehicle → +0.10
 * - Category match: matching category patterns → +0.10
 * - Total confidence: 0.00 - 1.00
 *
 * USAGE:
 * ```php
 * $engine = app(SmartSuggestionEngine::class);
 * $suggestions = $engine->generateForProduct($product, $shop);
 * $autoApplied = $engine->applyHighConfidenceSuggestions($shop, $user);
 * ```
 */
class SmartSuggestionEngine
{
    /**
     * Scoring weights for each match type
     */
    protected const WEIGHT_BRAND_MATCH = 0.50;
    protected const WEIGHT_NAME_MATCH = 0.30;
    protected const WEIGHT_DESCRIPTION_MATCH = 0.10;
    protected const WEIGHT_CATEGORY_MATCH = 0.10;

    /**
     * Minimum confidence threshold for suggestions
     */
    protected const MIN_CONFIDENCE_THRESHOLD = 0.30;

    /**
     * Auto-apply threshold (>=0.90 = very high confidence)
     */
    protected const AUTO_APPLY_THRESHOLD = 0.90;

    /**
     * Maximum suggestions per product per shop
     */
    protected const MAX_SUGGESTIONS_PER_PRODUCT = 50;

    /**
     * Generate suggestions for a single product
     *
     * @param Product $product Product to analyze
     * @param PrestaShopShop $shop Shop context (for brand filtering)
     * @param bool $refresh Force regeneration (ignore cache)
     * @return Collection<CompatibilitySuggestion>
     */
    public function generateForProduct(
        Product $product,
        PrestaShopShop $shop,
        bool $refresh = false
    ): Collection {
        // Check if shop has smart suggestions enabled
        if (!$shop->hasSmartSuggestionsEnabled()) {
            return collect();
        }

        // Check cache unless forced refresh
        if (!$refresh) {
            $cached = $this->getCachedSuggestions($product, $shop);
            if ($cached->isNotEmpty()) {
                return $cached;
            }
        }

        // Get existing compatibility records (to avoid duplicates)
        $existingVehicleIds = VehicleCompatibility::byProduct($product->id)
            ->byShop($shop->id)
            ->pluck('vehicle_model_id')
            ->toArray();

        // Get all vehicle models filtered by shop's allowed brands
        $vehicles = $this->getEligibleVehicles($shop, $existingVehicleIds);

        if ($vehicles->isEmpty()) {
            return collect();
        }

        $suggestions = collect();
        $minConfidence = $shop->getMinConfidenceScore();

        foreach ($vehicles as $vehicle) {
            $result = $this->calculateScore($product, $vehicle);

            // Skip if below minimum threshold
            if ($result['score'] < max($minConfidence, self::MIN_CONFIDENCE_THRESHOLD)) {
                continue;
            }

            $suggestions->push($this->createSuggestion(
                $product,
                $vehicle,
                $shop,
                $result['score'],
                $result['reason']
            ));

            // Limit suggestions per product
            if ($suggestions->count() >= self::MAX_SUGGESTIONS_PER_PRODUCT) {
                break;
            }
        }

        // Sort by confidence (highest first)
        return $suggestions->sortByDesc('confidence_score')->values();
    }

    /**
     * Generate suggestions for multiple products (batch)
     *
     * @param Collection<Product> $products Products to analyze
     * @param PrestaShopShop $shop Shop context
     * @param bool $refresh Force regeneration
     * @return int Number of suggestions generated
     */
    public function generateForProducts(
        Collection $products,
        PrestaShopShop $shop,
        bool $refresh = false
    ): int {
        $totalGenerated = 0;

        foreach ($products as $product) {
            $suggestions = $this->generateForProduct($product, $shop, $refresh);
            $totalGenerated += $suggestions->count();
        }

        Log::info("SmartSuggestionEngine: Generated {$totalGenerated} suggestions for " .
            $products->count() . " products in shop {$shop->name}");

        return $totalGenerated;
    }

    /**
     * Apply high-confidence suggestions automatically
     *
     * @param PrestaShopShop $shop Shop context
     * @param \App\Models\User $user User for attribution
     * @return int Number of suggestions applied
     */
    public function applyHighConfidenceSuggestions(
        PrestaShopShop $shop,
        \App\Models\User $user
    ): int {
        // Check if auto-apply is enabled
        if (!$shop->hasAutoApplySuggestionsEnabled()) {
            return 0;
        }

        $suggestions = CompatibilitySuggestion::byShop($shop->id)
            ->autoApplyReady()
            ->get();

        $appliedCount = 0;

        foreach ($suggestions as $suggestion) {
            try {
                $suggestion->apply($user);
                $appliedCount++;
            } catch (\Exception $e) {
                Log::warning("SmartSuggestionEngine: Failed to auto-apply suggestion #{$suggestion->id}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($appliedCount > 0) {
            Log::info("SmartSuggestionEngine: Auto-applied {$appliedCount} high-confidence suggestions for shop {$shop->name}");
        }

        return $appliedCount;
    }

    /**
     * Calculate confidence score for product-vehicle pair
     *
     * @param Product $product
     * @param VehicleModel $vehicle
     * @return array{score: float, reason: string}
     */
    protected function calculateScore(Product $product, VehicleModel $vehicle): array
    {
        $score = 0.0;
        $reasons = [];

        // 1. Brand match (highest weight: 0.50)
        if ($this->matchesBrand($product, $vehicle)) {
            $score += self::WEIGHT_BRAND_MATCH;
            $reasons[] = CompatibilitySuggestion::REASON_BRAND_MATCH;
        }

        // 2. Name match (0.30)
        if ($this->matchesName($product, $vehicle)) {
            $score += self::WEIGHT_NAME_MATCH;
            $reasons[] = CompatibilitySuggestion::REASON_NAME_MATCH;
        }

        // 3. Description match (0.10)
        if ($this->matchesDescription($product, $vehicle)) {
            $score += self::WEIGHT_DESCRIPTION_MATCH;
            $reasons[] = CompatibilitySuggestion::REASON_DESCRIPTION_MATCH;
        }

        // 4. Category match (0.10)
        if ($this->matchesCategory($product, $vehicle)) {
            $score += self::WEIGHT_CATEGORY_MATCH;
            $reasons[] = CompatibilitySuggestion::REASON_CATEGORY_MATCH;
        }

        // Primary reason is the highest weighted match
        $primaryReason = !empty($reasons)
            ? $reasons[0]
            : CompatibilitySuggestion::REASON_SIMILAR_PRODUCT;

        return [
            'score' => min(1.0, $score), // Cap at 1.0
            'reason' => $primaryReason,
        ];
    }

    /**
     * Check if product manufacturer matches vehicle brand
     */
    protected function matchesBrand(Product $product, VehicleModel $vehicle): bool
    {
        $productBrand = strtolower(trim($product->manufacturer ?? ''));
        $vehicleBrand = strtolower(trim($vehicle->brand ?? ''));

        if (empty($productBrand) || empty($vehicleBrand)) {
            return false;
        }

        // Exact match or contains
        return $productBrand === $vehicleBrand
            || str_contains($productBrand, $vehicleBrand)
            || str_contains($vehicleBrand, $productBrand);
    }

    /**
     * Check if product name contains vehicle model name
     */
    protected function matchesName(Product $product, VehicleModel $vehicle): bool
    {
        $productName = strtolower(trim($product->name ?? ''));
        $vehicleModel = strtolower(trim($vehicle->model ?? ''));
        $vehicleName = strtolower(trim($vehicle->name ?? ''));

        if (empty($productName)) {
            return false;
        }

        // Check model name
        if (!empty($vehicleModel) && str_contains($productName, $vehicleModel)) {
            return true;
        }

        // Check vehicle name
        if (!empty($vehicleName) && str_contains($productName, $vehicleName)) {
            return true;
        }

        return false;
    }

    /**
     * Check if product description mentions vehicle
     */
    protected function matchesDescription(Product $product, VehicleModel $vehicle): bool
    {
        $description = strtolower(strip_tags($product->description ?? ''));
        $vehicleModel = strtolower(trim($vehicle->model ?? ''));
        $vehicleBrand = strtolower(trim($vehicle->brand ?? ''));

        if (empty($description)) {
            return false;
        }

        // Check for model or brand mentions
        if (!empty($vehicleModel) && str_contains($description, $vehicleModel)) {
            return true;
        }

        if (!empty($vehicleBrand) && str_contains($description, $vehicleBrand)) {
            return true;
        }

        return false;
    }

    /**
     * Check if product category matches vehicle-related patterns
     */
    protected function matchesCategory(Product $product, VehicleModel $vehicle): bool
    {
        // Get product categories
        $categories = $product->categories ?? collect();
        if ($categories->isEmpty()) {
            return false;
        }

        $vehicleBrand = strtolower(trim($vehicle->brand ?? ''));

        foreach ($categories as $category) {
            $categoryName = strtolower($category->name ?? '');

            // Check if category contains brand name
            if (!empty($vehicleBrand) && str_contains($categoryName, $vehicleBrand)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get cached suggestions for product+shop
     */
    protected function getCachedSuggestions(Product $product, PrestaShopShop $shop): Collection
    {
        return CompatibilitySuggestion::byProduct($product->id)
            ->byShop($shop->id)
            ->active()
            ->orderByDesc('confidence_score')
            ->get();
    }

    /**
     * Get eligible vehicles for a shop (respecting brand restrictions)
     *
     * @param PrestaShopShop $shop
     * @param array $excludeVehicleIds Vehicle IDs to exclude (already matched)
     * @return Collection<VehicleModel>
     */
    protected function getEligibleVehicles(PrestaShopShop $shop, array $excludeVehicleIds = []): Collection
    {
        $query = VehicleModel::query();

        // Exclude already matched vehicles
        if (!empty($excludeVehicleIds)) {
            $query->whereNotIn('id', $excludeVehicleIds);
        }

        // Apply brand restrictions
        $allowedBrands = $shop->allowed_vehicle_brands;

        if ($allowedBrands === null) {
            // null = all brands allowed
        } elseif (empty($allowedBrands)) {
            // empty array = no brands allowed
            return collect();
        } else {
            // array = whitelist
            $query->whereIn('brand', $allowedBrands);
        }

        return $query->get();
    }

    /**
     * Create a suggestion record
     */
    protected function createSuggestion(
        Product $product,
        VehicleModel $vehicle,
        PrestaShopShop $shop,
        float $score,
        string $reason
    ): CompatibilitySuggestion {
        return CompatibilitySuggestion::updateOrCreate(
            [
                'product_id' => $product->id,
                'vehicle_model_id' => $vehicle->id,
                'shop_id' => $shop->id,
            ],
            [
                'part_sku' => $product->sku,
                'vehicle_sku' => $vehicle->sku ?? '',
                'suggestion_reason' => $reason,
                'confidence_score' => round($score, 2),
                'suggested_type' => CompatibilitySuggestion::TYPE_ORIGINAL,
                'expires_at' => now()->addHours(CompatibilitySuggestion::DEFAULT_TTL_HOURS),
            ]
        );
    }

    /**
     * Cleanup expired suggestions for all shops
     *
     * @return int Number of suggestions deleted
     */
    public function cleanupExpired(): int
    {
        $deleted = CompatibilitySuggestion::cleanupExpired();

        if ($deleted > 0) {
            Log::info("SmartSuggestionEngine: Cleaned up {$deleted} expired suggestions");
        }

        return $deleted;
    }

    /**
     * Get statistics for a shop
     *
     * @param PrestaShopShop $shop
     * @return array
     */
    public function getStatistics(PrestaShopShop $shop): array
    {
        $base = CompatibilitySuggestion::byShop($shop->id);

        return [
            'total' => (clone $base)->count(),
            'active' => (clone $base)->active()->count(),
            'applied' => (clone $base)->where('is_applied', true)->count(),
            'dismissed' => (clone $base)->where('is_dismissed', true)->count(),
            'expired' => (clone $base)->expired()->count(),
            'high_confidence' => (clone $base)->active()->highConfidence()->count(),
            'auto_apply_ready' => (clone $base)->autoApplyReady()->count(),
            'avg_confidence' => round((clone $base)->active()->avg('confidence_score') ?? 0, 2),
        ];
    }
}

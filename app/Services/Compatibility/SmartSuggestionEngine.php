<?php

namespace App\Services\Compatibility;

use App\Models\CompatibilitySuggestion;
use App\Models\PrestaShopShop;
use App\Models\Product;
use App\Models\SmartSuggestionDismissal;
use App\Models\VehicleCompatibility;
use App\Services\Compatibility\Results\BrandDetectionResult;
use App\Services\Compatibility\Results\KeywordMatchResult;
use App\Services\Compatibility\Results\ModelDetectionResult;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * SmartSuggestionEngine Service
 *
 * ETAP_05d FAZA 2.5 - Refactored with DI, cascading scoring and central mode
 *
 * SCORING ALGORITHM (5-layer cascade):
 * - Layer 1: Keyword Rules (configurable bonus from rule)
 * - Layer 2: Model Detection (alias_exact: 0.40, token: 0.30, alias_sku: 0.25)
 * - Layer 3: Brand Detection (exact: 0.50, name: 0.30)
 * - Layer 4: Description match (+0.10)
 * - Layer 5: Category match (+0.10)
 * - Total confidence capped at 1.00
 */
class SmartSuggestionEngine
{
    // Vehicle type keywords and prefixes are loaded dynamically from AiScoringConfig.
    // See: AiScoringConfig::getVehicleTypeKeywords() and getVehicleTypePrefixes()

    public function __construct(
        protected KeywordRuleMatcher $keywordMatcher,
        protected VehicleModelDetector $modelDetector,
        protected BrandDetector $brandDetector,
        protected AiScoringConfig $config,
    ) {}

    /**
     * Generate suggestions for a single product (per-shop)
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
        if (!$shop->hasSmartSuggestionsEnabled()) {
            return collect();
        }

        if (!$refresh) {
            $cached = $this->getCachedSuggestions($product, $shop);
            if ($cached->isNotEmpty()) {
                return $cached;
            }
        }

        $existingVehicleIds = VehicleCompatibility::byProduct($product->id)
            ->byShop($shop->id)
            ->pluck('vehicle_model_id')
            ->toArray();

        $vehicles = $this->getEligibleVehicles($shop, $existingVehicleIds);

        if ($vehicles->isEmpty()) {
            return collect();
        }

        $suggestions = collect();
        $minConfidence = $shop->getMinConfidenceScore();

        foreach ($vehicles as $vehicle) {
            $result = $this->calculateScore($product, $vehicle);

            if ($result['score'] < max($minConfidence, $this->config->get('min_confidence_threshold'))) {
                continue;
            }

            $suggestions->push($this->createSuggestion(
                $product,
                $vehicle,
                $shop,
                $result['score'],
                $result['reason']
            ));

            if ($suggestions->count() >= $this->config->get('max_suggestions_per_product')) {
                break;
            }
        }

        return $suggestions->sortByDesc('confidence_score')->values();
    }

    /**
     * Generate CENTRAL suggestions (not per-shop)
     * For ghost tiles in ProductForm - filters dismissed vehicles
     *
     * @param Product $product Product to analyze
     * @param int $maxSuggestions Maximum number of suggestions to return
     * @return array
     */
    public function generateForProductCentral(Product $product, int $maxSuggestions = 30): array
    {
        $existingVehicleIds = VehicleCompatibility::where('product_id', $product->id)
            ->pluck('vehicle_model_id')
            ->unique()
            ->toArray();

        $dismissedVehicleIds = SmartSuggestionDismissal::where('product_id', $product->id)
            ->whereNull('restored_at')
            ->pluck('vehicle_product_id')
            ->toArray();

        $vehicles = Product::byType('pojazd')
            ->where('is_active', true)
            ->whereNotIn('id', array_merge($existingVehicleIds, $dismissedVehicleIds))
            ->get();

        if ($vehicles->isEmpty()) {
            return [];
        }

        $suggestions = [];

        foreach ($vehicles as $vehicle) {
            $result = $this->calculateScore($product, $vehicle);

            if ($result['score'] < $this->config->get('min_confidence_threshold')) {
                continue;
            }

            $suggestions[] = [
                'vehicle_id' => $vehicle->id,
                'vehicle_name' => $vehicle->name,
                'vehicle_manufacturer' => $vehicle->manufacturer,
                'vehicle_sku' => $vehicle->sku,
                'score' => round($result['score'], 2),
                'reason' => $result['reason'],
                'breakdown' => $result['breakdown'],
            ];

            if (count($suggestions) >= $maxSuggestions) {
                break;
            }
        }

        usort($suggestions, fn($a, $b) => $b['score'] <=> $a['score']);

        return $suggestions;
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
     * Calculate confidence score using 5-layer cascade
     *
     * @param Product $product
     * @param Product $vehicle
     * @return array{score: float, reason: string, breakdown: array}
     */
    protected function calculateScore(Product $product, Product $vehicle): array
    {
        // Layer 0: Vehicle TYPE filter - reject type mismatches early
        $productType = $this->detectVehicleType($product->name ?? '');
        $vehicleType = $this->detectVehicleTypeFromVehicleName($vehicle->name ?? '');

        if ($productType !== null && $vehicleType !== null && $productType !== $vehicleType) {
            return [
                'score' => 0.0,
                'reason' => 'type_mismatch',
                'breakdown' => [
                    'type_filter' => [
                        'product_type' => $productType,
                        'vehicle_type' => $vehicleType,
                        'rejected' => true,
                    ],
                ],
            ];
        }

        $score = 0.0;
        $reasons = [];
        $breakdown = [];

        // Type match bonus: product type matches vehicle type
        if ($productType !== null && $vehicleType !== null && $productType === $vehicleType) {
            $typeBonus = $this->config->get('weight_type_match');
            $score += $typeBonus;
            $reasons[] = 'type_match';
            $breakdown['type_match'] = [
                'product_type' => $productType,
                'vehicle_type' => $vehicleType,
                'bonus' => $typeBonus,
            ];
        }

        // Layer 1: Keyword Rules (configurable bonus from rule)
        $keywordResult = $this->keywordMatcher->match($product, $vehicle);
        if ($keywordResult->hasMatch) {
            $score += $keywordResult->bonus;
            $reasons[] = 'keyword_rule';
            $breakdown['keyword'] = $keywordResult->toArray();
        }

        // Layer 2: Model Detection (alias/token)
        $modelResult = $this->modelDetector->detect($product, $vehicle);
        if ($modelResult->hasMatch) {
            $score += $modelResult->score;
            $reasons[] = 'model_detection';
            $breakdown['model'] = $modelResult->toArray();
        }

        // Layer 3: Brand Detection
        $brandResult = $this->brandDetector->detect($product, $vehicle);
        if ($brandResult->hasMatch) {
            $score += $brandResult->score;
            $reasons[] = CompatibilitySuggestion::REASON_BRAND_MATCH;
            $breakdown['brand'] = $brandResult->toArray();
        }

        // Layer 4: Description match
        if ($this->matchesDescription($product, $vehicle)) {
            $score += $this->config->get('weight_description_match');
            $reasons[] = CompatibilitySuggestion::REASON_DESCRIPTION_MATCH;
            $breakdown['description'] = true;
        }

        // Layer 5: Category match
        if ($this->matchesCategory($product, $vehicle)) {
            $score += $this->config->get('weight_category_match');
            $reasons[] = CompatibilitySuggestion::REASON_CATEGORY_MATCH;
            $breakdown['category'] = true;
        }

        $primaryReason = !empty($reasons)
            ? $reasons[0]
            : CompatibilitySuggestion::REASON_SIMILAR_PRODUCT;

        return [
            'score' => min(1.0, $score),
            'reason' => $primaryReason,
            'breakdown' => $breakdown,
        ];
    }

    /**
     * Detect vehicle type keyword in text (product name, description).
     * Types are loaded dynamically from AiScoringConfig (DB-backed).
     *
     * @return string|null Normalized type key (e.g. 'buggy', 'quad') or null
     */
    protected function detectVehicleType(string $text): ?string
    {
        $text = mb_strtolower(trim($text));

        foreach ($this->config->getVehicleTypeKeywords() as $type => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($text, $keyword)) {
                    return $type;
                }
            }
        }

        return null;
    }

    /**
     * Detect vehicle type from vehicle product name prefix.
     * Vehicle names follow pattern: "Type Brand Model" (e.g. "Buggy KAYO S70").
     * Prefixes are loaded dynamically from AiScoringConfig (DB-backed).
     *
     * @return string|null Normalized type key or null
     */
    protected function detectVehicleTypeFromVehicleName(string $vehicleName): ?string
    {
        $name = mb_strtolower(trim($vehicleName));

        foreach ($this->config->getVehicleTypePrefixes() as $type => $prefix) {
            if (str_starts_with($name, $prefix)) {
                return $type;
            }
        }

        return null;
    }

    /**
     * Check if product description mentions vehicle
     * Only matches full vehicle name, NOT brand alone (too broad)
     */
    protected function matchesDescription(Product $product, Product $vehicle): bool
    {
        $description = strtolower(strip_tags($product->description ?? ''));
        $vehicleName = strtolower(trim($vehicle->name ?? ''));

        if (empty($description) || empty($vehicleName)) {
            return false;
        }

        return str_contains($description, $vehicleName);
    }

    /**
     * Check if product category matches vehicle-related patterns
     * Only matches full vehicle name in category, NOT brand alone (too broad)
     */
    protected function matchesCategory(Product $product, Product $vehicle): bool
    {
        $categories = $product->categories ?? collect();
        if ($categories->isEmpty()) {
            return false;
        }

        $vehicleName = strtolower(trim($vehicle->name ?? ''));

        if (empty($vehicleName)) {
            return false;
        }

        foreach ($categories as $category) {
            $categoryName = strtolower($category->name ?? '');

            if (str_contains($categoryName, $vehicleName)) {
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
     * Uses Product::byType('pojazd') instead of VehicleModel
     *
     * @param PrestaShopShop $shop
     * @param array $excludeVehicleIds Vehicle IDs to exclude (already matched)
     * @return Collection<Product>
     */
    protected function getEligibleVehicles(PrestaShopShop $shop, array $excludeVehicleIds = []): Collection
    {
        $query = Product::byType('pojazd')
            ->where('is_active', true);

        if (!empty($excludeVehicleIds)) {
            $query->whereNotIn('id', $excludeVehicleIds);
        }

        $allowedBrands = $shop->allowed_vehicle_brands;

        if ($allowedBrands === null) {
            // null = all brands allowed
        } elseif (empty($allowedBrands)) {
            return collect();
        } else {
            $query->whereIn('manufacturer', $allowedBrands);
        }

        return $query->get();
    }

    /**
     * Create a suggestion record
     */
    protected function createSuggestion(
        Product $product,
        Product $vehicle,
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

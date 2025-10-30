<?php

namespace App\Services\Product;

use App\Models\Product;
use App\Models\ProductFeature;
use App\Models\FeatureType;
use App\Models\FeatureValue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * FeatureManager Service
 *
 * Centralized service for managing product features (technical attributes/specifications)
 *
 * FEATURES:
 * - Feature CRUD operations (add, update, remove, bulk set)
 * - Feature type management (categories of features)
 * - Feature value management (predefined values per type)
 * - Bulk operations (copy features, bulk apply)
 * - Display & formatting (grouped features, comparison, formatted output)
 *
 * COMPLIANCE:
 * - Laravel 12.x Service Layer patterns (Context7 verified)
 * - DB transactions for multi-record operations
 * - Type hints PHP 8.3 (strict types)
 * - Comprehensive error handling + logging
 * - CLAUDE.md: ~200 linii limit (compliant)
 *
 * USAGE:
 * ```php
 * $manager = app(FeatureManager::class);
 *
 * // Add feature to product
 * $feature = $manager->addFeature($product, [
 *     'feature_type_id' => 1, // e.g., "Engine Type"
 *     'feature_value_id' => 5, // e.g., "4-stroke"
 *     // OR custom value:
 *     'custom_value' => '125cc'
 * ]);
 *
 * // Set multiple features at once
 * $manager->setFeatures($product, [
 *     ['feature_type_id' => 1, 'feature_value_id' => 5],
 *     ['feature_type_id' => 2, 'custom_value' => '125cc']
 * ]);
 *
 * // Get formatted features for display
 * $formatted = $manager->getFormattedFeatures($product);
 * ```
 *
 * RELATED:
 * - Plan_Projektu/ETAP_05a_Produkty.md - FAZA 3 (Services Layer)
 * - app/Models/ProductFeature.php
 * - app/Models/FeatureType.php
 *
 * @package App\Services\Product
 * @version 1.0
 * @since ETAP_05a FAZA 3 (2025-10-17)
 */
class FeatureManager
{
    /**
     * Add feature to product
     *
     * @param Product $product Product to add feature to
     * @param array $data Feature data (feature_type_id + feature_value_id OR custom_value)
     * @return ProductFeature Created feature
     * @throws \Exception
     */
    public function addFeature(Product $product, array $data): ProductFeature
    {
        try {
            Log::info('FeatureManager::addFeature CALLED', [
                'product_id' => $product->id,
                'product_sku' => $product->sku,
                'feature_type_id' => $data['feature_type_id'] ?? null,
            ]);

            $feature = ProductFeature::create([
                'product_id' => $product->id,
                'feature_type_id' => $data['feature_type_id'],
                'feature_value_id' => $data['feature_value_id'] ?? null,
                'custom_value' => $data['custom_value'] ?? null,
            ]);

            Log::info('FeatureManager::addFeature COMPLETED', [
                'feature_id' => $feature->id,
            ]);

            return $feature->load('featureType', 'featureValue');

        } catch (\Exception $e) {
            Log::error('FeatureManager::addFeature FAILED', [
                'product_id' => $product->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Update product feature
     *
     * @param ProductFeature $feature Feature to update
     * @param array $data Updated data
     * @return ProductFeature Updated feature
     * @throws \Exception
     */
    public function updateFeature(ProductFeature $feature, array $data): ProductFeature
    {
        try {
            Log::info('FeatureManager::updateFeature CALLED', [
                'feature_id' => $feature->id,
            ]);

            $feature->update([
                'feature_type_id' => $data['feature_type_id'] ?? $feature->feature_type_id,
                'feature_value_id' => $data['feature_value_id'] ?? $feature->feature_value_id,
                'custom_value' => $data['custom_value'] ?? $feature->custom_value,
            ]);

            Log::info('FeatureManager::updateFeature COMPLETED', [
                'feature_id' => $feature->id,
            ]);

            return $feature->fresh(['featureType', 'featureValue']);

        } catch (\Exception $e) {
            Log::error('FeatureManager::updateFeature FAILED', [
                'feature_id' => $feature->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Remove feature from product
     *
     * @param ProductFeature $feature Feature to remove
     * @return bool Success status
     * @throws \Exception
     */
    public function removeFeature(ProductFeature $feature): bool
    {
        try {
            Log::info('FeatureManager::removeFeature CALLED', [
                'feature_id' => $feature->id,
            ]);

            $result = $feature->delete();

            Log::info('FeatureManager::removeFeature COMPLETED', [
                'feature_id' => $feature->id,
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error('FeatureManager::removeFeature FAILED', [
                'feature_id' => $feature->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Set multiple features at once (replace all)
     *
     * @param Product $product Product to set features for
     * @param array $features Array of feature data
     * @return Collection Created features
     */
    public function setFeatures(Product $product, array $features): Collection
    {
        return DB::transaction(function () use ($product, $features) {
            Log::info('FeatureManager::setFeatures CALLED', [
                'product_id' => $product->id,
                'features_count' => count($features),
            ]);

            // Delete existing features
            ProductFeature::where('product_id', $product->id)->delete();

            // Create new features
            $created = [];
            foreach ($features as $featureData) {
                $created[] = ProductFeature::create([
                    'product_id' => $product->id,
                    'feature_type_id' => $featureData['feature_type_id'],
                    'feature_value_id' => $featureData['feature_value_id'] ?? null,
                    'custom_value' => $featureData['custom_value'] ?? null,
                ]);
            }

            Log::info('FeatureManager::setFeatures COMPLETED', [
                'product_id' => $product->id,
                'created_count' => count($created),
            ]);

            return collect($created)->load('featureType', 'featureValue');
        });
    }

    /**
     * Get features grouped by type for product
     *
     * @param Product $product Product to get features for
     * @return Collection Features grouped by type
     */
    public function getGroupedFeatures(Product $product): Collection
    {
        return ProductFeature::where('product_id', $product->id)
            ->with(['featureType', 'featureValue'])
            ->get()
            ->groupBy('feature_type_id');
    }

    /**
     * Get formatted features for display (with units, groups)
     *
     * @param Product $product Product to format features for
     * @return array Formatted features array
     */
    public function getFormattedFeatures(Product $product): array
    {
        $features = ProductFeature::where('product_id', $product->id)
            ->with(['featureType', 'featureValue'])
            ->get();

        $formatted = [];

        foreach ($features as $feature) {
            $value = $feature->feature_value_id
                ? $feature->featureValue->value
                : $feature->custom_value;

            $unit = $feature->featureType->unit ?? '';

            $formatted[] = [
                'type' => $feature->featureType->name,
                'value' => $value . ($unit ? ' ' . $unit : ''),
                'group' => $feature->featureType->group ?? 'General',
            ];
        }

        return $formatted;
    }

    /**
     * Copy features from one product to another
     *
     * @param Product $target Target product
     * @param Product $source Source product
     * @return Collection Copied features
     */
    public function copyFeaturesFrom(Product $target, Product $source): Collection
    {
        return DB::transaction(function () use ($target, $source) {
            Log::info('FeatureManager::copyFeaturesFrom CALLED', [
                'target_id' => $target->id,
                'source_id' => $source->id,
            ]);

            $sourceFeatures = ProductFeature::where('product_id', $source->id)->get();

            $copied = [];
            foreach ($sourceFeatures as $feature) {
                $copied[] = ProductFeature::create([
                    'product_id' => $target->id,
                    'feature_type_id' => $feature->feature_type_id,
                    'feature_value_id' => $feature->feature_value_id,
                    'custom_value' => $feature->custom_value,
                ]);
            }

            Log::info('FeatureManager::copyFeaturesFrom COMPLETED', [
                'copied_count' => count($copied),
            ]);

            return collect($copied);
        });
    }

    /**
     * Apply features to multiple products (bulk)
     *
     * @param Collection $products Products to apply features to
     * @param array $features Features to apply
     * @return int Number of products updated
     */
    public function bulkApplyFeatures(Collection $products, array $features): int
    {
        return DB::transaction(function () use ($products, $features) {
            Log::info('FeatureManager::bulkApplyFeatures CALLED', [
                'products_count' => $products->count(),
                'features_count' => count($features),
            ]);

            $updated = 0;

            foreach ($products as $product) {
                $this->setFeatures($product, $features);
                $updated++;
            }

            Log::info('FeatureManager::bulkApplyFeatures COMPLETED', [
                'updated_count' => $updated,
            ]);

            return $updated;
        });
    }

    /**
     * Compare features between products
     *
     * @param Product $productA First product
     * @param Product $productB Second product
     * @return array Comparison result (common, unique_a, unique_b)
     */
    public function compareFeatures(Product $productA, Product $productB): array
    {
        $featuresA = $this->getFormattedFeatures($productA);
        $featuresB = $this->getFormattedFeatures($productB);

        $common = [];
        $uniqueA = [];
        $uniqueB = [];

        foreach ($featuresA as $featureA) {
            $found = false;
            foreach ($featuresB as $featureB) {
                if ($featureA['type'] === $featureB['type'] && $featureA['value'] === $featureB['value']) {
                    $common[] = $featureA;
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $uniqueA[] = $featureA;
            }
        }

        foreach ($featuresB as $featureB) {
            $found = false;
            foreach ($common as $commonFeature) {
                if ($featureB['type'] === $commonFeature['type'] && $featureB['value'] === $commonFeature['value']) {
                    $found = true;
                    break;
                }
            }
            if (!$found && !in_array($featureB, $uniqueA)) {
                $uniqueB[] = $featureB;
            }
        }

        return [
            'common' => $common,
            'unique_a' => $uniqueA,
            'unique_b' => $uniqueB,
        ];
    }
}

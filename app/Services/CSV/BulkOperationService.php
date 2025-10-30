<?php

namespace App\Services\CSV;

use App\Models\AttributeType;
use App\Models\FeatureType;
use App\Models\PriceGroup;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\VehicleModel;
use App\Models\Warehouse;
use App\Services\CompatibilityManager;
use App\Services\Product\FeatureManager;
use App\Services\Product\VariantManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * CSV Bulk Operation Service
 *
 * Handles bulk operations for CSV imports:
 * - Bulk variant creation with attribute combinations
 * - Bulk compatibility addition/update
 * - Bulk feature application from templates
 * - Batch processing with transactions
 *
 * Uses existing services: VariantManager, FeatureManager, CompatibilityManager
 */
class BulkOperationService
{
    protected VariantManager $variantManager;
    protected FeatureManager $featureManager;
    protected CompatibilityManager $compatibilityManager;

    /**
     * Batch size for DB transactions
     */
    protected int $batchSize = 100;

    public function __construct(
        VariantManager $variantManager,
        FeatureManager $featureManager,
        CompatibilityManager $compatibilityManager
    ) {
        $this->variantManager = $variantManager;
        $this->featureManager = $featureManager;
        $this->compatibilityManager = $compatibilityManager;
    }

    /**
     * Bulk add compatibility records from CSV data
     *
     * @param array $compatibilityData Array of compatibility rows
     * @param string $mode Operation mode: 'add', 'update', 'replace'
     * @return array ['success' => int, 'failed' => int, 'errors' => array]
     */
    public function bulkAddCompatibility(array $compatibilityData, string $mode = 'add'): array
    {
        Log::info('BulkOperationService: Bulk add compatibility started', [
            'row_count' => count($compatibilityData),
            'mode' => $mode,
        ]);

        $successCount = 0;
        $failedCount = 0;
        $errors = [];

        $batches = array_chunk($compatibilityData, $this->batchSize);

        foreach ($batches as $batchIndex => $batch) {
            try {
                DB::transaction(function () use ($batch, $mode, &$successCount, &$failedCount, &$errors) {
                    foreach ($batch as $rowIndex => $row) {
                        try {
                            $this->processSingleCompatibility($row, $mode);
                            $successCount++;
                        } catch (\Exception $e) {
                            $failedCount++;
                            $errors[$rowIndex] = $e->getMessage();
                            Log::error('BulkOperationService: Compatibility row failed', [
                                'row_index' => $rowIndex,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                });

                Log::info('BulkOperationService: Batch processed', [
                    'batch_index' => $batchIndex,
                    'success_count' => $successCount,
                    'failed_count' => $failedCount,
                ]);
            } catch (\Exception $e) {
                Log::error('BulkOperationService: Batch transaction failed', [
                    'batch_index' => $batchIndex,
                    'error' => $e->getMessage(),
                ]);
                $failedCount += count($batch);
            }
        }

        Log::info('BulkOperationService: Bulk add compatibility completed', [
            'success_count' => $successCount,
            'failed_count' => $failedCount,
        ]);

        return [
            'success' => $successCount,
            'failed' => $failedCount,
            'errors' => $errors,
        ];
    }

    /**
     * Process single compatibility record
     *
     * @param array $row Row data
     * @param string $mode Operation mode
     * @return void
     */
    protected function processSingleCompatibility(array $row, string $mode): void
    {
        // Find or create vehicle model
        $vehicleModel = $this->findOrCreateVehicleModel([
            'brand' => $row['vehicle_brand'],
            'model' => $row['vehicle_model'],
            'year_from' => $row['year_from'] ?? null,
            'year_to' => $row['year_to'] ?? null,
            'vehicle_sku' => $row['vehicle_sku'] ?? null,
        ]);

        // Add compatibility using CompatibilityManager
        $this->compatibilityManager->addCompatibility(
            partSku: $row['sku'],
            vehicleModelId: $vehicleModel->id,
            compatibilityAttributeId: $this->resolveCompatibilityAttributeId($row['compatibility_type'] ?? 'Oryginal'),
            sourceId: $this->resolveSourceId($row['source'] ?? 'Import'),
            isVerified: $row['is_verified'] ?? false,
            notes: $row['notes'] ?? null
        );
    }

    /**
     * Bulk create variants with attribute combinations
     *
     * @param array $variantData Array of variant definitions
     * @param bool $autoGenerateCombinations Auto-generate all attribute combinations
     * @return array ['success' => int, 'failed' => int, 'errors' => array]
     */
    public function bulkCreateVariants(array $variantData, bool $autoGenerateCombinations = false): array
    {
        Log::info('BulkOperationService: Bulk create variants started', [
            'row_count' => count($variantData),
            'auto_generate_combinations' => $autoGenerateCombinations,
        ]);

        $successCount = 0;
        $failedCount = 0;
        $errors = [];

        if ($autoGenerateCombinations) {
            // Generate all combinations from attribute values
            $variantData = $this->generateVariantCombinations($variantData);
        }

        $batches = array_chunk($variantData, $this->batchSize);

        foreach ($batches as $batchIndex => $batch) {
            try {
                DB::transaction(function () use ($batch, &$successCount, &$failedCount, &$errors) {
                    foreach ($batch as $rowIndex => $row) {
                        try {
                            $this->processSingleVariant($row);
                            $successCount++;
                        } catch (\Exception $e) {
                            $failedCount++;
                            $errors[$rowIndex] = $e->getMessage();
                            Log::error('BulkOperationService: Variant row failed', [
                                'row_index' => $rowIndex,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                });

                Log::info('BulkOperationService: Batch processed', [
                    'batch_index' => $batchIndex,
                    'success_count' => $successCount,
                    'failed_count' => $failedCount,
                ]);
            } catch (\Exception $e) {
                Log::error('BulkOperationService: Batch transaction failed', [
                    'batch_index' => $batchIndex,
                    'error' => $e->getMessage(),
                ]);
                $failedCount += count($batch);
            }
        }

        Log::info('BulkOperationService: Bulk create variants completed', [
            'success_count' => $successCount,
            'failed_count' => $failedCount,
        ]);

        return [
            'success' => $successCount,
            'failed' => $failedCount,
            'errors' => $errors,
        ];
    }

    /**
     * Process single variant record
     *
     * @param array $row Row data
     * @return void
     */
    protected function processSingleVariant(array $row): void
    {
        $parentProduct = Product::where('sku', $row['parent_sku'])->firstOrFail();

        // Create variant using VariantManager
        $variant = $this->variantManager->createVariant(
            productId: $parentProduct->id,
            sku: $row['sku'],
            name: $row['name'],
            attributes: $this->extractAttributes($row),
            isActive: $row['is_active'] ?? true,
            isDefault: $row['is_default'] ?? false,
            position: $row['position'] ?? null
        );

        // Set prices if provided
        $prices = $this->extractPrices($row);
        if (!empty($prices)) {
            $this->variantManager->setPrices($variant->id, $prices);
        }

        // Set stock if provided
        $stock = $this->extractStock($row);
        if (!empty($stock)) {
            $this->variantManager->setStock($variant->id, $stock);
        }
    }

    /**
     * Apply feature template to products
     *
     * @param array $skus Array of product SKUs
     * @param int $featureTemplateId Feature template ID
     * @return array ['success' => int, 'failed' => int, 'errors' => array]
     */
    public function applyFeatureTemplate(array $skus, int $featureTemplateId): array
    {
        Log::info('BulkOperationService: Applying feature template', [
            'sku_count' => count($skus),
            'template_id' => $featureTemplateId,
        ]);

        $successCount = 0;
        $failedCount = 0;
        $errors = [];

        // Load template features
        $templateFeatures = $this->loadFeatureTemplate($featureTemplateId);

        foreach ($skus as $sku) {
            try {
                $product = Product::where('sku', $sku)->firstOrFail();

                // Apply features using FeatureManager
                foreach ($templateFeatures as $feature) {
                    $this->featureManager->addFeature(
                        productId: $product->id,
                        featureTypeId: $feature['feature_type_id'],
                        value: $feature['value'],
                        customValue: $feature['custom_value'] ?? null
                    );
                }

                $successCount++;
            } catch (\Exception $e) {
                $failedCount++;
                $errors[$sku] = $e->getMessage();
                Log::error('BulkOperationService: Feature template application failed', [
                    'sku' => $sku,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('BulkOperationService: Feature template application completed', [
            'success_count' => $successCount,
            'failed_count' => $failedCount,
        ]);

        return [
            'success' => $successCount,
            'failed' => $failedCount,
            'errors' => $errors,
        ];
    }

    /**
     * Process batch of rows
     *
     * @param array $batch Batch of rows
     * @param callable $processor Callback to process single row
     * @return array ['success' => int, 'failed' => int, 'errors' => array]
     */
    public function processBatch(array $batch, callable $processor): array
    {
        $successCount = 0;
        $failedCount = 0;
        $errors = [];

        DB::transaction(function () use ($batch, $processor, &$successCount, &$failedCount, &$errors) {
            foreach ($batch as $rowIndex => $row) {
                try {
                    $processor($row);
                    $successCount++;
                } catch (\Exception $e) {
                    $failedCount++;
                    $errors[$rowIndex] = $e->getMessage();
                }
            }
        });

        return [
            'success' => $successCount,
            'failed' => $failedCount,
            'errors' => $errors,
        ];
    }

    /**
     * Generate variant combinations from attribute values
     *
     * @param array $variantData Variant data with attribute arrays
     * @return array Expanded variant combinations
     */
    protected function generateVariantCombinations(array $variantData): array
    {
        // Example: Size: [S, M, L] × Color: [Red, Blue] = 6 variants
        // This is a simplified implementation
        // Full implementation would handle all attribute types dynamically

        $combinations = [];

        foreach ($variantData as $baseVariant) {
            $attributeArrays = [];

            // Extract attribute values that are arrays
            foreach ($baseVariant as $key => $value) {
                if (str_starts_with($key, 'attribute:') && is_array($value)) {
                    $attributeArrays[$key] = $value;
                }
            }

            if (empty($attributeArrays)) {
                // No combinations needed, use as-is
                $combinations[] = $baseVariant;
                continue;
            }

            // Generate Cartesian product of attribute values
            $cartesianProduct = $this->cartesianProduct($attributeArrays);

            foreach ($cartesianProduct as $combination) {
                $variant = $baseVariant;

                // Apply combination values
                foreach ($combination as $attributeKey => $attributeValue) {
                    $variant[$attributeKey] = $attributeValue;
                }

                // Generate unique SKU for combination
                $variant['sku'] = $this->generateCombinationSku($baseVariant['parent_sku'], $combination);

                $combinations[] = $variant;
            }
        }

        return $combinations;
    }

    /**
     * Cartesian product of arrays
     *
     * @param array $arrays
     * @return array
     */
    protected function cartesianProduct(array $arrays): array
    {
        $result = [[]];

        foreach ($arrays as $key => $values) {
            $append = [];

            foreach ($result as $product) {
                foreach ($values as $item) {
                    $product[$key] = $item;
                    $append[] = $product;
                }
            }

            $result = $append;
        }

        return $result;
    }

    /**
     * Generate SKU for variant combination
     *
     * @param string $parentSku
     * @param array $combination
     * @return string
     */
    protected function generateCombinationSku(string $parentSku, array $combination): string
    {
        $suffix = '';

        foreach ($combination as $attributeKey => $attributeValue) {
            $suffix .= '-' . Str::slug($attributeValue);
        }

        return $parentSku . $suffix;
    }

    /**
     * Find or create vehicle model
     *
     * @param array $data Vehicle model data
     * @return VehicleModel
     */
    protected function findOrCreateVehicleModel(array $data): VehicleModel
    {
        // Try to find by SKU first (SKU-first pattern)
        if (!empty($data['vehicle_sku'])) {
            $vehicle = VehicleModel::where('sku', $data['vehicle_sku'])->first();
            if ($vehicle) {
                return $vehicle;
            }
        }

        // Try to find by brand + model + year range
        $query = VehicleModel::where('brand', $data['brand'])
            ->where('model', $data['model']);

        if (isset($data['year_from'])) {
            $query->where('year_from', $data['year_from']);
        }

        if (isset($data['year_to'])) {
            $query->where('year_to', $data['year_to']);
        }

        $vehicle = $query->first();

        if ($vehicle) {
            return $vehicle;
        }

        // Create new vehicle model
        return VehicleModel::create([
            'sku' => $data['vehicle_sku'] ?? 'VEH-' . Str::random(8),
            'brand' => $data['brand'],
            'model' => $data['model'],
            'year_from' => $data['year_from'] ?? null,
            'year_to' => $data['year_to'] ?? null,
        ]);
    }

    /**
     * Extract attributes from row data
     *
     * @param array $row
     * @return array [attribute_type_id => value]
     */
    protected function extractAttributes(array $row): array
    {
        $attributes = [];

        foreach ($row as $key => $value) {
            if (str_starts_with($key, 'attribute:') && $value !== null) {
                $attributeSlug = str_replace('attribute:', '', $key);
                $attributeType = AttributeType::where('code', $attributeSlug)->first();

                if ($attributeType) {
                    $attributes[$attributeType->id] = $value;
                }
            }
        }

        return $attributes;
    }

    /**
     * Extract prices from row data
     *
     * @param array $row
     * @return array [price_group_id => price]
     */
    protected function extractPrices(array $row): array
    {
        $prices = [];

        foreach ($row as $key => $value) {
            if (str_starts_with($key, 'price:') && $value !== null) {
                $priceGroupSlug = str_replace('price:', '', $key);
                $priceGroup = PriceGroup::where('code', $priceGroupSlug)->first();

                if ($priceGroup) {
                    $prices[$priceGroup->id] = (float) $value;
                }
            }
        }

        return $prices;
    }

    /**
     * Extract stock from row data
     *
     * @param array $row
     * @return array [warehouse_id => quantity]
     */
    protected function extractStock(array $row): array
    {
        $stock = [];

        foreach ($row as $key => $value) {
            if (str_starts_with($key, 'stock:') && $value !== null) {
                $warehouseSlug = str_replace('stock:', '', $key);
                $warehouse = Warehouse::where('code', $warehouseSlug)->first();

                if ($warehouse) {
                    $stock[$warehouse->id] = (int) $value;
                }
            }
        }

        return $stock;
    }

    /**
     * Resolve compatibility attribute ID by name
     *
     * @param string $attributeName
     * @return int
     */
    protected function resolveCompatibilityAttributeId(string $attributeName): int
    {
        // Simplified - would use CompatibilityAttribute model
        return 1; // Default to first attribute
    }

    /**
     * Resolve source ID by name
     *
     * @param string $sourceName
     * @return int
     */
    protected function resolveSourceId(string $sourceName): int
    {
        // Simplified - would use CompatibilitySource model
        return 1; // Default to first source
    }

    /**
     * Load feature template (stub - would load from database)
     *
     * @param int $templateId
     * @return array
     */
    protected function loadFeatureTemplate(int $templateId): array
    {
        // Stub implementation
        // Full implementation would load from feature_templates table
        return [];
    }
}

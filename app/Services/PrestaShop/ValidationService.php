<?php

namespace App\Services\PrestaShop;

use App\Models\ProductShopData;
use App\Models\Product;
use Illuminate\Support\Facades\Log;

/**
 * ValidationService
 *
 * Validates PPM product data against PrestaShop data fetched from API.
 * Detects inconsistencies and generates warnings with severity levels.
 *
 * Severity Levels:
 * - error: Critical differences (e.g., price diff > 10%)
 * - warning: Important differences (e.g., name mismatch, no categories)
 * - info: Minor differences (e.g., stock, descriptions)
 */
class ValidationService
{
    /**
     * Validate product data against PrestaShop data
     *
     * @param ProductShopData $ppmData PPM database record
     * @param array $psData PrestaShop API response
     * @return array Array of warnings with structure: ['field' => string, 'severity' => string, 'message' => string, 'ppm_value' => mixed, 'prestashop_value' => mixed]
     */
    public function validateProductData(ProductShopData $ppmData, array $psData): array
    {
        $warnings = [];

        // Validate name
        $warnings = array_merge($warnings, $this->validateName($ppmData, $psData));

        // Validate descriptions
        $warnings = array_merge($warnings, $this->validateDescriptions($ppmData, $psData));

        // Validate prices
        $warnings = array_merge($warnings, $this->validatePrices($ppmData, $psData));

        // Validate stock
        $warnings = array_merge($warnings, $this->validateStock($ppmData, $psData));

        // Validate categories
        $warnings = array_merge($warnings, $this->validateCategories($ppmData, $psData));

        // Validate active status
        $warnings = array_merge($warnings, $this->validateActiveStatus($ppmData, $psData));

        return $warnings;
    }

    /**
     * Validate product name
     *
     * @param ProductShopData $ppmData
     * @param array $psData
     * @return array
     */
    private function validateName(ProductShopData $ppmData, array $psData): array
    {
        $warnings = [];

        // PrestaShop returns name as language array or direct string
        $psName = data_get($psData, 'name.0.value') ?? data_get($psData, 'name');

        if ($ppmData->name && $ppmData->name !== $psName) {
            $warnings[] = [
                'field' => 'name',
                'severity' => 'warning', // Common, can be intentional
                'message' => 'Product name differs between PPM and PrestaShop',
                'ppm_value' => $ppmData->name,
                'prestashop_value' => $psName,
            ];
        }

        return $warnings;
    }

    /**
     * Validate product descriptions
     *
     * @param ProductShopData $ppmData
     * @param array $psData
     * @return array
     */
    private function validateDescriptions(ProductShopData $ppmData, array $psData): array
    {
        $warnings = [];

        // Short description
        $psShortDesc = data_get($psData, 'description_short.0.value') ?? data_get($psData, 'description_short');
        if ($ppmData->short_description && $ppmData->short_description !== $psShortDesc) {
            $warnings[] = [
                'field' => 'short_description',
                'severity' => 'info', // Low priority
                'message' => 'Short description differs',
                'ppm_value' => substr($ppmData->short_description, 0, 50) . '...',
                'prestashop_value' => substr($psShortDesc ?? '', 0, 50) . '...',
            ];
        }

        // Long description
        $psLongDesc = data_get($psData, 'description.0.value') ?? data_get($psData, 'description');
        if ($ppmData->description && $ppmData->description !== $psLongDesc) {
            $warnings[] = [
                'field' => 'description',
                'severity' => 'info', // Low priority
                'message' => 'Long description differs',
                'ppm_value' => substr($ppmData->description, 0, 50) . '...',
                'prestashop_value' => substr($psLongDesc ?? '', 0, 50) . '...',
            ];
        }

        return $warnings;
    }

    /**
     * Validate product prices
     *
     * @param ProductShopData $ppmData
     * @param array $psData
     * @return array
     */
    private function validatePrices(ProductShopData $ppmData, array $psData): array
    {
        $warnings = [];

        // Get PPM default price (grupa cenowa: detaliczna, id=1)
        $ppmPrice = $ppmData->product->prices()
            ->where('price_group_id', 1)
            ->first();

        $psPrice = (float) data_get($psData, 'price');

        if ($ppmPrice && $ppmPrice->price > 0) {
            $priceDiff = abs($ppmPrice->price - $psPrice);
            $priceDiffPercent = ($priceDiff / $ppmPrice->price) * 100;

            if ($priceDiffPercent > 10) {
                // Price difference > 10%
                $warnings[] = [
                    'field' => 'price',
                    'severity' => 'error', // Likely mistake
                    'message' => sprintf('Price differs by %.1f%% (> 10%% threshold)', $priceDiffPercent),
                    'ppm_value' => number_format($ppmPrice->price, 2) . ' PLN',
                    'prestashop_value' => number_format($psPrice, 2) . ' PLN',
                ];
            } elseif ($priceDiffPercent > 5) {
                // Price difference 5-10%
                $warnings[] = [
                    'field' => 'price',
                    'severity' => 'warning',
                    'message' => sprintf('Price differs by %.1f%%', $priceDiffPercent),
                    'ppm_value' => number_format($ppmPrice->price, 2) . ' PLN',
                    'prestashop_value' => number_format($psPrice, 2) . ' PLN',
                ];
            }
        }

        return $warnings;
    }

    /**
     * Validate product stock
     *
     * @param ProductShopData $ppmData
     * @param array $psData
     * @return array
     */
    private function validateStock(ProductShopData $ppmData, array $psData): array
    {
        $warnings = [];

        // Get PPM total stock (sum across all warehouses)
        $ppmStock = $ppmData->product->stocks()
            ->sum('quantity');

        $psStock = (int) data_get($psData, 'quantity', 0);

        if ($ppmStock !== $psStock) {
            $stockDiff = abs($ppmStock - $psStock);

            // Only warn if difference is significant (> 5 units or > 20%)
            if ($stockDiff > 5 || ($ppmStock > 0 && ($stockDiff / $ppmStock) > 0.2)) {
                $warnings[] = [
                    'field' => 'stock',
                    'severity' => 'info', // Frequent changes, normal
                    'message' => 'Stock quantity differs',
                    'ppm_value' => $ppmStock . ' units',
                    'prestashop_value' => $psStock . ' units',
                ];
            }
        }

        return $warnings;
    }

    /**
     * Validate product categories
     *
     * @param ProductShopData $ppmData
     * @param array $psData
     * @return array
     */
    private function validateCategories(ProductShopData $ppmData, array $psData): array
    {
        $warnings = [];

        $psCategories = data_get($psData, 'associations.categories', []);

        if (empty($psCategories) || count($psCategories) === 0) {
            $warnings[] = [
                'field' => 'categories',
                'severity' => 'warning', // Product not visible
                'message' => 'Product has no categories on PrestaShop - may be hidden',
                'ppm_value' => 'N/A',
                'prestashop_value' => 'No categories',
            ];
        }

        return $warnings;
    }

    /**
     * Validate product active status
     *
     * @param ProductShopData $ppmData
     * @param array $psData
     * @return array
     */
    private function validateActiveStatus(ProductShopData $ppmData, array $psData): array
    {
        $warnings = [];

        $psActive = (bool) data_get($psData, 'active', false);

        if (!$psActive) {
            $warnings[] = [
                'field' => 'active',
                'severity' => 'info',
                'message' => 'Product is inactive on PrestaShop',
                'ppm_value' => 'N/A',
                'prestashop_value' => 'Inactive',
            ];
        }

        return $warnings;
    }

    /**
     * Store validation warnings to database
     *
     * @param ProductShopData $shopData
     * @param array $warnings
     * @return void
     */
    public function storeValidationWarnings(ProductShopData $shopData, array $warnings): void
    {
        $shopData->update([
            'validation_warnings' => $warnings,
            'has_validation_warnings' => count($warnings) > 0,
            'validation_checked_at' => now(),
        ]);

        if (count($warnings) > 0) {
            $errorCount = collect($warnings)->where('severity', 'error')->count();
            $warningCount = collect($warnings)->where('severity', 'warning')->count();
            $infoCount = collect($warnings)->where('severity', 'info')->count();

            Log::info('Validation warnings detected', [
                'product_id' => $shopData->product_id,
                'shop_id' => $shopData->shop_id,
                'warnings_count' => count($warnings),
                'errors' => $errorCount,
                'warnings' => $warningCount,
                'info' => $infoCount,
            ]);
        }
    }
}

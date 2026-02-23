<?php

namespace App\Services\Compatibility;

use App\Models\Product;
use App\Services\Compatibility\Results\BrandDetectionResult;

class BrandDetector
{
    /**
     * Detect if vehicle brand appears in product data
     *
     * @param Product $product Część zamienna
     * @param Product $vehicle Pojazd (type='pojazd')
     */
    public function detect(Product $product, Product $vehicle): BrandDetectionResult
    {
        $vehicleBrand = mb_strtolower(trim($vehicle->manufacturer ?? ''));

        if (empty($vehicleBrand)) {
            return BrandDetectionResult::noMatch();
        }

        $productManufacturer = mb_strtolower(trim($product->manufacturer ?? ''));
        $productName = mb_strtolower(trim($product->name ?? ''));
        $productSku = mb_strtolower(trim($product->sku ?? ''));

        // 1. Exact manufacturer match → highest score
        if (!empty($productManufacturer) && $productManufacturer === $vehicleBrand) {
            return new BrandDetectionResult(true, 0.50, 'manufacturer_exact');
        }

        // 2. Brand found in product name (supportive signal, not primary)
        if (!empty($productName) && str_contains($productName, $vehicleBrand)) {
            return new BrandDetectionResult(true, 0.20, 'name_contains');
        }

        // 3. Brand found in product SKU (supportive signal, not primary)
        if (!empty($productSku) && str_contains($productSku, $vehicleBrand)) {
            return new BrandDetectionResult(true, 0.20, 'sku_contains');
        }

        return BrandDetectionResult::noMatch();
    }
}

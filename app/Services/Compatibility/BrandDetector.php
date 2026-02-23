<?php

namespace App\Services\Compatibility;

use App\Models\Product;
use App\Services\Compatibility\Results\BrandDetectionResult;

class BrandDetector
{
    public function __construct(
        protected AiScoringConfig $config,
    ) {}

    /**
     * Detect if vehicle brand appears in product data
     *
     * Weights are read from AiScoringConfig (DB-backed, with defaults):
     * - weight_brand_manufacturer_exact (default: 0.50)
     * - weight_brand_name_contains (default: 0.20)
     * - weight_brand_sku_contains (default: 0.20)
     *
     * @param Product $product Part/accessory
     * @param Product $vehicle Vehicle (type='pojazd')
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

        // 1. Exact manufacturer match - highest score
        if (!empty($productManufacturer) && $productManufacturer === $vehicleBrand) {
            return new BrandDetectionResult(
                true,
                $this->config->get('weight_brand_manufacturer_exact'),
                'manufacturer_exact'
            );
        }

        // 2. Brand found in product name (supportive signal, not primary)
        if (!empty($productName) && str_contains($productName, $vehicleBrand)) {
            return new BrandDetectionResult(
                true,
                $this->config->get('weight_brand_name_contains'),
                'name_contains'
            );
        }

        // 3. Brand found in product SKU (supportive signal, not primary)
        if (!empty($productSku) && str_contains($productSku, $vehicleBrand)) {
            return new BrandDetectionResult(
                true,
                $this->config->get('weight_brand_sku_contains'),
                'sku_contains'
            );
        }

        return BrandDetectionResult::noMatch();
    }
}

<?php

namespace App\Services\ERP\SubiektGT;

use Illuminate\Support\Facades\Log;

/**
 * Resolves variant relationships for Subiekt GT products.
 *
 * Subiekt GT doesn't have native variant support - each variant is a separate product.
 * We use tw_Pole8 to store parent_sku, creating a parent-child relationship.
 */
class SubiektVariantResolver
{
    /**
     * Detection strategy for variants:
     * 1. PRIMARY: tw_Pole8 contains parent_sku (explicit)
     * 2. FALLBACK: SKU pattern PARENT-SUFFIX where parent exists
     */

    /**
     * Groups products by parent_sku (Pole8).
     * Products with empty Pole8 are standalone.
     *
     * @param array $products Array of Subiekt products
     * @return array ['standalone' => [...], 'variant_groups' => ['PARENT_SKU' => [...]]]
     */
    public function groupByParentSku(array $products): array
    {
        $groups = [];
        $standalone = [];

        foreach ($products as $product) {
            $parentSku = $this->getParentSku($product);

            if (empty($parentSku)) {
                $standalone[] = $product;
            } else {
                if (!isset($groups[$parentSku])) {
                    $groups[$parentSku] = [];
                }
                $groups[$parentSku][] = $product;
            }
        }

        Log::debug('SubiektVariantResolver: grouped products', [
            'standalone_count' => count($standalone),
            'variant_groups_count' => count($groups),
            'total_variants' => array_sum(array_map('count', $groups)),
        ]);

        return [
            'standalone' => $standalone,
            'variant_groups' => $groups,
        ];
    }

    /**
     * Checks if a product is a variant (has parent_sku in Pole8).
     *
     * @param array|object $product Subiekt product data
     * @return bool
     */
    public function isVariant($product): bool
    {
        return !empty($this->getParentSku($product));
    }

    /**
     * Gets parent_sku from product's Pole8 field.
     *
     * @param array|object $product Subiekt product data
     * @return string|null
     */
    public function getParentSku($product): ?string
    {
        if (is_array($product)) {
            $parentSku = $product['Pole8'] ?? $product['pole8'] ?? '';
        } else {
            $parentSku = $product->Pole8 ?? $product->pole8 ?? '';
        }

        return empty($parentSku) ? null : trim($parentSku);
    }

    /**
     * Detects potential parent SKU from variant SKU using naming convention.
     * Pattern: PARENT-SUFFIX (e.g., PROD-001-RED -> PROD-001)
     *
     * @param string $variantSku
     * @return string|null Potential parent SKU or null
     */
    public function detectParentFromSku(string $variantSku): ?string
    {
        // Pattern: Remove last segment after dash
        // PROD-001-RED -> PROD-001
        // ABC-XYZ-123 -> ABC-XYZ
        if (preg_match('/^(.+)-[^-]+$/', $variantSku, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Builds Pole8 value for a variant product.
     *
     * @param string $parentSku Parent product SKU
     * @return string Value to store in tw_Pole8
     */
    public function buildPole8Value(string $parentSku): string
    {
        // Pole8 is varchar(50), ensure we don't exceed
        return substr($parentSku, 0, 50);
    }

    /**
     * Validates that a parent SKU exists in the given product list.
     *
     * @param string $parentSku
     * @param array $products Array of products with SKU field
     * @return bool
     */
    public function parentExists(string $parentSku, array $products): bool
    {
        foreach ($products as $product) {
            $sku = is_array($product)
                ? ($product['Sku'] ?? $product['sku'] ?? '')
                : ($product->Sku ?? $product->sku ?? '');

            if ($sku === $parentSku) {
                return true;
            }
        }

        return false;
    }
}

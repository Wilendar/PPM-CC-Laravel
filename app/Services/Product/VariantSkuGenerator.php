<?php

namespace App\Services\Product;

use App\Models\AttributeValue;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * VariantSkuGenerator - Auto SKU generation for product variants
 *
 * Generates SKU based on:
 * - Base product SKU
 * - Attribute values with auto_prefix/auto_suffix enabled
 *
 * Format: PREFIX1-PREFIX2-BASE-SKU-SUFFIX1-SUFFIX2
 *
 * Example:
 * - Base: "MR-MRF-E"
 * - Attributes: Kolor=Czerwony (suffix="-CZE"), Rozmiar=XL (prefix="XL-")
 * - Result: "XL-MR-MRF-E-CZE"
 *
 * @package App\Services\Product
 * @since ETAP_05f Auto SKU System
 */
class VariantSkuGenerator
{
    /**
     * Generate SKU for variant based on product base SKU and attributes
     *
     * @param Product $product Base product
     * @param array $attributes Array of [attribute_type_id => value_id]
     * @return string Generated SKU
     */
    public function generateSku(Product $product, array $attributes): string
    {
        $baseSku = $product->sku ?? '';

        if (empty($baseSku)) {
            Log::warning('[AUTO SKU] Base product SKU is empty', [
                'product_id' => $product->id,
            ]);
            return '';
        }

        if (empty($attributes)) {
            Log::debug('[AUTO SKU] No attributes provided, returning base SKU', [
                'base_sku' => $baseSku,
            ]);
            return $baseSku;
        }

        $prefixes = $this->getPrefixesFromAttributes($attributes);
        $suffixes = $this->getSuffixesFromAttributes($attributes);

        $result = $this->composeSku($baseSku, $prefixes, $suffixes);

        Log::debug('[AUTO SKU] Generated variant SKU', [
            'base_sku' => $baseSku,
            'prefixes' => $prefixes,
            'suffixes' => $suffixes,
            'result' => $result,
        ]);

        return $result;
    }

    /**
     * Extract prefixes from attributes
     *
     * @param array $attributes [attribute_type_id => value_id]
     * @return array Array of prefix strings
     */
    protected function getPrefixesFromAttributes(array $attributes): array
    {
        $prefixes = [];

        foreach ($attributes as $typeId => $valueId) {
            if (empty($valueId)) {
                continue;
            }

            $attributeValue = AttributeValue::find($valueId);
            if (!$attributeValue) {
                continue;
            }

            if ($attributeValue->auto_prefix_enabled && !empty($attributeValue->auto_prefix)) {
                // Clean prefix - remove trailing dash if present (we'll add it in compose)
                $prefix = rtrim($attributeValue->auto_prefix, '-');
                if (!empty($prefix) && !in_array($prefix, $prefixes)) {
                    $prefixes[] = $prefix;
                }
            }
        }

        return $prefixes;
    }

    /**
     * Extract suffixes from attributes
     *
     * @param array $attributes [attribute_type_id => value_id]
     * @return array Array of suffix strings
     */
    protected function getSuffixesFromAttributes(array $attributes): array
    {
        $suffixes = [];

        foreach ($attributes as $typeId => $valueId) {
            if (empty($valueId)) {
                continue;
            }

            $attributeValue = AttributeValue::find($valueId);
            if (!$attributeValue) {
                continue;
            }

            if ($attributeValue->auto_suffix_enabled && !empty($attributeValue->auto_suffix)) {
                // Clean suffix - remove leading dash if present (we'll add it in compose)
                $suffix = ltrim($attributeValue->auto_suffix, '-');
                if (!empty($suffix) && !in_array($suffix, $suffixes)) {
                    $suffixes[] = $suffix;
                }
            }
        }

        return $suffixes;
    }

    /**
     * Compose final SKU from parts
     *
     * @param string $baseSku Base product SKU
     * @param array $prefixes Array of prefix strings
     * @param array $suffixes Array of suffix strings
     * @return string Final composed SKU
     */
    protected function composeSku(string $baseSku, array $prefixes, array $suffixes): string
    {
        // Build parts array: prefixes + base + suffixes
        $parts = array_merge($prefixes, [$baseSku], $suffixes);

        // Filter empty parts and join with dash
        $parts = array_filter($parts, fn($part) => !empty($part));

        return implode('-', $parts);
    }

    /**
     * Preview SKU without saving (for real-time UI updates)
     *
     * @param string $baseSku Base product SKU
     * @param array $attributes [attribute_type_id => value_id]
     * @return string Preview SKU
     */
    public function previewSku(string $baseSku, array $attributes): string
    {
        if (empty($baseSku)) {
            return '';
        }

        if (empty($attributes)) {
            return $baseSku;
        }

        $prefixes = $this->getPrefixesFromAttributes($attributes);
        $suffixes = $this->getSuffixesFromAttributes($attributes);

        return $this->composeSku($baseSku, $prefixes, $suffixes);
    }

    /**
     * Get attribute values with SKU info for display in dropdowns
     *
     * @param Collection $values AttributeValue collection
     * @return Collection Values with SKU preview info
     */
    public function enrichValuesWithSkuInfo(Collection $values): Collection
    {
        return $values->map(function ($value) {
            $skuParts = [];

            if ($value->auto_prefix_enabled && !empty($value->auto_prefix)) {
                $skuParts[] = rtrim($value->auto_prefix, '-') . '-...';
            }

            if ($value->auto_suffix_enabled && !empty($value->auto_suffix)) {
                $skuParts[] = '...-' . ltrim($value->auto_suffix, '-');
            }

            $value->sku_preview = !empty($skuParts) ? implode(' ', $skuParts) : null;

            return $value;
        });
    }
}

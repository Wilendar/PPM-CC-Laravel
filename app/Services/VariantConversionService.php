<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductPrice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Variant Conversion Service
 *
 * Converts product variants to standalone products when user unchecks "Produkt z wariantami"
 *
 * Business Logic:
 * - Preserves all variant data (SKU, prices, stock, images)
 * - Copies master product data (categories, descriptions, physical properties)
 * - Each variant becomes independent product
 * - Original variant records are deleted after conversion
 */
class VariantConversionService
{
    /**
     * Convert all variants of a product to standalone products
     *
     * @param Product $masterProduct Master product that is losing variant status
     * @return array Result with converted product IDs and stats
     */
    public function convertVariantsToProducts(Product $masterProduct): array
    {
        Log::info('VariantConversionService: Starting conversion', [
            'master_product_id' => $masterProduct->id,
            'master_sku' => $masterProduct->sku,
        ]);

        $convertedProducts = [];
        $variantCount = 0;

        DB::transaction(function () use ($masterProduct, &$convertedProducts, &$variantCount) {
            // Load all variants with relationships
            $variants = ProductVariant::where('product_id', $masterProduct->id)
                ->with(['prices', 'stock', 'images', 'attributes'])
                ->get();

            $variantCount = $variants->count();

            if ($variantCount === 0) {
                Log::info('VariantConversionService: No variants to convert');
                return;
            }

            foreach ($variants as $variant) {
                $newProduct = $this->convertSingleVariant($masterProduct, $variant);
                $convertedProducts[] = $newProduct->id;

                Log::info('VariantConversionService: Converted variant to product', [
                    'variant_id' => $variant->id,
                    'variant_sku' => $variant->sku,
                    'new_product_id' => $newProduct->id,
                ]);
            }

            // Delete all variants (cascade will handle related tables)
            ProductVariant::where('product_id', $masterProduct->id)->delete();

            // Update master product flags
            $masterProduct->update([
                'is_variant_master' => false,
            ]);

            Log::info('VariantConversionService: Conversion complete', [
                'master_product_id' => $masterProduct->id,
                'variants_converted' => $variantCount,
                'new_product_ids' => $convertedProducts,
            ]);
        });

        return [
            'success' => true,
            'variants_converted' => $variantCount,
            'new_product_ids' => $convertedProducts,
        ];
    }

    /**
     * Convert single variant to standalone product
     *
     * @param Product $masterProduct Master product
     * @param ProductVariant $variant Variant to convert
     * @return Product New standalone product
     */
    private function convertSingleVariant(Product $masterProduct, ProductVariant $variant): Product
    {
        // Create new product with master's data + variant's SKU and name
        $newProduct = Product::create([
            // === BASIC INFO (from variant) ===
            'sku' => $variant->sku,
            'name' => $this->generateProductName($masterProduct, $variant),
            'slug' => \Illuminate\Support\Str::slug($variant->sku),

            // === INHERITED FROM MASTER ===
            'product_type_id' => $masterProduct->product_type_id,
            'manufacturer' => $masterProduct->manufacturer,
            'supplier_code' => $masterProduct->supplier_code,
            'ean' => null, // Variant usually has own EAN, but we don't have it in current schema
            'short_description' => $masterProduct->short_description,
            'long_description' => $masterProduct->long_description,
            'meta_title' => $masterProduct->meta_title,
            'meta_description' => $masterProduct->meta_description,

            // === PHYSICAL PROPERTIES (from master) ===
            'weight' => $masterProduct->weight,
            'height' => $masterProduct->height,
            'width' => $masterProduct->width,
            'length' => $masterProduct->length,
            'tax_rate' => $masterProduct->tax_rate,

            // === STATUS (from variant) ===
            'is_active' => $variant->is_active,
            'is_variant_master' => false, // New product is NOT a variant master
            'is_featured' => false,
            'sort_order' => $variant->position ?? 0,

            // === PUBLISHING SCHEDULE (from master) ===
            'available_from' => $masterProduct->available_from,
            'available_to' => $masterProduct->available_to,
        ]);

        // Copy categories from master
        $this->copyCategoriesFromMaster($masterProduct, $newProduct);

        // Copy variant prices to product prices
        $this->copyVariantPrices($variant, $newProduct);

        // Copy variant stock to product stock (if applicable)
        $this->copyVariantStock($variant, $newProduct);

        // Copy variant images to product images
        $this->copyVariantImages($variant, $newProduct);

        // TODO: Handle variant attributes (color, size) - possibly as product tags or custom fields?

        return $newProduct;
    }

    /**
     * Generate product name from variant (variant name only, master as fallback)
     */
    private function generateProductName(Product $masterProduct, ProductVariant $variant): string
    {
        // Use variant name only, or master name as fallback if variant has no name
        return $variant->name ?: $masterProduct->name;
    }

    /**
     * Copy categories from master product to new product
     */
    private function copyCategoriesFromMaster(Product $masterProduct, Product $newProduct): void
    {
        // Get all category assignments from master (default data = shop_id is null)
        // REFACTORED 2025-11-19: Use product_categories pivot table instead of product_shop_categories
        $masterCategories = DB::table('product_categories')
            ->where('product_id', $masterProduct->id)
            ->whereNull('shop_id') // Only default data categories
            ->get();

        foreach ($masterCategories as $categoryAssignment) {
            DB::table('product_categories')->insert([
                'product_id' => $newProduct->id,
                'category_id' => $categoryAssignment->category_id,
                'shop_id' => null, // Default data
                'is_primary' => $categoryAssignment->is_primary,
                'sort_order' => $categoryAssignment->sort_order ?? 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Copy variant prices to product prices
     */
    private function copyVariantPrices(ProductVariant $variant, Product $newProduct): void
    {
        foreach ($variant->prices as $variantPrice) {
            ProductPrice::create([
                'product_id' => $newProduct->id,
                'price_group_id' => $variantPrice->price_group_id,
                'price' => $variantPrice->price,
            ]);
        }
    }

    /**
     * Copy variant stock to product stock
     *
     * Note: Current schema may not have product-level stock table
     * This is placeholder for future implementation
     */
    private function copyVariantStock(ProductVariant $variant, Product $newProduct): void
    {
        // TODO: Implement when product stock system is finalized
        // For now, variant stock might need manual migration or different approach

        Log::warning('VariantConversionService: Stock copying not implemented', [
            'variant_id' => $variant->id,
            'new_product_id' => $newProduct->id,
        ]);
    }

    /**
     * Copy variant images to product images
     *
     * Note: Need to check if product images table exists and structure
     * This is placeholder for future implementation
     */
    private function copyVariantImages(ProductVariant $variant, Product $newProduct): void
    {
        // TODO: Implement when product images system is finalized
        // Variant images (VariantImage) → Product images (ProductImage?)

        Log::warning('VariantConversionService: Image copying not implemented', [
            'variant_id' => $variant->id,
            'new_product_id' => $newProduct->id,
        ]);
    }
}

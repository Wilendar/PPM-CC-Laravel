<?php

namespace Tests\Unit\Rules;

use App\Rules\UniqueSKU;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * UniqueSKU Rule Unit Tests
 *
 * Tests for SKU uniqueness validation across products and product_variants tables.
 *
 * TEST COVERAGE:
 * - New SKU validation (no conflicts)
 * - SKU conflicts with existing products
 * - SKU conflicts with existing variants
 * - Case-insensitive comparison
 * - Update scenarios (ignore current record)
 * - Empty SKU handling
 *
 * @package Tests\Unit\Rules
 * @version 1.0
 * @since ETAP_05b Phase 6 (2025-10-30)
 */
class UniqueSKUTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: New SKU passes validation (no conflicts)
     *
     * @return void
     */
    public function test_new_sku_passes_validation(): void
    {
        $rule = new UniqueSKU();

        $this->assertTrue(
            $rule->passes('sku', 'NEW-UNIQUE-SKU-123'),
            'New SKU should pass validation when no conflicts exist'
        );
    }

    /**
     * Test: SKU fails validation when exists in products table
     *
     * @return void
     */
    public function test_sku_fails_when_exists_in_products_table(): void
    {
        // Create product with SKU
        Product::factory()->create(['sku' => 'EXISTING-PRODUCT-SKU']);

        $rule = new UniqueSKU();

        $this->assertFalse(
            $rule->passes('sku', 'EXISTING-PRODUCT-SKU'),
            'SKU should fail validation when exists in products table'
        );

        $this->assertStringContainsString(
            'jest już używane',
            $rule->message(),
            'Error message should indicate SKU is already in use'
        );
    }

    /**
     * Test: SKU fails validation when exists in product_variants table
     *
     * @return void
     */
    public function test_sku_fails_when_exists_in_variants_table(): void
    {
        // Create product and variant with SKU
        $product = Product::factory()->create();
        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => 'EXISTING-VARIANT-SKU',
        ]);

        $rule = new UniqueSKU();

        $this->assertFalse(
            $rule->passes('sku', 'EXISTING-VARIANT-SKU'),
            'SKU should fail validation when exists in product_variants table'
        );
    }

    /**
     * Test: Case-insensitive SKU validation
     *
     * @return void
     */
    public function test_sku_validation_is_case_insensitive(): void
    {
        // Create product with uppercase SKU
        Product::factory()->create(['sku' => 'UPPERCASE-SKU']);

        $rule = new UniqueSKU();

        // Test lowercase variant should fail
        $this->assertFalse(
            $rule->passes('sku', 'uppercase-sku'),
            'Lowercase SKU should fail when uppercase version exists (case-insensitive)'
        );

        // Test mixed case should fail
        $this->assertFalse(
            $rule->passes('sku', 'UpPeRcAsE-sKu'),
            'Mixed case SKU should fail when uppercase version exists (case-insensitive)'
        );
    }

    /**
     * Test: SKU passes validation when updating same product (ignore current)
     *
     * @return void
     */
    public function test_sku_passes_when_updating_same_product(): void
    {
        // Create product with SKU
        $product = Product::factory()->create(['sku' => 'PRODUCT-UPDATE-SKU']);

        // Rule with ignoreProductId should pass for same SKU
        $rule = new UniqueSKU($product->id);

        $this->assertTrue(
            $rule->passes('sku', 'PRODUCT-UPDATE-SKU'),
            'SKU should pass validation when updating same product'
        );
    }

    /**
     * Test: SKU passes validation when updating same variant (ignore current)
     *
     * @return void
     */
    public function test_sku_passes_when_updating_same_variant(): void
    {
        // Create product and variant with SKU
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => 'VARIANT-UPDATE-SKU',
        ]);

        // Rule with ignoreVariantId should pass for same SKU
        $rule = new UniqueSKU(null, $variant->id);

        $this->assertTrue(
            $rule->passes('sku', 'VARIANT-UPDATE-SKU'),
            'SKU should pass validation when updating same variant'
        );
    }

    /**
     * Test: SKU fails when updating to another product's SKU
     *
     * @return void
     */
    public function test_sku_fails_when_updating_to_another_products_sku(): void
    {
        // Create two products
        $product1 = Product::factory()->create(['sku' => 'PRODUCT-1-SKU']);
        $product2 = Product::factory()->create(['sku' => 'PRODUCT-2-SKU']);

        // Try to update product2 to product1's SKU
        $rule = new UniqueSKU($product2->id);

        $this->assertFalse(
            $rule->passes('sku', 'PRODUCT-1-SKU'),
            'SKU should fail when updating to another product\'s SKU'
        );
    }

    /**
     * Test: SKU fails when updating to another variant's SKU
     *
     * @return void
     */
    public function test_sku_fails_when_updating_to_another_variants_sku(): void
    {
        // Create product with two variants
        $product = Product::factory()->create();
        $variant1 = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => 'VARIANT-1-SKU',
        ]);
        $variant2 = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => 'VARIANT-2-SKU',
        ]);

        // Try to update variant2 to variant1's SKU
        $rule = new UniqueSKU(null, $variant2->id);

        $this->assertFalse(
            $rule->passes('sku', 'VARIANT-1-SKU'),
            'SKU should fail when updating to another variant\'s SKU'
        );
    }

    /**
     * Test: Empty SKU passes validation (handled by 'required' rule)
     *
     * @return void
     */
    public function test_empty_sku_passes_validation(): void
    {
        $rule = new UniqueSKU();

        $this->assertTrue(
            $rule->passes('sku', ''),
            'Empty SKU should pass UniqueSKU validation (handled by required rule)'
        );

        $this->assertTrue(
            $rule->passes('sku', null),
            'Null SKU should pass UniqueSKU validation (handled by required rule)'
        );
    }

    /**
     * Test: Error message contains the SKU value
     *
     * @return void
     */
    public function test_error_message_contains_sku_value(): void
    {
        // Create product with SKU
        Product::factory()->create(['sku' => 'DUPLICATE-SKU']);

        $rule = new UniqueSKU();
        $rule->passes('sku', 'DUPLICATE-SKU');

        $message = $rule->message();

        $this->assertStringContainsString(
            'DUPLICATE-SKU',
            $message,
            'Error message should contain the duplicate SKU value'
        );

        $this->assertStringContainsString(
            'jest już używane',
            $message,
            'Error message should be in Polish'
        );
    }

    /**
     * Test: Cross-table validation (variant SKU conflicts with product SKU)
     *
     * @return void
     */
    public function test_variant_sku_fails_when_conflicts_with_product_sku(): void
    {
        // Create product with SKU
        Product::factory()->create(['sku' => 'CROSS-TABLE-SKU']);

        // Try to create variant with same SKU
        $rule = new UniqueSKU();

        $this->assertFalse(
            $rule->passes('sku', 'CROSS-TABLE-SKU'),
            'Variant SKU should fail when conflicts with product SKU'
        );
    }

    /**
     * Test: Cross-table validation (product SKU conflicts with variant SKU)
     *
     * @return void
     */
    public function test_product_sku_fails_when_conflicts_with_variant_sku(): void
    {
        // Create product and variant with SKU
        $product = Product::factory()->create();
        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => 'VARIANT-FIRST-SKU',
        ]);

        // Try to create product with same SKU
        $rule = new UniqueSKU();

        $this->assertFalse(
            $rule->passes('sku', 'VARIANT-FIRST-SKU'),
            'Product SKU should fail when conflicts with variant SKU'
        );
    }

    /**
     * Test: Multiple ignores (product + variant) - edge case
     *
     * @return void
     */
    public function test_multiple_ignores_edge_case(): void
    {
        // Create product and variant with same SKU (edge case - shouldn't happen but test it)
        $product = Product::factory()->create(['sku' => 'EDGE-CASE-SKU']);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => 'EDGE-CASE-SKU', // Same SKU as product (edge case)
        ]);

        // Rule with both ignores
        $rule = new UniqueSKU($product->id, $variant->id);

        $this->assertTrue(
            $rule->passes('sku', 'EDGE-CASE-SKU'),
            'SKU should pass when ignoring both product and variant with same SKU'
        );
    }
}

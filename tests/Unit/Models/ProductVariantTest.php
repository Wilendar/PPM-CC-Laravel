<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

/**
 * ProductVariantTest - Unit tests dla ProductVariant model
 * 
 * Test coverage:
 * - Master-variant relationships
 * - Inheritance logic (prices, stock, attributes)
 * - Business logic methods
 * - Accessors dla effective properties
 * - Query scopes
 * - Validation rules
 * 
 * @package Tests\Unit\Models
 * @version 1.0
 * @since FAZA A - Core Models Implementation
 */
class ProductVariantTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * Setup test environment
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Run migrations
        $this->artisan('migrate');
    }

    /*
    |--------------------------------------------------------------------------
    | BASIC MODEL TESTS
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function it_can_create_a_product_variant()
    {
        $product = Product::factory()->withVariants()->create();
        
        $variant = ProductVariant::factory()->forProduct($product)->create([
            'variant_sku' => 'TEST1',
            'variant_name' => 'Test Variant',
        ]);

        $this->assertInstanceOf(ProductVariant::class, $variant);
        $this->assertEquals('TEST1', $variant->variant_sku);
        $this->assertEquals('Test Variant', $variant->variant_name);
        $this->assertEquals($product->id, $variant->product_id);
        $this->assertTrue($variant->is_active);
    }

    /** @test */
    public function it_has_fillable_attributes()
    {
        $fillable = [
            'product_id', 'variant_sku', 'variant_name', 'ean', 'sort_order',
            'inherit_prices', 'inherit_stock', 'inherit_attributes', 'is_active'
        ];

        $variant = new ProductVariant();
        $this->assertEquals($fillable, $variant->getFillable());
    }

    /** @test */
    public function it_has_correct_casts()
    {
        $variant = new ProductVariant();
        $casts = $variant->getCasts();

        $this->assertEquals('integer', $casts['product_id']);
        $this->assertEquals('integer', $casts['sort_order']);
        $this->assertEquals('boolean', $casts['inherit_prices']);
        $this->assertEquals('boolean', $casts['inherit_stock']);
        $this->assertEquals('boolean', $casts['inherit_attributes']);
        $this->assertEquals('boolean', $casts['is_active']);
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIP TESTS
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function it_belongs_to_product()
    {
        $product = Product::factory()->withVariants()->create();
        $variant = ProductVariant::factory()->forProduct($product)->create();

        $this->assertInstanceOf(Product::class, $variant->product);
        $this->assertEquals($product->id, $variant->product->id);
    }

    /** @test */
    public function master_product_is_marked_as_variant_master_when_variant_created()
    {
        $product = Product::factory()->create(['is_variant_master' => false]);
        
        $variant = ProductVariant::factory()->forProduct($product)->create();

        // Product should be auto-marked as variant master
        $product->refresh();
        $this->assertTrue($product->is_variant_master);
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSOR TESTS - Inheritance Logic
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function effective_prices_returns_master_prices_when_inheriting()
    {
        $product = Product::factory()->withVariants()->create();
        $variant = ProductVariant::factory()->forProduct($product)->create([
            'inherit_prices' => true
        ]);

        $effectivePrices = $variant->effective_prices;

        $this->assertEquals($product->formatted_prices, $effectivePrices);
    }

    /** @test */
    public function effective_prices_returns_own_prices_when_not_inheriting()
    {
        $product = Product::factory()->withVariants()->create();
        $variant = ProductVariant::factory()->forProduct($product)->create([
            'inherit_prices' => false
        ]);

        $effectivePrices = $variant->effective_prices;

        // Should return placeholder prices for FAZA A (own prices not implemented yet)
        $this->assertIsArray($effectivePrices);
        $this->assertArrayHasKey('retail', $effectivePrices);
    }

    /** @test */
    public function effective_stock_returns_master_stock_when_inheriting()
    {
        $product = Product::factory()->withVariants()->create();
        $variant = ProductVariant::factory()->forProduct($product)->create([
            'inherit_stock' => true
        ]);

        $effectiveStock = $variant->effective_stock;

        $this->assertEquals($product->total_stock, $effectiveStock);
    }

    /** @test */
    public function effective_stock_returns_own_stock_when_not_inheriting()
    {
        $product = Product::factory()->withVariants()->create();
        $variant = ProductVariant::factory()->forProduct($product)->create([
            'inherit_stock' => false
        ]);

        $effectiveStock = $variant->effective_stock;

        // Should return 0 for FAZA A (own stock not implemented yet)
        $this->assertEquals(0, $effectiveStock);
    }

    /** @test */
    public function display_name_combines_product_and_variant_names()
    {
        $product = Product::factory()->withVariants()->create(['name' => 'Test Product']);
        $variant = ProductVariant::factory()->forProduct($product)->create([
            'variant_name' => 'Red Large'
        ]);

        $displayName = $variant->display_name;

        $this->assertEquals('Test Product - Red Large', $displayName);
    }

    /** @test */
    public function full_sku_combines_master_and_variant_skus()
    {
        $product = Product::factory()->withVariants()->create(['sku' => 'MASTER123']);
        $variant = ProductVariant::factory()->forProduct($product)->create([
            'variant_sku' => 'VAR1'
        ]);

        $fullSku = $variant->full_sku;

        $this->assertEquals('MASTER123-VAR1', $fullSku);
    }

    /** @test */
    public function inheritance_status_returns_correct_summary()
    {
        $product = Product::factory()->withVariants()->create();
        $variant = ProductVariant::factory()->forProduct($product)->create([
            'inherit_prices' => true,
            'inherit_stock' => false,
            'inherit_attributes' => true
        ]);

        $status = $variant->inheritance_status;

        $this->assertEquals('inherited', $status['prices']);
        $this->assertEquals('own', $status['stock']);
        $this->assertEquals('inherited+own', $status['attributes']);
        $this->assertEquals('inherited', $status['media']); // TODO for FAZA C
    }

    /** @test */
    public function has_own_prices_reflects_inheritance_setting()
    {
        $product = Product::factory()->withVariants()->create();
        
        $inheritingVariant = ProductVariant::factory()->forProduct($product)->create([
            'inherit_prices' => true
        ]);
        $ownPricesVariant = ProductVariant::factory()->forProduct($product)->create([
            'inherit_prices' => false
        ]);

        $this->assertFalse($inheritingVariant->has_own_prices);
        $this->assertTrue($ownPricesVariant->has_own_prices);
    }

    /** @test */
    public function has_own_stock_reflects_inheritance_setting()
    {
        $product = Product::factory()->withVariants()->create();
        
        $inheritingVariant = ProductVariant::factory()->forProduct($product)->create([
            'inherit_stock' => true
        ]);
        $ownStockVariant = ProductVariant::factory()->forProduct($product)->create([
            'inherit_stock' => false
        ]);

        $this->assertFalse($inheritingVariant->has_own_stock);
        $this->assertTrue($ownStockVariant->has_own_stock);
    }

    /*
    |--------------------------------------------------------------------------
    | QUERY SCOPE TESTS
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function active_scope_returns_only_active_variants()
    {
        $product = Product::factory()->withVariants()->create();
        
        ProductVariant::factory()->forProduct($product)->active()->create();
        ProductVariant::factory()->forProduct($product)->inactive()->create();

        $activeVariants = ProductVariant::active()->get();

        $this->assertCount(1, $activeVariants);
        $this->assertTrue($activeVariants->every(fn($v) => $v->is_active));
    }

    /** @test */
    public function for_product_scope_returns_variants_for_specific_product()
    {
        $product1 = Product::factory()->withVariants()->create();
        $product2 = Product::factory()->withVariants()->create();
        
        $variant1 = ProductVariant::factory()->forProduct($product1)->create();
        $variant2 = ProductVariant::factory()->forProduct($product1)->create();
        ProductVariant::factory()->forProduct($product2)->create();

        $product1Variants = ProductVariant::forProduct($product1->id)->get();

        $this->assertCount(2, $product1Variants);
        $this->assertTrue($product1Variants->every(fn($v) => $v->product_id === $product1->id));
    }

    /** @test */
    public function with_own_prices_scope_returns_variants_with_own_prices()
    {
        $product = Product::factory()->withVariants()->create();
        
        ProductVariant::factory()->forProduct($product)->create(['inherit_prices' => true]);
        ProductVariant::factory()->forProduct($product)->withOwnPrices()->create();

        $ownPricesVariants = ProductVariant::withOwnPrices()->get();

        $this->assertCount(1, $ownPricesVariants);
        $this->assertFalse($ownPricesVariants->first()->inherit_prices);
    }

    /** @test */
    public function with_own_stock_scope_returns_variants_with_own_stock()
    {
        $product = Product::factory()->withVariants()->create();
        
        ProductVariant::factory()->forProduct($product)->create(['inherit_stock' => true]);
        ProductVariant::factory()->forProduct($product)->withOwnStock()->create();

        $ownStockVariants = ProductVariant::withOwnStock()->get();

        $this->assertCount(1, $ownStockVariants);
        $this->assertFalse($ownStockVariants->first()->inherit_stock);
    }

    /*
    |--------------------------------------------------------------------------
    | BUSINESS LOGIC TESTS
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function sync_with_master_returns_true()
    {
        $product = Product::factory()->withVariants()->create();
        $variant = ProductVariant::factory()->forProduct($product)->create();

        $result = $variant->syncWithMaster();

        // For FAZA A, this just returns true (implementation placeholder)
        $this->assertTrue($result);
    }

    /** @test */
    public function check_inheritance_returns_empty_array_for_faza_a()
    {
        $product = Product::factory()->withVariants()->create();
        $variant = ProductVariant::factory()->forProduct($product)->create();

        $issues = $variant->checkInheritance();

        // For FAZA A, no issues are checked yet
        $this->assertIsArray($issues);
        $this->assertEmpty($issues);
    }

    /** @test */
    public function toggle_inheritance_updates_inheritance_flags()
    {
        $product = Product::factory()->withVariants()->create();
        $variant = ProductVariant::factory()->forProduct($product)->create([
            'inherit_prices' => true
        ]);

        $result = $variant->toggleInheritance('prices', false);

        $this->assertTrue($result);
        $variant->refresh();
        $this->assertFalse($variant->inherit_prices);
    }

    /** @test */
    public function toggle_inheritance_throws_exception_for_invalid_property()
    {
        $product = Product::factory()->withVariants()->create();
        $variant = ProductVariant::factory()->forProduct($product)->create();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid inheritance property: invalid');

        $variant->toggleInheritance('invalid', true);
    }

    /** @test */
    public function create_from_master_creates_variant_with_proper_defaults()
    {
        $product = Product::factory()->withVariants()->create();

        $variant = ProductVariant::createFromMaster(
            $product,
            'Test Variant',
            'TESTVAR',
            ['inherit_stock' => true, 'sort_order' => 10]
        );

        $this->assertInstanceOf(ProductVariant::class, $variant);
        $this->assertEquals($product->id, $variant->product_id);
        $this->assertEquals('Test Variant', $variant->variant_name);
        $this->assertEquals('TESTVAR', $variant->variant_sku);
        $this->assertTrue($variant->inherit_prices); // Default
        $this->assertTrue($variant->inherit_stock); // Override
        $this->assertTrue($variant->inherit_attributes); // Default
        $this->assertEquals(10, $variant->sort_order); // Override
        $this->assertTrue($variant->is_active); // Default
    }

    /** @test */
    public function create_from_master_throws_exception_if_product_not_variant_master()
    {
        $product = Product::factory()->create(['is_variant_master' => false]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Product must be marked as variant master');

        ProductVariant::createFromMaster($product, 'Test Variant', 'TESTVAR');
    }

    /*
    |--------------------------------------------------------------------------
    | VALIDATION TESTS
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function validate_business_rules_checks_sku_format()
    {
        $variant = ProductVariant::factory()->make(['variant_sku' => 'invalid-sku!@#']);
        
        $errors = $variant->validateBusinessRules();
        
        $this->assertContains('Variant SKU must contain only uppercase letters, numbers, hyphens and underscores', $errors);
    }

    /** @test */
    public function validate_business_rules_checks_empty_variant_name()
    {
        $variant = ProductVariant::factory()->make(['variant_name' => '   ']);
        
        $errors = $variant->validateBusinessRules();
        
        $this->assertContains('Variant name is required', $errors);
    }

    /** @test */
    public function validate_business_rules_checks_master_product_variant_flag()
    {
        $product = Product::factory()->create(['is_variant_master' => false]);
        $variant = ProductVariant::factory()->make(['product_id' => $product->id]);
        
        $errors = $variant->validateBusinessRules();
        
        $this->assertContains('Product must be marked as variant master to have variants', $errors);
    }

    /*
    |--------------------------------------------------------------------------
    | SKU NORMALIZATION TESTS
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function variant_sku_is_normalized_to_uppercase_on_create()
    {
        $product = Product::factory()->withVariants()->create();
        
        $variant = ProductVariant::factory()->forProduct($product)->create([
            'variant_sku' => '  test123  '
        ]);

        $this->assertEquals('TEST123', $variant->variant_sku);
    }

    /** @test */
    public function variant_sku_is_normalized_to_uppercase_on_update()
    {
        $product = Product::factory()->withVariants()->create();
        $variant = ProductVariant::factory()->forProduct($product)->create();

        $variant->update(['variant_sku' => '  updated123  ']);

        $this->assertEquals('UPDATED123', $variant->variant_sku);
    }

    /*
    |--------------------------------------------------------------------------
    | SORT ORDER TESTS
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function sort_order_is_auto_set_based_on_existing_variants()
    {
        $product = Product::factory()->withVariants()->create();
        
        // Create variants without specifying sort_order
        $variant1 = ProductVariant::factory()->forProduct($product)->create(['sort_order' => null]);
        $variant2 = ProductVariant::factory()->forProduct($product)->create(['sort_order' => null]);

        // Auto-set sort_order should be based on count
        $this->assertEquals(1, $variant1->fresh()->sort_order);
        $this->assertEquals(2, $variant2->fresh()->sort_order);
    }

    /*
    |--------------------------------------------------------------------------
    | SOFT DELETE TESTS
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function variant_can_be_soft_deleted()
    {
        $product = Product::factory()->withVariants()->create();
        $variant = ProductVariant::factory()->forProduct($product)->create();
        $variantId = $variant->id;

        $variant->delete();

        $this->assertSoftDeleted('product_variants', ['id' => $variantId]);
    }

    /*
    |--------------------------------------------------------------------------
    | FACTORY HELPER TESTS
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function create_variants_for_product_creates_multiple_variants()
    {
        $product = Product::factory()->create(['is_variant_master' => false]);
        
        $variants = ProductVariantFactory::createVariantsForProduct($product, ['S', 'M', 'L']);

        $this->assertCount(3, $variants);
        
        // Product should be marked as variant master
        $product->refresh();
        $this->assertTrue($product->is_variant_master);
        
        // Check variant names and sort order
        $this->assertEquals('S', $variants[0]->variant_name);
        $this->assertEquals('M', $variants[1]->variant_name);
        $this->assertEquals('L', $variants[2]->variant_name);
        
        $this->assertEquals(1, $variants[0]->sort_order);
        $this->assertEquals(2, $variants[1]->sort_order);
        $this->assertEquals(3, $variants[2]->sort_order);
    }

    /** @test */
    public function create_clothing_sizes_creates_standard_size_variants()
    {
        $product = Product::factory()->create();
        
        $variants = ProductVariantFactory::createClothingSizes($product);

        $this->assertCount(6, $variants);
        $expectedSizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
        
        foreach ($variants as $index => $variant) {
            $this->assertEquals($expectedSizes[$index], $variant->variant_name);
        }
    }

    /** @test */
    public function create_color_variants_creates_standard_color_variants()
    {
        $product = Product::factory()->create();
        
        $variants = ProductVariantFactory::createColorVariants($product);

        $this->assertCount(5, $variants);
        $expectedColors = ['Czarny', 'Biały', 'Czerwony', 'Niebieski', 'Zielony'];
        
        foreach ($variants as $index => $variant) {
            $this->assertEquals($expectedColors[$index], $variant->variant_name);
        }
    }

    /** @test */
    public function route_key_name_is_id()
    {
        $variant = new ProductVariant();
        
        $this->assertEquals('id', $variant->getRouteKeyName());
    }
}
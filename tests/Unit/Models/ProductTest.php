<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Category;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Database\Eloquent\Collection;

/**
 * ProductTest - Unit tests dla Product model
 * 
 * Test coverage:
 * - Model attributes casting i validation
 * - Business logic methods
 * - Relationships (categories, variants)
 * - Accessors i mutators
 * - Query scopes
 * - Enterprise validation rules
 * 
 * @package Tests\Unit\Models
 * @version 1.0
 * @since FAZA A - Core Models Implementation
 */
class ProductTest extends TestCase
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
    public function it_can_create_a_product()
    {
        $product = Product::factory()->create([
            'sku' => 'TEST123',
            'name' => 'Test Product',
            'product_type' => 'spare_part',
        ]);

        $this->assertInstanceOf(Product::class, $product);
        $this->assertEquals('TEST123', $product->sku);
        $this->assertEquals('Test Product', $product->name);
        $this->assertEquals('spare_part', $product->product_type);
        $this->assertTrue($product->is_active);
    }

    /** @test */
    public function it_has_fillable_attributes()
    {
        $fillable = [
            'sku', 'name', 'short_description', 'long_description',
            'product_type', 'manufacturer', 'supplier_code',
            'weight', 'height', 'width', 'length', 'ean', 'tax_rate',
            'is_active', 'is_variant_master', 'sort_order',
            'meta_title', 'meta_description'
        ];

        $product = new Product();
        $this->assertEquals($fillable, $product->getFillable());
    }

    /** @test */
    public function it_has_correct_casts()
    {
        $product = new Product();
        $casts = $product->getCasts();

        $this->assertEquals('decimal:3', $casts['weight']);
        $this->assertEquals('decimal:2', $casts['height']);
        $this->assertEquals('decimal:2', $casts['width']);
        $this->assertEquals('decimal:2', $casts['length']);
        $this->assertEquals('decimal:2', $casts['tax_rate']);
        $this->assertEquals('boolean', $casts['is_active']);
        $this->assertEquals('boolean', $casts['is_variant_master']);
        $this->assertEquals('integer', $casts['sort_order']);
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSOR & MUTATOR TESTS
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function sku_accessor_returns_uppercase_trimmed_value()
    {
        $product = Product::factory()->make(['sku' => '  test123  ']);
        
        $this->assertEquals('TEST123', $product->sku);
    }

    /** @test */
    public function sku_mutator_sets_uppercase_trimmed_value()
    {
        $product = new Product();
        $product->sku = '  test123  ';
        
        $this->assertEquals('TEST123', $product->getAttributes()['sku']);
    }

    /** @test */
    public function primary_image_returns_placeholder_when_no_media()
    {
        $product = Product::factory()->create(['product_type' => 'vehicle']);
        
        $this->assertEquals('/images/placeholders/vehicle-placeholder.jpg', $product->primary_image);
    }

    /** @test */
    public function display_name_includes_manufacturer_when_present()
    {
        $product = Product::factory()->make([
            'name' => 'Test Product',
            'manufacturer' => 'YAMAHA'
        ]);
        
        $this->assertEquals('YAMAHA Test Product', $product->display_name);
    }

    /** @test */
    public function display_name_returns_name_only_when_no_manufacturer()
    {
        $product = Product::factory()->make([
            'name' => 'Test Product',
            'manufacturer' => null
        ]);
        
        $this->assertEquals('Test Product', $product->display_name);
    }

    /** @test */
    public function dimensions_accessor_returns_array()
    {
        $product = Product::factory()->make([
            'length' => 100.50,
            'width' => 50.25,
            'height' => 30.00,
            'weight' => 15.500
        ]);

        $dimensions = $product->dimensions;
        
        $this->assertIsArray($dimensions);
        $this->assertEquals(100.50, $dimensions['length']);
        $this->assertEquals(50.25, $dimensions['width']);
        $this->assertEquals(30.00, $dimensions['height']);
        $this->assertEquals(15.500, $dimensions['weight']);
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIP TESTS
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function it_has_many_variants()
    {
        $product = Product::factory()->withVariants()->create();
        $variant1 = ProductVariant::factory()->forProduct($product)->create();
        $variant2 = ProductVariant::factory()->forProduct($product)->create();

        $this->assertInstanceOf(Collection::class, $product->variants);
        $this->assertCount(2, $product->variants);
        $this->assertTrue($product->variants->contains($variant1));
        $this->assertTrue($product->variants->contains($variant2));
    }

    /** @test */
    public function it_belongs_to_many_categories()
    {
        $product = Product::factory()->create();
        $category1 = Category::factory()->create();
        $category2 = Category::factory()->create();

        $product->categories()->attach($category1->id, ['is_primary' => true]);
        $product->categories()->attach($category2->id, ['is_primary' => false]);

        $this->assertInstanceOf(Collection::class, $product->categories);
        $this->assertCount(2, $product->categories);
        $this->assertTrue($product->categories->contains($category1));
        $this->assertTrue($product->categories->contains($category2));
    }

    /** @test */
    public function it_has_primary_category_relationship()
    {
        $product = Product::factory()->create();
        $primaryCategory = Category::factory()->create();
        $regularCategory = Category::factory()->create();

        $product->categories()->attach($primaryCategory->id, ['is_primary' => true]);
        $product->categories()->attach($regularCategory->id, ['is_primary' => false]);

        $primary = $product->primaryCategory()->first();
        
        $this->assertNotNull($primary);
        $this->assertEquals($primaryCategory->id, $primary->id);
    }

    /*
    |--------------------------------------------------------------------------
    | QUERY SCOPE TESTS
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function active_scope_returns_only_active_products()
    {
        Product::factory()->create(['is_active' => true, 'sku' => 'ACTIVE1']);
        Product::factory()->create(['is_active' => false, 'sku' => 'INACTIVE1']);
        Product::factory()->create(['is_active' => true, 'sku' => 'ACTIVE2']);

        $activeProducts = Product::active()->get();

        $this->assertCount(2, $activeProducts);
        $this->assertTrue($activeProducts->every(fn($p) => $p->is_active));
    }

    /** @test */
    public function with_variants_scope_returns_variant_masters_with_variants()
    {
        $masterWithVariants = Product::factory()->withVariants()->create();
        ProductVariant::factory()->forProduct($masterWithVariants)->create();
        
        $masterWithoutVariants = Product::factory()->withVariants()->create();
        $regularProduct = Product::factory()->create(['is_variant_master' => false]);

        $productsWithVariants = Product::withVariants()->get();

        $this->assertCount(1, $productsWithVariants);
        $this->assertTrue($productsWithVariants->contains($masterWithVariants));
    }

    /** @test */
    public function by_type_scope_filters_by_product_type()
    {
        Product::factory()->create(['product_type' => 'vehicle', 'sku' => 'VEH1']);
        Product::factory()->create(['product_type' => 'spare_part', 'sku' => 'PART1']);
        Product::factory()->create(['product_type' => 'vehicle', 'sku' => 'VEH2']);

        $vehicles = Product::byType('vehicle')->get();

        $this->assertCount(2, $vehicles);
        $this->assertTrue($vehicles->every(fn($p) => $p->product_type === 'vehicle'));
    }

    /** @test */
    public function search_scope_finds_products_by_various_fields()
    {
        Product::factory()->create(['sku' => 'SEARCH123', 'name' => 'Different Name']);
        Product::factory()->create(['sku' => 'OTHER456', 'name' => 'Search Term Here']);
        Product::factory()->create(['sku' => 'NOMATCH', 'name' => 'No Match', 'manufacturer' => 'SEARCH']);

        // Search by SKU
        $skuResults = Product::search('SEARCH123')->get();
        $this->assertCount(1, $skuResults);

        // Search by name
        $nameResults = Product::search('Search Term')->get();
        $this->assertCount(1, $nameResults);

        // Search by manufacturer
        $mfrResults = Product::search('SEARCH')->get();
        $this->assertCount(2, $mfrResults); // Should find both SKU and manufacturer matches
    }

    /*
    |--------------------------------------------------------------------------
    | BUSINESS LOGIC TESTS
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function set_primary_category_removes_other_primary_categories()
    {
        $product = Product::factory()->create();
        $category1 = Category::factory()->create();
        $category2 = Category::factory()->create();
        $category3 = Category::factory()->create();

        // Attach categories with one as primary
        $product->categories()->attach($category1->id, ['is_primary' => true]);
        $product->categories()->attach($category2->id, ['is_primary' => false]);

        // Set new primary category
        $result = $product->setPrimaryCategory($category3->id);

        $this->assertTrue($result);
        
        // Refresh relationships
        $product->load('categories');
        
        // Check that only category3 is primary
        $primaryCategories = $product->categories()->wherePivot('is_primary', true)->get();
        $this->assertCount(1, $primaryCategories);
        $this->assertEquals($category3->id, $primaryCategories->first()->id);
    }

    /** @test */
    public function can_delete_returns_false_for_products_with_active_variants()
    {
        $product = Product::factory()->withVariants()->create();
        ProductVariant::factory()->forProduct($product)->active()->create();

        $this->assertFalse($product->canDelete());
    }

    /** @test */
    public function can_delete_returns_true_for_products_without_active_variants()
    {
        $product = Product::factory()->create();
        
        $this->assertTrue($product->canDelete());
    }

    /** @test */
    public function validate_business_rules_checks_sku_format()
    {
        $product = Product::factory()->make(['sku' => 'invalid-sku!@#']);
        
        $errors = $product->validateBusinessRules();
        
        $this->assertContains('SKU must contain only uppercase letters, numbers, hyphens and underscores', $errors);
    }

    /** @test */
    public function validate_business_rules_checks_description_lengths()
    {
        $product = Product::factory()->make([
            'short_description' => str_repeat('a', 801),
            'long_description' => str_repeat('b', 21845)
        ]);
        
        $errors = $product->validateBusinessRules();
        
        $this->assertContains('Short description cannot exceed 800 characters', $errors);
        $this->assertContains('Long description cannot exceed 21844 characters', $errors);
    }

    /*
    |--------------------------------------------------------------------------
    | SLUG GENERATION TESTS
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function slug_is_auto_generated_on_create_when_not_provided()
    {
        $product = Product::factory()->create([
            'name' => 'Test Product Name',
            'slug' => null
        ]);

        $this->assertNotNull($product->slug);
        $this->assertStringStartsWith('test-product-name', $product->slug);
    }

    /** @test */
    public function slug_generation_ensures_uniqueness()
    {
        Product::factory()->create([
            'name' => 'Same Name',
            'slug' => 'same-name'
        ]);

        $product2 = Product::factory()->create([
            'name' => 'Same Name',
            'slug' => null
        ]);

        $this->assertNotEquals('same-name', $product2->slug);
        $this->assertStringStartsWith('same-name-', $product2->slug);
    }

    /*
    |--------------------------------------------------------------------------
    | ROUTE MODEL BINDING TESTS
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function route_key_name_is_slug()
    {
        $product = new Product();
        
        $this->assertEquals('slug', $product->getRouteKeyName());
    }

    /** @test */
    public function resolve_route_binding_finds_by_slug_first()
    {
        $product = Product::factory()->create(['slug' => 'test-product']);
        
        $resolved = (new Product())->resolveRouteBinding('test-product');
        
        $this->assertInstanceOf(Product::class, $resolved);
        $this->assertEquals($product->id, $resolved->id);
    }

    /** @test */
    public function resolve_route_binding_falls_back_to_id()
    {
        $product = Product::factory()->create();
        
        $resolved = (new Product())->resolveRouteBinding($product->id);
        
        $this->assertInstanceOf(Product::class, $resolved);
        $this->assertEquals($product->id, $resolved->id);
    }

    /*
    |--------------------------------------------------------------------------
    | SOFT DELETE TESTS
    |--------------------------------------------------------------------------
    */

    /** @test */
    public function product_can_be_soft_deleted()
    {
        $product = Product::factory()->create();
        $productId = $product->id;

        $product->delete();

        $this->assertSoftDeleted('products', ['id' => $productId]);
        $this->assertNotNull($product->fresh()->deleted_at);
    }

    /** @test */
    public function soft_deleted_products_are_excluded_from_default_queries()
    {
        $activeProduct = Product::factory()->create(['sku' => 'ACTIVE']);
        $deletedProduct = Product::factory()->create(['sku' => 'DELETED']);
        
        $deletedProduct->delete();

        $products = Product::all();
        
        $this->assertCount(1, $products);
        $this->assertTrue($products->contains($activeProduct));
        $this->assertFalse($products->contains($deletedProduct));
    }
}
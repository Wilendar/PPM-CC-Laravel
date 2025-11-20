<?php

namespace Tests\Feature\Livewire;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\PrestaShopShop;
use App\Models\ProductShopData;
use App\Models\Category;
use App\Services\CategoryMappingsConverter;
use App\Services\PrestaShop\CategoryMapper;
use App\Http\Livewire\Products\Management\ProductForm;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

/**
 * ProductFormCategoryMappingsTest - FIX #12
 *
 * Tests for category mappings refactoring using Option A canonical format
 *
 * Coverage:
 * - ProductFormSaver::saveShopData() - UI to Option A conversion
 * - ProductMultiStoreManager::loadShopData() - Option A to UI conversion
 * - ProductForm::pullShopData() - PrestaShop to Option A conversion
 * - ProductForm::getPendingChangesForShop() - Category comparison
 * - ProductForm::reloadCleanShopCategories() - UI state reload
 *
 * @package Tests\Feature\Livewire
 * @version 1.0
 * @since 2025-11-18
 */
class ProductFormCategoryMappingsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected PrestaShopShop $shop;
    protected Product $product;
    protected Category $category1;
    protected Category $category2;
    protected Category $category3;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user with admin privileges
        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        // Create test PrestaShop shop
        $this->shop = PrestaShopShop::factory()->create([
            'name' => 'Test Shop',
            'is_active' => true,
            'connection_status' => 'connected',
        ]);

        // Create test product
        $this->product = Product::factory()->create([
            'sku' => 'TEST-MAPPINGS-001',
            'name' => 'Test Product Category Mappings',
        ]);

        // Create test categories
        $this->category1 = Category::factory()->create(['name' => 'Category 1']);
        $this->category2 = Category::factory()->create(['name' => 'Category 2']);
        $this->category3 = Category::factory()->create(['name' => 'Category 3']);
    }

    /**
     * Test 1: Save product builds Option A category mappings from UI state
     *
     * Scenario: User selects categories in UI, saves product
     * Expected: ProductFormSaver converts UI format to Option A using CategoryMappingsConverter
     */
    public function test_save_product_builds_option_a_category_mappings(): void
    {
        // Mock CategoryMapper to return PrestaShop IDs
        $this->mock(CategoryMapper::class, function ($mock) {
            $mock->shouldReceive('mapToPrestaShop')
                ->with($this->category1->id, $this->shop)
                ->andReturn(100);
            $mock->shouldReceive('mapToPrestaShop')
                ->with($this->category2->id, $this->shop)
                ->andReturn(200);
        });

        // Create Livewire component
        $component = Livewire::test(ProductForm::class, ['productId' => $this->product->id])
            ->set('activeShopId', $this->shop->id)
            ->set('shopCategories.' . $this->shop->id, [
                'selected' => [$this->category1->id, $this->category2->id],
                'primary' => $this->category1->id,
            ])
            ->call('save');

        // Verify ProductShopData was created with Option A format
        $shopData = ProductShopData::where('product_id', $this->product->id)
            ->where('shop_id', $this->shop->id)
            ->first();

        $this->assertNotNull($shopData);
        $this->assertIsArray($shopData->category_mappings);
        $this->assertArrayHasKey('ui', $shopData->category_mappings);
        $this->assertArrayHasKey('mappings', $shopData->category_mappings);
        $this->assertArrayHasKey('metadata', $shopData->category_mappings);

        // Verify UI section
        $this->assertEquals([$this->category1->id, $this->category2->id], $shopData->category_mappings['ui']['selected']);
        $this->assertEquals($this->category1->id, $shopData->category_mappings['ui']['primary']);

        // Verify mappings section
        $this->assertEquals(100, $shopData->category_mappings['mappings'][(string) $this->category1->id]);
        $this->assertEquals(200, $shopData->category_mappings['mappings'][(string) $this->category2->id]);

        // Verify metadata
        $this->assertEquals('manual', $shopData->category_mappings['metadata']['source']);
    }

    /**
     * Test 2: Pull shop data converts PrestaShop IDs to Option A
     *
     * Scenario: User clicks "Wczytaj dane ze sklepu", PrestaShop API returns categories
     * Expected: ProductForm converts PrestaShop IDs to Option A using CategoryMappingsConverter
     */
    public function test_pull_shop_data_converts_prestashop_to_option_a(): void
    {
        // Mock CategoryMapper to reverse-lookup PPM IDs
        $this->mock(CategoryMapper::class, function ($mock) {
            $mock->shouldReceive('mapFromPrestaShop')
                ->with(100, $this->shop)
                ->andReturn($this->category1->id);
            $mock->shouldReceive('mapFromPrestaShop')
                ->with(200, $this->shop)
                ->andReturn($this->category2->id);
        });

        // Mock PrestaShop client response
        $this->mock(\App\Services\PrestaShop\PrestaShopClientFactory::class, function ($mock) {
            $client = \Mockery::mock();
            $client->shouldReceive('getProduct')
                ->with(\Mockery::any())
                ->andReturn([
                    'id' => 9999,
                    'name' => 'Product from PrestaShop',
                    'categories' => [
                        ['id' => 100],
                        ['id' => 200],
                    ],
                ]);

            $mock->shouldReceive('make')
                ->andReturn($client);
        });

        // Create ProductShopData with PrestaShop product ID
        ProductShopData::create([
            'product_id' => $this->product->id,
            'shop_id' => $this->shop->id,
            'prestashop_product_id' => 9999,
        ]);

        // Pull shop data
        $component = Livewire::test(ProductForm::class, ['productId' => $this->product->id])
            ->call('pullShopData', $this->shop->id);

        // Verify ProductShopData was updated with Option A format
        $shopData = ProductShopData::where('product_id', $this->product->id)
            ->where('shop_id', $this->shop->id)
            ->first();

        $this->assertNotNull($shopData);
        $this->assertIsArray($shopData->category_mappings);
        $this->assertArrayHasKey('ui', $shopData->category_mappings);
        $this->assertArrayHasKey('mappings', $shopData->category_mappings);

        // Verify UI section (reverse-mapped from PrestaShop)
        $this->assertContains($this->category1->id, $shopData->category_mappings['ui']['selected']);
        $this->assertContains($this->category2->id, $shopData->category_mappings['ui']['selected']);

        // Verify mappings section
        $this->assertEquals(100, $shopData->category_mappings['mappings'][(string) $this->category1->id]);
        $this->assertEquals(200, $shopData->category_mappings['mappings'][(string) $this->category2->id]);

        // Verify metadata
        $this->assertEquals('pull', $shopData->category_mappings['metadata']['source']);
    }

    /**
     * Test 3: Load shop data converts Option A to UI format
     *
     * Scenario: User opens product edit form with existing ProductShopData
     * Expected: ProductMultiStoreManager converts Option A to UI format
     */
    public function test_load_shop_data_converts_option_a_to_ui(): void
    {
        // Create ProductShopData with Option A canonical format
        ProductShopData::create([
            'product_id' => $this->product->id,
            'shop_id' => $this->shop->id,
            'category_mappings' => [
                'ui' => [
                    'selected' => [$this->category1->id, $this->category2->id],
                    'primary' => $this->category1->id,
                ],
                'mappings' => [
                    (string) $this->category1->id => 100,
                    (string) $this->category2->id => 200,
                ],
                'metadata' => [
                    'last_updated' => now()->toIso8601String(),
                    'source' => 'manual',
                ],
            ],
        ]);

        // Load product in Livewire component
        $component = Livewire::test(ProductForm::class, ['productId' => $this->product->id]);

        // Verify UI state was populated from Option A
        $this->assertArrayHasKey($this->shop->id, $component->shopCategories);
        $this->assertEquals([$this->category1->id, $this->category2->id], $component->shopCategories[$this->shop->id]['selected']);
        $this->assertEquals($this->category1->id, $component->shopCategories[$this->shop->id]['primary']);
    }

    /**
     * Test 4: Pending changes detection using Option A comparison
     *
     * Scenario: ProductShopData has different categories than cached PrestaShop data
     * Expected: getPendingChangesForShop() detects category changes using Option A format
     */
    public function test_pending_changes_detects_category_differences(): void
    {
        // Create ProductShopData with Option A format
        $shopData = ProductShopData::create([
            'product_id' => $this->product->id,
            'shop_id' => $this->shop->id,
            'category_mappings' => [
                'ui' => [
                    'selected' => [$this->category1->id, $this->category2->id],
                    'primary' => $this->category1->id,
                ],
                'mappings' => [
                    (string) $this->category1->id => 100,
                    (string) $this->category2->id => 200,
                ],
                'metadata' => [
                    'last_updated' => now()->toIso8601String(),
                    'source' => 'manual',
                ],
            ],
        ]);

        // Create Livewire component with cached PrestaShop data (different categories)
        $component = Livewire::test(ProductForm::class, ['productId' => $this->product->id])
            ->set('loadedShopData.' . $this->shop->id, [
                'categories' => [
                    ['id' => 100], // Same as saved
                    ['id' => 300], // DIFFERENT (200 changed to 300)
                ],
            ]);

        // Get pending changes
        $changes = $component->instance()->getPendingChangesForShop($this->shop->id);

        // Verify category changes were detected
        $this->assertContains('Kategorie', $changes);
    }

    /**
     * Test 5: Backward compatibility with old category_mappings formats
     *
     * Scenario: ProductShopData has old format (PrestaShop ID → PrestaShop ID)
     * Expected: System handles gracefully without errors
     */
    public function test_backward_compatibility_with_old_formats(): void
    {
        // Create ProductShopData with OLD format ({"2": 2, "15": 15})
        ProductShopData::create([
            'product_id' => $this->product->id,
            'shop_id' => $this->shop->id,
            'category_mappings' => [
                '2' => 2,
                '15' => 15,
            ],
        ]);

        // Load product in Livewire component (should not crash)
        $component = Livewire::test(ProductForm::class, ['productId' => $this->product->id]);

        // Verify component loaded successfully
        $this->assertNotNull($component->instance()->product);
        $this->assertEquals($this->product->id, $component->instance()->product->id);

        // Old format should NOT populate shopCategories (no 'ui' section)
        $this->assertEmpty($component->shopCategories[$this->shop->id] ?? []);
    }

    /**
     * Test 6: reloadCleanShopCategories() syncs UI state from database
     *
     * Scenario: ProductShopData updated externally (e.g., by job), UI needs refresh
     * Expected: reloadCleanShopCategories() converts Option A to UI and dispatches event
     */
    public function test_reload_clean_shop_categories(): void
    {
        // Create ProductShopData with Option A format
        ProductShopData::create([
            'product_id' => $this->product->id,
            'shop_id' => $this->shop->id,
            'category_mappings' => [
                'ui' => [
                    'selected' => [$this->category1->id, $this->category3->id],
                    'primary' => $this->category1->id,
                ],
                'mappings' => [
                    (string) $this->category1->id => 100,
                    (string) $this->category3->id => 300,
                ],
                'metadata' => [
                    'last_updated' => now()->toIso8601String(),
                    'source' => 'pull',
                ],
            ],
        ]);

        // Create Livewire component
        $component = Livewire::test(ProductForm::class, ['productId' => $this->product->id]);

        // Call protected method using reflection
        $reflection = new \ReflectionClass($component->instance());
        $method = $reflection->getMethod('reloadCleanShopCategories');
        $method->setAccessible(true);
        $method->invoke($component->instance(), $this->shop->id);

        // Verify UI state was updated
        $this->assertArrayHasKey($this->shop->id, $component->shopCategories);
        $this->assertEquals([$this->category1->id, $this->category3->id], $component->shopCategories[$this->shop->id]['selected']);
        $this->assertEquals($this->category1->id, $component->shopCategories[$this->shop->id]['primary']);

        // Verify event was dispatched
        $component->assertDispatched('shop-categories-reloaded');
    }

    /**
     * Test 7: CategoryMappingsConverter integration with ProductShopData cast
     *
     * Scenario: ProductShopData uses CategoryMappingsCast for automatic conversion
     * Expected: Canonical format stored in DB, accessed as PHP array seamlessly
     */
    public function test_category_mappings_cast_integration(): void
    {
        // Create ProductShopData with Option A format
        $shopData = ProductShopData::create([
            'product_id' => $this->product->id,
            'shop_id' => $this->shop->id,
            'category_mappings' => [
                'ui' => [
                    'selected' => [$this->category1->id, $this->category2->id],
                    'primary' => $this->category1->id,
                ],
                'mappings' => [
                    (string) $this->category1->id => 100,
                    (string) $this->category2->id => 200,
                ],
                'metadata' => [
                    'last_updated' => now()->toIso8601String(),
                    'source' => 'manual',
                ],
            ],
        ]);

        // Reload from database
        $reloaded = ProductShopData::find($shopData->id);

        // Verify CategoryMappingsCast preserved structure
        $this->assertIsArray($reloaded->category_mappings);
        $this->assertArrayHasKey('ui', $reloaded->category_mappings);
        $this->assertArrayHasKey('mappings', $reloaded->category_mappings);
        $this->assertArrayHasKey('metadata', $reloaded->category_mappings);

        // Verify hasCategoryMappings() helper works
        $this->assertTrue($reloaded->hasCategoryMappings());

        // Verify getCategoryMappingsUi() helper extracts UI format
        $uiFormat = $reloaded->getCategoryMappingsUi();
        $this->assertEquals([$this->category1->id, $this->category2->id], $uiFormat['selected']);
        $this->assertEquals($this->category1->id, $uiFormat['primary']);
    }
}

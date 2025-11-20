<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\PrestaShop\ProductTransformer;
use App\Services\PrestaShop\CategoryMapper;
use App\Services\PrestaShop\PriceGroupMapper;
use App\Services\PrestaShop\WarehouseMapper;
use App\Services\PrestaShop\BasePrestaShopClient;
use App\Services\PrestaShop\PrestaShop8Client;
use App\Models\Product;
use App\Models\ProductShopData;
use App\Models\PrestaShopShop;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

/**
 * ProductTransformer Category Mapping Tests
 *
 * FIX #12 (2025-11-18): Tests for Option A category_mappings structure
 *
 * Test Coverage:
 * 1. Option A format: extracting PrestaShop IDs from mappings
 * 2. Backward compatibility: legacy formats
 * 3. Fallback to CategoryMapper when mappings empty
 * 4. Fallback to global categories when no shop data
 * 5. Helper method: extractPrestaShopIds()
 */
class ProductTransformerCategoryTest extends TestCase
{
    use RefreshDatabase;

    private ProductTransformer $transformer;
    private PrestaShopShop $shop;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock dependencies
        $categoryMapper = $this->createMock(CategoryMapper::class);
        $categoryMapper->method('mapToPrestaShop')
            ->willReturnCallback(function ($ppmId, $shop) {
                // Simple mock: PPM ID 100 → PrestaShop ID 9, etc.
                return match ($ppmId) {
                    100 => 9,
                    103 => 15,
                    42 => 800,
                    default => null,
                };
            });

        $priceGroupMapper = $this->createMock(PriceGroupMapper::class);
        $warehouseMapper = $this->createMock(WarehouseMapper::class);

        $this->transformer = new ProductTransformer(
            $categoryMapper,
            $priceGroupMapper,
            $warehouseMapper
        );

        // Create test shop
        $this->shop = PrestaShopShop::factory()->create([
            'name' => 'Test Shop',
            'url' => 'https://test.example.com',
            'api_key' => encrypt('test_key'),
            'version' => '8',
        ]);

        // Create test product with categories
        $this->product = Product::factory()->create([
            'sku' => 'TEST-SKU-001',
            'name' => 'Test Product',
        ]);

        // Create global categories
        $category1 = Category::factory()->create(['id' => 100, 'name' => 'Category 1']);
        $category2 = Category::factory()->create(['id' => 103, 'name' => 'Category 2']);
        $this->product->categories()->attach([$category1->id, $category2->id]);
    }

    /**
     * Test: Option A structure - extract PrestaShop IDs from mappings
     *
     * Expected:
     * - Extract from category_mappings['mappings'] (values only)
     * - Return PrestaShop category IDs: [9, 15, 800]
     * - Build associations: [['id' => 9], ['id' => 15], ['id' => 800]]
     */
    public function test_build_category_associations_option_a_format(): void
    {
        // Arrange: Create ProductShopData with Option A structure
        $shopData = ProductShopData::factory()->create([
            'product_id' => $this->product->id,
            'shop_id' => $this->shop->id,
            'category_mappings' => [
                'ui' => [
                    'selected' => [100, 103, 42],
                    'primary' => 100,
                ],
                'mappings' => [
                    '100' => 9,
                    '103' => 15,
                    '42' => 800,
                ],
                'metadata' => [
                    'last_updated' => '2025-11-18 10:00:00',
                    'source' => 'manual',
                ],
            ],
        ]);

        // Create mock client
        $client = $this->createMockClient($this->shop);

        // Act: Transform product
        $result = $this->transformer->transformForPrestaShop($this->product, $client);

        // Assert: Verify category associations
        $this->assertArrayHasKey('product', $result);
        $this->assertArrayHasKey('associations', $result['product']);
        $this->assertArrayHasKey('categories', $result['product']['associations']);

        $categories = $result['product']['associations']['categories'];
        $this->assertCount(3, $categories);

        // Verify PrestaShop IDs extracted from mappings
        $ids = array_column($categories, 'id');
        $this->assertContains(9, $ids);
        $this->assertContains(15, $ids);
        $this->assertContains(800, $ids);
    }

    /**
     * Test: Option A structure with empty mappings - fallback to CategoryMapper
     *
     * Expected:
     * - No mappings in category_mappings
     * - Use ui.selected and map via CategoryMapper
     * - Return mapped PrestaShop IDs
     */
    public function test_build_category_associations_option_a_no_mappings_use_category_mapper(): void
    {
        // Arrange: Option A without mappings
        $shopData = ProductShopData::factory()->create([
            'product_id' => $this->product->id,
            'shop_id' => $this->shop->id,
            'category_mappings' => [
                'ui' => [
                    'selected' => [100, 103],
                    'primary' => 100,
                ],
                // No mappings key
                'metadata' => [
                    'last_updated' => '2025-11-18 10:00:00',
                    'source' => 'manual',
                ],
            ],
        ]);

        $client = $this->createMockClient($this->shop);

        // Act
        $result = $this->transformer->transformForPrestaShop($this->product, $client);

        // Assert: CategoryMapper should map [100, 103] → [9, 15]
        $categories = $result['product']['associations']['categories'];
        $this->assertCount(2, $categories);

        $ids = array_column($categories, 'id');
        $this->assertContains(9, $ids);
        $this->assertContains(15, $ids);
    }

    /**
     * Test: Backward compatibility - legacy format (direct mapping)
     *
     * Expected:
     * - Legacy format: {"100": 9, "103": 15}
     * - extractPrestaShopIds() handles this format
     * - Returns PrestaShop IDs: [9, 15]
     */
    public function test_build_category_associations_backward_compatibility_legacy_format(): void
    {
        // Arrange: Legacy format (ProductShopDataCast auto-converts to Option A)
        $shopData = ProductShopData::factory()->create([
            'product_id' => $this->product->id,
            'shop_id' => $this->shop->id,
            'category_mappings' => [
                '100' => 9,
                '103' => 15,
            ],
        ]);

        $client = $this->createMockClient($this->shop);

        // Act
        $result = $this->transformer->transformForPrestaShop($this->product, $client);

        // Assert: Should extract PrestaShop IDs [9, 15]
        $categories = $result['product']['associations']['categories'];
        $this->assertGreaterThanOrEqual(2, count($categories));

        $ids = array_column($categories, 'id');
        $this->assertContains(9, $ids);
        $this->assertContains(15, $ids);
    }

    /**
     * Test: No shop data - fallback to global categories
     *
     * Expected:
     * - No ProductShopData record
     * - Use product global categories
     * - Map via CategoryMapper
     */
    public function test_build_category_associations_no_shop_data_use_global_categories(): void
    {
        // Arrange: No ProductShopData record
        $client = $this->createMockClient($this->shop);

        // Act
        $result = $this->transformer->transformForPrestaShop($this->product, $client);

        // Assert: Should use global categories [100, 103] → [9, 15]
        $categories = $result['product']['associations']['categories'];
        $this->assertCount(2, $categories);

        $ids = array_column($categories, 'id');
        $this->assertContains(9, $ids);
        $this->assertContains(15, $ids);
    }

    /**
     * Test: No categories at all - use default (Home)
     *
     * Expected:
     * - Product has no categories
     * - No shop data
     * - Return default category: [['id' => 2]] (Home)
     */
    public function test_build_category_associations_no_categories_use_default(): void
    {
        // Arrange: Product without categories
        $product = Product::factory()->create([
            'sku' => 'TEST-NO-CATS',
            'name' => 'Product Without Categories',
        ]);

        $client = $this->createMockClient($this->shop);

        // Act
        $result = $this->transformer->transformForPrestaShop($product, $client);

        // Assert: Should return default category (Home = 2)
        $categories = $result['product']['associations']['categories'];
        $this->assertCount(1, $categories);
        $this->assertEquals(2, $categories[0]['id']);
    }

    /**
     * Test: Helper method - extractPrestaShopIds() with Option A format
     */
    public function test_extract_prestashop_ids_from_option_a(): void
    {
        // Arrange: Option A structure
        $categoryMappings = [
            'ui' => [
                'selected' => [100, 103, 42],
                'primary' => 100,
            ],
            'mappings' => [
                '100' => 9,
                '103' => 15,
                '42' => 800,
            ],
            'metadata' => [
                'last_updated' => '2025-11-18 10:00:00',
            ],
        ];

        // Act: Use reflection to test private method
        $reflection = new \ReflectionClass($this->transformer);
        $method = $reflection->getMethod('extractPrestaShopIds');
        $method->setAccessible(true);

        $result = $method->invoke($this->transformer, $categoryMappings);

        // Assert: Should return [9, 15, 800]
        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertContains(9, $result);
        $this->assertContains(15, $result);
        $this->assertContains(800, $result);
    }

    /**
     * Test: Helper method - extractPrestaShopIds() with legacy format
     */
    public function test_extract_prestashop_ids_from_legacy_formats(): void
    {
        // Arrange: Legacy format
        $categoryMappings = [
            '100' => 9,
            '103' => 15,
            '42' => 800,
        ];

        // Act
        $reflection = new \ReflectionClass($this->transformer);
        $method = $reflection->getMethod('extractPrestaShopIds');
        $method->setAccessible(true);

        $result = $method->invoke($this->transformer, $categoryMappings);

        // Assert: Should extract values [9, 15, 800]
        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertContains(9, $result);
        $this->assertContains(15, $result);
        $this->assertContains(800, $result);
    }

    /**
     * Test: Helper method - extractPrestaShopIds() with UI only (no mappings)
     */
    public function test_extract_prestashop_ids_ui_only_returns_empty(): void
    {
        // Arrange: UI only (requires CategoryMapper)
        $categoryMappings = [
            'ui' => [
                'selected' => [100, 103],
                'primary' => 100,
            ],
            // No mappings
        ];

        // Act
        $reflection = new \ReflectionClass($this->transformer);
        $method = $reflection->getMethod('extractPrestaShopIds');
        $method->setAccessible(true);

        $result = $method->invoke($this->transformer, $categoryMappings);

        // Assert: Should return empty (requires CategoryMapper)
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Create mock PrestaShop client
     */
    private function createMockClient(PrestaShopShop $shop): BasePrestaShopClient
    {
        $client = $this->createMock(PrestaShop8Client::class);
        $client->method('getShop')->willReturn($shop);
        $client->method('getVersion')->willReturn('8');

        return $client;
    }
}

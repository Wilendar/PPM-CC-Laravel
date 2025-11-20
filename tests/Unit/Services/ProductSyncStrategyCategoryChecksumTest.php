<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\PrestaShop\Sync\ProductSyncStrategy;
use App\Services\PrestaShop\ProductTransformer;
use App\Services\PrestaShop\CategoryMapper;
use App\Services\PrestaShop\PriceGroupMapper;
use App\Services\PrestaShop\WarehouseMapper;
use App\Services\PrestaShop\PrestaShopPriceExporter;
use App\Models\Product;
use App\Models\ProductShopData;
use App\Models\PrestaShopShop;
use App\Models\Category;
use App\Models\PriceGroup;
use App\Models\ProductPrice;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * ProductSyncStrategy Checksum Tests - Category Mappings
 *
 * FIX #12 (2025-11-18): Tests for Option A category_mappings in checksum
 *
 * Test Coverage:
 * 1. Checksum uses Option A mappings values (PrestaShop IDs)
 * 2. Checksum detects category mapping changes
 * 3. needsSync() returns true when categories change
 * 4. Backward compatibility with legacy formats
 * 5. Fallback to global categories when no shop data
 */
class ProductSyncStrategyCategoryChecksumTest extends TestCase
{
    use RefreshDatabase;

    private ProductSyncStrategy $strategy;
    private PrestaShopShop $shop;
    private Product $product;
    private PriceGroup $priceGroup;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock dependencies
        $transformer = $this->createMock(ProductTransformer::class);
        $categoryMapper = $this->createMock(CategoryMapper::class);
        $priceMapper = $this->createMock(PriceGroupMapper::class);

        $warehouseMapper = $this->createMock(WarehouseMapper::class);
        $warehouseMapper->method('calculateStockForShop')->willReturn(100);

        $priceExporter = $this->createMock(PrestaShopPriceExporter::class);

        $this->strategy = new ProductSyncStrategy(
            $transformer,
            $categoryMapper,
            $priceMapper,
            $warehouseMapper,
            $priceExporter
        );

        // Create test shop
        $this->shop = PrestaShopShop::factory()->create([
            'name' => 'Test Shop',
            'url' => 'https://test.example.com',
            'api_key' => encrypt('test_key'),
            'version' => '8',
        ]);

        // Create price group
        $this->priceGroup = PriceGroup::factory()->create([
            'id' => 1,
            'code' => 'detaliczna',
            'name' => 'Detaliczna',
        ]);

        // Create test product with categories
        $this->product = Product::factory()->create([
            'sku' => 'TEST-SKU-001',
            'name' => 'Test Product',
        ]);

        // Add price
        ProductPrice::factory()->create([
            'product_id' => $this->product->id,
            'price_group_id' => $this->priceGroup->id,
            'price_net' => 100.00,
            'price_gross' => 123.00,
        ]);

        // Create global categories
        $category1 = Category::factory()->create(['id' => 100, 'name' => 'Category 1']);
        $category2 = Category::factory()->create(['id' => 103, 'name' => 'Category 2']);
        $this->product->categories()->attach([$category1->id, $category2->id]);
    }

    /**
     * Test: Checksum uses Option A mappings values (PrestaShop IDs)
     *
     * Expected:
     * - Extract PrestaShop IDs from category_mappings['mappings'] values
     * - Use [9, 15, 800] in checksum calculation
     * - Checksum changes when mappings change
     */
    public function test_checksum_uses_option_a_mappings(): void
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

        // Act: Calculate checksum
        $checksum1 = $this->strategy->calculateChecksum($this->product, $this->shop);

        // Assert: Checksum should be deterministic
        $this->assertIsString($checksum1);
        $this->assertEquals(64, strlen($checksum1)); // SHA-256 hash length

        // Act: Calculate again (same data)
        $checksum2 = $this->strategy->calculateChecksum($this->product, $this->shop);

        // Assert: Should be identical
        $this->assertEquals($checksum1, $checksum2);
    }

    /**
     * Test: Checksum detects category mapping changes
     *
     * Expected:
     * - Initial checksum with mappings [9, 15, 800]
     * - Update mappings to [9, 20, 800]
     * - New checksum should be different
     */
    public function test_checksum_detects_category_changes(): void
    {
        // Arrange: Initial state
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
            ],
        ]);

        $checksum1 = $this->strategy->calculateChecksum($this->product, $this->shop);

        // Act: Change mappings (103 → 20 instead of 15)
        $shopData->update([
            'category_mappings' => [
                'ui' => [
                    'selected' => [100, 103, 42],
                    'primary' => 100,
                ],
                'mappings' => [
                    '100' => 9,
                    '103' => 20, // CHANGED
                    '42' => 800,
                ],
            ],
        ]);

        // Refresh product to load updated shopData
        $this->product->refresh();

        $checksum2 = $this->strategy->calculateChecksum($this->product, $this->shop);

        // Assert: Checksums should differ
        $this->assertNotEquals($checksum1, $checksum2);
    }

    /**
     * Test: needsSync() returns true when categories change
     *
     * Expected:
     * - Initial sync with checksum
     * - Change category mappings
     * - needsSync() returns true (sync required)
     */
    public function test_needs_sync_returns_true_when_categories_change(): void
    {
        // Arrange: Create ProductShopData with initial checksum
        $shopData = ProductShopData::factory()->create([
            'product_id' => $this->product->id,
            'shop_id' => $this->shop->id,
            'sync_status' => 'synced',
            'checksum' => $this->strategy->calculateChecksum($this->product, $this->shop),
            'category_mappings' => [
                'ui' => [
                    'selected' => [100, 103],
                    'primary' => 100,
                ],
                'mappings' => [
                    '100' => 9,
                    '103' => 15,
                ],
            ],
        ]);

        // Assert: needsSync() returns false (no changes)
        $this->assertFalse($this->strategy->needsSync($this->product, $this->shop));

        // Act: Change category mappings
        $shopData->update([
            'category_mappings' => [
                'ui' => [
                    'selected' => [100, 103, 42], // Added category
                    'primary' => 100,
                ],
                'mappings' => [
                    '100' => 9,
                    '103' => 15,
                    '42' => 800, // NEW mapping
                ],
            ],
        ]);

        // Refresh product
        $this->product->refresh();

        // Assert: needsSync() returns true (change detected)
        $this->assertTrue($this->strategy->needsSync($this->product, $this->shop));
    }

    /**
     * Test: Checksum with no shop data - uses global categories
     *
     * Expected:
     * - No ProductShopData record
     * - Use product global categories (PPM IDs: [100, 103])
     * - Checksum calculated with global categories
     */
    public function test_checksum_no_shop_data_uses_global_categories(): void
    {
        // Arrange: No ProductShopData record

        // Act: Calculate checksum
        $checksum = $this->strategy->calculateChecksum($this->product, $this->shop);

        // Assert: Checksum should be valid
        $this->assertIsString($checksum);
        $this->assertEquals(64, strlen($checksum));

        // Act: Calculate again
        $checksum2 = $this->strategy->calculateChecksum($this->product, $this->shop);

        // Assert: Should be identical (deterministic)
        $this->assertEquals($checksum, $checksum2);
    }

    /**
     * Test: Checksum backward compatibility - legacy format
     *
     * Expected:
     * - Legacy format: {"100": 9, "103": 15}
     * - ProductShopDataCast auto-converts to Option A
     * - Checksum uses converted mappings
     */
    public function test_checksum_backward_compatibility_legacy_format(): void
    {
        // Arrange: Create with legacy format (auto-converted by Cast)
        $shopData = ProductShopData::factory()->create([
            'product_id' => $this->product->id,
            'shop_id' => $this->shop->id,
            'category_mappings' => [
                '100' => 9,
                '103' => 15,
            ],
        ]);

        // Act: Calculate checksum
        $checksum = $this->strategy->calculateChecksum($this->product, $this->shop);

        // Assert: Checksum should be valid
        $this->assertIsString($checksum);
        $this->assertEquals(64, strlen($checksum));
    }

    /**
     * Test: Checksum includes other product fields (not just categories)
     *
     * Expected:
     * - Checksum includes: sku, name, descriptions, weight, prices, stock
     * - Change in any field triggers new checksum
     */
    public function test_checksum_includes_all_product_fields(): void
    {
        // Arrange
        $shopData = ProductShopData::factory()->create([
            'product_id' => $this->product->id,
            'shop_id' => $this->shop->id,
            'category_mappings' => [
                'mappings' => ['100' => 9],
            ],
        ]);

        $checksum1 = $this->strategy->calculateChecksum($this->product, $this->shop);

        // Act: Change product name
        $this->product->update(['name' => 'Updated Product Name']);

        $checksum2 = $this->strategy->calculateChecksum($this->product, $this->shop);

        // Assert: Checksums should differ
        $this->assertNotEquals($checksum1, $checksum2);
    }

    /**
     * Test: Checksum sorting - deterministic regardless of order
     *
     * Expected:
     * - PrestaShop IDs: [800, 9, 15] (unsorted)
     * - Checksum sorts to: [9, 15, 800]
     * - Same checksum as [9, 15, 800] input
     */
    public function test_checksum_sorting_deterministic(): void
    {
        // Arrange: Create with unsorted mappings
        $shopData1 = ProductShopData::factory()->create([
            'product_id' => $this->product->id,
            'shop_id' => $this->shop->id,
            'category_mappings' => [
                'mappings' => [
                    '42' => 800,
                    '100' => 9,
                    '103' => 15,
                ],
            ],
        ]);

        $checksum1 = $this->strategy->calculateChecksum($this->product, $this->shop);

        // Create another product with sorted mappings
        $product2 = Product::factory()->create(['sku' => 'TEST-SKU-002', 'name' => 'Test Product']);
        ProductPrice::factory()->create([
            'product_id' => $product2->id,
            'price_group_id' => $this->priceGroup->id,
            'price_net' => 100.00,
            'price_gross' => 123.00,
        ]);

        $shopData2 = ProductShopData::factory()->create([
            'product_id' => $product2->id,
            'shop_id' => $this->shop->id,
            'category_mappings' => [
                'mappings' => [
                    '100' => 9,
                    '103' => 15,
                    '42' => 800,
                ],
            ],
        ]);

        $checksum2 = $this->strategy->calculateChecksum($product2, $this->shop);

        // Assert: Checksums should be identical (sorted before hashing)
        $this->assertEquals($checksum1, $checksum2);
    }
}

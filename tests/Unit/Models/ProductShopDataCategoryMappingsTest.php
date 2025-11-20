<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\ProductShopData;
use App\Models\Product;
use App\Models\PrestaShopShop;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * ProductShopData - Category Mappings Tests
 *
 * Tests for category_mappings Option A architecture refactoring
 *
 * Architecture: CATEGORY_MAPPINGS_ARCHITECTURE.md v2.0 (2025-11-18)
 *
 * Tests:
 * - CategoryMappingsCast deserialization (database → model)
 * - CategoryMappingsCast serialization (model → database)
 * - Backward compatibility (UI format, PrestaShop format)
 * - Helper methods (getCategoryMappingsUi, getCategoryMappingsList, etc.)
 *
 * @package Tests\Unit\Models
 * @version 2.0
 * @since 2025-11-18
 */
class ProductShopDataCategoryMappingsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test CategoryMappingsCast deserializes correctly
     *
     * @return void
     */
    public function test_category_mappings_cast_deserializes_correctly(): void
    {
        // Create product and shop
        $product = Product::factory()->create();
        $shop = PrestaShopShop::factory()->create();

        // Create ProductShopData with canonical Option A format
        $canonicalFormat = [
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
                'last_updated' => '2025-11-18T10:30:00Z',
                'source' => 'manual',
            ],
        ];

        $productShopData = ProductShopData::create([
            'product_id' => $product->id,
            'shop_id' => $shop->id,
            'category_mappings' => $canonicalFormat,
        ]);

        // Retrieve from database
        $retrieved = ProductShopData::find($productShopData->id);

        // Assert deserialization
        $this->assertIsArray($retrieved->category_mappings);
        $this->assertArrayHasKey('ui', $retrieved->category_mappings);
        $this->assertArrayHasKey('mappings', $retrieved->category_mappings);
        $this->assertArrayHasKey('metadata', $retrieved->category_mappings);

        // Assert UI section
        $this->assertEquals([100, 103, 42], $retrieved->category_mappings['ui']['selected']);
        $this->assertEquals(100, $retrieved->category_mappings['ui']['primary']);

        // Assert mappings section
        $this->assertEquals(9, $retrieved->category_mappings['mappings']['100']);
        $this->assertEquals(15, $retrieved->category_mappings['mappings']['103']);
        $this->assertEquals(800, $retrieved->category_mappings['mappings']['42']);

        // Assert metadata section
        $this->assertEquals('manual', $retrieved->category_mappings['metadata']['source']);
    }

    /**
     * Test CategoryMappingsCast serializes correctly
     *
     * @return void
     */
    public function test_category_mappings_cast_serializes_correctly(): void
    {
        // Create product and shop
        $product = Product::factory()->create();
        $shop = PrestaShopShop::factory()->create();

        // Create ProductShopData
        $productShopData = ProductShopData::create([
            'product_id' => $product->id,
            'shop_id' => $shop->id,
        ]);

        // Set category_mappings (triggers serialization)
        $canonicalFormat = [
            'ui' => [
                'selected' => [200, 201],
                'primary' => 200,
            ],
            'mappings' => [
                '200' => 10,
                '201' => 11,
            ],
            'metadata' => [
                'last_updated' => now()->toIso8601String(),
                'source' => 'manual',
            ],
        ];

        $productShopData->category_mappings = $canonicalFormat;
        $productShopData->save();

        // Retrieve raw JSON from database
        $rawJson = \DB::table('product_shop_data')
            ->where('id', $productShopData->id)
            ->value('category_mappings');

        // Decode and assert structure
        $decoded = json_decode($rawJson, true);

        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('ui', $decoded);
        $this->assertArrayHasKey('mappings', $decoded);
        $this->assertArrayHasKey('metadata', $decoded);

        // Assert correct serialization
        $this->assertEquals([200, 201], $decoded['ui']['selected']);
        $this->assertEquals(200, $decoded['ui']['primary']);
        $this->assertEquals(10, $decoded['mappings']['200']);
        $this->assertEquals(11, $decoded['mappings']['201']);
    }

    /**
     * Test backward compatibility with UI format
     *
     * @return void
     */
    public function test_category_mappings_backward_compatibility_ui_format(): void
    {
        // Create product and shop
        $product = Product::factory()->create();
        $shop = PrestaShopShop::factory()->create();

        // Insert OLD UI format directly into database
        $oldUiFormat = json_encode([
            'selected' => [300, 301],
            'primary' => 300,
        ]);

        \DB::table('product_shop_data')->insert([
            'product_id' => $product->id,
            'shop_id' => $shop->id,
            'category_mappings' => $oldUiFormat,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Retrieve using Eloquent (triggers cast deserialization)
        $productShopData = ProductShopData::where('product_id', $product->id)
            ->where('shop_id', $shop->id)
            ->first();

        // Assert conversion to Option A
        $this->assertIsArray($productShopData->category_mappings);
        $this->assertArrayHasKey('ui', $productShopData->category_mappings);
        $this->assertArrayHasKey('mappings', $productShopData->category_mappings);
        $this->assertArrayHasKey('metadata', $productShopData->category_mappings);

        // Assert UI section preserved
        $this->assertEquals([300, 301], $productShopData->category_mappings['ui']['selected']);
        $this->assertEquals(300, $productShopData->category_mappings['ui']['primary']);

        // Assert mappings section created (with placeholders)
        $this->assertArrayHasKey('300', $productShopData->category_mappings['mappings']);
        $this->assertArrayHasKey('301', $productShopData->category_mappings['mappings']);

        // Assert metadata indicates migration
        $this->assertEquals('migration_ui_format', $productShopData->category_mappings['metadata']['source']);
    }

    /**
     * Test backward compatibility with PrestaShop format
     *
     * @return void
     */
    public function test_category_mappings_backward_compatibility_prestashop_format(): void
    {
        // Create product and shop
        $product = Product::factory()->create();
        $shop = PrestaShopShop::factory()->create();

        // Insert OLD PrestaShop format directly into database
        $oldPsFormat = json_encode([
            '9' => 9,
            '15' => 15,
            '800' => 800,
        ]);

        \DB::table('product_shop_data')->insert([
            'product_id' => $product->id,
            'shop_id' => $shop->id,
            'category_mappings' => $oldPsFormat,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Retrieve using Eloquent (triggers cast deserialization)
        $productShopData = ProductShopData::where('product_id', $product->id)
            ->where('shop_id', $shop->id)
            ->first();

        // Assert conversion to Option A
        $this->assertIsArray($productShopData->category_mappings);
        $this->assertArrayHasKey('ui', $productShopData->category_mappings);
        $this->assertArrayHasKey('mappings', $productShopData->category_mappings);
        $this->assertArrayHasKey('metadata', $productShopData->category_mappings);

        // Assert UI section is empty (cannot reverse-map without CategoryMapper)
        $this->assertEmpty($productShopData->category_mappings['ui']['selected']);
        $this->assertNull($productShopData->category_mappings['ui']['primary']);

        // Assert mappings section has PrestaShop IDs (with placeholder keys)
        $this->assertArrayHasKey('_ps_9', $productShopData->category_mappings['mappings']);
        $this->assertArrayHasKey('_ps_15', $productShopData->category_mappings['mappings']);
        $this->assertArrayHasKey('_ps_800', $productShopData->category_mappings['mappings']);

        // Assert metadata indicates migration
        $this->assertEquals('migration_prestashop_format', $productShopData->category_mappings['metadata']['source']);
    }

    /**
     * Test helper methods
     *
     * @return void
     */
    public function test_category_mappings_helper_methods(): void
    {
        // Create product and shop
        $product = Product::factory()->create();
        $shop = PrestaShopShop::factory()->create();

        // Create ProductShopData with canonical format
        $productShopData = ProductShopData::create([
            'product_id' => $product->id,
            'shop_id' => $shop->id,
            'category_mappings' => [
                'ui' => [
                    'selected' => [400, 401, 402],
                    'primary' => 400,
                ],
                'mappings' => [
                    '400' => 20,
                    '401' => 21,
                    '402' => 0, // Unmapped
                ],
                'metadata' => [
                    'last_updated' => '2025-11-18T12:00:00Z',
                    'source' => 'manual',
                ],
            ],
        ]);

        // Test getCategoryMappingsUi()
        $ui = $productShopData->getCategoryMappingsUi();
        $this->assertEquals([400, 401, 402], $ui['selected']);
        $this->assertEquals(400, $ui['primary']);

        // Test getCategoryMappingsList() - should filter out placeholder (0)
        $list = $productShopData->getCategoryMappingsList();
        $this->assertEquals([20, 21], $list);
        $this->assertCount(2, $list);

        // Test hasCategoryMappings()
        $this->assertTrue($productShopData->hasCategoryMappings());

        // Test getPrimaryCategoryId()
        $this->assertEquals(20, $productShopData->getPrimaryCategoryId());

        // Test getUnmappedCategoriesCount()
        $this->assertEquals(1, $productShopData->getUnmappedCategoriesCount());

        // Test getCategoryMappingsSource()
        $this->assertEquals('manual', $productShopData->getCategoryMappingsSource());

        // Test getCategoryMappingsLastUpdated()
        $lastUpdated = $productShopData->getCategoryMappingsLastUpdated();
        $this->assertInstanceOf(\Carbon\Carbon::class, $lastUpdated);
    }

    /**
     * Test empty category_mappings
     *
     * @return void
     */
    public function test_empty_category_mappings(): void
    {
        // Create product and shop
        $product = Product::factory()->create();
        $shop = PrestaShopShop::factory()->create();

        // Create ProductShopData without category_mappings
        $productShopData = ProductShopData::create([
            'product_id' => $product->id,
            'shop_id' => $shop->id,
        ]);

        // Assert empty structure
        $this->assertIsArray($productShopData->category_mappings);
        $this->assertEmpty($productShopData->category_mappings['ui']['selected']);
        $this->assertNull($productShopData->category_mappings['ui']['primary']);
        $this->assertEmpty($productShopData->category_mappings['mappings']);

        // Test helper methods with empty data
        $this->assertFalse($productShopData->hasCategoryMappings());
        $this->assertEmpty($productShopData->getCategoryMappingsList());
        $this->assertNull($productShopData->getPrimaryCategoryId());
        $this->assertEquals(0, $productShopData->getUnmappedCategoriesCount());
    }

    /**
     * Test NULL category_mappings handling
     *
     * @return void
     */
    public function test_null_category_mappings(): void
    {
        // Create product and shop
        $product = Product::factory()->create();
        $shop = PrestaShopShop::factory()->create();

        // Insert with NULL category_mappings
        \DB::table('product_shop_data')->insert([
            'product_id' => $product->id,
            'shop_id' => $shop->id,
            'category_mappings' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Retrieve using Eloquent
        $productShopData = ProductShopData::where('product_id', $product->id)
            ->where('shop_id', $shop->id)
            ->first();

        // Assert converts NULL to empty structure
        $this->assertIsArray($productShopData->category_mappings);
        $this->assertArrayHasKey('ui', $productShopData->category_mappings);
        $this->assertArrayHasKey('mappings', $productShopData->category_mappings);
        $this->assertArrayHasKey('metadata', $productShopData->category_mappings);
        $this->assertEquals('empty', $productShopData->category_mappings['metadata']['source']);
    }
}

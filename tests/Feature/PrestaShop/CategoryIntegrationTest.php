<?php

namespace Tests\Feature\PrestaShop;

use Tests\TestCase;
use App\Services\PrestaShop\PrestaShopCategoryService;
use App\Services\PrestaShop\PrestaShopClientFactory;
use App\Services\PrestaShop\CategoryMapper;
use App\Models\PrestaShopShop;
use App\Models\ShopMapping;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * PrestaShop Category Integration Tests
 *
 * ETAP_07b FAZA 1 - Phase 4: Integration Testing
 *
 * Tests with REAL PrestaShop API calls:
 * 1. Fetch categories from Shop 1 (Pitbike.pl)
 * 2. Fetch categories from Shop 5 (Test KAYO)
 * 3. Verify category tree structure
 * 4. Test cache refresh functionality
 * 5. Test category mapping status
 *
 * @package Tests\Feature\PrestaShop
 */
class CategoryIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected PrestaShopCategoryService $categoryService;
    protected CategoryMapper $categoryMapper;
    protected PrestaShopClientFactory $clientFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->categoryService = app(PrestaShopCategoryService::class);
        $this->categoryMapper = app(CategoryMapper::class);
        $this->clientFactory = app(PrestaShopClientFactory::class);
    }

    /**
     * Test 1: Fetch categories from Shop 1 (Pitbike.pl)
     *
     * @group integration
     * @group prestashop-api
     */
    public function test_fetch_categories_from_shop_1_pitbike(): void
    {
        $shop = PrestaShopShop::where('id', 1)->first();

        if (!$shop || !$shop->sync_enabled) {
            $this->markTestSkipped('Shop 1 (Pitbike.pl) not configured or sync disabled');
        }

        // Clear cache to force fresh API call
        Cache::forget("prestashop_categories_{$shop->id}");

        try {
            // Fetch categories from API
            $categories = $this->categoryService->fetchCategoriesFromShop($shop);

            // Assertions
            $this->assertIsArray($categories);
            $this->assertNotEmpty($categories, 'Should fetch at least one category');

            // Verify category structure
            foreach ($categories as $category) {
                $this->assertArrayHasKey('id', $category);
                $this->assertArrayHasKey('name', $category);
                $this->assertArrayHasKey('id_parent', $category);
                $this->assertArrayHasKey('position', $category);
                $this->assertArrayHasKey('active', $category);

                // Verify types
                $this->assertIsInt($category['id']);
                $this->assertIsString($category['name']);
                $this->assertNotEmpty($category['name'], "Category {$category['id']} should have non-empty name");
            }

            Log::info('Integration test: Fetched categories from Pitbike.pl', [
                'shop_id' => $shop->id,
                'category_count' => count($categories),
                'sample_categories' => array_slice($categories, 0, 3),
            ]);

        } catch (\Exception $e) {
            $this->fail("Failed to fetch categories from Pitbike.pl: {$e->getMessage()}");
        }
    }

    /**
     * Test 2: Fetch categories from Shop 5 (Test KAYO)
     *
     * @group integration
     * @group prestashop-api
     */
    public function test_fetch_categories_from_shop_5_test_kayo(): void
    {
        $shop = PrestaShopShop::where('id', 5)->first();

        if (!$shop || !$shop->sync_enabled) {
            $this->markTestSkipped('Shop 5 (Test KAYO) not configured or sync disabled');
        }

        // Clear cache
        Cache::forget("prestashop_categories_{$shop->id}");

        try {
            // Fetch categories
            $categories = $this->categoryService->fetchCategoriesFromShop($shop);

            // Assertions
            $this->assertIsArray($categories);
            $this->assertNotEmpty($categories);

            // Log for manual verification
            Log::info('Integration test: Fetched categories from Test KAYO', [
                'shop_id' => $shop->id,
                'category_count' => count($categories),
                'sample_names' => array_column(array_slice($categories, 0, 5), 'name'),
            ]);

        } catch (\Exception $e) {
            $this->fail("Failed to fetch categories from Test KAYO: {$e->getMessage()}");
        }
    }

    /**
     * Test 3: Verify category tree structure matches PrestaShop hierarchy
     *
     * @group integration
     * @group prestashop-api
     */
    public function test_category_tree_structure_matches_prestashop(): void
    {
        $shop = PrestaShopShop::where('sync_enabled', true)->first();

        if (!$shop) {
            $this->markTestSkipped('No shops with sync_enabled=true found');
        }

        // Clear cache
        Cache::forget("prestashop_categories_{$shop->id}");

        try {
            // Get category tree
            $tree = $this->categoryService->getCachedCategoryTree($shop);

            // Assertions
            $this->assertIsArray($tree);

            // Verify tree structure recursively
            $this->verifyTreeStructure($tree, 1);

            // Verify cache was set
            $cachedTree = Cache::get("prestashop_categories_{$shop->id}");
            $this->assertNotNull($cachedTree, 'Category tree should be cached');
            $this->assertEquals($tree, $cachedTree, 'Cached tree should match returned tree');

            Log::info('Integration test: Category tree structure verified', [
                'shop_id' => $shop->id,
                'root_count' => count($tree),
            ]);

        } catch (\Exception $e) {
            $this->fail("Failed to verify category tree structure: {$e->getMessage()}");
        }
    }

    /**
     * Test 4: Refresh button clears cache and fetches fresh data
     *
     * @group integration
     * @group prestashop-api
     */
    public function test_refresh_button_clears_cache(): void
    {
        $shop = PrestaShopShop::where('sync_enabled', true)->first();

        if (!$shop) {
            $this->markTestSkipped('No shops with sync_enabled=true found');
        }

        try {
            // First call - populates cache
            $tree1 = $this->categoryService->getCachedCategoryTree($shop);
            $this->assertNotNull(Cache::get("prestashop_categories_{$shop->id}"));

            // Clear cache (simulates "Odśwież kategorie" button)
            $this->categoryService->clearCache($shop);
            $this->assertNull(Cache::get("prestashop_categories_{$shop->id}"));

            // Second call - fetches fresh data
            $tree2 = $this->categoryService->getCachedCategoryTree($shop);
            $this->assertNotNull(Cache::get("prestashop_categories_{$shop->id}"));

            // Trees should be identical (assuming no changes in PrestaShop)
            $this->assertEquals($tree1, $tree2);

            Log::info('Integration test: Cache refresh verified', [
                'shop_id' => $shop->id,
            ]);

        } catch (\Exception $e) {
            $this->fail("Failed to test cache refresh: {$e->getMessage()}");
        }
    }

    /**
     * Test 5: Category mapping status badges
     *
     * @group integration
     */
    public function test_category_mapping_status(): void
    {
        $shop = PrestaShopShop::where('sync_enabled', true)->first();

        if (!$shop) {
            $this->markTestSkipped('No shops with sync_enabled=true found');
        }

        // Create test mapping
        ShopMapping::createOrUpdateMapping(
            shopId: $shop->id,
            type: ShopMapping::TYPE_CATEGORY,
            ppmValue: '1',
            prestashopId: 2,
            prestashopValue: 'Home'
        );

        // Test mapped category
        $status1 = $this->categoryMapper->getMappingStatus(1, $shop->id);
        $this->assertEquals('mapped', $status1);

        // Test unmapped category
        $status2 = $this->categoryMapper->getMappingStatus(999, $shop->id);
        $this->assertEquals('unmapped', $status2);

        Log::info('Integration test: Category mapping status verified', [
            'shop_id' => $shop->id,
            'mapped_status' => $status1,
            'unmapped_status' => $status2,
        ]);
    }

    /**
     * Helper: Verify tree structure recursively
     *
     * @param array $nodes Tree nodes
     * @param int $expectedLevel Expected depth level
     */
    protected function verifyTreeStructure(array $nodes, int $expectedLevel): void
    {
        foreach ($nodes as $node) {
            // Required fields
            $this->assertArrayHasKey('id', $node);
            $this->assertArrayHasKey('name', $node);
            $this->assertArrayHasKey('level', $node);
            $this->assertArrayHasKey('children', $node);

            // Verify level
            $this->assertEquals($expectedLevel, $node['level'], "Category {$node['id']} should have level {$expectedLevel}");

            // Verify children recursively
            if (!empty($node['children'])) {
                $this->verifyTreeStructure($node['children'], $expectedLevel + 1);
            }
        }
    }
}

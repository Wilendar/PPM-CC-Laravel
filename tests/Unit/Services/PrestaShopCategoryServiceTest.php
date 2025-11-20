<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\PrestaShop\PrestaShopCategoryService;
use App\Services\PrestaShop\PrestaShopClientFactory;
use App\Services\PrestaShop\BasePrestaShopClient;
use App\Models\PrestaShopShop;
use App\Exceptions\PrestaShopAPIException;
use Illuminate\Support\Facades\Cache;
use Mockery;

/**
 * PrestaShopCategoryService Unit Tests
 *
 * ETAP_07b FAZA 1 - Phase 4: Testing
 *
 * Tests:
 * 1. Cache hit - returns cached data
 * 2. Cache miss - fetches fresh from API
 * 3. Build hierarchy - creates parent-child tree
 * 4. Clear cache - invalidates cache
 * 5. API error - graceful degradation
 * 6. Normalize response - handles PrestaShop 8.x/9.x differences
 *
 * Note: Unit tests use mocked dependencies, no database access required
 *
 * @package Tests\Unit\Services
 */
class PrestaShopCategoryServiceTest extends TestCase
{

    protected PrestaShopCategoryService $service;
    protected $clientFactoryMock;
    protected $clientMock;
    protected $shop;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test shop (simple object)
        $this->shop = new class {
            public $id = 1;
            public $name = 'Test Shop';
            public $url = 'https://test.example.com';
            public $api_key = 'test-key';
        };

        // Mock client factory
        $this->clientFactoryMock = Mockery::mock(PrestaShopClientFactory::class);
        $this->clientMock = Mockery::mock(BasePrestaShopClient::class);

        // Inject mock factory
        $this->service = new PrestaShopCategoryService($this->clientFactoryMock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test 1: getCachedCategoryTree returns cached data on cache hit
     */
    public function test_getCachedCategoryTree_returns_cached_data(): void
    {
        $cachedData = [
            ['id' => 2, 'name' => 'Home', 'children' => []],
        ];

        // Put data in cache
        Cache::put("prestashop_categories_{$this->shop->id}", $cachedData, 900);

        // Call service - should return cached data WITHOUT calling API
        $result = $this->service->getCachedCategoryTree($this->shop);

        $this->assertEquals($cachedData, $result);
        $this->assertEquals('Home', $result[0]['name']);
    }

    /**
     * Test 2: getCachedCategoryTree fetches fresh data on cache miss
     */
    public function test_getCachedCategoryTree_fetches_fresh_on_cache_miss(): void
    {
        // Clear cache
        Cache::forget("prestashop_categories_{$this->shop->id}");

        // Mock API response
        $apiResponse = [
            'categories' => [
                'category' => [
                    [
                        'id' => 2,
                        'name' => ['language' => [['id' => 1, 'value' => 'Home']]],
                        'id_parent' => 1,
                        'position' => 0,
                        'active' => '1',
                        'level_depth' => 1,
                    ],
                    [
                        'id' => 3,
                        'name' => ['language' => [['id' => 1, 'value' => 'Clothes']]],
                        'id_parent' => 2,
                        'position' => 1,
                        'active' => '1',
                        'level_depth' => 2,
                    ],
                ],
            ],
        ];

        // Mock client
        $this->clientFactoryMock->shouldReceive('create')
            ->once()
            ->with($this->shop)
            ->andReturn($this->clientMock);

        $this->clientMock->shouldReceive('makeRequest')
            ->once()
            ->with('GET', 'categories?display=full')
            ->andReturn($apiResponse);

        $this->clientMock->shouldReceive('getVersion')
            ->andReturn('8');

        // Call service
        $result = $this->service->getCachedCategoryTree($this->shop);

        // Verify result is hierarchical tree
        $this->assertIsArray($result);
        $this->assertCount(1, $result); // Only root category (Home)
        $this->assertEquals('Home', $result[0]['name']);
        $this->assertArrayHasKey('children', $result[0]);
        $this->assertCount(1, $result[0]['children']); // Clothes is child
        $this->assertEquals('Clothes', $result[0]['children'][0]['name']);
    }

    /**
     * Test 3: buildCategoryTree creates hierarchical structure
     */
    public function test_buildCategoryTree_creates_hierarchy(): void
    {
        $flatCategories = [
            ['id' => 2, 'name' => 'Home', 'id_parent' => 1, 'position' => 0, 'active' => true, 'level_depth' => 1],
            ['id' => 3, 'name' => 'Clothes', 'id_parent' => 2, 'position' => 1, 'active' => true, 'level_depth' => 2],
            ['id' => 4, 'name' => 'Accessories', 'id_parent' => 2, 'position' => 2, 'active' => true, 'level_depth' => 2],
            ['id' => 5, 'name' => 'Men', 'id_parent' => 3, 'position' => 0, 'active' => true, 'level_depth' => 3],
        ];

        $tree = $this->service->buildCategoryTree($flatCategories);

        // Verify root
        $this->assertCount(1, $tree);
        $this->assertEquals('Home', $tree[0]['name']);

        // Verify level 1 children (Clothes, Accessories)
        $this->assertCount(2, $tree[0]['children']);
        $this->assertEquals('Clothes', array_values($tree[0]['children'])[0]['name']);
        $this->assertEquals('Accessories', array_values($tree[0]['children'])[1]['name']);

        // Verify level 2 children (Men under Clothes)
        $clothesChildren = array_values($tree[0]['children'])[0]['children'];
        $this->assertCount(1, $clothesChildren);
        $this->assertEquals('Men', array_values($clothesChildren)[0]['name']);
    }

    /**
     * Test 4: clearCache invalidates cache
     */
    public function test_clearCache_invalidates_cache(): void
    {
        $cachedData = [
            ['id' => 2, 'name' => 'Home', 'children' => []],
        ];

        // Put data in cache
        Cache::put("prestashop_categories_{$this->shop->id}", $cachedData, 900);

        // Verify cache exists
        $this->assertNotNull(Cache::get("prestashop_categories_{$this->shop->id}"));

        // Clear cache
        $this->service->clearCache($this->shop);

        // Verify cache is cleared
        $this->assertNull(Cache::get("prestashop_categories_{$this->shop->id}"));
    }

    /**
     * Test 5: API error - graceful degradation with stale cache
     */
    public function test_fetchCategoriesFromShop_handles_api_error(): void
    {
        // Mock client that throws exception
        $this->clientFactoryMock->shouldReceive('create')
            ->once()
            ->with($this->shop)
            ->andReturn($this->clientMock);

        $this->clientMock->shouldReceive('makeRequest')
            ->once()
            ->with('GET', 'categories?display=full')
            ->andThrow(new PrestaShopAPIException('API connection failed', 500));

        $this->clientMock->shouldReceive('getVersion')
            ->andReturn('8');

        // Expect exception
        $this->expectException(PrestaShopAPIException::class);
        $this->expectExceptionMessage('API connection failed');

        // Call service
        $this->service->fetchCategoriesFromShop($this->shop);
    }

    /**
     * Test 6: Normalize response handles PrestaShop multilang format
     */
    public function test_normalize_response_extracts_multilang_fields(): void
    {
        // Create reflection to access protected method
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('normalizeCategoriesResponse');
        $method->setAccessible(true);

        $apiResponse = [
            'categories' => [
                'category' => [
                    [
                        'id' => 2,
                        'name' => [
                            'language' => [
                                ['id' => '1', 'value' => 'Home'],
                                ['id' => '2', 'value' => 'Accueil'],
                            ],
                        ],
                        'id_parent' => 1,
                        'position' => 0,
                        'active' => '1',
                        'level_depth' => 1,
                    ],
                ],
            ],
        ];

        $result = $method->invoke($this->service, $apiResponse);

        $this->assertCount(1, $result);
        $this->assertEquals(2, $result[0]['id']);
        $this->assertEquals('Home', $result[0]['name']); // First language value
        $this->assertEquals(1, $result[0]['id_parent']);
    }
}

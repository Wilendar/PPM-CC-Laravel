<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\CategoryMappingsConverter;
use App\Services\CategoryMappingsValidator;
use App\Services\PrestaShop\CategoryMapper;
use App\Models\PrestaShopShop;
use App\Models\ShopMapping;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

/**
 * CategoryMappingsConverter - Unit Tests
 *
 * Tests for bidirectional conversion between UI format and canonical Option A format
 *
 * Architecture: CATEGORY_MAPPINGS_ARCHITECTURE.md v2.0 (2025-11-18)
 *
 * Tests:
 * - fromUiFormat() - UI → Canonical (with CategoryMapper lookup)
 * - fromPrestaShopFormat() - PrestaShop IDs → Canonical (with reverse lookup)
 * - toUiFormat() - Canonical → UI (extraction)
 * - toPrestaShopIdsList() - Canonical → PrestaShop IDs list
 * - Helper methods (getPrimaryPrestaShopId, hasValidMappings, etc.)
 * - Edge cases (null, empty, malformed data)
 *
 * @package Tests\Unit\Services
 * @version 2.0
 * @since 2025-11-18
 */
class CategoryMappingsConverterTest extends TestCase
{
    use RefreshDatabase;

    private CategoryMappingsConverter $converter;
    private CategoryMapper $categoryMapper;
    private PrestaShopShop $shop;

    /**
     * Set up test environment
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->shop = PrestaShopShop::factory()->create();

        // Create real CategoryMapper instance
        $this->categoryMapper = app(CategoryMapper::class);

        // Create real CategoryMappingsConverter instance
        $this->converter = new CategoryMappingsConverter(
            $this->categoryMapper,
            app(CategoryMappingsValidator::class)
        );
    }

    /**
     * Test fromUiFormat() converts UI to canonical
     *
     * @return void
     */
    public function test_from_ui_format_converts_to_canonical(): void
    {
        // Create mappings in shop_mappings table
        ShopMapping::createOrUpdateMapping(
            shopId: $this->shop->id,
            type: ShopMapping::TYPE_CATEGORY,
            ppmValue: '100',
            prestashopId: 9
        );

        ShopMapping::createOrUpdateMapping(
            shopId: $this->shop->id,
            type: ShopMapping::TYPE_CATEGORY,
            ppmValue: '103',
            prestashopId: 15
        );

        // UI format
        $uiFormat = [
            'selected' => [100, 103],
            'primary' => 100,
        ];

        // Convert
        $canonical = $this->converter->fromUiFormat($uiFormat, $this->shop);

        // Assert canonical structure
        $this->assertArrayHasKey('ui', $canonical);
        $this->assertArrayHasKey('mappings', $canonical);
        $this->assertArrayHasKey('metadata', $canonical);

        // Assert UI section
        $this->assertEquals([100, 103], $canonical['ui']['selected']);
        $this->assertEquals(100, $canonical['ui']['primary']);

        // Assert mappings section (PrestaShop IDs)
        $this->assertEquals(9, $canonical['mappings']['100']);
        $this->assertEquals(15, $canonical['mappings']['103']);

        // Assert metadata
        $this->assertEquals('manual', $canonical['metadata']['source']);
    }

    /**
     * Test fromUiFormat() skips unmapped categories
     *
     * @return void
     */
    public function test_from_ui_format_skips_unmapped_categories(): void
    {
        // Create mapping for only ONE category
        ShopMapping::createOrUpdateMapping(
            shopId: $this->shop->id,
            type: ShopMapping::TYPE_CATEGORY,
            ppmValue: '100',
            prestashopId: 9
        );

        // UI format with TWO categories (one unmapped)
        $uiFormat = [
            'selected' => [100, 999], // 999 is NOT mapped
            'primary' => 100,
        ];

        // Convert
        $canonical = $this->converter->fromUiFormat($uiFormat, $this->shop);

        // Assert only mapped category is in mappings
        $this->assertArrayHasKey('100', $canonical['mappings']);
        $this->assertArrayNotHasKey('999', $canonical['mappings']);
    }

    /**
     * Test fromPrestaShopFormat() converts PrestaShop IDs to canonical
     *
     * @return void
     */
    public function test_from_prestashop_format_converts_to_canonical(): void
    {
        // Create reverse mappings (PrestaShop → PPM)
        ShopMapping::createOrUpdateMapping(
            shopId: $this->shop->id,
            type: ShopMapping::TYPE_CATEGORY,
            ppmValue: '200',
            prestashopId: 10
        );

        ShopMapping::createOrUpdateMapping(
            shopId: $this->shop->id,
            type: ShopMapping::TYPE_CATEGORY,
            ppmValue: '201',
            prestashopId: 11
        );

        // PrestaShop format (array of IDs)
        $psFormat = [10, 11];

        // Convert
        $canonical = $this->converter->fromPrestaShopFormat($psFormat, $this->shop);

        // Assert canonical structure
        $this->assertArrayHasKey('ui', $canonical);
        $this->assertArrayHasKey('mappings', $canonical);
        $this->assertArrayHasKey('metadata', $canonical);

        // Assert UI section (reverse-mapped PPM IDs)
        $this->assertContains(200, $canonical['ui']['selected']);
        $this->assertContains(201, $canonical['ui']['selected']);
        $this->assertEquals(200, $canonical['ui']['primary']); // First selected

        // Assert mappings section
        $this->assertEquals(10, $canonical['mappings']['200']);
        $this->assertEquals(11, $canonical['mappings']['201']);

        // Assert metadata
        $this->assertEquals('pull', $canonical['metadata']['source']);
    }

    /**
     * Test toUiFormat() extracts UI section
     *
     * @return void
     */
    public function test_to_ui_format_extracts_ui_section(): void
    {
        // Canonical format
        $canonical = [
            'ui' => [
                'selected' => [300, 301, 302],
                'primary' => 300,
            ],
            'mappings' => [
                '300' => 20,
                '301' => 21,
                '302' => 22,
            ],
            'metadata' => [
                'last_updated' => now()->toIso8601String(),
                'source' => 'manual',
            ],
        ];

        // Convert
        $uiFormat = $this->converter->toUiFormat($canonical);

        // Assert UI format
        $this->assertArrayHasKey('selected', $uiFormat);
        $this->assertArrayHasKey('primary', $uiFormat);
        $this->assertEquals([300, 301, 302], $uiFormat['selected']);
        $this->assertEquals(300, $uiFormat['primary']);

        // Assert no extra keys
        $this->assertArrayNotHasKey('mappings', $uiFormat);
        $this->assertArrayNotHasKey('metadata', $uiFormat);
    }

    /**
     * Test toPrestaShopIdsList() extracts PrestaShop IDs
     *
     * @return void
     */
    public function test_to_prestashop_ids_list_extracts_ids(): void
    {
        // Canonical format
        $canonical = [
            'ui' => [
                'selected' => [400, 401, 402],
                'primary' => 400,
            ],
            'mappings' => [
                '400' => 30,
                '401' => 31,
                '402' => 32,
            ],
            'metadata' => [
                'last_updated' => now()->toIso8601String(),
                'source' => 'manual',
            ],
        ];

        // Convert
        $psIds = $this->converter->toPrestaShopIdsList($canonical);

        // Assert PrestaShop IDs list
        $this->assertIsArray($psIds);
        $this->assertCount(3, $psIds);
        $this->assertContains(30, $psIds);
        $this->assertContains(31, $psIds);
        $this->assertContains(32, $psIds);
    }

    /**
     * Test toPrestaShopIdsList() filters out placeholders
     *
     * @return void
     */
    public function test_to_prestashop_ids_list_filters_placeholders(): void
    {
        // Canonical format with placeholder (0 = not mapped)
        $canonical = [
            'ui' => [
                'selected' => [500, 501, 502],
                'primary' => 500,
            ],
            'mappings' => [
                '500' => 40,
                '501' => 0,  // Placeholder (not mapped)
                '502' => 42,
            ],
            'metadata' => [
                'last_updated' => now()->toIso8601String(),
                'source' => 'manual',
            ],
        ];

        // Convert
        $psIds = $this->converter->toPrestaShopIdsList($canonical);

        // Assert only valid IDs (no placeholders)
        $this->assertIsArray($psIds);
        $this->assertCount(2, $psIds);
        $this->assertContains(40, $psIds);
        $this->assertContains(42, $psIds);
        $this->assertNotContains(0, $psIds);
    }

    /**
     * Test getPrimaryPrestaShopId() resolves primary
     *
     * @return void
     */
    public function test_get_primary_prestashop_id_resolves_primary(): void
    {
        // Canonical format
        $canonical = [
            'ui' => [
                'selected' => [600, 601],
                'primary' => 600,
            ],
            'mappings' => [
                '600' => 50,
                '601' => 51,
            ],
            'metadata' => [
                'last_updated' => now()->toIso8601String(),
                'source' => 'manual',
            ],
        ];

        // Get primary
        $primaryId = $this->converter->getPrimaryPrestaShopId($canonical);

        // Assert primary PrestaShop ID
        $this->assertEquals(50, $primaryId);
    }

    /**
     * Test getPrimaryPrestaShopId() returns null when primary is unmapped
     *
     * @return void
     */
    public function test_get_primary_prestashop_id_returns_null_when_unmapped(): void
    {
        // Canonical format with unmapped primary
        $canonical = [
            'ui' => [
                'selected' => [700, 701],
                'primary' => 700,
            ],
            'mappings' => [
                '700' => 0,  // Unmapped
                '701' => 61,
            ],
            'metadata' => [
                'last_updated' => now()->toIso8601String(),
                'source' => 'manual',
            ],
        ];

        // Get primary
        $primaryId = $this->converter->getPrimaryPrestaShopId($canonical);

        // Assert null (unmapped)
        $this->assertNull($primaryId);
    }

    /**
     * Test hasValidMappings() detects valid mappings
     *
     * @return void
     */
    public function test_has_valid_mappings_detects_valid_mappings(): void
    {
        // Canonical with valid mappings
        $canonicalValid = [
            'ui' => [
                'selected' => [800, 801],
                'primary' => 800,
            ],
            'mappings' => [
                '800' => 70,
                '801' => 71,
            ],
            'metadata' => [
                'last_updated' => now()->toIso8601String(),
                'source' => 'manual',
            ],
        ];

        // Canonical with NO valid mappings (all placeholders)
        $canonicalInvalid = [
            'ui' => [
                'selected' => [900, 901],
                'primary' => 900,
            ],
            'mappings' => [
                '900' => 0,
                '901' => 0,
            ],
            'metadata' => [
                'last_updated' => now()->toIso8601String(),
                'source' => 'manual',
            ],
        ];

        // Test valid
        $this->assertTrue($this->converter->hasValidMappings($canonicalValid));

        // Test invalid
        $this->assertFalse($this->converter->hasValidMappings($canonicalInvalid));
    }

    /**
     * Test getUnmappedCount() counts unmapped categories
     *
     * @return void
     */
    public function test_get_unmapped_count_counts_unmapped(): void
    {
        // Canonical with 2 mapped, 1 unmapped
        $canonical = [
            'ui' => [
                'selected' => [1000, 1001, 1002],
                'primary' => 1000,
            ],
            'mappings' => [
                '1000' => 80,
                '1001' => 0,  // Unmapped
                '1002' => 82,
            ],
            'metadata' => [
                'last_updated' => now()->toIso8601String(),
                'source' => 'manual',
            ],
        ];

        // Count unmapped
        $unmappedCount = $this->converter->getUnmappedCount($canonical);

        // Assert count
        $this->assertEquals(1, $unmappedCount);
    }

    /**
     * Test refreshMappings() updates mappings from CategoryMapper
     *
     * @return void
     */
    public function test_refresh_mappings_updates_from_category_mapper(): void
    {
        // Create mappings in shop_mappings table
        ShopMapping::createOrUpdateMapping(
            shopId: $this->shop->id,
            type: ShopMapping::TYPE_CATEGORY,
            ppmValue: '1100',
            prestashopId: 90
        );

        ShopMapping::createOrUpdateMapping(
            shopId: $this->shop->id,
            type: ShopMapping::TYPE_CATEGORY,
            ppmValue: '1101',
            prestashopId: 91
        );

        // Canonical with stale mappings
        $canonical = [
            'ui' => [
                'selected' => [1100, 1101],
                'primary' => 1100,
            ],
            'mappings' => [
                '1100' => 0,  // Stale (should refresh to 90)
                '1101' => 0,  // Stale (should refresh to 91)
            ],
            'metadata' => [
                'last_updated' => now()->subHour()->toIso8601String(),
                'source' => 'manual',
            ],
        ];

        // Refresh
        $refreshed = $this->converter->refreshMappings($canonical, $this->shop);

        // Assert mappings refreshed
        $this->assertEquals(90, $refreshed['mappings']['1100']);
        $this->assertEquals(91, $refreshed['mappings']['1101']);

        // Assert metadata updated
        $this->assertEquals('refresh', $refreshed['metadata']['source']);
    }

    /**
     * Test edge case: empty canonical format
     *
     * @return void
     */
    public function test_edge_case_empty_canonical_format(): void
    {
        // Empty canonical
        $canonical = [
            'ui' => [
                'selected' => [],
                'primary' => null,
            ],
            'mappings' => [],
            'metadata' => [
                'last_updated' => now()->toIso8601String(),
                'source' => 'empty',
            ],
        ];

        // Test conversions
        $uiFormat = $this->converter->toUiFormat($canonical);
        $this->assertEmpty($uiFormat['selected']);
        $this->assertNull($uiFormat['primary']);

        $psIds = $this->converter->toPrestaShopIdsList($canonical);
        $this->assertEmpty($psIds);

        $primaryId = $this->converter->getPrimaryPrestaShopId($canonical);
        $this->assertNull($primaryId);

        $this->assertFalse($this->converter->hasValidMappings($canonical));
        $this->assertEquals(0, $this->converter->getUnmappedCount($canonical));
    }
}

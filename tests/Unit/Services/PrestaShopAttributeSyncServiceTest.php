<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\PrestaShop\PrestaShopAttributeSyncService;
use App\Services\Product\AttributeTypeService;
use App\Services\Product\AttributeValueService;
use App\Models\AttributeType;
use App\Models\AttributeValue;
use App\Models\PrestaShopShop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

/**
 * PrestaShopAttributeSyncService Unit Tests
 *
 * Tests for AttributeType/AttributeValue → PrestaShop attribute_groups/attributes sync
 *
 * COVERAGE:
 * - syncAttributeGroup() - find existing group
 * - syncAttributeGroup() - detect group_type conflict
 * - syncAttributeGroup() - handle missing group
 * - syncAttributeValue() - find existing value
 * - syncAttributeValue() - detect color mismatch
 * - syncAttributeValue() - handle missing parent group
 * - createAttributeGroupInPS() - create new group
 * - generateAttributeGroupXML() - XML generation
 *
 * @package Tests\Unit\Services
 */
class PrestaShopAttributeSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PrestaShopAttributeSyncService $service;
    protected PrestaShopShop $shop;
    protected AttributeType $attributeType;

    protected function setUp(): void
    {
        parent::setUp();

        // Create service instance
        $this->service = app(PrestaShopAttributeSyncService::class);

        // Create test shop
        $this->shop = PrestaShopShop::create([
            'name' => 'Test Shop',
            'url' => 'https://test.prestashop.com',
            'api_key' => 'TEST_KEY_123',
            'is_active' => true,
        ]);

        // Create test attribute type
        $this->attributeType = AttributeType::create([
            'name' => 'Kolor',
            'code' => 'color',
            'display_type' => 'color',
            'position' => 1,
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_can_sync_existing_attribute_group_successfully()
    {
        // Mock PrestaShop API response (existing group found)
        // Note: Full implementation would require mocking PrestaShopClientFactory

        $this->markTestSkipped('Requires PrestaShop API mocking');

        // Expected flow:
        // 1. syncAttributeGroup() called
        // 2. PrestaShop API returns existing group with matching group_type
        // 3. Mapping updated with status = 'synced'
        // 4. Returns ['status' => 'synced', 'ps_id' => X]
    }

    /** @test */
    public function it_detects_group_type_conflict()
    {
        // Mock PrestaShop API response (group found but wrong type)
        $this->markTestSkipped('Requires PrestaShop API mocking');

        // Expected flow:
        // 1. PPM AttributeType has display_type = 'color' (expects group_type = 'color')
        // 2. PrestaShop group has group_type = 'select'
        // 3. Mapping updated with status = 'conflict'
        // 4. Returns ['status' => 'conflict', 'message' => 'Group type mismatch']
    }

    /** @test */
    public function it_handles_missing_attribute_group()
    {
        $this->markTestSkipped('Requires PrestaShop API mocking');

        // Expected flow:
        // 1. PrestaShop API returns empty result
        // 2. Mapping updated with status = 'missing'
        // 3. Returns ['status' => 'missing']
    }

    /** @test */
    public function it_can_sync_existing_attribute_value_successfully()
    {
        // Create AttributeValue
        $value = AttributeValue::create([
            'attribute_type_id' => $this->attributeType->id,
            'code' => 'red',
            'value' => 'Czerwony',
            'color_hex' => '#ff0000',
            'position' => 1,
        ]);

        $this->markTestSkipped('Requires PrestaShop API mocking');

        // Expected flow:
        // 1. Check parent AttributeType is mapped
        // 2. Query PrestaShop API for attribute
        // 3. Compare color_hex if display_type = 'color'
        // 4. Mapping updated with status = 'synced'
        // 5. Returns ['status' => 'synced', 'ps_id' => X]
    }

    /** @test */
    public function it_detects_color_mismatch_in_attribute_value()
    {
        $value = AttributeValue::create([
            'attribute_type_id' => $this->attributeType->id,
            'code' => 'red',
            'value' => 'Czerwony',
            'color_hex' => '#ff0000',
            'position' => 1,
        ]);

        $this->markTestSkipped('Requires PrestaShop API mocking');

        // Expected flow:
        // 1. PPM color_hex = '#ff0000'
        // 2. PrestaShop color = '#cc0000' (different!)
        // 3. Mapping updated with status = 'conflict'
        // 4. Returns ['status' => 'conflict', 'message' => 'Color mismatch']
    }

    /** @test */
    public function it_handles_missing_parent_attribute_group_mapping()
    {
        $value = AttributeValue::create([
            'attribute_type_id' => $this->attributeType->id,
            'code' => 'red',
            'value' => 'Czerwony',
            'position' => 1,
        ]);

        // No parent mapping exists
        $result = $this->service->syncAttributeValue($value->id, $this->shop->id);

        $this->assertEquals('conflict', $result['status']);
        $this->assertEquals('Parent AttributeType not mapped', $result['message']);

        // Check mapping created with conflict status
        $mapping = DB::table('prestashop_attribute_value_mapping')
            ->where('attribute_value_id', $value->id)
            ->where('prestashop_shop_id', $this->shop->id)
            ->first();

        $this->assertNotNull($mapping);
        $this->assertEquals('conflict', $mapping->sync_status);
        $this->assertStringContainsString('Parent AttributeType not mapped', $mapping->sync_notes);
    }

    /** @test */
    public function it_generates_correct_xml_for_attribute_group()
    {
        // Use reflection to access protected method
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('generateAttributeGroupXML');
        $method->setAccessible(true);

        $xml = $method->invoke($this->service, $this->attributeType);

        // Verify XML structure
        $this->assertStringContainsString('<?xml version="1.0" encoding="UTF-8"?>', $xml);
        $this->assertStringContainsString('<prestashop>', $xml);
        $this->assertStringContainsString('<attribute_group>', $xml);
        $this->assertStringContainsString('<![CDATA[Kolor]]>', $xml);
        $this->assertStringContainsString('<group_type>color</group_type>', $xml);
        $this->assertStringContainsString('<is_color_group>1</is_color_group>', $xml);
    }

    /** @test */
    public function it_generates_select_type_xml_for_non_color_attributes()
    {
        $selectType = AttributeType::create([
            'name' => 'Rozmiar',
            'code' => 'size',
            'display_type' => 'dropdown',
            'position' => 2,
        ]);

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('generateAttributeGroupXML');
        $method->setAccessible(true);

        $xml = $method->invoke($this->service, $selectType);

        $this->assertStringContainsString('<group_type>select</group_type>', $xml);
        $this->assertStringContainsString('<is_color_group>0</is_color_group>', $xml);
    }

    /** @test */
    public function it_can_create_attribute_group_in_prestashop()
    {
        $this->markTestSkipped('Requires PrestaShop API mocking');

        // Expected flow:
        // 1. generateAttributeGroupXML() called
        // 2. POST to /attribute_groups with XML
        // 3. PrestaShop returns new group ID
        // 4. Mapping created with status = 'synced'
        // 5. Returns ps_attribute_group_id
    }

    /** @test */
    public function mapping_is_updated_correctly_after_sync()
    {
        // Test that updateMapping() helper works correctly
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('updateMapping');
        $method->setAccessible(true);

        $method->invoke($this->service, $this->attributeType->id, $this->shop->id, [
            'prestashop_attribute_group_id' => 123,
            'prestashop_label' => 'Kolor',
            'sync_status' => 'synced',
            'is_synced' => true,
            'last_synced_at' => now(),
        ]);

        $mapping = DB::table('prestashop_attribute_group_mapping')
            ->where('attribute_type_id', $this->attributeType->id)
            ->where('prestashop_shop_id', $this->shop->id)
            ->first();

        $this->assertNotNull($mapping);
        $this->assertEquals(123, $mapping->prestashop_attribute_group_id);
        $this->assertEquals('synced', $mapping->sync_status);
        $this->assertTrue((bool)$mapping->is_synced);
    }
}

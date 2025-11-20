<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\TaxRateService;
use App\Models\PrestaShopShop;
use App\Models\Product;
use App\Models\ProductShopData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

/**
 * TaxRateService Tests
 *
 * ETAP_07 FAZA 5.3 - Tax Rules UI Enhancement
 *
 * Tests for TaxRateService business logic
 */
class TaxRateServiceTest extends TestCase
{
    use RefreshDatabase;

    protected TaxRateService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TaxRateService();
        Cache::flush(); // Clear cache before each test
    }

    /**
     * Test getAvailableTaxRatesForShop() - all rates mapped
     */
    public function test_get_available_tax_rates_for_shop_all_mapped()
    {
        $shop = PrestaShopShop::factory()->create([
            'tax_rules_group_id_23' => 1,
            'tax_rules_group_id_8' => 2,
            'tax_rules_group_id_5' => 3,
            'tax_rules_group_id_0' => 4,
        ]);

        $rates = $this->service->getAvailableTaxRatesForShop($shop);

        $this->assertCount(4, $rates);
        $this->assertEquals([23.00, 8.00, 5.00, 0.00], array_column($rates, 'rate'));
    }

    /**
     * Test getAvailableTaxRatesForShop() - partial mapping
     */
    public function test_get_available_tax_rates_for_shop_partial()
    {
        $shop = PrestaShopShop::factory()->create([
            'tax_rules_group_id_23' => 1,
            'tax_rules_group_id_8' => null, // Not mapped
            'tax_rules_group_id_5' => null,
            'tax_rules_group_id_0' => 2,
        ]);

        $rates = $this->service->getAvailableTaxRatesForShop($shop);

        $this->assertCount(2, $rates);
        $this->assertEquals([23.00, 0.00], array_column($rates, 'rate'));
    }

    /**
     * Test validateTaxRateForShop() - valid rate
     */
    public function test_validate_tax_rate_for_shop_valid()
    {
        $shop = PrestaShopShop::factory()->create([
            'tax_rules_group_id_23' => 1,
            'tax_rules_group_id_8' => 2,
            'tax_rules_group_id_5' => null,
            'tax_rules_group_id_0' => null,
        ]);

        $result = $this->service->validateTaxRateForShop(23.00, $shop);

        $this->assertTrue($result['valid']);
        $this->assertNull($result['warning']);
        $this->assertEquals(1, $result['prestashop_group_id']);
    }

    /**
     * Test validateTaxRateForShop() - invalid rate
     */
    public function test_validate_tax_rate_for_shop_invalid()
    {
        $shop = PrestaShopShop::factory()->create([
            'tax_rules_group_id_23' => 1,
            'tax_rules_group_id_8' => null, // Not mapped
            'tax_rules_group_id_5' => null,
            'tax_rules_group_id_0' => null,
        ]);

        $result = $this->service->validateTaxRateForShop(8.00, $shop);

        $this->assertFalse($result['valid']);
        $this->assertNotNull($result['warning']);
        $this->assertStringContainsString('8', $result['warning']);
        $this->assertNull($result['prestashop_group_id']);
    }

    /**
     * Test getPrestaShopTaxRuleGroupId()
     */
    public function test_get_prestashop_tax_rule_group_id()
    {
        $shop = PrestaShopShop::factory()->create([
            'tax_rules_group_id_23' => 10,
            'tax_rules_group_id_8' => 20,
            'tax_rules_group_id_5' => 30,
            'tax_rules_group_id_0' => 40,
        ]);

        $this->assertEquals(10, $this->service->getPrestaShopTaxRuleGroupId(23.00, $shop));
        $this->assertEquals(20, $this->service->getPrestaShopTaxRuleGroupId(8.00, $shop));
        $this->assertEquals(30, $this->service->getPrestaShopTaxRuleGroupId(5.00, $shop));
        $this->assertEquals(40, $this->service->getPrestaShopTaxRuleGroupId(0.00, $shop));

        // Unmapped rate
        $shop2 = PrestaShopShop::factory()->create([
            'tax_rules_group_id_23' => null,
            'tax_rules_group_id_8' => null,
            'tax_rules_group_id_5' => null,
            'tax_rules_group_id_0' => null,
        ]);

        $this->assertNull($this->service->getPrestaShopTaxRuleGroupId(23.00, $shop2));
    }

    /**
     * Test getTaxRateOptionsForDropdown()
     */
    public function test_get_tax_rate_options_for_dropdown()
    {
        $shop = PrestaShopShop::factory()->create([
            'tax_rules_group_id_23' => 1,
            'tax_rules_group_id_8' => 2,
            'tax_rules_group_id_5' => null,
            'tax_rules_group_id_0' => null,
        ]);

        $options = $this->service->getTaxRateOptionsForDropdown($shop);

        $this->assertCount(2, $options);
        $this->assertEquals(23.00, $options[0]['value']);
        $this->assertEquals('VAT 23% (Standard)', $options[0]['label']);
        $this->assertEquals(8.00, $options[1]['value']);
        $this->assertEquals('VAT 8% (Obniżona)', $options[1]['label']);
    }

    /**
     * Test validateProductTaxRateForAllShops()
     */
    public function test_validate_product_tax_rate_for_all_shops()
    {
        $product = Product::factory()->create(['tax_rate' => 23.00]);

        $shop1 = PrestaShopShop::factory()->create([
            'name' => 'Shop 1',
            'tax_rules_group_id_23' => 1, // Mapped
            'tax_rules_group_id_8' => null,
            'tax_rules_group_id_5' => null,
            'tax_rules_group_id_0' => null,
        ]);

        $shop2 = PrestaShopShop::factory()->create([
            'name' => 'Shop 2',
            'tax_rules_group_id_23' => null, // NOT mapped
            'tax_rules_group_id_8' => 2,
            'tax_rules_group_id_5' => null,
            'tax_rules_group_id_0' => null,
        ]);

        ProductShopData::factory()->create([
            'product_id' => $product->id,
            'shop_id' => $shop1->id,
            'tax_rate_override' => null, // Uses product default 23.00
        ]);

        ProductShopData::factory()->create([
            'product_id' => $product->id,
            'shop_id' => $shop2->id,
            'tax_rate_override' => null, // Uses product default 23.00
        ]);

        $results = $this->service->validateProductTaxRateForAllShops($product);

        $this->assertCount(2, $results);

        // Shop 1 - valid (23.00 mapped)
        $this->assertTrue($results[$shop1->id]['valid']);
        $this->assertEquals(23.00, $results[$shop1->id]['effective_tax_rate']);
        $this->assertNull($results[$shop1->id]['warning']);

        // Shop 2 - invalid (23.00 NOT mapped)
        $this->assertFalse($results[$shop2->id]['valid']);
        $this->assertEquals(23.00, $results[$shop2->id]['effective_tax_rate']);
        $this->assertNotNull($results[$shop2->id]['warning']);
    }

    /**
     * Test clearCacheForShop()
     */
    public function test_clear_cache_for_shop()
    {
        $shop = PrestaShopShop::factory()->create([
            'tax_rules_group_id_23' => 1,
            'tax_rules_group_id_8' => null,
            'tax_rules_group_id_5' => null,
            'tax_rules_group_id_0' => null,
        ]);

        // Populate cache
        $rates1 = $this->service->getAvailableTaxRatesForShop($shop);
        $this->assertCount(1, $rates1);

        // Clear cache
        $this->service->clearCacheForShop($shop);

        // Verify cache cleared (will re-fetch from database)
        $cacheKey = "tax_rates_shop_{$shop->id}";
        $this->assertNull(Cache::get($cacheKey));
    }

    /**
     * Test getStandardPolandVATRates()
     */
    public function test_get_standard_poland_vat_rates()
    {
        $rates = TaxRateService::getStandardPolandVATRates();

        $this->assertCount(4, $rates);
        $this->assertArrayHasKey(23.00, $rates);
        $this->assertArrayHasKey(8.00, $rates);
        $this->assertArrayHasKey(5.00, $rates);
        $this->assertArrayHasKey(0.00, $rates);
    }

    /**
     * Test caching behavior
     */
    public function test_caching_behavior()
    {
        $shop = PrestaShopShop::factory()->create([
            'tax_rules_group_id_23' => 1,
            'tax_rules_group_id_8' => 2,
            'tax_rules_group_id_5' => null,
            'tax_rules_group_id_0' => null,
        ]);

        // First call - cache miss
        $rates1 = $this->service->getAvailableTaxRatesForShop($shop);

        // Second call - cache hit
        $rates2 = $this->service->getAvailableTaxRatesForShop($shop);

        $this->assertEquals($rates1, $rates2);

        // Verify cache exists
        $cacheKey = "tax_rates_shop_{$shop->id}";
        $this->assertNotNull(Cache::get($cacheKey));
    }
}

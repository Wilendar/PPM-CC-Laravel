<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\ProductShopData;
use App\Models\Product;
use App\Models\PrestaShopShop;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * ProductShopData Tax Rate Helper Methods Tests
 *
 * ETAP_07 FAZA 5.3 - Tax Rules UI Enhancement
 *
 * Tests for tax rate helper methods added in Phase 1 - Backend Foundation
 */
class ProductShopDataTaxRateTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test getEffectiveTaxRate() - shop override scenario
     *
     * Priority: tax_rate_override (23.00) > product->tax_rate (8.00) > fallback (23.00)
     */
    public function test_get_effective_tax_rate_with_override()
    {
        $product = Product::factory()->create(['tax_rate' => 8.00]);
        $shop = PrestaShopShop::factory()->create();

        $shopData = ProductShopData::factory()->create([
            'product_id' => $product->id,
            'shop_id' => $shop->id,
            'tax_rate_override' => 23.00, // Override set
        ]);

        $this->assertEquals(23.00, $shopData->getEffectiveTaxRate());
    }

    /**
     * Test getEffectiveTaxRate() - product default scenario
     *
     * Priority: tax_rate_override (null) > product->tax_rate (8.00) > fallback (23.00)
     */
    public function test_get_effective_tax_rate_with_product_default()
    {
        $product = Product::factory()->create(['tax_rate' => 8.00]);
        $shop = PrestaShopShop::factory()->create();

        $shopData = ProductShopData::factory()->create([
            'product_id' => $product->id,
            'shop_id' => $shop->id,
            'tax_rate_override' => null, // No override
        ]);

        $this->assertEquals(8.00, $shopData->getEffectiveTaxRate());
    }

    /**
     * Test getEffectiveTaxRate() - system fallback scenario
     *
     * Priority: tax_rate_override (null) > product->tax_rate (null) > fallback (23.00)
     */
    public function test_get_effective_tax_rate_with_fallback()
    {
        $product = Product::factory()->create(['tax_rate' => null]);
        $shop = PrestaShopShop::factory()->create();

        $shopData = ProductShopData::factory()->create([
            'product_id' => $product->id,
            'shop_id' => $shop->id,
            'tax_rate_override' => null,
        ]);

        $this->assertEquals(23.00, $shopData->getEffectiveTaxRate());
    }

    /**
     * Test getTaxRateSourceType() - shop_override
     */
    public function test_get_tax_rate_source_type_override()
    {
        $product = Product::factory()->create(['tax_rate' => 8.00]);
        $shop = PrestaShopShop::factory()->create();

        $shopData = ProductShopData::factory()->create([
            'product_id' => $product->id,
            'shop_id' => $shop->id,
            'tax_rate_override' => 5.00,
        ]);

        $this->assertEquals('shop_override', $shopData->getTaxRateSourceType());
    }

    /**
     * Test getTaxRateSourceType() - product_default
     */
    public function test_get_tax_rate_source_type_product_default()
    {
        $product = Product::factory()->create(['tax_rate' => 8.00]);
        $shop = PrestaShopShop::factory()->create();

        $shopData = ProductShopData::factory()->create([
            'product_id' => $product->id,
            'shop_id' => $shop->id,
            'tax_rate_override' => null,
        ]);

        $this->assertEquals('product_default', $shopData->getTaxRateSourceType());
    }

    /**
     * Test getTaxRateSourceType() - system_fallback
     */
    public function test_get_tax_rate_source_type_fallback()
    {
        $product = Product::factory()->create(['tax_rate' => null]);
        $shop = PrestaShopShop::factory()->create();

        $shopData = ProductShopData::factory()->create([
            'product_id' => $product->id,
            'shop_id' => $shop->id,
            'tax_rate_override' => null,
        ]);

        $this->assertEquals('system_fallback', $shopData->getTaxRateSourceType());
    }

    /**
     * Test taxRateMatchesPrestaShopMapping() - valid mapping
     */
    public function test_tax_rate_matches_prestashop_mapping_valid()
    {
        $product = Product::factory()->create(['tax_rate' => 23.00]);
        $shop = PrestaShopShop::factory()->create([
            'tax_rules_group_id_23' => 1, // Mapped
            'tax_rules_group_id_8' => 2,
            'tax_rules_group_id_5' => null,
            'tax_rules_group_id_0' => null,
        ]);

        $shopData = ProductShopData::factory()->create([
            'product_id' => $product->id,
            'shop_id' => $shop->id,
            'tax_rate_override' => null,
        ]);

        $this->assertTrue($shopData->taxRateMatchesPrestaShopMapping());
    }

    /**
     * Test taxRateMatchesPrestaShopMapping() - invalid mapping
     */
    public function test_tax_rate_matches_prestashop_mapping_invalid()
    {
        $product = Product::factory()->create(['tax_rate' => 5.00]);
        $shop = PrestaShopShop::factory()->create([
            'tax_rules_group_id_23' => 1,
            'tax_rules_group_id_8' => 2,
            'tax_rules_group_id_5' => null, // NOT mapped
            'tax_rules_group_id_0' => null,
        ]);

        $shopData = ProductShopData::factory()->create([
            'product_id' => $product->id,
            'shop_id' => $shop->id,
            'tax_rate_override' => null,
        ]);

        $this->assertFalse($shopData->taxRateMatchesPrestaShopMapping());
    }

    /**
     * Test getTaxRateValidationWarning() - valid (no warning)
     */
    public function test_get_tax_rate_validation_warning_valid()
    {
        $product = Product::factory()->create(['tax_rate' => 8.00]);
        $shop = PrestaShopShop::factory()->create([
            'tax_rules_group_id_23' => 1,
            'tax_rules_group_id_8' => 2, // Mapped
            'tax_rules_group_id_5' => null,
            'tax_rules_group_id_0' => null,
        ]);

        $shopData = ProductShopData::factory()->create([
            'product_id' => $product->id,
            'shop_id' => $shop->id,
            'tax_rate_override' => null,
        ]);

        $this->assertNull($shopData->getTaxRateValidationWarning());
    }

    /**
     * Test getTaxRateValidationWarning() - invalid (warning expected)
     */
    public function test_get_tax_rate_validation_warning_invalid()
    {
        $product = Product::factory()->create(['tax_rate' => 5.00]);
        $shop = PrestaShopShop::factory()->create([
            'name' => 'Test Shop',
            'tax_rules_group_id_23' => 1,
            'tax_rules_group_id_8' => 2,
            'tax_rules_group_id_5' => null, // NOT mapped
            'tax_rules_group_id_0' => null,
        ]);

        $shopData = ProductShopData::factory()->create([
            'product_id' => $product->id,
            'shop_id' => $shop->id,
            'tax_rate_override' => null,
        ]);

        $warning = $shopData->getTaxRateValidationWarning();

        $this->assertNotNull($warning);
        $this->assertStringContainsString('5', $warning);
        $this->assertStringContainsString('nie jest zmapowana', $warning);
    }

    /**
     * Test hasTaxRateOverride()
     */
    public function test_has_tax_rate_override()
    {
        $product = Product::factory()->create(['tax_rate' => 23.00]);
        $shop = PrestaShopShop::factory()->create();

        // With override
        $shopDataWithOverride = ProductShopData::factory()->create([
            'product_id' => $product->id,
            'shop_id' => $shop->id,
            'tax_rate_override' => 8.00,
        ]);

        $this->assertTrue($shopDataWithOverride->hasTaxRateOverride());

        // Without override
        $shopDataNoOverride = ProductShopData::factory()->create([
            'product_id' => $product->id,
            'shop_id' => $shop->id,
            'tax_rate_override' => null,
        ]);

        $this->assertFalse($shopDataNoOverride->hasTaxRateOverride());
    }
}

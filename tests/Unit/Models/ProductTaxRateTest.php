<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Product;
use App\Models\ProductShopData;
use App\Models\PrestaShopShop;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Product Tax Rate Helper Methods Tests
 *
 * ETAP_07 FAZA 5.3 - Tax Rules UI Enhancement
 *
 * Tests for Product::getTaxRateForShop() method
 */
class ProductTaxRateTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test getTaxRateForShop() - no shop specified (global default)
     */
    public function test_get_tax_rate_for_shop_no_shop()
    {
        $product = Product::factory()->create(['tax_rate' => 8.00]);

        $this->assertEquals(8.00, $product->getTaxRateForShop());
    }

    /**
     * Test getTaxRateForShop() - shop specified with override
     */
    public function test_get_tax_rate_for_shop_with_override()
    {
        $product = Product::factory()->create(['tax_rate' => 23.00]);
        $shop = PrestaShopShop::factory()->create();

        ProductShopData::factory()->create([
            'product_id' => $product->id,
            'shop_id' => $shop->id,
            'tax_rate_override' => 8.00, // Override
        ]);

        $this->assertEquals(8.00, $product->getTaxRateForShop($shop->id));
    }

    /**
     * Test getTaxRateForShop() - shop specified without override (product default)
     */
    public function test_get_tax_rate_for_shop_without_override()
    {
        $product = Product::factory()->create(['tax_rate' => 23.00]);
        $shop = PrestaShopShop::factory()->create();

        ProductShopData::factory()->create([
            'product_id' => $product->id,
            'shop_id' => $shop->id,
            'tax_rate_override' => null, // No override
        ]);

        $this->assertEquals(23.00, $product->getTaxRateForShop($shop->id));
    }

    /**
     * Test getTaxRateForShop() - shop specified but no shop data exists (fallback)
     */
    public function test_get_tax_rate_for_shop_no_shop_data()
    {
        $product = Product::factory()->create(['tax_rate' => 8.00]);
        $shop = PrestaShopShop::factory()->create();

        // No ProductShopData created

        $this->assertEquals(8.00, $product->getTaxRateForShop($shop->id));
    }

    /**
     * Test getTaxRateForShop() - product has null tax_rate (fallback to 23.00)
     */
    public function test_get_tax_rate_for_shop_null_product_rate()
    {
        $product = Product::factory()->create(['tax_rate' => null]);

        $this->assertEquals(23.00, $product->getTaxRateForShop());
    }

    /**
     * Test getTaxRateForShop() - shop specified, product null, no override (fallback)
     */
    public function test_get_tax_rate_for_shop_null_product_rate_no_override()
    {
        $product = Product::factory()->create(['tax_rate' => null]);
        $shop = PrestaShopShop::factory()->create();

        ProductShopData::factory()->create([
            'product_id' => $product->id,
            'shop_id' => $shop->id,
            'tax_rate_override' => null,
        ]);

        $this->assertEquals(23.00, $product->getTaxRateForShop($shop->id));
    }
}

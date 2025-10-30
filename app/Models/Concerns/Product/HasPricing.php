<?php

namespace App\Models\Concerns\Product;

use App\Models\ProductPrice;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

/**
 * HasPricing Trait - Product Pricing Business Logic
 *
 * Responsibility: Pricing system dla 8 grup cenowych PPM
 *
 * Features:
 * - Price groups relationships (8 grup: retail, dealer, workshop, etc.)
 * - Valid prices filtering (active + date range)
 * - Price retrieval methods (by group ID, by group code)
 * - Price comparison (lowest, highest)
 * - Formatted prices accessor dla UI
 *
 * Performance: Optimized queries z proper eager loading
 * Integration: PrestaShop specific_price mapping ready
 *
 * @package App\Models\Concerns\Product
 * @version 1.0
 * @since ETAP_05a SEKCJA 0 - Product.php Refactoring
 */
trait HasPricing
{
    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS - Pricing Relations
    |--------------------------------------------------------------------------
    */

    /**
     * Product prices relationship (1:many) - FAZA B ✅ IMPLEMENTED
     *
     * Business Logic: 8 grup cenowych PPM z support dla variants
     * Performance: Eager loading ready z proper indexing
     * Integration: PrestaShop specific_price mapping ready
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class, 'product_id', 'id')
                    ->orderBy('price_group_id', 'asc');
    }

    /**
     * Valid prices only (active and within date range)
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function validPrices(): HasMany
    {
        return $this->prices()->active()->validNow();
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS - Computed Pricing Attributes
    |--------------------------------------------------------------------------
    */

    /**
     * Get formatted prices for all groups - FAZA B ✅ IMPLEMENTED
     *
     * Business Logic: Ceny dla 8 grup cenowych PPM z integration ready
     * Performance: Optimized query z proper relationships
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function formattedPrices(): Attribute
    {
        return Attribute::make(
            get: function (): array {
                $prices = [];

                // Get valid prices with price group relationship
                $validPrices = $this->validPrices()->with('priceGroup')->get();

                foreach ($validPrices as $price) {
                    $groupCode = $price->priceGroup->code ?? 'unknown';
                    $prices[$groupCode] = [
                        'net' => $price->formatted_price_net,
                        'gross' => $price->formatted_price_gross,
                        'currency' => $price->currency,
                        'is_promotion' => $price->is_promotion,
                        'valid_until' => $price->valid_to?->format('Y-m-d'),
                    ];
                }

                return $prices;
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | BUSINESS METHODS - Pricing Operations
    |--------------------------------------------------------------------------
    */

    /**
     * Get price for specific price group
     *
     * @param int $priceGroupId
     * @return \App\Models\ProductPrice|null
     */
    public function getPriceForGroup(int $priceGroupId): ?ProductPrice
    {
        return $this->validPrices()
                    ->where('price_group_id', $priceGroupId)
                    ->first();
    }

    /**
     * Get lowest price from all active price groups
     *
     * @return \App\Models\ProductPrice|null
     */
    public function getLowestPrice(): ?ProductPrice
    {
        return $this->validPrices()
                    ->orderBy('price_gross', 'asc')
                    ->first();
    }

    /**
     * Get highest price from all active price groups
     *
     * @return \App\Models\ProductPrice|null
     */
    public function getHighestPrice(): ?ProductPrice
    {
        return $this->validPrices()
                    ->orderBy('price_gross', 'desc')
                    ->first();
    }

    /**
     * Get price for specific price group by code
     *
     * @param string $groupCode (retail, dealer_std, etc.)
     * @return \App\Models\ProductPrice|null
     */
    public function getPriceByGroupCode(string $groupCode): ?ProductPrice
    {
        return $this->validPrices()
                    ->whereHas('priceGroup', function ($query) use ($groupCode) {
                        $query->where('code', $groupCode);
                    })
                    ->first();
    }
}

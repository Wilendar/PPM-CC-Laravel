<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Product;
use App\Models\ProductErpData;
use App\Models\ProductMedia;
use App\Models\ProductPrice;
use App\Models\ProductShopData;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Services\Product\ProductStatusAggregator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Observer for invalidating product status cache when related models change.
 *
 * Monitors: Product, ProductShopData, ProductErpData, ProductPrice, ProductStock,
 * ProductMedia, ProductVariant
 *
 * @package App\Observers
 * @since 2026-02-04
 * @see Plan_Projektu/synthetic-mixing-thunder.md
 */
class ProductStatusCacheObserver
{
    /**
     * Cache key prefix (must match ProductStatusAggregator)
     */
    private const CACHE_PREFIX = 'product_status_';

    /**
     * Handle the Product "saved" event.
     */
    public function productSaved(Product $product): void
    {
        $this->invalidateProductCache($product->id);
    }

    /**
     * Handle the Product "deleted" event.
     */
    public function productDeleted(Product $product): void
    {
        $this->invalidateProductCache($product->id);
    }

    /**
     * Handle the ProductShopData "saved" event.
     */
    public function shopDataSaved(ProductShopData $shopData): void
    {
        $this->invalidateProductCache($shopData->product_id);
    }

    /**
     * Handle the ProductErpData "saved" event.
     */
    public function erpDataSaved(ProductErpData $erpData): void
    {
        $this->invalidateProductCache($erpData->product_id);
    }

    /**
     * Handle the ProductPrice "saved" event.
     */
    public function priceSaved(ProductPrice $price): void
    {
        // Price can be for product or variant
        if ($price->product_id) {
            $this->invalidateProductCache($price->product_id);
        } elseif ($price->variant_id) {
            $this->invalidateVariantParentCache($price->variant_id);
        }
    }

    /**
     * Handle the ProductStock "saved" event.
     */
    public function stockSaved(ProductStock $stock): void
    {
        // Stock can be for product or variant
        if ($stock->product_id) {
            $this->invalidateProductCache($stock->product_id);
        } elseif ($stock->variant_id) {
            $this->invalidateVariantParentCache($stock->variant_id);
        }
    }

    /**
     * Handle the ProductMedia "saved" event.
     */
    public function mediaSaved(ProductMedia $media): void
    {
        if ($media->mediable_type === Product::class) {
            $this->invalidateProductCache($media->mediable_id);
        }
    }

    /**
     * Handle the ProductVariant "saved" event.
     */
    public function variantSaved(ProductVariant $variant): void
    {
        $this->invalidateProductCache($variant->product_id);
    }

    /**
     * Invalidate cache for a product by touching its updated_at
     */
    private function invalidateProductCache(int $productId): void
    {
        try {
            // Touch the product to update timestamp (which changes cache key)
            Product::withoutEvents(function () use ($productId) {
                Product::where('id', $productId)->update(['updated_at' => now()]);
            });

            // Also clear any cached entries with the old key pattern
            // Note: Pattern clearing requires Redis driver
            // For file/database cache, old entries will naturally expire
            if (config('cache.default') === 'redis') {
                Cache::forget(self::CACHE_PREFIX . $productId . '_*');
            }
        } catch (\Exception $e) {
            Log::warning('ProductStatusCacheObserver: Failed to invalidate cache', [
                'product_id' => $productId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Find parent product ID from variant and invalidate cache
     */
    private function invalidateVariantParentCache(int $variantId): void
    {
        try {
            $variant = ProductVariant::select('product_id')->find($variantId);
            if ($variant && $variant->product_id) {
                $this->invalidateProductCache($variant->product_id);
            }
        } catch (\Exception $e) {
            Log::warning('ProductStatusCacheObserver: Failed to find variant parent', [
                'variant_id' => $variantId,
                'error' => $e->getMessage()
            ]);
        }
    }
}

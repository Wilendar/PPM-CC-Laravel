<?php

namespace App\Jobs\PrestaShop;

use App\Exceptions\PrestaShopAPIException;
use App\Models\Product;
use App\Models\PrestaShopShop;
use App\Services\PrestaShop\PrestaShopClientFactory;
use App\Services\PrestaShop\PrestaShopPriceImporter;
use App\Services\PrestaShop\PrestaShopStockImporter;
use App\Services\PrestaShop\ConflictResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Pull Single Product from PrestaShop Job
 *
 * Background job to pull current product data from PrestaShop → PPM (single product)
 *
 * TEST 2 FIX: Separate single-product pull from bulk pull
 * - Triggered from "Pobierz dane" button in Shop Tab
 * - Fetches single product from PrestaShop API
 * - Updates product_shop_data with fresh data
 * - Imports prices and stock
 * - Applies conflict resolution strategy
 *
 * @package App\Jobs\PrestaShop
 */
class PullSingleProductFromPrestaShop implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Product to pull data for
     */
    public Product $product;

    /**
     * PrestaShop shop to pull from
     */
    public PrestaShopShop $shop;

    /**
     * Number of times job may be attempted
     */
    public int $tries = 3;

    /**
     * Maximum seconds job can run before timing out
     */
    public int $timeout = 300;

    /**
     * Create a new job instance.
     *
     * @param Product $product Product to pull data for
     * @param PrestaShopShop $shop Shop to pull from
     */
    public function __construct(Product $product, PrestaShopShop $shop)
    {
        $this->product = $product;
        $this->shop = $shop;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Find shop data
            $shopData = $this->product->shopData()
                ->where('shop_id', $this->shop->id)
                ->first();

            if (!$shopData || !$shopData->prestashop_product_id) {
                Log::warning('Product not linked to shop or missing PrestaShop ID', [
                    'product_id' => $this->product->id,
                    'sku' => $this->product->sku,
                    'shop_id' => $this->shop->id,
                ]);
                throw new \Exception('Product not linked to this shop');
            }

            Log::info('Pulling single product from PrestaShop', [
                'product_id' => $this->product->id,
                'sku' => $this->product->sku,
                'shop_id' => $this->shop->id,
                'prestashop_product_id' => $shopData->prestashop_product_id,
            ]);

            // Create API client
            $client = PrestaShopClientFactory::create($this->shop);

            // Fetch product from PrestaShop
            $psData = $client->getProduct($shopData->prestashop_product_id);

            if (isset($psData['product'])) {
                $psData = $psData['product'];
            }

            // Apply conflict resolution strategy
            $conflictResolver = app(ConflictResolver::class);
            $resolution = $conflictResolver->resolve($shopData, $psData);

            Log::debug('Conflict resolution result', [
                'product_id' => $this->product->id,
                'sku' => $this->product->sku,
                'should_update' => $resolution['should_update'],
                'reason' => $resolution['reason'],
                'has_conflicts' => !empty($resolution['conflicts']),
            ]);

            if ($resolution['should_update']) {
                // Update allowed - apply PrestaShop data
                $shopData->update(array_merge($resolution['data'], [
                    'last_pulled_at' => now(),
                    'sync_status' => 'synced',
                    'has_conflicts' => false,
                    'conflict_log' => null,
                    'conflicts_detected_at' => null,
                ]));

                Log::info('Product updated from PrestaShop', [
                    'product_id' => $this->product->id,
                    'sku' => $this->product->sku,
                    'reason' => $resolution['reason'],
                ]);
            } else {
                // Update blocked - store conflicts if detected
                if ($resolution['conflicts']) {
                    $shopData->update([
                        'last_pulled_at' => now(),
                        'sync_status' => 'conflict',
                        'conflict_log' => $resolution['conflicts'],
                        'has_conflicts' => true,
                        'conflicts_detected_at' => now(),
                    ]);

                    Log::warning('Conflict detected - update blocked', [
                        'product_id' => $this->product->id,
                        'sku' => $this->product->sku,
                        'reason' => $resolution['reason'],
                        'conflicts_count' => count($resolution['conflicts']),
                    ]);
                } else {
                    // No conflicts, just different strategy (e.g., ppm_wins)
                    $shopData->update([
                        'last_pulled_at' => now(),
                    ]);

                    Log::info('Update skipped by conflict resolution strategy', [
                        'product_id' => $this->product->id,
                        'sku' => $this->product->sku,
                        'reason' => $resolution['reason'],
                    ]);
                }
            }

            // Import prices from PrestaShop specific_prices
            try {
                $priceImporter = app(PrestaShopPriceImporter::class);
                $importedPrices = $priceImporter->importPricesForProduct($this->product, $this->shop);

                Log::info('Prices imported for product', [
                    'product_id' => $this->product->id,
                    'sku' => $this->product->sku,
                    'prices_count' => count($importedPrices),
                ]);
            } catch (PrestaShopAPIException $priceError) {
                // 404 = product deleted, re-throw
                if ($priceError->isNotFound()) {
                    Log::debug('Product prices not found (404), product may be deleted', [
                        'product_id' => $this->product->id,
                        'sku' => $this->product->sku,
                    ]);
                    throw $priceError;
                }

                // Other errors - log but continue
                Log::warning('Failed to import prices', [
                    'product_id' => $this->product->id,
                    'error_code' => $priceError->getHttpStatusCode(),
                    'error' => $priceError->getMessage(),
                ]);
            }

            // Import stock from PrestaShop stock_availables
            try {
                $stockImporter = app(PrestaShopStockImporter::class);
                $importedStock = $stockImporter->importStockForProduct($this->product, $this->shop);

                Log::info('Stock imported for product', [
                    'product_id' => $this->product->id,
                    'sku' => $this->product->sku,
                    'stock_records_count' => count($importedStock),
                ]);
            } catch (PrestaShopAPIException $stockError) {
                // 404 = product deleted, re-throw
                if ($stockError->isNotFound()) {
                    Log::debug('Product stock not found (404), product may be deleted', [
                        'product_id' => $this->product->id,
                        'sku' => $this->product->sku,
                    ]);
                    throw $stockError;
                }

                // Other errors - log but continue
                Log::warning('Failed to import stock', [
                    'product_id' => $this->product->id,
                    'error_code' => $stockError->getHttpStatusCode(),
                    'error' => $stockError->getMessage(),
                ]);
            }

            Log::info('Single product pull completed successfully', [
                'product_id' => $this->product->id,
                'sku' => $this->product->sku,
                'shop_id' => $this->shop->id,
            ]);

        } catch (PrestaShopAPIException $e) {
            // Graceful 404 handling - product deleted from PrestaShop
            if ($e->isNotFound()) {
                Log::warning('Product not found in PrestaShop (404), unlinking', [
                    'product_id' => $this->product->id,
                    'sku' => $this->product->sku,
                    'shop_id' => $this->shop->id,
                    'prestashop_product_id' => $shopData->prestashop_product_id ?? null,
                    'action' => 'unlinked',
                ]);

                // Clear PrestaShop link - allow re-sync in future
                if ($shopData) {
                    $shopData->update([
                        'prestashop_product_id' => null,
                        'sync_status' => 'not_synced',
                        'last_sync_error' => 'Product deleted from PrestaShop (404)',
                    ]);
                }

                // Don't throw - job completed successfully (graceful unlink)
                return;
            }

            // Other PrestaShop API errors (rate limit, auth, server error)
            Log::error('PrestaShop API error during single product pull', [
                'product_id' => $this->product->id,
                'shop_id' => $this->shop->id,
                'error_code' => $e->getHttpStatusCode(),
                'error_category' => $e->getErrorCategory(),
                'error' => $e->getMessage(),
            ]);

            throw $e;

        } catch (\Exception $e) {
            Log::error('Error pulling single product from PrestaShop', [
                'product_id' => $this->product->id,
                'shop_id' => $this->shop->id,
                'error' => $e->getMessage(),
                'trace' => $e->getFile() . ':' . $e->getLine(),
            ]);

            throw $e;
        }
    }

    /**
     * Backoff delays between retries (seconds)
     *
     * @return array<int>
     */
    public function backoff(): array
    {
        return [30, 60, 300]; // 30s, 1min, 5min
    }

    /**
     * Job failed permanently (after all retries)
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Single product pull failed permanently', [
            'product_id' => $this->product->id,
            'sku' => $this->product->sku,
            'shop_id' => $this->shop->id,
            'shop_name' => $this->shop->name,
            'attempts' => $this->attempts(),
            'error' => $exception->getMessage(),
        ]);

        // Update shopData to error state
        $shopData = $this->product->shopData()
            ->where('shop_id', $this->shop->id)
            ->first();

        if ($shopData) {
            $shopData->update([
                'sync_status' => 'error',
                'last_sync_error' => 'Pull failed after ' . $this->attempts() . ' attempts: ' . $exception->getMessage(),
            ]);
        }
    }
}

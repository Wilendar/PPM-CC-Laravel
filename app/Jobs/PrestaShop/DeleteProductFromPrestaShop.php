<?php

namespace App\Jobs\PrestaShop;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\Product;
use App\Models\PrestaShopShop;
use App\Models\ProductShopData;
use App\Services\PrestaShop\PrestaShopClientFactory;
use Carbon\Carbon;
use Throwable;

/**
 * Delete Product From PrestaShop Job
 *
 * Background job dla fizycznego usunięcia produktu z PrestaShop
 *
 * Features:
 * - Unique jobs (prevents duplicate delete operations)
 * - Retry strategy with exponential backoff
 * - Comprehensive error handling
 * - Sync status tracking
 * - Product shop data cleanup
 *
 * ETAP_07 FAZA 3B: Product Delete Operations
 *
 * @package App\Jobs\PrestaShop
 */
class DeleteProductFromPrestaShop implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Product to delete from shop
     */
    public Product $product;

    /**
     * Target PrestaShop shop
     */
    public PrestaShopShop $shop;

    /**
     * Number of times job may be attempted
     */
    public int $tries = 3;

    /**
     * Maximum seconds job can run before timing out
     */
    public int $timeout = 120; // 2 minutes

    /**
     * Unique job identifier (prevents duplicate deletes)
     */
    public function uniqueId(): string
    {
        return "delete_product_{$this->product->id}_shop_{$this->shop->id}";
    }

    /**
     * How long unique lock should be maintained (seconds)
     */
    public int $uniqueFor = 3600; // 1 hour

    /**
     * Create new job instance
     */
    public function __construct(Product $product, PrestaShopShop $shop)
    {
        $this->product = $product;
        $this->shop = $shop;
    }

    /**
     * Execute the job - Delete product from PrestaShop
     */
    public function handle(): void
    {
        Log::info('DeleteProductFromPrestaShop job started', [
            'product_id' => $this->product->id,
            'product_sku' => $this->product->sku,
            'shop_id' => $this->shop->id,
            'shop_name' => $this->shop->name,
        ]);

        // Get ProductShopData to find PrestaShop product ID
        $productShopData = ProductShopData::where('product_id', $this->product->id)
            ->where('shop_id', $this->shop->id)
            ->first();

        if (!$productShopData) {
            Log::warning('No ProductShopData found - product not associated with shop', [
                'product_id' => $this->product->id,
                'shop_id' => $this->shop->id,
            ]);
            return;
        }

        // Check if product exists in PrestaShop (has prestashop_product_id)
        if (!$productShopData->prestashop_product_id) {
            Log::info('Product not synced to PrestaShop yet - only removing local association', [
                'product_id' => $this->product->id,
                'shop_id' => $this->shop->id,
            ]);

            // Delete local ProductShopData record
            $productShopData->delete();

            // Update sync status
            $this->updateSyncStatus('deleted', null);
            return;
        }

        try {
            // Create PrestaShop API client
            $client = PrestaShopClientFactory::create($this->shop);

            // Delete product from PrestaShop
            Log::info('Deleting product from PrestaShop', [
                'prestashop_product_id' => $productShopData->prestashop_product_id,
                'shop_id' => $this->shop->id,
            ]);

            $client->deleteProduct($productShopData->prestashop_product_id);

            // Delete local ProductShopData record
            $productShopData->delete();

            // Update sync status
            $this->updateSyncStatus('deleted', null);

            Log::info('Product successfully deleted from PrestaShop', [
                'product_id' => $this->product->id,
                'prestashop_product_id' => $productShopData->prestashop_product_id,
                'shop_id' => $this->shop->id,
            ]);

        } catch (Throwable $e) {
            Log::error('Failed to delete product from PrestaShop', [
                'product_id' => $this->product->id,
                'shop_id' => $this->shop->id,
                'prestashop_product_id' => $productShopData->prestashop_product_id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Update sync status with error
            $this->updateSyncStatus('error', $e->getMessage());

            // Re-throw to trigger retry
            throw $e;
        }
    }

    /**
     * Update product sync status after delete operation (CONSOLIDATED 2025-10-13)
     *
     * CRITICAL: ProductShopData is already deleted in handle() before this method is called
     * This method only needs to handle ERROR cases where delete failed and we want to keep record
     *
     * UPDATED: Now uses ProductShopData instead of deprecated ProductSyncStatus
     */
    protected function updateSyncStatus(string $status, ?string $errorMessage): void
    {
        // If successful delete (status='deleted' and no error), ProductShopData is already deleted
        // No action needed - just log completion
        if ($status === 'deleted' && $errorMessage === null) {
            Log::info('ProductShopData already deleted after successful shop delete', [
                'product_id' => $this->product->id,
                'shop_id' => $this->shop->id,
            ]);
        } else {
            // If error during delete, UPDATE ProductShopData to show error
            // Use updateOrCreate in case record was already deleted
            ProductShopData::updateOrCreate(
                [
                    'product_id' => $this->product->id,
                    'shop_id' => $this->shop->id,
                ],
                [
                    'sync_status' => $status,
                    'last_sync_at' => now(),
                    'error_message' => $errorMessage,
                    'prestashop_product_id' => null,
                ]
            );

            Log::info('ProductShopData updated with error status', [
                'product_id' => $this->product->id,
                'shop_id' => $this->shop->id,
                'sync_status' => $status,
            ]);
        }
    }

    /**
     * Handle job failure
     */
    public function failed(Throwable $exception): void
    {
        Log::error('DeleteProductFromPrestaShop job failed permanently', [
            'product_id' => $this->product->id,
            'shop_id' => $this->shop->id,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);

        // Update sync status with permanent failure
        $this->updateSyncStatus('error', 'Delete failed after ' . $this->attempts() . ' attempts: ' . $exception->getMessage());
    }
}

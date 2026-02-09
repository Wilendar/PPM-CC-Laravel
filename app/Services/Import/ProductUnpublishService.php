<?php

declare(strict_types=1);

namespace App\Services\Import;

use App\Models\PendingProduct;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\ProductShopData;
use App\Models\PublishHistory;
use App\Models\Media;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductUnpublishService
{
    /**
     * Unpublish a pending product - full rollback from PPM.
     * Deletes the Product and all related data, resets PendingProduct to draft.
     */
    public function unpublish(int $pendingProductId): array
    {
        $pendingProduct = PendingProduct::find($pendingProductId);
        if (!$pendingProduct) {
            return ['success' => false, 'errors' => ['Produkt nie istnieje']];
        }

        if (!$pendingProduct->isPublished()) {
            return ['success' => false, 'errors' => ['Produkt nie jest opublikowany']];
        }

        $productId = $pendingProduct->published_as_product_id;
        $product = Product::find($productId);

        if (!$product) {
            // Product already deleted - just reset PendingProduct
            $this->resetPendingProduct($pendingProduct);
            return ['success' => true, 'errors' => []];
        }

        try {
            return DB::transaction(function () use ($pendingProduct, $product) {
                // 1. Log external sync deletion warnings
                // PrestaShop/ERP jobs don't support delete action yet
                $shopData = ProductShopData::where('product_id', $product->id)->get();
                if ($shopData->isNotEmpty()) {
                    Log::warning('ProductUnpublishService: External PrestaShop delete not dispatched (not supported yet)', [
                        'product_id' => $product->id,
                        'sku' => $product->sku,
                        'shop_ids' => $shopData->pluck('shop_id')->toArray(),
                    ]);
                }

                $publicationTargets = $pendingProduct->publication_targets ?? [];
                $erpConnections = $publicationTargets['erp_connections'] ?? [];
                if (!empty($erpConnections)) {
                    Log::warning('ProductUnpublishService: External ERP delete not dispatched (not supported yet)', [
                        'product_id' => $product->id,
                        'sku' => $product->sku,
                        'erp_connections' => $erpConnections,
                    ]);
                }

                // 2. Delete related PPM data
                ProductPrice::where('product_id', $product->id)->delete();
                ProductShopData::where('product_id', $product->id)->delete();
                Media::where('mediable_type', Product::class)
                    ->where('mediable_id', $product->id)
                    ->delete();
                $product->categories()->detach();

                // 3. Audit trail via application log
                // NOTE: PublishHistory has FK cascade on product_id - records get
                // auto-deleted when product is forceDeleted. Using Log for audit.
                $unpublishedBy = auth()->id() ?? $pendingProduct->imported_by;
                Log::info('ProductUnpublishService: UNPUBLISH AUDIT', [
                    'action' => 'unpublish',
                    'pending_product_id' => $pendingProduct->id,
                    'deleted_product_id' => $product->id,
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'unpublished_by' => $unpublishedBy,
                    'unpublished_at' => now()->toIso8601String(),
                    'shop_ids' => $shopData->pluck('shop_id')->toArray(),
                    'erp_connections' => $erpConnections,
                ]);

                // 4. Delete existing publish history (cascade will also handle this,
                // but explicit delete ensures clean state within transaction)
                PublishHistory::where('product_id', $product->id)->delete();

                // 5. Force delete the Product (bypasses SoftDeletes)
                $product->forceDelete();

                // 6. Reset PendingProduct to draft
                $this->resetPendingProduct($pendingProduct);

                Log::info('ProductUnpublishService: Product unpublished', [
                    'pending_product_id' => $pendingProduct->id,
                    'deleted_product_id' => $product->id,
                    'sku' => $product->sku,
                ]);

                return ['success' => true, 'errors' => []];
            });
        } catch (\Throwable $e) {
            Log::error('ProductUnpublishService: Unpublish failed', [
                'pending_product_id' => $pendingProductId,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'errors' => ['Blad cofania publikacji: ' . $e->getMessage()]];
        }
    }

    protected function resetPendingProduct(PendingProduct $pendingProduct): void
    {
        $pendingProduct->update([
            'publish_status' => 'draft',
            'published_at' => null,
            'published_as_product_id' => null,
        ]);
    }
}

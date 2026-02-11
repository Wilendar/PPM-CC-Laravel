<?php

declare(strict_types=1);

namespace App\Services\Import;

use App\Jobs\PrestaShop\DeleteProductFromPrestaShop;
use App\Models\ERPConnection;
use App\Models\PendingProduct;
use App\Models\PrestaShopShop;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\ProductShopData;
use App\Models\PublishHistory;
use App\Models\Media;
use App\Services\ERP\BaselinkerService;
use App\Models\VariantImage;
use App\Services\ERP\SubiektGTService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductUnpublishService
{
    /**
     * Unpublish a pending product - full rollback from PPM AND external targets.
     *
     * FIX #10: Now dispatches delete jobs for PrestaShop and ERP targets
     * before deleting local PPM data. Uses dispatchSync for synchronous execution.
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
            $this->resetPendingProduct($pendingProduct);
            return ['success' => true, 'errors' => []];
        }

        try {
            return DB::transaction(function () use ($pendingProduct, $product) {
                $externalErrors = [];

                // 1. Delete from PrestaShop shops (BEFORE deleting local data!)
                $shopData = ProductShopData::where('product_id', $product->id)->get();
                foreach ($shopData as $psd) {
                    if ($psd->prestashop_product_id) {
                        $shop = PrestaShopShop::find($psd->shop_id);
                        if ($shop) {
                            try {
                                DeleteProductFromPrestaShop::dispatchSync($product, $shop);
                                Log::info('ProductUnpublishService: PrestaShop delete dispatched', [
                                    'product_id' => $product->id,
                                    'shop_id' => $shop->id,
                                    'ps_product_id' => $psd->prestashop_product_id,
                                ]);
                            } catch (\Throwable $e) {
                                Log::error('ProductUnpublishService: PrestaShop delete failed', [
                                    'product_id' => $product->id,
                                    'shop_id' => $shop->id,
                                    'error' => $e->getMessage(),
                                ]);
                                $externalErrors[] = "PS shop {$shop->name}: " . $e->getMessage();
                            }
                        }
                    }
                }

                // 2. Delete from ERP targets (Baselinker, etc.)
                $publicationTargets = $pendingProduct->publication_targets ?? [];
                $erpConnectionIds = $publicationTargets['erp_connections'] ?? [];
                foreach ($erpConnectionIds as $connId) {
                    $connection = ERPConnection::find($connId);
                    if (!$connection) {
                        continue;
                    }

                    try {
                        if ($connection->erp_type === 'baselinker') {
                            $blService = app(BaselinkerService::class);
                            $result = $blService->deleteProductFromBaselinker($connection, $product);
                            if (!$result['success']) {
                                $externalErrors[] = "BL {$connection->instance_name}: " . $result['message'];
                            } else {
                                Log::info('ProductUnpublishService: BL delete succeeded', [
                                    'product_id' => $product->id,
                                    'connection' => $connection->instance_name,
                                ]);
                            }
                        } elseif ($connection->erp_type === 'subiekt_gt') {
                            $result = $this->deactivateProductInSubiektGT($connection, $product);
                            if (!$result['success']) {
                                $externalErrors[] = "Subiekt {$connection->instance_name}: " . $result['message'];
                            } else {
                                Log::info('ProductUnpublishService: Subiekt GT deactivation succeeded', [
                                    'product_id' => $product->id,
                                    'sku' => $product->sku,
                                    'connection' => $connection->instance_name,
                                ]);
                            }
                        } else {
                            Log::warning('ProductUnpublishService: ERP delete not implemented for type', [
                                'erp_type' => $connection->erp_type,
                                'product_id' => $product->id,
                            ]);
                        }
                    } catch (\Throwable $e) {
                        Log::error('ProductUnpublishService: ERP delete failed', [
                            'product_id' => $product->id,
                            'connection_id' => $connId,
                            'error' => $e->getMessage(),
                        ]);
                        $externalErrors[] = "ERP #{$connId}: " . $e->getMessage();
                    }
                }

                // 3. Delete remaining local PPM data (some may already be deleted by PS job)
                ProductPrice::where('product_id', $product->id)->delete();
                ProductShopData::where('product_id', $product->id)->delete();
                Media::where('mediable_type', Product::class)
                    ->where('mediable_id', $product->id)
                    ->delete();
                $product->categories()->detach();

                // Delete integration mappings (BL mappings may already be deleted)
                $product->integrationMappings()->delete();

                // Delete variant images before deleting variants
                $variantIds = $product->variants()->pluck('id')->toArray();
                if (!empty($variantIds)) {
                    VariantImage::whereIn('variant_id', $variantIds)->delete();
                }

                // Delete variants
                $product->variants()->delete();

                // 4. Audit trail
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
                    'erp_connections' => $erpConnectionIds,
                    'external_errors' => $externalErrors,
                ]);

                // 5. Delete publish history
                PublishHistory::where('product_id', $product->id)->delete();

                // 6. Force delete the Product
                $product->forceDelete();

                // 7. Reset PendingProduct to draft
                $this->resetPendingProduct($pendingProduct);

                Log::info('ProductUnpublishService: Product unpublished', [
                    'pending_product_id' => $pendingProduct->id,
                    'deleted_product_id' => $product->id,
                    'sku' => $product->sku,
                    'external_errors_count' => count($externalErrors),
                ]);

                return [
                    'success' => true,
                    'errors' => $externalErrors,
                ];
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

    /**
     * Deactivate product in Subiekt GT via REST API.
     *
     * BUG#13 FIX: Subiekt GT doesn't support deletion via API,
     * but we can set IsActive=false to deactivate the product.
     * Also deactivates variants (each variant = separate product in Subiekt).
     */
    protected function deactivateProductInSubiektGT(ERPConnection $connection, Product $product): array
    {
        try {
            $config = $connection->connection_config;
            $client = new \App\Services\ERP\SubiektGT\SubiektRestApiClient([
                'base_url' => $config['rest_api_url'] ?? 'https://sapi.mpptrade.pl',
                'api_key' => $config['rest_api_key'] ?? '',
                'timeout' => 30,
                'verify_ssl' => $config['rest_api_verify_ssl'] ?? false,
            ]);

            // Deactivate main product
            $result = $client->updateProductBySku($product->sku, [
                'is_active' => false,
            ]);

            Log::info('ProductUnpublishService: Subiekt GT product deactivated', [
                'product_id' => $product->id,
                'sku' => $product->sku,
                'api_result' => $result,
            ]);

            // Deactivate variants (in Subiekt GT each variant = separate product by SKU)
            if ($product->is_variant_master) {
                foreach ($product->variants as $variant) {
                    try {
                        $client->updateProductBySku($variant->sku, [
                            'is_active' => false,
                        ]);
                        Log::info('ProductUnpublishService: Subiekt GT variant deactivated', [
                            'variant_id' => $variant->id,
                            'variant_sku' => $variant->sku,
                        ]);
                    } catch (\Throwable $e) {
                        Log::warning('ProductUnpublishService: Subiekt GT variant deactivation failed', [
                            'variant_sku' => $variant->sku,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }

            return ['success' => true, 'message' => 'Product deactivated in Subiekt GT'];
        } catch (\Throwable $e) {
            Log::error('ProductUnpublishService: Subiekt GT deactivation failed', [
                'product_id' => $product->id,
                'sku' => $product->sku,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}

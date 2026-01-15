<?php

namespace App\Services\Import;

use App\Models\PendingProduct;
use App\Models\Product;
use App\Models\PublishHistory;
use App\Models\Category;
use App\Models\ProductShopData;
use App\Jobs\PrestaShop\SyncProductToPrestaShop;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * ProductPublicationService
 *
 * ETAP_06 FAZA 6: Publikacja pending products do tabeli products
 *
 * Workflow:
 * 1. Walidacja kompletnosci PendingProduct
 * 2. Tworzenie Product z danych PendingProduct
 * 3. Przypisanie kategorii, sklepow, mediow
 * 4. Tworzenie PublishHistory (audit trail)
 * 5. Dispatch SyncProductToPrestaShop jobs per shop
 *
 * @package App\Services\Import
 * @since 2025-12-09
 */
class ProductPublicationService
{
    /**
     * Validate pending product before publication
     *
     * @param PendingProduct $pendingProduct
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validateForPublication(PendingProduct $pendingProduct): array
    {
        $errors = $pendingProduct->getPublishValidationErrors();

        // Additional validation: check if already published
        if ($pendingProduct->isPublished()) {
            $errors[] = 'Produkt zostal juz opublikowany';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Publish single pending product
     *
     * @param PendingProduct $pendingProduct
     * @param bool $dispatchSyncJobs Whether to dispatch PrestaShop sync jobs
     * @return array ['success' => bool, 'product' => Product|null, 'errors' => array]
     */
    public function publishSingle(PendingProduct $pendingProduct, bool $dispatchSyncJobs = true): array
    {
        // Validate first
        $validation = $this->validateForPublication($pendingProduct);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'product' => null,
                'errors' => $validation['errors'],
            ];
        }

        try {
            return DB::transaction(function () use ($pendingProduct, $dispatchSyncJobs) {
                // 1. Create Product
                $product = $this->createProductFromPending($pendingProduct);

                // 2. Assign categories
                $this->assignCategories($product, $pendingProduct);

                // 3. Create shop data entries
                $this->createShopData($product, $pendingProduct);

                // 4. Handle media (if any)
                $this->handleMedia($product, $pendingProduct);

                // 5. Mark pending product as published
                $pendingProduct->markAsPublished($product);

                // 6. Create publish history record
                $this->createPublishHistory($pendingProduct, $product);

                // 7. Dispatch sync jobs if requested
                if ($dispatchSyncJobs) {
                    $this->dispatchSyncJobs($product, $pendingProduct->shop_ids ?? []);
                }

                Log::info('ProductPublicationService: Published product', [
                    'pending_id' => $pendingProduct->id,
                    'product_id' => $product->id,
                    'sku' => $product->sku,
                ]);

                return [
                    'success' => true,
                    'product' => $product,
                    'errors' => [],
                ];
            });
        } catch (\Exception $e) {
            Log::error('ProductPublicationService: Publication failed', [
                'pending_id' => $pendingProduct->id,
                'sku' => $pendingProduct->sku,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'product' => null,
                'errors' => ['Blad podczas publikacji: ' . $e->getMessage()],
            ];
        }
    }

    /**
     * Publish multiple pending products (batch)
     *
     * @param array $pendingProductIds
     * @param bool $dispatchSyncJobs
     * @return array ['total' => int, 'success' => int, 'failed' => int, 'results' => array]
     */
    public function publishBatch(array $pendingProductIds, bool $dispatchSyncJobs = true): array
    {
        $results = [
            'total' => count($pendingProductIds),
            'success' => 0,
            'failed' => 0,
            'results' => [],
        ];

        $batchId = Str::uuid()->toString();

        foreach ($pendingProductIds as $id) {
            $pendingProduct = PendingProduct::find($id);

            if (!$pendingProduct) {
                $results['failed']++;
                $results['results'][$id] = [
                    'success' => false,
                    'errors' => ['Produkt nie istnieje'],
                ];
                continue;
            }

            $result = $this->publishSingle($pendingProduct, $dispatchSyncJobs);

            if ($result['success']) {
                $results['success']++;

                // Update publish history with batch ID
                PublishHistory::where('pending_product_id', $pendingProduct->id)
                    ->latest()
                    ->first()
                    ?->update([
                        'batch_id' => $batchId,
                        'publish_mode' => PublishHistory::MODE_BULK,
                    ]);
            } else {
                $results['failed']++;
            }

            $results['results'][$id] = [
                'success' => $result['success'],
                'product_id' => $result['product']?->id,
                'errors' => $result['errors'],
            ];
        }

        Log::info('ProductPublicationService: Batch publication completed', [
            'batch_id' => $batchId,
            'total' => $results['total'],
            'success' => $results['success'],
            'failed' => $results['failed'],
        ]);

        return $results;
    }

    /**
     * Create Product model from PendingProduct data
     */
    protected function createProductFromPending(PendingProduct $pendingProduct): Product
    {
        $product = new Product();

        // Basic fields
        $product->sku = $pendingProduct->sku;
        $product->name = $pendingProduct->name;
        $product->slug = $pendingProduct->slug ?: Str::slug($pendingProduct->name);
        $product->product_type_id = $pendingProduct->product_type_id;

        // Optional fields
        $product->manufacturer = $pendingProduct->manufacturer;
        $product->supplier_code = $pendingProduct->supplier_code;
        $product->ean = $pendingProduct->ean;

        // Dimensions
        $product->weight = $pendingProduct->weight ?? 0;
        $product->height = $pendingProduct->height;
        $product->width = $pendingProduct->width;
        $product->length = $pendingProduct->length;
        $product->tax_rate = $pendingProduct->tax_rate ?? 23.00;

        // Descriptions
        $product->short_description = $pendingProduct->short_description;
        $product->long_description = $pendingProduct->long_description;

        // SEO
        $product->meta_title = $pendingProduct->meta_title;
        $product->meta_description = $pendingProduct->meta_description;
        $product->meta_keywords = $pendingProduct->meta_keywords;

        // Prices
        $product->base_price = $pendingProduct->base_price ?? 0;
        $product->purchase_price = $pendingProduct->purchase_price;

        // Status
        $product->is_active = true;
        $product->is_visible = true;

        // Metadata
        $product->created_by = Auth::id() ?? $pendingProduct->imported_by;

        $product->save();

        return $product;
    }

    /**
     * Assign categories to product
     */
    protected function assignCategories(Product $product, PendingProduct $pendingProduct): void
    {
        $categoryIds = $pendingProduct->category_ids ?? [];

        if (empty($categoryIds)) {
            return;
        }

        // Sync categories through relationship
        $product->categories()->sync($categoryIds);

        // Set primary category (deepest level)
        $deepestCategory = Category::whereIn('id', $categoryIds)
            ->orderBy('level', 'desc')
            ->first();

        if ($deepestCategory) {
            $product->update(['primary_category_id' => $deepestCategory->id]);
        }
    }

    /**
     * Create ProductShopData entries for each shop
     */
    protected function createShopData(Product $product, PendingProduct $pendingProduct): void
    {
        $shopIds = $pendingProduct->shop_ids ?? [];
        $shopCategories = $pendingProduct->shop_categories ?? [];

        foreach ($shopIds as $shopId) {
            // Get shop-specific categories if defined, otherwise use global
            $categories = $shopCategories[$shopId] ?? $pendingProduct->category_ids ?? [];

            ProductShopData::updateOrCreate(
                [
                    'product_id' => $product->id,
                    'shop_id' => $shopId,
                ],
                [
                    'is_active' => true,
                    'is_visible' => true,
                    'category_ids' => $categories,
                    'price' => $pendingProduct->base_price,
                    'sync_status' => 'pending',
                ]
            );
        }
    }

    /**
     * Handle media files (move from temp to permanent storage)
     */
    protected function handleMedia(Product $product, PendingProduct $pendingProduct): void
    {
        $tempPaths = $pendingProduct->temp_media_paths ?? [];
        $primaryIndex = $pendingProduct->primary_media_index ?? 0;

        if (empty($tempPaths)) {
            return;
        }

        // TODO: Implement media migration from temp to permanent storage
        // This will depend on your Media system implementation
        // For now, we log it for later implementation

        Log::info('ProductPublicationService: Media migration needed', [
            'product_id' => $product->id,
            'temp_paths' => $tempPaths,
            'primary_index' => $primaryIndex,
        ]);
    }

    /**
     * Create audit trail record
     */
    protected function createPublishHistory(PendingProduct $pendingProduct, Product $product): void
    {
        PublishHistory::create([
            'pending_product_id' => $pendingProduct->id,
            'product_id' => $product->id,
            'published_by' => Auth::id() ?? $pendingProduct->imported_by,
            'published_at' => now(),
            'sku_snapshot' => $pendingProduct->sku,
            'name_snapshot' => $pendingProduct->name,
            'published_shops' => $pendingProduct->shop_ids ?? [],
            'published_categories' => $pendingProduct->category_ids ?? [],
            'sync_status' => PublishHistory::SYNC_PENDING,
            'publish_mode' => PublishHistory::MODE_SINGLE,
        ]);
    }

    /**
     * Dispatch PrestaShop sync jobs for each shop
     */
    protected function dispatchSyncJobs(Product $product, array $shopIds): void
    {
        if (empty($shopIds)) {
            return;
        }

        $jobIds = [];

        foreach ($shopIds as $shopId) {
            try {
                // Dispatch sync job
                SyncProductToPrestaShop::dispatch($product->id, $shopId)
                    ->onQueue('prestashop-sync');

                $jobIds[] = [
                    'shop_id' => $shopId,
                    'dispatched_at' => now()->toIso8601String(),
                ];
            } catch (\Exception $e) {
                Log::error('ProductPublicationService: Failed to dispatch sync job', [
                    'product_id' => $product->id,
                    'shop_id' => $shopId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Update publish history with dispatched jobs
        $history = PublishHistory::where('product_id', $product->id)
            ->latest()
            ->first();

        if ($history) {
            $history->update([
                'sync_jobs_dispatched' => $jobIds,
                'sync_status' => PublishHistory::SYNC_IN_PROGRESS,
            ]);
        }

        Log::info('ProductPublicationService: Sync jobs dispatched', [
            'product_id' => $product->id,
            'shops' => $shopIds,
            'jobs_count' => count($jobIds),
        ]);
    }

    /**
     * Get publication statistics for a batch
     */
    public function getBatchStats(string $batchId): array
    {
        return PublishHistory::getBatchStats($batchId);
    }

    /**
     * Validate multiple products and return summary
     */
    public function validateBatch(array $pendingProductIds): array
    {
        $results = [
            'total' => count($pendingProductIds),
            'valid' => 0,
            'invalid' => 0,
            'details' => [],
        ];

        foreach ($pendingProductIds as $id) {
            $pendingProduct = PendingProduct::find($id);

            if (!$pendingProduct) {
                $results['invalid']++;
                $results['details'][$id] = [
                    'valid' => false,
                    'errors' => ['Produkt nie istnieje'],
                ];
                continue;
            }

            $validation = $this->validateForPublication($pendingProduct);

            if ($validation['valid']) {
                $results['valid']++;
            } else {
                $results['invalid']++;
            }

            $results['details'][$id] = [
                'valid' => $validation['valid'],
                'sku' => $pendingProduct->sku,
                'errors' => $validation['errors'],
            ];
        }

        return $results;
    }
}

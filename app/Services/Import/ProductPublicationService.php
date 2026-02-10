<?php

namespace App\Services\Import;

use App\Models\PendingProduct;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\PriceGroup;
use App\Models\PublishHistory;
use App\Models\Category;
use App\Models\ProductShopData;
use App\Models\PrestaShopShop;
use App\Services\Import\PublicationTargetService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\Media;

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

                // 2. Create price entries from price_data
                $this->createPriceEntries($product, $pendingProduct);

                // 3. Assign categories
                $this->assignCategories($product, $pendingProduct);

                // 4. Create shop data entries
                $this->createShopData($product, $pendingProduct);

                // 5. Handle media (if any)
                $this->handleMedia($product, $pendingProduct);

                // 6. Mark pending product as published
                $pendingProduct->markAsPublished($product);

                // 7. Create publish history record
                $this->createPublishHistory($pendingProduct, $product);

                // 8. Dispatch sync jobs if requested (uses PublicationTargetService)
                if ($dispatchSyncJobs) {
                    $this->dispatchAllSyncJobs($product, $pendingProduct);
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
        // Check for soft-deleted product with same SKU (re-publication scenario)
        $existing = Product::withTrashed()->where('sku', $pendingProduct->sku)->first();
        if ($existing) {
            if ($existing->trashed()) {
                $existing->restore();
                Log::info('Restored soft-deleted product for re-publication', [
                    'product_id' => $existing->id,
                    'sku' => $existing->sku,
                ]);
            }
            $product = $existing;
        } else {
            $product = new Product();
        }

        // Basic fields
        $product->sku = $pendingProduct->sku;
        $product->name = $pendingProduct->name;
        $baseSlug = $pendingProduct->slug ?: Str::slug($pendingProduct->name);
        $slug = $baseSlug;
        $counter = 2;
        while (Product::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter++;
        }
        $product->slug = $slug;
        $product->product_type_id = $pendingProduct->product_type_id;

        // Optional fields
        $product->manufacturer = $pendingProduct->manufacturer;
        $product->supplier_code = $pendingProduct->supplier_code;
        $product->ean = $pendingProduct->ean;

        // FAZA 9.4: New product fields from import redesign
        $product->cn_code = $pendingProduct->cn_code;
        $product->material = $pendingProduct->material;
        $product->defect_symbol = $pendingProduct->defect_symbol;
        $product->application = $pendingProduct->application;
        $product->split_payment = $pendingProduct->split_payment ?? false;
        $product->shop_internet = $pendingProduct->shop_internet ?? false;
        $product->is_variant_master = $pendingProduct->is_variant_master ?? false;

        // Relationships
        $product->manufacturer_id = $pendingProduct->manufacturer_id;
        $product->supplier_id = $pendingProduct->supplier_id;
        $product->importer_id = $pendingProduct->importer_id;

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

        // Status
        $product->is_active = true;

        $product->save();

        return $product;
    }

    /**
     * Create ProductPrice entries from PendingProduct price_data
     *
     * FAZA 9.4: Maps price_data JSON to ProductPrice per group.
     */
    protected function createPriceEntries(Product $product, PendingProduct $pendingProduct): void
    {
        $priceData = $pendingProduct->price_data ?? [];
        $groups = $priceData['groups'] ?? [];

        if (empty($groups)) {
            return;
        }

        $taxRate = $pendingProduct->tax_rate ?? $product->tax_rate ?? 23.00;

        foreach ($groups as $groupId => $prices) {
            $netPrice = $prices['net'] ?? null;
            $grossPrice = $prices['gross'] ?? null;

            if ($netPrice === null && $grossPrice === null) {
                continue;
            }

            // Verify price group exists
            $priceGroup = PriceGroup::find($groupId);
            if (!$priceGroup) {
                continue;
            }

            // Auto-calculate missing price (DB constraint: price_gross >= price_net)
            $net = (float) ($netPrice ?? 0);
            $gross = (float) ($grossPrice ?? 0);
            if ($net > 0 && $gross <= 0) {
                $gross = round($net * (1 + $taxRate / 100), 2);
            } elseif ($gross > 0 && $net <= 0) {
                $net = round($gross / (1 + $taxRate / 100), 2);
            }

            ProductPrice::updateOrCreate(
                [
                    'product_id' => $product->id,
                    'price_group_id' => $groupId,
                ],
                [
                    'price_net' => $net,
                    'price_gross' => $gross,
                ]
            );
        }

        Log::debug('ProductPublicationService: Price entries created', [
            'product_id' => $product->id,
            'groups_count' => count($groups),
        ]);
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
    }

    /**
     * Create ProductShopData entries for each shop
     */
    protected function createShopData(Product $product, PendingProduct $pendingProduct): void
    {
        $shopIds = $pendingProduct->shop_ids ?? [];
        $shopCategories = $pendingProduct->shop_categories ?? [];

        foreach ($shopIds as $shopId) {
            // Get shop-specific categories if defined, otherwise use global PPM categories
            $hasShopSpecificCategories = !empty($shopCategories[$shopId]);
            $categoryIds = $shopCategories[$shopId] ?? $pendingProduct->category_ids ?? [];

            // FIX 2026-02-10: Mark source correctly
            // shop_categories contains PrestaShop category IDs (from PS category picker)
            // category_ids contains PPM internal category IDs
            // ProductTransformer needs to know which type to handle correctly
            $categorySource = $hasShopSpecificCategories ? 'prestashop_direct' : 'import';

            // Build category_mappings structure for CategoryMappingsCast
            $intCategoryIds = array_values(array_map('intval', $categoryIds));
            // Identity mapping: each ID maps to itself (for prestashop_direct, IDs ARE PS IDs)
            $mappings = [];
            foreach ($intCategoryIds as $catId) {
                $mappings[$catId] = $catId;
            }

            $categoryMappings = [
                'ui' => [
                    'selected' => $intCategoryIds,
                    'primary' => !empty($intCategoryIds) ? end($intCategoryIds) : null,
                ],
                'mappings' => $mappings,
                'metadata' => [
                    'last_updated' => now()->format('Y-m-d\TH:i:sP'),
                    'source' => $categorySource,
                ],
            ];

            ProductShopData::updateOrCreate(
                [
                    'product_id' => $product->id,
                    'shop_id' => $shopId,
                ],
                [
                    'is_active' => true,
                    'is_published' => true,
                    'category_mappings' => $categoryMappings,
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
        $rawPaths = $pendingProduct->temp_media_paths ?? [];
        $primaryIndex = $pendingProduct->primary_media_index ?? 0;

        // Handle nested format: {"images": [...], "source": "...", "updated_at": "..."}
        $images = isset($rawPaths['images']) ? $rawPaths['images'] : $rawPaths;

        if (empty($images) || !is_array($images)) {
            return;
        }

        $disk = Storage::disk('public');
        $migrated = 0;

        foreach ($images as $index => $item) {
            $sourcePath = is_array($item) ? ($item['path'] ?? null) : $item;
            if (!$sourcePath || !$disk->exists($sourcePath)) {
                Log::warning('ProductPublicationService: Media file not found, skipping', [
                    'product_id' => $product->id,
                    'source_path' => $sourcePath,
                    'index' => $index,
                ]);
                continue;
            }

            try {
                $fileName = basename($sourcePath);
                $newPath = 'products/' . $product->id . '/' . $fileName;
                $disk->copy($sourcePath, $newPath);

                $isCover = is_array($item) ? ($item['is_cover'] ?? false) : false;
                $position = is_array($item) ? ($item['position'] ?? $index) : $index;
                $originalName = is_array($item)
                    ? ($item['original_name'] ?? $item['filename'] ?? $fileName)
                    : $fileName;

                Media::create([
                    'mediable_type' => Product::class,
                    'mediable_id' => $product->id,
                    'file_path' => $newPath,
                    'file_name' => $fileName,
                    'original_name' => $originalName,
                    'file_size' => $disk->size($newPath),
                    'mime_type' => $disk->mimeType($newPath),
                    'context' => Media::CONTEXT_PRODUCT_GALLERY,
                    'sort_order' => $position,
                    'is_primary' => $isCover || $index === $primaryIndex,
                    'sync_status' => 'pending',
                ]);

                $migrated++;
            } catch (\Throwable $e) {
                Log::error('ProductPublicationService: Media migration failed for file', [
                    'product_id' => $product->id,
                    'source_path' => $sourcePath,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('ProductPublicationService: Media migration completed', [
            'product_id' => $product->id,
            'total' => count($images),
            'migrated' => $migrated,
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
     * Dispatch all sync jobs (PrestaShop + ERP) via PublicationTargetService.
     * Uses the SAME job classes and dispatch patterns as ProductForm.
     *
     * FIX 2026-02-10: Replaces old dispatchSyncJobs() that:
     * - Used wrong queue name 'prestashop-sync' (not processed by CRON)
     * - Didn't pass userId to SyncProductToPrestaShop
     * - Didn't dispatch ERP sync jobs at all
     */
    protected function dispatchAllSyncJobs(Product $product, PendingProduct $pendingProduct): void
    {
        $targetService = app(PublicationTargetService::class);

        // Resolve targets from publication_targets (supports both new and legacy format)
        $resolvedTargets = $targetService->resolveTargets($pendingProduct->publication_targets);

        // Also include shops from shop_ids if not already in resolved targets
        $shopIdsFromTargets = $resolvedTargets['prestashop_shop_ids'] ?? [];
        $shopIdsFromPending = $pendingProduct->shop_ids ?? [];
        $allShopIds = array_unique(array_merge($shopIdsFromTargets, $shopIdsFromPending));
        $resolvedTargets['prestashop_shop_ids'] = array_values($allShopIds);

        if (empty($resolvedTargets['prestashop_shop_ids']) && empty($resolvedTargets['erp_connection_ids'])) {
            Log::warning('ProductPublicationService: No sync targets resolved', [
                'product_id' => $product->id,
                'publication_targets' => $pendingProduct->publication_targets,
            ]);
            return;
        }

        Log::info('ProductPublicationService: Dispatching sync jobs via PublicationTargetService', [
            'product_id' => $product->id,
            'prestashop_shops' => $resolvedTargets['prestashop_shop_ids'],
            'erp_connections' => $resolvedTargets['erp_connection_ids'],
        ]);

        // Dispatch all jobs (PS on default queue, ERP synchronous)
        $targetService->dispatchSyncJobs($product, $resolvedTargets);

        // Update publish history with dispatched jobs
        $history = PublishHistory::where('product_id', $product->id)
            ->latest()
            ->first();

        if ($history) {
            $jobIds = [];
            foreach ($resolvedTargets['prestashop_shop_ids'] as $shopId) {
                $jobIds[] = ['type' => 'prestashop', 'shop_id' => $shopId, 'dispatched_at' => now()->toIso8601String()];
            }
            foreach ($resolvedTargets['erp_connection_ids'] as $connId) {
                $jobIds[] = ['type' => 'erp', 'connection_id' => $connId, 'dispatched_at' => now()->toIso8601String()];
            }

            $history->update([
                'sync_jobs_dispatched' => $jobIds,
                'sync_status' => PublishHistory::SYNC_IN_PROGRESS,
            ]);
        }
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

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
use App\Models\ProductVariant;
use App\Models\ShopVariant;
use App\Models\VariantAttribute;
use App\Models\VariantImage;

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

                // FIX #9: Auto-detect variant master from variant_data
                // PendingProduct may have variant_data with variants array even if
                // is_variant_master flag wasn't explicitly set during import
                $variantData = $pendingProduct->variant_data ?? [];
                if (!$product->is_variant_master && !empty($variantData['variants'] ?? [])) {
                    $product->is_variant_master = true;
                    $product->save();
                    Log::info('ProductPublicationService: Auto-set is_variant_master from variant_data', [
                        'product_id' => $product->id,
                        'sku' => $product->sku,
                        'variants_count' => count($variantData['variants']),
                    ]);
                }

                // 2b. Create variants from variant_data (if variant master)
                if ($product->is_variant_master) {
                    $this->createVariants($product, $pendingProduct);
                }

                // 3. Assign categories
                $this->assignCategories($product, $pendingProduct);

                // 4. Create shop data entries
                $this->createShopData($product, $pendingProduct);

                // 4b. Create ShopVariant records for variant sync
                // BUG#12 FIX: SyncShopVariantsToPrestaShopJob requires ShopVariant
                // records with sync_status='pending' to create PS combinations
                if ($product->is_variant_master) {
                    $this->createShopVariants($product, $pendingProduct);
                }

                // 5. Handle media (if any)
                $this->handleMedia($product, $pendingProduct);

                // 5b. Assign product images to variants (for PPM UI + PS/ERP sync)
                // FIX BUG#2: Pass PendingProduct to read per-variant image assignments from draft
                if ($product->is_variant_master) {
                    $this->assignVariantImages($product, $pendingProduct);
                }

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
     * Create ProductVariant records from PendingProduct variant_data.
     *
     * FIX BUG#5/7: variant_data was stored on PendingProduct but never materialized
     * as ProductVariant records during publication. This caused is_variant_master=true
     * products to have zero variants, so Baselinker sync skipped variant creation.
     *
     * variant_data structure:
     * {
     *   "variants": [
     *     {"sku_suffix": "-RED-XL", "name": "Czerwony XL", "attributes": [
     *       {"attribute_type_id": 1, "value_id": 5, "value": "Czerwony", "color_hex": "#FF0000"},
     *       {"attribute_type_id": 2, "value_id": 12, "value": "XL"},
     *     ]},
     *   ],
     *   "attribute_types_used": [1, 2]
     * }
     */
    protected function createVariants(Product $product, PendingProduct $pendingProduct): void
    {
        $variantData = $pendingProduct->variant_data ?? [];
        $variants = $variantData['variants'] ?? [];

        if (empty($variants)) {
            Log::info('ProductPublicationService: is_variant_master=true but no variant_data', [
                'product_id' => $product->id,
                'sku' => $product->sku,
            ]);
            return;
        }

        $position = 0;

        foreach ($variants as $index => $variantInfo) {
            $skuSuffix = $variantInfo['sku_suffix'] ?? '-V' . ($index + 1);
            $variantSku = $product->sku . $skuSuffix;
            $variantName = $variantInfo['name'] ?? $product->name . ' ' . $skuSuffix;

            // Create or restore variant (handle re-publication)
            $existing = ProductVariant::withTrashed()
                ->where('product_id', $product->id)
                ->where('sku', $variantSku)
                ->first();

            if ($existing) {
                if ($existing->trashed()) {
                    $existing->restore();
                }
                $variant = $existing;
                $variant->update([
                    'name' => $variantName,
                    'is_active' => true,
                    'is_default' => $index === 0,
                    'position' => $position++,
                ]);
            } else {
                $variant = ProductVariant::create([
                    'product_id' => $product->id,
                    'sku' => $variantSku,
                    'name' => $variantName,
                    'is_active' => true,
                    'is_default' => $index === 0,
                    'position' => $position++,
                ]);
            }

            // Create variant attributes (skip if FK references don't exist)
            $attributes = $variantInfo['attributes'] ?? [];
            foreach ($attributes as $attr) {
                if (!empty($attr['attribute_type_id']) && !empty($attr['value_id'])) {
                    // Verify FK references exist before insert
                    $typeExists = DB::table('attribute_types')->where('id', $attr['attribute_type_id'])->exists();
                    if (!$typeExists) {
                        Log::warning('ProductPublicationService: Skipping variant attribute - type not found', [
                            'attribute_type_id' => $attr['attribute_type_id'],
                            'variant_id' => $variant->id,
                        ]);
                        continue;
                    }
                    VariantAttribute::updateOrCreate(
                        [
                            'variant_id' => $variant->id,
                            'attribute_type_id' => $attr['attribute_type_id'],
                        ],
                        [
                            'value_id' => $attr['value_id'],
                        ]
                    );
                }
            }
        }

        Log::info('ProductPublicationService: Variants created', [
            'product_id' => $product->id,
            'sku' => $product->sku,
            'variants_count' => count($variants),
        ]);
    }

    /**
     * Create ShopVariant records for each shop/variant combination.
     *
     * BUG#12 FIX v2: Must use operation_type='ADD' with populated variant_data
     * so SyncShopVariantsToPrestaShopJob::handleAddOperation() creates actual
     * PrestaShop combinations via API. Previous INHERIT just marked as synced
     * without creating any ps_product_attribute records.
     */
    protected function createShopVariants(Product $product, PendingProduct $pendingProduct): void
    {
        // FIX BUG#1: Use publication_targets as source of truth, fallback to legacy shop_ids
        $targets = $pendingProduct->publication_targets ?? [];
        $shopIds = !empty($targets['prestashop_shops'])
            ? $targets['prestashop_shops']
            : ($pendingProduct->shop_ids ?? []);
        $variants = $product->variants()->with('attributes')->get();

        if ($variants->isEmpty() || empty($shopIds)) {
            return;
        }

        $created = 0;
        foreach ($shopIds as $shopId) {
            foreach ($variants as $variant) {
                $variantData = [
                    'sku' => $variant->sku,
                    'name' => $variant->name,
                    'is_default' => (bool) $variant->is_default,
                    'price_impact' => 0,
                    'weight_impact' => 0,
                    'minimal_quantity' => 1,
                    'attributes' => $variant->attributes->map(fn($a) => [
                        'attribute_type_id' => $a->attribute_type_id,
                        'value_id' => $a->value_id,
                    ])->toArray(),
                ];

                ShopVariant::updateOrCreate(
                    [
                        'shop_id' => $shopId,
                        'product_id' => $product->id,
                        'variant_id' => $variant->id,
                    ],
                    [
                        'operation_type' => 'ADD',
                        'sync_status' => 'pending',
                        'variant_data' => $variantData,
                    ]
                );
                $created++;
            }
        }

        Log::info('ProductPublicationService: ShopVariants created for PS sync', [
            'product_id' => $product->id,
            'shops_count' => count($shopIds),
            'variants_count' => $variants->count(),
            'shop_variants_created' => $created,
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
        // FIX BUG#1: Use publication_targets as source of truth, fallback to legacy shop_ids
        $targets = $pendingProduct->publication_targets ?? [];
        $shopIds = !empty($targets['prestashop_shops'])
            ? $targets['prestashop_shops']
            : ($pendingProduct->shop_ids ?? []);
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
                    'name' => $product->name,
                    'short_description' => $product->short_description,
                    'long_description' => $product->long_description,
                    'meta_title' => $product->meta_title,
                    'meta_description' => $product->meta_description,
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
     * Assign product images to variants based on draft assignments.
     *
     * FIX BUG#2: Reads variant_sku from PendingProduct.temp_media_paths
     * to assign SPECIFIC images to SPECIFIC variants (not all-to-all).
     *
     * Logic:
     * - Images with variant_sku -> assigned ONLY to matching variant
     * - Images without variant_sku (null) -> assigned to ALL variants (shared)
     * - No assignments in draft -> fallback to all-to-all (backward compat)
     */
    protected function assignVariantImages(Product $product, ?PendingProduct $pendingProduct = null): void
    {
        $variants = $product->variants()->get();
        if ($variants->isEmpty()) {
            return;
        }

        $mediaRecords = Media::where('mediable_type', Product::class)
            ->where('mediable_id', $product->id)
            ->orderBy('is_primary', 'desc')
            ->orderBy('sort_order', 'asc')
            ->get();

        if ($mediaRecords->isEmpty()) {
            Log::debug('ProductPublicationService: No media to assign to variants', [
                'product_id' => $product->id,
            ]);
            return;
        }

        // Build filename -> variant_sku mapping from draft
        $fileVariantMap = $this->buildFileVariantMap($pendingProduct);
        $hasAnyAssignment = !empty(array_filter($fileVariantMap));

        // Build variant_sku -> cover filename mapping from draft
        $variantCoverFileMap = $this->buildVariantCoverMap($pendingProduct);

        $variantImagesCreated = 0;
        $variantMediaMap = []; // variant_id -> [media_id, ...]

        foreach ($variants as $variant) {
            if ($variant->images()->count() > 0) {
                continue;
            }

            $variantMediaIds = [];
            $position = 0;

            foreach ($mediaRecords as $media) {
                $fileName = basename($media->file_path);
                $assignedSku = $fileVariantMap[$fileName] ?? null;

                // Determine if this image should be assigned to this variant
                $shouldAssign = false;
                if (!$hasAnyAssignment) {
                    // Fallback: no assignments in draft -> all images to all variants
                    $shouldAssign = true;
                } elseif ($assignedSku === null) {
                    // Shared image (no variant_sku) -> assign to all variants
                    $shouldAssign = true;
                } elseif (str_ends_with($variant->sku, $assignedSku)) {
                    // Specific assignment: variant SKU ends with assigned suffix
                    $shouldAssign = true;
                }

                if ($shouldAssign) {
                    // Determine cover: use draft per-variant cover, fallback to first image
                    $isCoverFromDraft = false;
                    $hasDraftCover = false;
                    if (!empty($variantCoverFileMap)) {
                        $variantSuffix = str_replace($product->sku, '', $variant->sku);
                        $coverFileName = $variantCoverFileMap[$variantSuffix] ?? null;
                        $hasDraftCover = ($coverFileName !== null);
                        $isCoverFromDraft = ($coverFileName !== null && $fileName === $coverFileName);
                    }

                    // If draft has a cover for this variant -> ONLY that image is cover
                    // If no draft cover -> fallback to first image (position === 0)
                    $isCover = $hasDraftCover ? $isCoverFromDraft : ($position === 0);

                    VariantImage::create([
                        'variant_id' => $variant->id,
                        'image_path' => $media->file_path,
                        'image_url' => null,
                        'is_cover' => $isCover,
                        'position' => $position,
                    ]);

                    $variantMediaIds[] = $media->id;
                    $variantImagesCreated++;
                    $position++;
                }
            }

            $variantMediaMap[$variant->id] = $variantMediaIds;
        }

        // Update ShopVariant records with PER-VARIANT media_ids
        $updatedShopVariants = 0;
        ShopVariant::where('product_id', $product->id)
            ->where('sync_status', 'pending')
            ->each(function ($sv) use ($variantMediaMap, &$updatedShopVariants) {
                $mediaIds = $variantMediaMap[$sv->variant_id] ?? [];
                if (empty($mediaIds)) {
                    return;
                }
                $variantData = $sv->variant_data ?? [];
                $variantData['media_ids'] = $mediaIds;
                $sv->update(['variant_data' => $variantData]);
                $updatedShopVariants++;
            });

        Log::info('ProductPublicationService: Variant images assigned', [
            'product_id' => $product->id,
            'variants_count' => $variants->count(),
            'media_count' => $mediaRecords->count(),
            'has_draft_assignments' => $hasAnyAssignment,
            'variant_images_created' => $variantImagesCreated,
            'shop_variants_updated' => $updatedShopVariants,
        ]);
    }

    /**
     * Build filename -> variant_sku mapping from PendingProduct draft data.
     *
     * @return array<string, string|null> filename => variant_sku (null = shared)
     */
    protected function buildFileVariantMap(?PendingProduct $pendingProduct): array
    {
        if (!$pendingProduct) {
            return [];
        }

        $rawPaths = $pendingProduct->temp_media_paths ?? [];
        $images = $rawPaths['images'] ?? $rawPaths;

        if (empty($images) || !is_array($images)) {
            return [];
        }

        $map = [];
        foreach ($images as $item) {
            if (!is_array($item)) {
                continue;
            }
            $path = $item['path'] ?? null;
            if (!$path) {
                continue;
            }
            $fileName = basename($path);
            $map[$fileName] = $item['variant_sku'] ?? null;
        }

        return $map;
    }

    /**
     * Build variant_sku -> cover filename mapping from draft per-variant covers.
     *
     * @return array<string, string> variant_sku => cover filename
     */
    protected function buildVariantCoverMap(?PendingProduct $pendingProduct): array
    {
        if (!$pendingProduct) {
            return [];
        }

        $rawPaths = $pendingProduct->temp_media_paths ?? [];
        $variantCovers = $rawPaths['variant_covers'] ?? [];
        $images = $rawPaths['images'] ?? [];

        if (empty($variantCovers) || empty($images)) {
            return [];
        }

        $coverFileMap = [];
        foreach ($variantCovers as $sku => $imageIndex) {
            if (isset($images[$imageIndex]) && !empty($images[$imageIndex]['path'])) {
                $coverFileMap[$sku] = basename($images[$imageIndex]['path']);
            }
        }

        return $coverFileMap;
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
            // FIX BUG#1: Record actual publication targets, not legacy shop_ids
            'published_shops' => !empty(($pendingProduct->publication_targets ?? [])['prestashop_shops'])
                ? $pendingProduct->publication_targets['prestashop_shops']
                : ($pendingProduct->shop_ids ?? []),
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

        // FIX BUG#1: publication_targets is the SOLE source of truth for sync targets
        // Previously merged legacy shop_ids which caused syncing to shops NOT selected by user
        $resolvedTargets = $targetService->resolveTargets($pendingProduct->publication_targets);

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

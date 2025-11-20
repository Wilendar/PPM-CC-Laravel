<?php

namespace App\Jobs\PrestaShop;

use App\Models\PrestaShopShop;
use App\Models\SyncJob;
use App\Models\Product;
use App\Models\ProductShopData;
use App\Models\ProductType;
use App\Services\PrestaShop\PrestaShopClientFactory;
use App\Services\PrestaShop\PrestaShopPriceImporter;
use App\Services\PrestaShop\PrestaShopStockImporter;
use App\Services\PrestaShop\ProductMatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * Import All Products Job
 *
 * ETAP_07 - Task 9.6: Import New Products Feature
 *
 * Imports ALL products from PrestaShop (create new + update existing).
 * Replaces user-triggered "← Import" button behavior.
 *
 * OLD BEHAVIOR: PullProductsFromPrestaShop (only updates existing linked products)
 * NEW BEHAVIOR: ImportAllProductsJob (imports new + updates existing)
 *
 * Architecture:
 * - Fetches products from PrestaShop API (all or by category)
 * - Uses ProductMatcher for SKU-FIRST matching
 * - Creates new products if not found
 * - Updates existing products if found
 * - Links products to shop via product_shop_data
 * - Imports prices via PrestaShopPriceImporter
 * - Imports stock via PrestaShopStockImporter
 * - Tracks progress in sync_jobs table
 *
 * Queue Configuration:
 * - Queue: database (or redis if available)
 * - Timeout: 3600s (1 hour) - configurable via SystemSettings
 * - Tries: 3 attempts with exponential backoff
 * - Batch size: 100 products per run (configurable)
 *
 * Usage (User-triggered):
 * ```php
 * ImportAllProductsJob::dispatch($shop, $categoryId, $onlyNew);
 * ```
 *
 * Usage (Scheduler - automatic sync):
 * ```php
 * // In routes/console.php
 * Schedule::job(new ImportAllProductsJob($shop))->daily();
 * ```
 *
 * @package App\Jobs\PrestaShop
 * @version 1.0
 * @since ETAP_07 - Task 9.6
 */
class ImportAllProductsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public PrestaShopShop $shop;
    public ?int $categoryId;
    public bool $onlyNew;

    public int $tries = 3;
    public int $timeout = 3600; // 1 hour (configurable)

    private int $productsTotal = 0;
    private int $productsImported = 0;
    private int $productsUpdated = 0;
    private int $productsSkipped = 0;
    private array $errors = [];

    /**
     * Create a new job instance.
     *
     * @param PrestaShopShop $shop Shop to import from
     * @param int|null $categoryId Optional category filter
     * @param bool $onlyNew Import only new products (skip existing)
     */
    public function __construct(PrestaShopShop $shop, ?int $categoryId = null, bool $onlyNew = false)
    {
        $this->shop = $shop;
        $this->categoryId = $categoryId;
        $this->onlyNew = $onlyNew;

        // Read timeout from settings (fallback to 600s)
        $this->timeout = config('sync.timeout', 600);
    }

    /**
     * Execute the job.
     *
     * Workflow:
     * 1. Create SyncJob record (status: processing)
     * 2. Fetch products from PrestaShop API
     * 3. Process each product (match → create/update → link → import prices/stock)
     * 4. Update SyncJob record (status: completed/failed)
     *
     * @return void
     * @throws \Exception On critical errors
     */
    public function handle(): void
    {
        Log::info('ImportAllProductsJob: Starting import', [
            'shop_id' => $this->shop->id,
            'shop_name' => $this->shop->name,
            'category_id' => $this->categoryId,
            'only_new' => $this->onlyNew,
        ]);

        // Create SyncJob record
        $syncJob = SyncJob::create([
            'prestashop_shop_id' => $this->shop->id,
            'type' => 'import_new_products',
            'status' => 'processing',
            'started_at' => now(),
            'metadata' => [
                'category_id' => $this->categoryId,
                'only_new' => $this->onlyNew,
                'triggered_by' => 'user', // vs 'scheduler'
            ],
        ]);

        try {
            // Get PrestaShop client
            $client = PrestaShopClientFactory::create($this->shop);

            // Fetch products (all or by category)
            $products = $this->categoryId
                ? $client->getProductsByCategory($this->categoryId)
                : $this->fetchAllProducts($client);

            $this->productsTotal = count($products);

            Log::info('ImportAllProductsJob: Fetched products from PrestaShop', [
                'shop_id' => $this->shop->id,
                'products_count' => $this->productsTotal,
                'category_id' => $this->categoryId,
            ]);

            // Initialize services
            $matcher = app(ProductMatcher::class);
            $priceImporter = app(PrestaShopPriceImporter::class);
            $stockImporter = app(PrestaShopStockImporter::class);

            // Process each product
            foreach ($products as $psProduct) {
                try {
                    $this->processProduct($psProduct, $matcher, $priceImporter, $stockImporter);
                } catch (\Exception $e) {
                    $psId = data_get($psProduct, 'id');
                    Log::error('ImportAllProductsJob: Failed to process product', [
                        'prestashop_product_id' => $psId,
                        'shop_id' => $this->shop->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);

                    $this->errors[] = [
                        'prestashop_product_id' => $psId,
                        'error' => $e->getMessage(),
                    ];

                    $this->productsSkipped++;
                }
            }

            // Complete SyncJob
            $syncJob->update([
                'status' => 'completed',
                'finished_at' => now(),
                'metadata' => array_merge($syncJob->metadata ?? [], [
                    'products_total' => $this->productsTotal,
                    'products_imported' => $this->productsImported,
                    'products_updated' => $this->productsUpdated,
                    'products_skipped' => $this->productsSkipped,
                    'errors' => $this->errors,
                ]),
            ]);

            Log::info('ImportAllProductsJob: Import completed successfully', [
                'shop_id' => $this->shop->id,
                'total' => $this->productsTotal,
                'imported' => $this->productsImported,
                'updated' => $this->productsUpdated,
                'skipped' => $this->productsSkipped,
                'errors_count' => count($this->errors),
            ]);

        } catch (\Exception $e) {
            $syncJob->update([
                'status' => 'failed',
                'finished_at' => now(),
                'error_message' => $e->getMessage(),
                'metadata' => array_merge($syncJob->metadata ?? [], [
                    'products_total' => $this->productsTotal,
                    'products_imported' => $this->productsImported,
                    'products_updated' => $this->productsUpdated,
                    'products_skipped' => $this->productsSkipped,
                    'errors' => $this->errors,
                ]),
            ]);

            Log::error('ImportAllProductsJob: Import failed', [
                'shop_id' => $this->shop->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Fetch all products from PrestaShop (with pagination)
     *
     * PrestaShop API returns products in paginated format.
     * This method fetches all pages and merges results.
     *
     * @param mixed $client PrestaShop client instance
     * @return array All products from all pages
     */
    protected function fetchAllProducts($client): array
    {
        $allProducts = [];
        $limit = 100; // Products per page
        $offset = 0;

        do {
            $response = $client->getProducts([
                'display' => 'full',
                'limit' => "$offset,$limit",
            ]);

            $products = [];
            if (isset($response['products'])) {
                $products = is_array($response['products']) ? $response['products'] : [$response['products']];
            }

            $allProducts = array_merge($allProducts, $products);
            $offset += $limit;

            // Stop if less than limit (last page)
        } while (count($products) >= $limit);

        return $allProducts;
    }

    /**
     * Process single product (match → create/update → link → import)
     *
     * Workflow:
     * 1. Match product by SKU or external_id
     * 2. If not found → create new product
     * 3. If found → update existing product (if not onlyNew)
     * 4. Link product to shop via product_shop_data
     * 5. Import prices via PrestaShopPriceImporter
     * 6. Import stock via PrestaShopStockImporter
     *
     * @param array $psProduct PrestaShop product data
     * @param ProductMatcher $matcher Product matcher service
     * @param PrestaShopPriceImporter $priceImporter Price importer service
     * @param PrestaShopStockImporter $stockImporter Stock importer service
     * @return void
     */
    protected function processProduct(
        array $psProduct,
        ProductMatcher $matcher,
        PrestaShopPriceImporter $priceImporter,
        PrestaShopStockImporter $stockImporter
    ): void {
        $psId = data_get($psProduct, 'id');
        $sku = data_get($psProduct, 'reference');
        $name = $matcher->extractProductName($psProduct);

        Log::debug('ImportAllProductsJob: Processing product', [
            'prestashop_id' => $psId,
            'sku' => $sku,
            'name' => $name,
        ]);

        // Find or create product
        $product = $matcher->findExistingProduct($psProduct, $this->shop);

        if (!$product) {
            // CREATE NEW PRODUCT
            if (!$this->onlyNew) {
                // User wants only new products → skip
                Log::debug('ImportAllProductsJob: Skipping existing product (onlyNew=false)', [
                    'prestashop_id' => $psId,
                ]);
                $this->productsSkipped++;
                return;
            }

            // Generate SKU if empty
            if (!$sku) {
                $sku = $matcher->generateSKU($psProduct, $this->shop);
            }

            // Check for duplicate SKU
            if (!$matcher->isSkuUnique($sku)) {
                Log::warning('ImportAllProductsJob: Duplicate SKU found - skipping', [
                    'sku' => $sku,
                    'prestashop_id' => $psId,
                ]);
                $this->productsSkipped++;
                return;
            }

            // Get default product type
            $productType = ProductType::where('slug', 'general')->first()
                ?? ProductType::first();

            if (!$productType) {
                Log::error('ImportAllProductsJob: No product type found - cannot create product', [
                    'prestashop_id' => $psId,
                ]);
                $this->productsSkipped++;
                return;
            }

            // Create product
            $product = Product::create([
                'sku' => $sku,
                'name' => $name,
                'product_type_id' => $productType->id,
                'is_active' => (bool) data_get($psProduct, 'active', 1),
                'tax_rate' => 23.00, // Default VAT (Poland)
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->productsImported++;

            Log::info('ImportAllProductsJob: New product created', [
                'product_id' => $product->id,
                'sku' => $sku,
                'name' => $name,
                'prestashop_id' => $psId,
            ]);
        } else {
            // EXISTING PRODUCT
            if ($this->onlyNew) {
                // User wants only new products → skip existing
                Log::debug('ImportAllProductsJob: Skipping existing product (onlyNew=true)', [
                    'product_id' => $product->id,
                    'sku' => $product->sku,
                ]);
                $this->productsSkipped++;
                return;
            }

            // Check if already linked to this shop
            if ($matcher->isAlreadyLinked($product, $this->shop)) {
                // Update existing link
                $shopData = $product->shopData()->where('shop_id', $this->shop->id)->first();
                $this->updateProductShopData($shopData, $psProduct, $name);
                $this->productsUpdated++;

                Log::debug('ImportAllProductsJob: Product already linked - updated shop data', [
                    'product_id' => $product->id,
                    'sku' => $product->sku,
                    'shop_id' => $this->shop->id,
                ]);

                // Continue to update prices/stock
            } else {
                // Not linked yet → will create link below
                $this->productsUpdated++;

                Log::debug('ImportAllProductsJob: Product found but not linked - creating link', [
                    'product_id' => $product->id,
                    'sku' => $product->sku,
                    'shop_id' => $this->shop->id,
                ]);
            }
        }

        // Create or update ProductShopData link
        $shopData = ProductShopData::updateOrCreate(
            [
                'product_id' => $product->id,
                'shop_id' => $this->shop->id,
            ],
            [
                'prestashop_product_id' => $psId,
                'name' => $name,
                'slug' => data_get($psProduct, 'link_rewrite.0.value') ?? data_get($psProduct, 'link_rewrite'),
                'short_description' => data_get($psProduct, 'description_short.0.value'),
                'description' => data_get($psProduct, 'description.0.value'),
                'last_pulled_at' => now(),
                'sync_status' => 'synced',
            ]
        );

        Log::debug('ImportAllProductsJob: Product linked to shop', [
            'product_id' => $product->id,
            'shop_id' => $this->shop->id,
            'prestashop_id' => $psId,
        ]);

        // Import prices (handle 404 gracefully)
        try {
            $priceImporter->importPricesForProduct($product, $this->shop);
        } catch (\App\Exceptions\PrestaShopAPIException $e) {
            if ($e->isNotFound()) {
                Log::warning('ImportAllProductsJob: Price import skipped (404 - product not found)', [
                    'product_id' => $product->id,
                    'prestashop_id' => $psId,
                ]);
            } else {
                throw $e; // Re-throw non-404 errors
            }
        }

        // Import stock (handle 404 gracefully)
        try {
            $stockImporter->importStockForProduct($product, $this->shop);
        } catch (\App\Exceptions\PrestaShopAPIException $e) {
            if ($e->isNotFound()) {
                Log::warning('ImportAllProductsJob: Stock import skipped (404 - product not found)', [
                    'product_id' => $product->id,
                    'prestashop_id' => $psId,
                ]);
            } else {
                throw $e; // Re-throw non-404 errors
            }
        }

        Log::debug('ImportAllProductsJob: Product processed successfully', [
            'product_id' => $product->id,
            'prestashop_id' => $psId,
        ]);
    }

    /**
     * Update existing ProductShopData record
     *
     * Updates shop-specific data (name, descriptions, sync status).
     * Preserves existing prestashop_product_id and timestamps.
     *
     * @param ProductShopData $shopData Existing shop data record
     * @param array $psProduct PrestaShop product data
     * @param string $name Extracted product name
     * @return void
     */
    protected function updateProductShopData(ProductShopData $shopData, array $psProduct, string $name): void
    {
        $shopData->update([
            'name' => $name,
            'slug' => data_get($psProduct, 'link_rewrite.0.value') ?? data_get($psProduct, 'link_rewrite'),
            'short_description' => data_get($psProduct, 'description_short.0.value'),
            'description' => data_get($psProduct, 'description.0.value'),
            'last_pulled_at' => now(),
            'sync_status' => 'synced',
        ]);
    }

    /**
     * Calculate exponential backoff delay for retries
     *
     * Retry delays: 30s, 60s, 300s (5 minutes)
     *
     * @return array Backoff delays in seconds
     */
    public function backoff(): array
    {
        return [30, 60, 300];
    }

    /**
     * Handle job failure
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ImportAllProductsJob: Job failed permanently', [
            'shop_id' => $this->shop->id,
            'shop_name' => $this->shop->name,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Update SyncJob if exists
        $syncJob = SyncJob::where('prestashop_shop_id', $this->shop->id)
            ->where('type', 'import_new_products')
            ->where('status', 'processing')
            ->latest()
            ->first();

        if ($syncJob) {
            $syncJob->update([
                'status' => 'failed',
                'finished_at' => now(),
                'error_message' => $exception->getMessage(),
            ]);
        }
    }
}

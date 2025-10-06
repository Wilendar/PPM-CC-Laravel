<?php

namespace App\Jobs\PrestaShop;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\PrestaShopShop;
use App\Models\Product;
use App\Services\PrestaShop\PrestaShopClientFactory;

/**
 * BulkImportProducts Job - Import products from PrestaShop to PPM-CC-Laravel
 *
 * ETAP_07 FAZA 3: PrestaShop Product Import
 *
 * Supports three import modes:
 * - 'all': Import ALL products from shop
 * - 'category': Import products from specific category (with optional subcategories)
 * - 'individual': Import specific products by ID list
 *
 * Features:
 * - Background processing via Laravel Queue
 * - Automatic SKU conflict detection
 * - Progress tracking and error handling
 * - Notification on completion
 *
 * Usage:
 * ```
 * BulkImportProducts::dispatch($shop, 'all');
 * BulkImportProducts::dispatch($shop, 'category', ['category_id' => 5, 'include_subcategories' => true]);
 * BulkImportProducts::dispatch($shop, 'individual', ['product_ids' => [1, 2, 3]]);
 * ```
 *
 * @package App\Jobs\PrestaShop
 * @version 1.0
 * @since ETAP_07_FAZA_3
 */
class BulkImportProducts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The PrestaShop shop instance
     *
     * @var PrestaShopShop
     */
    protected PrestaShopShop $shop;

    /**
     * Import mode: all|category|individual
     *
     * @var string
     */
    protected string $mode;

    /**
     * Import options (category_id, include_subcategories, product_ids)
     *
     * @var array
     */
    protected array $options;

    /**
     * Number of tries for the job
     *
     * @var int
     */
    public int $tries = 3;

    /**
     * Timeout for the job (15 minutes)
     *
     * @var int
     */
    public int $timeout = 900;

    /**
     * Create a new job instance
     *
     * @param PrestaShopShop $shop
     * @param string $mode all|category|individual
     * @param array $options
     */
    public function __construct(PrestaShopShop $shop, string $mode = 'all', array $options = [])
    {
        $this->shop = $shop;
        $this->mode = $mode;
        $this->options = $options;
    }

    /**
     * Execute the job
     */
    public function handle(): void
    {
        Log::info('BulkImportProducts job started', [
            'shop_id' => $this->shop->id,
            'shop_name' => $this->shop->name,
            'mode' => $this->mode,
            'options' => $this->options,
        ]);

        try {
            $client = app(PrestaShopClientFactory::class)->create($this->shop);

            $productsToImport = $this->getProductsToImport($client);

            $imported = 0;
            $skipped = 0;
            $errors = [];

            foreach ($productsToImport as $psProduct) {
                try {
                    $result = $this->importProduct($psProduct);

                    if ($result === 'imported') {
                        $imported++;
                    } else {
                        $skipped++;
                    }

                } catch (\Exception $e) {
                    $errors[] = [
                        'product_id' => $psProduct['id'] ?? 'N/A',
                        'sku' => $psProduct['reference'] ?? 'N/A',
                        'error' => $e->getMessage(),
                    ];

                    Log::error('Failed to import product', [
                        'shop_id' => $this->shop->id,
                        'product_id' => $psProduct['id'] ?? null,
                        'sku' => $psProduct['reference'] ?? null,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            Log::info('BulkImportProducts job completed', [
                'shop_id' => $this->shop->id,
                'total' => count($productsToImport),
                'imported' => $imported,
                'skipped' => $skipped,
                'errors' => count($errors),
            ]);

            // TODO: Send notification to user about completion
            // TODO: Store import results in database table for user viewing

        } catch (\Exception $e) {
            Log::error('BulkImportProducts job failed', [
                'shop_id' => $this->shop->id,
                'mode' => $this->mode,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get products to import based on mode
     *
     * @param mixed $client PrestaShop client instance
     * @return array
     */
    protected function getProductsToImport($client): array
    {
        switch ($this->mode) {
            case 'all':
                return $this->getAllProducts($client);

            case 'category':
                return $this->getProductsByCategory($client);

            case 'individual':
                return $this->getProductsByIds($client);

            default:
                throw new \InvalidArgumentException("Invalid import mode: {$this->mode}");
        }
    }

    /**
     * Get ALL products from shop
     *
     * @param mixed $client
     * @return array
     */
    protected function getAllProducts($client): array
    {
        $response = $client->getProducts(['display' => 'full']);

        if (isset($response['products']) && is_array($response['products'])) {
            return $response['products'];
        } elseif (isset($response[0])) {
            return $response;
        }

        return [];
    }

    /**
     * Get products by category
     *
     * @param mixed $client
     * @return array
     */
    protected function getProductsByCategory($client): array
    {
        $categoryId = $this->options['category_id'] ?? null;

        if (!$categoryId) {
            throw new \InvalidArgumentException('category_id is required for category mode');
        }

        // TODO: Implement category filtering
        // PrestaShop API: filter[id_category_default]={categoryId}
        // If include_subcategories: need to get all child category IDs first

        $params = [
            'display' => 'full',
            'filter[id_category_default]' => $categoryId,
        ];

        $response = $client->getProducts($params);

        if (isset($response['products']) && is_array($response['products'])) {
            return $response['products'];
        } elseif (isset($response[0])) {
            return $response;
        }

        return [];
    }

    /**
     * Get specific products by IDs
     *
     * @param mixed $client
     * @return array
     */
    protected function getProductsByIds($client): array
    {
        $productIds = $this->options['product_ids'] ?? [];

        if (empty($productIds)) {
            throw new \InvalidArgumentException('product_ids array is required for individual mode');
        }

        $products = [];

        foreach ($productIds as $productId) {
            try {
                $product = $client->getProduct($productId);
                if ($product) {
                    $products[] = $product;
                }
            } catch (\Exception $e) {
                Log::warning('Failed to fetch individual product', [
                    'shop_id' => $this->shop->id,
                    'product_id' => $productId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $products;
    }

    /**
     * Import single product from PrestaShop
     *
     * @param array $psProduct
     * @return string 'imported'|'skipped'
     */
    protected function importProduct(array $psProduct): string
    {
        $sku = $psProduct['reference'] ?? null;

        if (!$sku) {
            Log::warning('Product without SKU - skipped', [
                'shop_id' => $this->shop->id,
                'product_id' => $psProduct['id'] ?? null,
            ]);
            return 'skipped';
        }

        // Check if product already exists
        $existingProduct = Product::where('sku', $sku)->first();

        if ($existingProduct) {
            Log::info('Product already exists - skipped', [
                'shop_id' => $this->shop->id,
                'sku' => $sku,
                'prestashop_id' => $psProduct['id'] ?? null,
                'ppm_id' => $existingProduct->id,
            ]);
            return 'skipped';
        }

        // Create new product
        $product = new Product();
        $product->sku = $sku;
        $product->name = $psProduct['name'] ?? 'Imported Product';
        $product->short_description = $psProduct['description_short'] ?? null;
        $product->long_description = $psProduct['description'] ?? null;
        $product->is_active = (bool)($psProduct['active'] ?? true);

        // TODO: Map more fields from PrestaShop product structure
        // - price (from ps_product_price table)
        // - stock (from ps_stock_available table)
        // - images (from ps_image table)
        // - categories (from ps_category_product table)
        // - manufacturer (from ps_manufacturer table)

        $product->save();

        Log::info('Product imported successfully', [
            'shop_id' => $this->shop->id,
            'sku' => $sku,
            'prestashop_id' => $psProduct['id'] ?? null,
            'ppm_id' => $product->id,
        ]);

        return 'imported';
    }

    /**
     * Handle job failure
     *
     * @param \Throwable $exception
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('BulkImportProducts job failed permanently', [
            'shop_id' => $this->shop->id,
            'mode' => $this->mode,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // TODO: Send failure notification to user
    }
}

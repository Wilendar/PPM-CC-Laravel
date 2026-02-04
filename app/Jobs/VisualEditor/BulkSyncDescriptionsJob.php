<?php

declare(strict_types=1);

namespace App\Jobs\VisualEditor;

use App\Models\Product;
use App\Models\ProductDescription;
use App\Models\PrestaShopShop;
use App\Services\JobProgressService;
use App\Services\PrestaShop\PrestaShopClientFactory;
use App\Services\VisualEditor\DescriptionRenderer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Bulk Sync Descriptions to PrestaShop Job.
 *
 * Synchronizes visual editor descriptions for multiple products
 * to PrestaShop. Processes in chunks for memory efficiency.
 *
 * Features:
 * - Chunked processing (20 products per batch)
 * - Progress tracking via JobProgressService
 * - Renders HTML from blocks using DescriptionRenderer
 * - Batch API calls where possible
 *
 * @package App\Jobs\VisualEditor
 * @since ETAP_07f Faza 8.2 - Description Sync Integration
 */
class BulkSyncDescriptionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of products per processing batch.
     */
    private const CHUNK_SIZE = 20;

    /**
     * Number of times job may be attempted.
     */
    public int $tries = 3;

    /**
     * Maximum seconds job can run.
     */
    public int $timeout = 600; // 10 minutes

    /**
     * Create a new job instance.
     *
     * @param array $productIds Product IDs to sync
     * @param int $shopId PrestaShop shop ID
     * @param int $userId User who initiated the sync
     * @param int|null $progressId Pre-created progress ID (optional)
     */
    public function __construct(
        public array $productIds,
        public int $shopId,
        public int $userId,
        public ?int $progressId = null
    ) {
        $this->onQueue('prestashop');
    }

    /**
     * Execute the job.
     */
    public function handle(
        DescriptionRenderer $renderer,
        JobProgressService $progressService
    ): void {
        $totalProducts = count($this->productIds);

        Log::info('BulkSyncDescriptionsJob: rozpoczeto', [
            'shop_id' => $this->shopId,
            'user_id' => $this->userId,
            'total_products' => $totalProducts,
            'progress_id' => $this->progressId,
        ]);

        // Initialize progress tracking
        $progressId = $this->progressId;
        if (!$progressId) {
            $shop = PrestaShopShop::find($this->shopId);
            if ($shop) {
                $progressId = $progressService->createJobProgress(
                    $this->job->getJobId(),
                    $shop,
                    'description_sync',
                    $totalProducts
                );
            }
        } else {
            $progressService->startPendingJob($this->job->getJobId(), $totalProducts);
        }

        try {
            $shop = PrestaShopShop::find($this->shopId);
            if (!$shop) {
                throw new \RuntimeException("Sklep o ID {$this->shopId} nie istnieje");
            }

            $client = PrestaShopClientFactory::create($shop);
            $languageId = $shop->default_language_id ?? 1;

            $processed = 0;
            $successCount = 0;
            $errors = [];

            // Process in chunks
            $chunks = array_chunk($this->productIds, self::CHUNK_SIZE);

            foreach ($chunks as $chunkIndex => $chunkProductIds) {
                $products = Product::whereIn('id', $chunkProductIds)
                    ->with(['shopData.shop:id,name,label_color,label_icon'])
                    ->get();

                foreach ($products as $product) {
                    try {
                        $result = $this->syncProductDescription(
                            $product,
                            $shop,
                            $client,
                            $renderer,
                            $languageId
                        );

                        if ($result) {
                            $successCount++;
                        }
                    } catch (Throwable $e) {
                        $errors[] = [
                            'sku' => $product->sku,
                            'product_id' => $product->id,
                            'error' => $e->getMessage(),
                        ];

                        Log::warning('BulkSyncDescriptionsJob: blad dla produktu', [
                            'product_id' => $product->id,
                            'sku' => $product->sku,
                            'error' => $e->getMessage(),
                        ]);
                    }

                    $processed++;
                }

                // Update progress after each chunk
                if ($progressId) {
                    $progressService->updateProgress($progressId, $processed, $errors);
                }

                Log::debug('BulkSyncDescriptionsJob: chunk przetworzony', [
                    'chunk_index' => $chunkIndex,
                    'processed' => $processed,
                    'total' => $totalProducts,
                    'success' => $successCount,
                    'errors' => count($errors),
                ]);
            }

            // Mark as completed
            if ($progressId) {
                $progressService->markCompleted($progressId, [
                    'total_processed' => $processed,
                    'success_count' => $successCount,
                    'error_count' => count($errors),
                    'shop_name' => $shop->name,
                ]);
            }

            Log::info('BulkSyncDescriptionsJob: zakonczono', [
                'shop_id' => $this->shopId,
                'processed' => $processed,
                'success' => $successCount,
                'errors' => count($errors),
            ]);

        } catch (Throwable $e) {
            if ($progressId) {
                $progressService->markFailed($progressId, $e->getMessage(), [
                    'trace' => $e->getTraceAsString(),
                ]);
            }

            Log::error('BulkSyncDescriptionsJob: blad krytyczny', [
                'shop_id' => $this->shopId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Sync description for a single product.
     *
     * @param Product $product
     * @param PrestaShopShop $shop
     * @param mixed $client PrestaShop API client
     * @param DescriptionRenderer $renderer
     * @param int $languageId
     * @return bool Success status
     */
    private function syncProductDescription(
        Product $product,
        PrestaShopShop $shop,
        $client,
        DescriptionRenderer $renderer,
        int $languageId
    ): bool {
        // Get shop data to find PrestaShop product ID
        $shopData = $product->getShopData($shop->id);
        if (!$shopData || !$shopData->prestashop_id) {
            Log::debug('BulkSyncDescriptionsJob: produkt nie zsynchronizowany', [
                'product_id' => $product->id,
                'sku' => $product->sku,
                'shop_id' => $shop->id,
            ]);
            return false;
        }

        // Get ProductDescription
        $description = ProductDescription::forProduct($product->id)
            ->forShop($shop->id)
            ->first();

        if (!$description || empty($description->blocks)) {
            Log::debug('BulkSyncDescriptionsJob: brak opisu wizualnego', [
                'product_id' => $product->id,
                'sku' => $product->sku,
            ]);
            return false;
        }

        // Render HTML
        $html = $renderer->renderAndCache($description, minify: true);

        if (empty($html)) {
            return false;
        }

        // Build update payload
        $payload = [
            'description' => [
                'language' => [
                    [
                        '@attributes' => ['id' => $languageId],
                        '@value' => $html,
                    ],
                ],
            ],
        ];

        // Update in PrestaShop
        $client->updateProduct($shopData->prestashop_id, $payload);

        Log::debug('BulkSyncDescriptionsJob: opis zsynchronizowany', [
            'product_id' => $product->id,
            'sku' => $product->sku,
            'prestashop_id' => $shopData->prestashop_id,
        ]);

        return true;
    }

    /**
     * Handle permanent failure.
     */
    public function failed(Throwable $exception): void
    {
        Log::error('BulkSyncDescriptionsJob: trwaly blad', [
            'shop_id' => $this->shopId,
            'user_id' => $this->userId,
            'products_count' => count($this->productIds),
            'error' => $exception->getMessage(),
        ]);
    }
}

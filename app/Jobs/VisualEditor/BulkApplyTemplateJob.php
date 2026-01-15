<?php

declare(strict_types=1);

namespace App\Jobs\VisualEditor;

use App\Models\DescriptionTemplate;
use App\Models\Product;
use App\Models\ProductDescription;
use App\Models\PrestaShopShop;
use App\Services\JobProgressService;
use App\Services\VisualEditor\BlockRenderer;
use App\Services\VisualEditor\TemplateVariableService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Bulk Apply Template Job.
 *
 * Applies a description template to multiple products for a specific shop.
 * Uses variable replacement to personalize template content per product.
 *
 * Features:
 * - Chunked processing (50 products per batch)
 * - Progress tracking via JobProgressService
 * - Variable replacement for dynamic content
 * - HTML rendering after block application
 *
 * @package App\Jobs\VisualEditor
 * @since ETAP_07f Faza 6.2 - Bulk Operations
 */
class BulkApplyTemplateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of products per processing batch.
     */
    private const CHUNK_SIZE = 50;

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
     * @param int $templateId Template to apply
     * @param array $productIds Products to process
     * @param int $shopId Target shop
     * @param int $userId User who initiated the job
     * @param int|null $progressId Pre-created progress ID (optional)
     */
    public function __construct(
        public int $templateId,
        public array $productIds,
        public int $shopId,
        public int $userId,
        public ?int $progressId = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(
        TemplateVariableService $variableService,
        JobProgressService $progressService,
        BlockRenderer $blockRenderer
    ): void {
        $totalProducts = count($this->productIds);

        Log::info('BulkApplyTemplateJob started', [
            'template_id' => $this->templateId,
            'shop_id' => $this->shopId,
            'user_id' => $this->userId,
            'total_products' => $totalProducts,
            'progress_id' => $this->progressId,
        ]);

        // Create progress tracking if not pre-created
        $progressId = $this->progressId;
        if (!$progressId) {
            $shop = PrestaShopShop::find($this->shopId);
            if ($shop) {
                $progressId = $progressService->createJobProgress(
                    $this->job->getJobId(),
                    $shop,
                    'template_apply',
                    $totalProducts
                );
            }
        } else {
            // Update pending progress to running
            $progressService->startPendingJob($this->job->getJobId(), $totalProducts);
        }

        try {
            // Load template
            $template = DescriptionTemplate::find($this->templateId);
            if (!$template) {
                throw new \RuntimeException("Szablon o ID {$this->templateId} nie istnieje");
            }

            // Load shop for variable replacement
            $shop = PrestaShopShop::find($this->shopId);
            if (!$shop) {
                throw new \RuntimeException("Sklep o ID {$this->shopId} nie istnieje");
            }

            $processed = 0;
            $errors = [];
            $successCount = 0;

            // Process in chunks
            $chunks = array_chunk($this->productIds, self::CHUNK_SIZE);

            foreach ($chunks as $chunkIndex => $chunkProductIds) {
                $products = Product::whereIn('id', $chunkProductIds)
                    ->with(['category', 'manufacturer'])
                    ->get();

                foreach ($products as $product) {
                    try {
                        $this->applyTemplateToProduct(
                            $template,
                            $product,
                            $shop,
                            $variableService,
                            $blockRenderer
                        );
                        $successCount++;
                    } catch (Throwable $e) {
                        $errors[] = [
                            'sku' => $product->sku,
                            'product_id' => $product->id,
                            'error' => $e->getMessage(),
                        ];

                        Log::warning('BulkApplyTemplateJob: blad dla produktu', [
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

                Log::debug('BulkApplyTemplateJob: chunk processed', [
                    'chunk_index' => $chunkIndex,
                    'processed' => $processed,
                    'total' => $totalProducts,
                    'success_count' => $successCount,
                    'error_count' => count($errors),
                ]);
            }

            // Mark as completed
            if ($progressId) {
                $progressService->markCompleted($progressId, [
                    'total_processed' => $processed,
                    'success_count' => $successCount,
                    'error_count' => count($errors),
                    'template_name' => $template->name,
                    'shop_name' => $shop->name,
                ]);
            }

            Log::info('BulkApplyTemplateJob completed', [
                'template_id' => $this->templateId,
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

            Log::error('BulkApplyTemplateJob failed', [
                'template_id' => $this->templateId,
                'shop_id' => $this->shopId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Apply template to a single product.
     */
    private function applyTemplateToProduct(
        DescriptionTemplate $template,
        Product $product,
        PrestaShopShop $shop,
        TemplateVariableService $variableService,
        BlockRenderer $blockRenderer
    ): void {
        // Get or create ProductDescription
        $description = ProductDescription::getOrCreate($product->id, $shop->id);

        // Get template blocks and replace variables
        $blocks = $variableService->replaceVariables(
            $template->blocks_json ?? [],
            $product,
            $shop
        );

        // Update description
        $description->blocks_json = $blocks;
        $description->template_id = $template->id;
        $description->save();

        // Render HTML
        $html = $blockRenderer->generateCleanHtml($description);
        $description->setRenderedHtml($html);

        Log::debug('Template applied to product', [
            'product_id' => $product->id,
            'sku' => $product->sku,
            'template_id' => $template->id,
            'shop_id' => $shop->id,
            'block_count' => count($blocks),
        ]);
    }

    /**
     * Handle job failure.
     */
    public function failed(Throwable $exception): void
    {
        Log::error('BulkApplyTemplateJob failed permanently', [
            'template_id' => $this->templateId,
            'shop_id' => $this->shopId,
            'user_id' => $this->userId,
            'products_count' => count($this->productIds),
            'error' => $exception->getMessage(),
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Jobs\VisualEditor;

use App\Models\Product;
use App\Models\ProductDescription;
use App\Models\PrestaShopShop;
use App\Services\JobProgressService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Bulk Export Descriptions Job.
 *
 * Exports visual descriptions for multiple products to a JSON file.
 * Output includes blocks array, rendered HTML, and template info.
 *
 * Features:
 * - Chunked processing (50 products per batch)
 * - Progress tracking via JobProgressService
 * - JSON export with full description data
 * - Downloadable file stored on local disk
 *
 * @package App\Jobs\VisualEditor
 * @since ETAP_07f Faza 6.2 - Bulk Operations
 */
class BulkExportDescriptionsJob implements ShouldQueue
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
     * @param array $productIds Products to export
     * @param int $shopId Target shop
     * @param int $userId User who initiated the export
     * @param int|null $progressId Pre-created progress ID (optional)
     */
    public function __construct(
        public array $productIds,
        public int $shopId,
        public int $userId,
        public ?int $progressId = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(JobProgressService $progressService): void
    {
        $totalProducts = count($this->productIds);

        Log::info('BulkExportDescriptionsJob started', [
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
                    'description_export',
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

            $exportData = [
                'export_info' => [
                    'exported_at' => now()->toIso8601String(),
                    'shop_id' => $shop->id,
                    'shop_name' => $shop->name,
                    'total_products' => $totalProducts,
                    'version' => '1.0',
                    'exported_by' => $this->userId,
                ],
                'descriptions' => [],
            ];

            $processed = 0;
            $errors = [];
            $exportedCount = 0;

            // Process in chunks
            $chunks = array_chunk($this->productIds, self::CHUNK_SIZE);

            foreach ($chunks as $chunkIndex => $chunkProductIds) {
                // Load products with descriptions
                $descriptions = ProductDescription::whereIn('product_id', $chunkProductIds)
                    ->where('shop_id', $this->shopId)
                    ->with(['product:id,sku,name', 'template:id,name'])
                    ->get();

                // Index by product_id for quick lookup
                $descriptionsByProductId = $descriptions->keyBy('product_id');

                // Load products that might not have descriptions
                $products = Product::whereIn('id', $chunkProductIds)
                    ->select('id', 'sku', 'name')
                    ->get();

                foreach ($products as $product) {
                    try {
                        $description = $descriptionsByProductId->get($product->id);

                        if ($description && !empty($description->blocks_json)) {
                            $exportData['descriptions'][] = $this->formatDescriptionForExport(
                                $product,
                                $description
                            );
                            $exportedCount++;
                        }
                    } catch (Throwable $e) {
                        $errors[] = [
                            'sku' => $product->sku,
                            'product_id' => $product->id,
                            'error' => $e->getMessage(),
                        ];

                        Log::warning('BulkExportDescriptionsJob: blad dla produktu', [
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

                Log::debug('BulkExportDescriptionsJob: chunk processed', [
                    'chunk_index' => $chunkIndex,
                    'processed' => $processed,
                    'total' => $totalProducts,
                    'exported_count' => $exportedCount,
                ]);
            }

            // Update export info with actual counts
            $exportData['export_info']['exported_count'] = $exportedCount;
            $exportData['export_info']['error_count'] = count($errors);
            if (!empty($errors)) {
                $exportData['export_info']['errors'] = $errors;
            }

            // Save to file
            $filePath = $this->saveExportFile($exportData, $shop->name);

            // Mark as completed with file path
            if ($progressId) {
                $progressService->markCompleted($progressId, [
                    'total_processed' => $processed,
                    'exported_count' => $exportedCount,
                    'error_count' => count($errors),
                    'file_path' => $filePath,
                    'download_url' => $this->getDownloadUrl($filePath),
                    'shop_name' => $shop->name,
                ]);

                // Set action button for download
                $progressService->updateMetadata($progressId, [
                    'download_file' => $filePath,
                    'download_ready' => true,
                ]);
            }

            Log::info('BulkExportDescriptionsJob completed', [
                'shop_id' => $this->shopId,
                'processed' => $processed,
                'exported' => $exportedCount,
                'errors' => count($errors),
                'file_path' => $filePath,
            ]);

        } catch (Throwable $e) {
            if ($progressId) {
                $progressService->markFailed($progressId, $e->getMessage(), [
                    'trace' => $e->getTraceAsString(),
                ]);
            }

            Log::error('BulkExportDescriptionsJob failed', [
                'shop_id' => $this->shopId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Format a description for export.
     */
    private function formatDescriptionForExport(Product $product, ProductDescription $description): array
    {
        return [
            'product_id' => $product->id,
            'sku' => $product->sku,
            'product_name' => $product->name,
            'shop_id' => $description->shop_id,
            'blocks' => $description->blocks_json,
            'rendered_html' => $description->rendered_html,
            'template' => $description->template ? [
                'id' => $description->template->id,
                'name' => $description->template->name,
            ] : null,
            'block_count' => count($description->blocks_json ?? []),
            'last_rendered_at' => $description->last_rendered_at?->toIso8601String(),
            'updated_at' => $description->updated_at->toIso8601String(),
        ];
    }

    /**
     * Save export data to file.
     */
    private function saveExportFile(array $exportData, string $shopName): string
    {
        $safeName = Str::slug($shopName);
        $timestamp = now()->format('Y-m-d_His');
        $fileName = "descriptions_export_{$safeName}_{$timestamp}.json";
        $filePath = "exports/descriptions/{$fileName}";

        // Ensure directory exists
        Storage::disk('local')->makeDirectory('exports/descriptions');

        // Save as formatted JSON
        $json = json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        Storage::disk('local')->put($filePath, $json);

        Log::debug('Export file saved', [
            'file_path' => $filePath,
            'size_bytes' => strlen($json),
        ]);

        return $filePath;
    }

    /**
     * Get download URL for export file.
     */
    private function getDownloadUrl(string $filePath): string
    {
        // Generate a temporary signed URL or route for download
        // This will be handled by a download controller
        return route('admin.exports.download', ['file' => base64_encode($filePath)]);
    }

    /**
     * Handle job failure.
     */
    public function failed(Throwable $exception): void
    {
        Log::error('BulkExportDescriptionsJob failed permanently', [
            'shop_id' => $this->shopId,
            'user_id' => $this->userId,
            'products_count' => count($this->productIds),
            'error' => $exception->getMessage(),
        ]);
    }
}

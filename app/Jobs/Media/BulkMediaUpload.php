<?php

namespace App\Jobs\Media;

use App\DTOs\Media\MediaUploadDTO;
use App\Models\Product;
use App\Services\JobProgressService;
use App\Services\Media\MediaManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * BulkMediaUpload Job
 *
 * Przetwarzanie wielu plików multimedialnych (folder upload):
 * - Progress tracking per file
 * - Error handling + continue on error
 * - Summary report po zakończeniu
 * - JobProgress integration
 *
 * ETAP_07d: Media System Implementation
 * Max ~180 lines (zgodnie z CLAUDE.md)
 *
 * @package App\Jobs\Media
 */
class BulkMediaUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Product ID (mediable context)
     */
    public int $productId;

    /**
     * Array of temporary file paths to process
     */
    public array $filePaths;

    /**
     * User ID who triggered upload
     */
    public ?int $userId;

    /**
     * Number of times job may be attempted
     */
    public int $tries = 1; // No retry - handle errors per file

    /**
     * Maximum seconds job can run
     */
    public int $timeout = 600; // 10 minutes for bulk

    /**
     * Create new job instance
     *
     * @param int $productId Product ID
     * @param array $filePaths Array of temporary file paths
     * @param int|null $userId User ID who uploaded
     */
    public function __construct(int $productId, array $filePaths, ?int $userId = null)
    {
        $this->productId = $productId;
        $this->filePaths = $filePaths;
        $this->userId = $userId;

        // Use default queue
        $this->onQueue('default');
    }

    /**
     * Execute the job
     *
     * @param MediaManager $mediaManager Media manager service
     * @param JobProgressService $progressService Progress tracking service
     */
    public function handle(MediaManager $mediaManager, JobProgressService $progressService): void
    {
        $product = Product::find($this->productId);

        if (!$product) {
            Log::error('[BULK MEDIA] Product not found', [
                'product_id' => $this->productId,
            ]);
            return;
        }

        Log::info('[BULK MEDIA] Starting bulk upload', [
            'product_id' => $this->productId,
            'product_sku' => $product->sku,
            'files_count' => count($this->filePaths),
            'user_id' => $this->userId,
        ]);

        // Create progress tracking
        $progressId = $progressService->createJobProgress(
            $this->job->getJobId(),
            null, // No shop context for media upload
            'media_upload',
            count($this->filePaths)
        );

        $results = [
            'uploaded' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        foreach ($this->filePaths as $index => $filePath) {
            try {
                // Verify file exists
                if (!Storage::exists($filePath)) {
                    $results['errors'][] = [
                        'file' => basename($filePath),
                        'error' => 'File not found in storage',
                    ];
                    $results['skipped']++;
                    continue;
                }

                // Get file info
                $filename = basename($filePath);
                $mimeType = Storage::mimeType($filePath);
                $fileSize = Storage::size($filePath);

                // Create MediaUploadDTO
                $dto = new MediaUploadDTO(
                    mediableType: Product::class,
                    mediableId: $this->productId,
                    filePath: $filePath,
                    fileName: $filename,
                    mimeType: $mimeType,
                    fileSize: $fileSize,
                    originalName: $filename,
                    userId: $this->userId
                );

                // Upload via MediaManager
                $media = $mediaManager->uploadSingle($dto);

                // Dispatch ProcessMediaUpload job for async processing
                ProcessMediaUpload::dispatch($media->id, $this->productId, $this->userId);

                $results['uploaded']++;

                Log::info('[BULK MEDIA] File uploaded', [
                    'media_id' => $media->id,
                    'product_id' => $this->productId,
                    'file' => $filename,
                    'progress' => ($index + 1) . '/' . count($this->filePaths),
                ]);

            } catch (\Exception $e) {
                $results['errors'][] = [
                    'file' => basename($filePath),
                    'error' => $e->getMessage(),
                ];

                Log::error('[BULK MEDIA] File upload failed', [
                    'product_id' => $this->productId,
                    'file' => basename($filePath),
                    'error' => $e->getMessage(),
                ]);
            }

            // Update progress
            $progressService->updateProgress(
                $progressId,
                $index + 1,
                !empty($results['errors']) ? [end($results['errors'])] : []
            );
        }

        // Mark job as completed
        $progressService->markCompleted($progressId, [
            'uploaded' => $results['uploaded'],
            'skipped' => $results['skipped'],
            'errors_count' => count($results['errors']),
        ]);

        Log::info('[BULK MEDIA] Bulk upload completed', [
            'product_id' => $this->productId,
            'total_files' => count($this->filePaths),
            'uploaded' => $results['uploaded'],
            'skipped' => $results['skipped'],
            'errors' => count($results['errors']),
        ]);

        // Cleanup temporary files
        $this->cleanupTempFiles();
    }

    /**
     * Cleanup temporary uploaded files
     */
    private function cleanupTempFiles(): void
    {
        foreach ($this->filePaths as $filePath) {
            try {
                if (Storage::exists($filePath)) {
                    Storage::delete($filePath);
                }
            } catch (\Exception $e) {
                Log::warning('[BULK MEDIA] Failed to cleanup temp file', [
                    'file' => $filePath,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Job failed permanently
     *
     * @param Throwable $exception Exception that caused failure
     */
    public function failed(Throwable $exception): void
    {
        Log::error('[BULK MEDIA] BulkMediaUpload failed permanently', [
            'product_id' => $this->productId,
            'files_count' => count($this->filePaths),
            'user_id' => $this->userId,
            'error' => $exception->getMessage(),
        ]);

        // Cleanup temp files on failure
        $this->cleanupTempFiles();
    }
}

<?php

namespace App\Jobs\Media;

use App\Events\Media\MediaUploaded;
use App\Models\Media;
use App\Models\Product;
use App\Services\JobProgressService;
use App\Services\Media\ImageProcessor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * ProcessMediaUpload Job
 *
 * Asynchroniczne przetwarzanie pojedynczego pliku multimedialnego:
 * - WebP conversion dla optymalizacji
 * - Thumbnail generation (small, medium, large)
 * - Metadata extraction (EXIF, dimensions)
 * - JobProgress integration
 * - MediaUploaded event dispatch
 *
 * ETAP_07d: Media System Implementation
 * Max ~150 lines (zgodnie z CLAUDE.md)
 *
 * @package App\Jobs\Media
 */
class ProcessMediaUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Media ID to process
     */
    public int $mediaId;

    /**
     * Product ID (mediable context)
     */
    public int $productId;

    /**
     * User ID who triggered upload
     */
    public ?int $userId;

    /**
     * Number of times job may be attempted
     */
    public int $tries = 3;

    /**
     * Maximum seconds job can run
     */
    public int $timeout = 120;

    /**
     * Create new job instance
     *
     * @param int $mediaId Media record ID
     * @param int $productId Product ID
     * @param int|null $userId User ID who uploaded
     */
    public function __construct(int $mediaId, int $productId, ?int $userId = null)
    {
        $this->mediaId = $mediaId;
        $this->productId = $productId;
        $this->userId = $userId;

        // Use default queue
        $this->onQueue('default');
    }

    /**
     * Execute the job
     *
     * @param ImageProcessor $processor Image processing service
     * @param JobProgressService $progressService Progress tracking service
     */
    public function handle(ImageProcessor $processor, JobProgressService $progressService): void
    {
        $media = Media::find($this->mediaId);

        if (!$media) {
            Log::error('[MEDIA JOB] Media record not found', [
                'media_id' => $this->mediaId,
                'product_id' => $this->productId,
            ]);
            return;
        }

        Log::info('[MEDIA JOB] Processing media upload', [
            'media_id' => $this->mediaId,
            'product_id' => $this->productId,
            'file_path' => $media->file_path,
            'user_id' => $this->userId,
        ]);

        try {
            // Verify file exists in storage
            if (!Storage::exists($media->file_path)) {
                throw new \RuntimeException("File not found in storage: {$media->file_path}");
            }

            // Extract metadata (dimensions, EXIF)
            $metadata = $this->extractMetadata($media);
            $media->update([
                'width' => $metadata['width'] ?? null,
                'height' => $metadata['height'] ?? null,
            ]);

            // Convert to WebP (if not already WebP and supported)
            if ($media->mime_type !== 'image/webp' && $processor->isFormatSupported($media->mime_type)) {
                $webpPath = $processor->convertToWebp($media->file_path);

                if ($webpPath) {
                    Log::info('[MEDIA JOB] WebP conversion successful', [
                        'media_id' => $this->mediaId,
                        'original_path' => $media->file_path,
                        'webp_path' => $webpPath,
                    ]);
                }
            }

            // Generate thumbnails (small, medium, large)
            $thumbnails = $processor->generateThumbnails($media->file_path);

            Log::info('[MEDIA JOB] Thumbnails generated', [
                'media_id' => $this->mediaId,
                'thumbnails' => array_keys($thumbnails),
            ]);

            // Update sync status to pending
            $media->update(['sync_status' => 'pending']);

            // Dispatch MediaUploaded event
            event(new MediaUploaded($media, $this->userId));

            Log::info('[MEDIA JOB] Media processing completed', [
                'media_id' => $this->mediaId,
                'product_id' => $this->productId,
                'has_webp' => isset($webpPath),
                'thumbnails_count' => count($thumbnails),
            ]);

        } catch (\Exception $e) {
            Log::error('[MEDIA JOB] Media processing failed', [
                'media_id' => $this->mediaId,
                'product_id' => $this->productId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Extract image metadata (dimensions, EXIF)
     *
     * @param Media $media Media record
     * @return array Metadata array
     */
    private function extractMetadata(Media $media): array
    {
        $metadata = [];

        try {
            // FIX 2025-12-01: Use 'public' disk - media files are stored there
            $fullPath = Storage::disk('public')->path($media->file_path);

            if (!file_exists($fullPath)) {
                return $metadata;
            }

            // Get image dimensions
            $imageInfo = @getimagesize($fullPath);

            if ($imageInfo !== false) {
                $metadata['width'] = $imageInfo[0];
                $metadata['height'] = $imageInfo[1];
                $metadata['mime_type'] = $imageInfo['mime'];
            }

        } catch (\Exception $e) {
            Log::warning('[MEDIA JOB] Metadata extraction failed', [
                'media_id' => $media->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $metadata;
    }

    /**
     * Job failed permanently
     *
     * @param Throwable $exception Exception that caused failure
     */
    public function failed(Throwable $exception): void
    {
        Log::error('[MEDIA JOB] ProcessMediaUpload failed permanently', [
            'media_id' => $this->mediaId,
            'product_id' => $this->productId,
            'user_id' => $this->userId,
            'error' => $exception->getMessage(),
        ]);

        // Update media record to error state
        $media = Media::find($this->mediaId);
        if ($media) {
            $media->update(['sync_status' => 'error']);
        }
    }
}

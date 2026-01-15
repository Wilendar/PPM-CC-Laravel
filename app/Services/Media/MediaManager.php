<?php

declare(strict_types=1);

namespace App\Services\Media;

use App\DTOs\Media\MediaUploadDTO;
use App\Events\Media\MediaUploaded;
use App\Events\Media\MediaDeleted;
use App\Models\Media;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;

/**
 * MediaManager Service - Central Media Operations Handler
 *
 * Manages all media operations for products and variants:
 * - Upload (single, multiple, batch)
 * - Delete (PPM only, PrestaShop only, both)
 * - Set primary/cover image
 * - Reorder gallery
 *
 * ETAP_07d Phase 1.2.1: Services Layer
 * Max 300 lines (zgodnie z CLAUDE.md)
 *
 * @package App\Services\Media
 * @version 1.0
 */
class MediaManager
{
    /**
     * Create new MediaManager instance
     *
     * @param MediaStorageService $storage Storage service
     */
    public function __construct(
        private readonly MediaStorageService $storage,
    ) {}

    /**
     * Upload single media file
     *
     * @param MediaUploadDTO $dto Upload data
     * @return Media Created media record
     * @throws RuntimeException|InvalidArgumentException
     */
    public function upload(MediaUploadDTO $dto): Media
    {
        $mediable = $this->resolveMediable($dto->mediableType, $dto->mediableId);

        // Check limit
        if (!$this->storage->canAddMoreImages($mediable)) {
            throw new InvalidArgumentException(
                'Maximum image limit reached: ' . MediaStorageService::MAX_IMAGES_PER_PRODUCT
            );
        }

        // Get next available index
        $index = $dto->sortOrder ?? $this->storage->getNextAvailableIndex($mediable);
        if ($index === null) {
            throw new InvalidArgumentException('No available index for new image');
        }

        return DB::transaction(function () use ($dto, $mediable, $index) {
            // Store file
            $stored = $this->storage->store($dto->file, $mediable, $index);

            // Create media record
            $media = new Media([
                'mediable_type' => $dto->mediableType,
                'mediable_id' => $dto->mediableId,
                'file_name' => $stored['filename'],
                'original_name' => $dto->getOriginalName(),
                'file_path' => $stored['path'],
                'file_size' => $stored['size'],
                'mime_type' => $stored['mime_type'],
                'alt_text' => $dto->altText,
                'sort_order' => $index,
                'is_primary' => $dto->isPrimary,
                'sync_status' => 'pending',
                'is_active' => true,
            ]);

            // Extract dimensions if image
            if ($dto->isImage()) {
                $dimensions = $this->getImageDimensions($dto->file);
                $media->width = $dimensions['width'];
                $media->height = $dimensions['height'];
            }

            $media->save();

            // Dispatch event
            event(new MediaUploaded(
                media: $media,
                generateThumbnails: $dto->generateThumbnails,
                convertToWebp: $dto->convertToWebp,
            ));

            Log::info('Media uploaded', [
                'media_id' => $media->id,
                'path' => $stored['path'],
                'mediable' => $dto->mediableType . ':' . $dto->mediableId,
            ]);

            return $media;
        });
    }

    /**
     * Upload multiple files at once
     *
     * @param array $files Array of UploadedFile
     * @param string $mediableType Class name
     * @param int $mediableId Model ID
     * @param array $options Additional options
     * @return Collection<Media> Uploaded media records
     */
    public function uploadMultiple(
        array $files,
        string $mediableType,
        int $mediableId,
        array $options = []
    ): Collection {
        $uploaded = collect();
        $isPrimarySet = false;

        foreach ($files as $index => $file) {
            if (!$file instanceof UploadedFile) {
                continue;
            }

            try {
                $dto = MediaUploadDTO::fromArray([
                    'file' => $file,
                    'mediable_type' => $mediableType,
                    'mediable_id' => $mediableId,
                    'alt_text' => $options['alt_text'] ?? null,
                    'is_primary' => !$isPrimarySet && ($options['set_first_as_primary'] ?? false),
                    'convert_to_webp' => $options['convert_to_webp'] ?? true,
                    'generate_thumbnails' => $options['generate_thumbnails'] ?? true,
                ]);

                $media = $this->upload($dto);
                $uploaded->push($media);

                if ($media->is_primary) {
                    $isPrimarySet = true;
                }
            } catch (\Exception $e) {
                Log::error('Failed to upload file', [
                    'index' => $index,
                    'filename' => $file->getClientOriginalName(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $uploaded;
    }

    /**
     * Delete media
     *
     * @param Media $media Media to delete
     * @param string $source Where to delete from (ppm_only, prestashop_only, both)
     * @param int|null $userId User performing deletion
     * @return bool Success
     */
    public function delete(Media $media, string $source = 'ppm_only', ?int $userId = null): bool
    {
        // Create event before deletion
        $event = MediaDeleted::fromMedia($media, $source, $userId);

        $success = false;

        if ($event->shouldDeleteFromPpm()) {
            DB::transaction(function () use ($media, &$success) {
                // Delete thumbnails
                $this->storage->deleteThumbnails($media->file_path);

                // Delete main file
                $this->storage->delete($media->file_path);

                // Soft delete record
                $media->delete();

                $success = true;
            });
        }

        // Dispatch event for async PrestaShop cleanup
        event($event);

        Log::info('Media deleted', [
            'media_id' => $media->id,
            'source' => $source,
            'success' => $success,
        ]);

        return $success;
    }

    /**
     * Delete multiple media
     *
     * @param Collection|array $mediaIds Media IDs to delete
     * @param string $source Delete source
     * @param int|null $userId User performing deletion
     * @return int Number deleted
     */
    public function deleteMultiple(Collection|array $mediaIds, string $source = 'ppm_only', ?int $userId = null): int
    {
        $deleted = 0;

        foreach ($mediaIds as $mediaId) {
            $media = Media::find($mediaId);
            if ($media && $this->delete($media, $source, $userId)) {
                $deleted++;
            }
        }

        return $deleted;
    }

    /**
     * Set media as primary/cover image
     *
     * @param Media $media Media to set as primary
     * @return Media Updated media
     */
    public function setPrimary(Media $media): Media
    {
        DB::transaction(function () use ($media) {
            // Remove primary from siblings
            Media::where('mediable_type', $media->mediable_type)
                ->where('mediable_id', $media->mediable_id)
                ->where('id', '!=', $media->id)
                ->update(['is_primary' => false]);

            // Set this as primary
            $media->is_primary = true;
            $media->save();
        });

        Log::info('Media set as primary', [
            'media_id' => $media->id,
            'mediable' => $media->mediable_type . ':' . $media->mediable_id,
        ]);

        return $media;
    }

    /**
     * Reorder media gallery
     *
     * @param array $order Array of [media_id => sort_order]
     * @return int Number updated
     */
    public function reorder(array $order): int
    {
        $updated = 0;

        DB::transaction(function () use ($order, &$updated) {
            foreach ($order as $mediaId => $sortOrder) {
                $result = Media::where('id', $mediaId)
                    ->update(['sort_order' => (int) $sortOrder]);
                $updated += $result;
            }
        });

        return $updated;
    }

    /**
     * Get gallery for mediable sorted by order
     *
     * @param string $mediableType Class name
     * @param int $mediableId Model ID
     * @return Collection<Media>
     */
    public function getGallery(string $mediableType, int $mediableId): Collection
    {
        return Media::where('mediable_type', $mediableType)
            ->where('mediable_id', $mediableId)
            ->active()
            ->orderBy('is_primary', 'desc')
            ->orderBy('sort_order', 'asc')
            ->get();
    }

    /**
     * Get primary image for mediable
     *
     * @param string $mediableType Class name
     * @param int $mediableId Model ID
     * @return Media|null
     */
    public function getPrimary(string $mediableType, int $mediableId): ?Media
    {
        return Media::where('mediable_type', $mediableType)
            ->where('mediable_id', $mediableId)
            ->active()
            ->primary()
            ->first();
    }

    /**
     * Update media alt text
     *
     * @param Media $media Media to update
     * @param string $altText New alt text
     * @return Media Updated media
     */
    public function updateAltText(Media $media, string $altText): Media
    {
        $media->alt_text = $altText;
        $media->save();

        return $media;
    }

    /**
     * Resolve mediable model from type and ID
     *
     * @param string $type Class name
     * @param int $id Model ID
     * @return Product|ProductVariant
     * @throws InvalidArgumentException
     */
    private function resolveMediable(string $type, int $id): Product|ProductVariant
    {
        $model = match ($type) {
            'App\\Models\\Product' => Product::find($id),
            'App\\Models\\ProductVariant' => ProductVariant::find($id),
            default => null,
        };

        if (!$model) {
            throw new InvalidArgumentException("Mediable not found: {$type}:{$id}");
        }

        return $model;
    }

    /**
     * Get image dimensions from uploaded file
     *
     * @param UploadedFile $file Uploaded file
     * @return array ['width' => int|null, 'height' => int|null]
     */
    private function getImageDimensions(UploadedFile $file): array
    {
        try {
            $imageInfo = getimagesize($file->getRealPath());
            if ($imageInfo) {
                return [
                    'width' => $imageInfo[0],
                    'height' => $imageInfo[1],
                ];
            }
        } catch (\Exception $e) {
            Log::warning('Failed to get image dimensions', ['error' => $e->getMessage()]);
        }

        return ['width' => null, 'height' => null];
    }
}

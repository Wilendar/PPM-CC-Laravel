<?php

declare(strict_types=1);

namespace App\Services\Media;

use App\Models\Media;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

/**
 * MediaStorageService - SKU-based File Storage Management
 *
 * Handles all file storage operations with SKU-based folder structure.
 * Provides storage abstraction layer (local, S3-ready).
 *
 * Storage Structure:
 * - products/{SKU}/                    - Main images
 * - products/{SKU}/thumbs/             - Thumbnails
 * - products/{SKU}/variants/{variant_id}/  - Variant images
 *
 * Naming Convention:
 * - {Product_Name}_{NN}.webp (NN = 01-99)
 *
 * ETAP_07d Phase 1.2.4: Services Layer
 * Max 250 lines (zgodnie z CLAUDE.md)
 *
 * @package App\Services\Media
 * @version 1.0
 */
class MediaStorageService
{
    /**
     * Maximum images allowed per product (01-99 naming convention)
     */
    public const MAX_IMAGES_PER_PRODUCT = 99;

    /**
     * Storage disk name
     */
    private string $disk;

    /**
     * Base path for product media
     */
    private string $basePath = 'products';

    /**
     * Thumbnails subdirectory name
     */
    private string $thumbsDir = 'thumbs';

    /**
     * Variants subdirectory name
     */
    private string $variantsDir = 'variants';

    /**
     * Create new MediaStorageService instance
     *
     * @param string|null $disk Storage disk (null = 'public' for web access)
     */
    public function __construct(?string $disk = null)
    {
        // Use 'public' disk by default for web-accessible storage
        $this->disk = $disk ?? 'public';
    }

    /**
     * Store uploaded file with SKU-based path
     *
     * @param UploadedFile $file Uploaded file
     * @param Product|ProductVariant $mediable Parent model
     * @param int $index Position index (1-99)
     * @return array ['path' => string, 'filename' => string, 'size' => int]
     * @throws InvalidArgumentException|RuntimeException
     */
    public function store(UploadedFile $file, Product|ProductVariant $mediable, int $index): array
    {
        $this->validateIndex($index);

        $sku = $this->getSku($mediable);
        $directory = $this->getDirectory($mediable);
        $filename = $this->generateFileName($mediable, $index, $file->getClientOriginalExtension());

        $path = Storage::disk($this->disk)->putFileAs($directory, $file, $filename);

        if ($path === false) {
            throw new RuntimeException("Failed to store file: {$filename}");
        }

        return [
            'path' => $path,
            'filename' => $filename,
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'directory' => $directory,
        ];
    }

    /**
     * Store file from string contents
     *
     * @param string $contents File contents
     * @param Product|ProductVariant $mediable Parent model
     * @param int $index Position index
     * @param string $extension File extension
     * @return array Storage result
     */
    public function storeContents(
        string $contents,
        Product|ProductVariant $mediable,
        int $index,
        string $extension = 'webp'
    ): array {
        $this->validateIndex($index);

        $directory = $this->getDirectory($mediable);
        $filename = $this->generateFileName($mediable, $index, $extension);
        $path = $directory . '/' . $filename;

        $stored = Storage::disk($this->disk)->put($path, $contents);

        if (!$stored) {
            throw new RuntimeException("Failed to store contents: {$path}");
        }

        return [
            'path' => $path,
            'filename' => $filename,
            'size' => strlen($contents),
            'directory' => $directory,
        ];
    }

    /**
     * Delete file from storage
     *
     * @param string $path File path
     * @return bool Success
     */
    public function delete(string $path): bool
    {
        if (!Storage::disk($this->disk)->exists($path)) {
            return true; // Already deleted
        }

        return Storage::disk($this->disk)->delete($path);
    }

    /**
     * Delete all thumbnails for a file
     *
     * @param string $originalPath Original file path
     * @return int Number of deleted thumbnails
     */
    public function deleteThumbnails(string $originalPath): int
    {
        $thumbsPath = $this->getThumbnailDirectory($originalPath);
        $basename = pathinfo($originalPath, PATHINFO_FILENAME);

        $deleted = 0;
        $thumbnails = Storage::disk($this->disk)->files($thumbsPath);

        foreach ($thumbnails as $thumb) {
            if (str_starts_with(basename($thumb), $basename)) {
                if (Storage::disk($this->disk)->delete($thumb)) {
                    $deleted++;
                }
            }
        }

        return $deleted;
    }

    /**
     * Check if file exists
     *
     * @param string $path File path
     * @return bool
     */
    public function exists(string $path): bool
    {
        return Storage::disk($this->disk)->exists($path);
    }

    /**
     * Get file URL
     *
     * @param string $path File path
     * @return string
     */
    public function url(string $path): string
    {
        return Storage::disk($this->disk)->url($path);
    }

    /**
     * Get file contents
     *
     * @param string $path File path
     * @return string|null
     */
    public function get(string $path): ?string
    {
        return Storage::disk($this->disk)->get($path);
    }

    /**
     * Generate filename following naming convention
     *
     * Pattern: {Product_Name}_{NN}.{ext}
     *
     * @param Product|ProductVariant $mediable Parent model
     * @param int $index Position (1-99)
     * @param string $extension File extension
     * @return string Generated filename
     */
    public function generateFileName(Product|ProductVariant $mediable, int $index, string $extension = 'webp'): string
    {
        $this->validateIndex($index);

        $baseName = $this->getBaseName($mediable);
        $indexStr = str_pad((string) $index, 2, '0', STR_PAD_LEFT);
        $ext = ltrim($extension, '.');

        return "{$baseName}_{$indexStr}.{$ext}";
    }

    /**
     * Get directory for mediable
     *
     * @param Product|ProductVariant $mediable Parent model
     * @return string Directory path
     */
    public function getDirectory(Product|ProductVariant $mediable): string
    {
        $sku = $this->getSku($mediable);

        if ($mediable instanceof ProductVariant) {
            return "{$this->basePath}/{$sku}/{$this->variantsDir}/{$mediable->id}";
        }

        return "{$this->basePath}/{$sku}";
    }

    /**
     * Get thumbnails directory
     *
     * @param string $originalPath Original file path
     * @return string Thumbnails directory
     */
    public function getThumbnailDirectory(string $originalPath): string
    {
        $directory = dirname($originalPath);
        return "{$directory}/{$this->thumbsDir}";
    }

    /**
     * Get thumbnail path for specific size
     *
     * @param string $originalPath Original file path
     * @param string $size Size name (small, medium, large)
     * @return string Thumbnail path
     */
    public function getThumbnailPath(string $originalPath, string $size): string
    {
        $thumbDir = $this->getThumbnailDirectory($originalPath);
        $filename = pathinfo($originalPath, PATHINFO_FILENAME);
        $ext = pathinfo($originalPath, PATHINFO_EXTENSION);

        return "{$thumbDir}/{$filename}_{$size}.{$ext}";
    }

    /**
     * Check if can add more images
     *
     * @param Product|ProductVariant $mediable Parent model
     * @return bool
     */
    public function canAddMoreImages(Product|ProductVariant $mediable): bool
    {
        return $mediable->media()->count() < self::MAX_IMAGES_PER_PRODUCT;
    }

    /**
     * Get next available index
     *
     * @param Product|ProductVariant $mediable Parent model
     * @return int|null Next index or null if limit reached
     */
    public function getNextAvailableIndex(Product|ProductVariant $mediable): ?int
    {
        $existingIndexes = $mediable->media()
            ->pluck('sort_order')
            ->filter()
            ->toArray();

        for ($i = 1; $i <= self::MAX_IMAGES_PER_PRODUCT; $i++) {
            if (!in_array($i, $existingIndexes, true)) {
                return $i;
            }
        }

        return null; // Limit reached
    }

    /**
     * Ensure directory exists
     *
     * @param string $directory Directory path
     * @return bool
     */
    public function ensureDirectoryExists(string $directory): bool
    {
        if (!Storage::disk($this->disk)->exists($directory)) {
            return Storage::disk($this->disk)->makeDirectory($directory);
        }
        return true;
    }

    /**
     * Get SKU from mediable
     *
     * @param Product|ProductVariant $mediable Parent model
     * @return string SKU
     */
    private function getSku(Product|ProductVariant $mediable): string
    {
        if ($mediable instanceof ProductVariant) {
            return $mediable->product->sku ?? "variant-{$mediable->id}";
        }

        return $mediable->sku ?? "product-{$mediable->id}";
    }

    /**
     * Get base name for file naming
     *
     * @param Product|ProductVariant $mediable Parent model
     * @return string Slugified name
     */
    private function getBaseName(Product|ProductVariant $mediable): string
    {
        if ($mediable instanceof ProductVariant) {
            $name = $mediable->name ?? $mediable->product->name ?? 'variant';
        } else {
            $name = $mediable->name ?? 'product';
        }

        return Str::slug($name, '_');
    }

    /**
     * Validate index is within bounds
     *
     * @param int $index Index to validate
     * @throws InvalidArgumentException
     */
    private function validateIndex(int $index): void
    {
        if ($index < 1 || $index > self::MAX_IMAGES_PER_PRODUCT) {
            throw new InvalidArgumentException(
                "Image index must be between 1 and " . self::MAX_IMAGES_PER_PRODUCT . ", got: {$index}"
            );
        }
    }
}

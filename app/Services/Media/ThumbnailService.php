<?php

namespace App\Services\Media;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * ThumbnailService - Image thumbnail generation
 *
 * Provides unified thumbnail generation for product and variant images.
 * Supports Intervention Image (if available) and GD library fallback.
 *
 * EXTRACTED FROM: ProductFormVariants.php for separation of concerns
 *
 * @package App\Services\Media
 * @version 1.0
 * @since ETAP_05b FAZA 1
 */
class ThumbnailService
{
    /**
     * Generate thumbnail for image
     *
     * @param string $originalPath Storage path to original image
     * @param int $width Thumbnail width
     * @param int $height Thumbnail height
     * @return string Thumbnail path
     */
    public function generate(string $originalPath, int $width = 200, int $height = 200): string
    {
        try {
            if (class_exists('\Intervention\Image\Facades\Image')) {
                return $this->generateWithIntervention($originalPath, $width, $height);
            }

            return $this->generateWithGD($originalPath, $width, $height);
        } catch (\Exception $e) {
            Log::error('Thumbnail generation failed', [
                'path' => $originalPath,
                'error' => $e->getMessage(),
            ]);

            // Return original path as fallback
            return $originalPath;
        }
    }

    /**
     * Generate thumbnail using Intervention Image
     */
    protected function generateWithIntervention(string $originalPath, int $width, int $height): string
    {
        $image = \Intervention\Image\Facades\Image::make(storage_path("app/public/{$originalPath}"));
        $image->fit($width, $height);

        $thumbPath = $this->getThumbPath($originalPath);
        $this->ensureDirectory($thumbPath);

        $image->save(storage_path("app/public/{$thumbPath}"));

        return $thumbPath;
    }

    /**
     * Generate thumbnail using GD library
     */
    protected function generateWithGD(string $originalPath, int $width, int $height): string
    {
        $sourcePath = storage_path("app/public/{$originalPath}");
        $thumbPath = $this->getThumbPath($originalPath);
        $thumbFullPath = storage_path("app/public/{$thumbPath}");

        $this->ensureDirectory($thumbPath);

        [$origWidth, $origHeight, $type] = getimagesize($sourcePath);

        $source = $this->createImageFromFile($sourcePath, $type);

        if (!$source) {
            return $originalPath;
        }

        $thumb = imagecreatetruecolor($width, $height);
        imagecopyresampled($thumb, $source, 0, 0, 0, 0, $width, $height, $origWidth, $origHeight);

        $this->saveImage($thumb, $thumbFullPath, $type);

        imagedestroy($source);
        imagedestroy($thumb);

        return $thumbPath;
    }

    /**
     * Create image resource from file
     */
    protected function createImageFromFile(string $path, int $type): mixed
    {
        return match ($type) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($path),
            IMAGETYPE_PNG => imagecreatefrompng($path),
            IMAGETYPE_WEBP => imagecreatefromwebp($path),
            IMAGETYPE_GIF => imagecreatefromgif($path),
            default => null,
        };
    }

    /**
     * Save image resource to file
     */
    protected function saveImage($image, string $path, int $type): void
    {
        match ($type) {
            IMAGETYPE_JPEG => imagejpeg($image, $path, 90),
            IMAGETYPE_PNG => imagepng($image, $path, 9),
            IMAGETYPE_WEBP => imagewebp($image, $path, 90),
            IMAGETYPE_GIF => imagegif($image, $path),
            default => null,
        };
    }

    /**
     * Get thumbnail path from original path
     */
    protected function getThumbPath(string $originalPath): string
    {
        $pathInfo = pathinfo($originalPath);
        $directory = str_replace('variants/', 'variants/thumbs/', $pathInfo['dirname']);

        return $directory . '/' . $pathInfo['basename'];
    }

    /**
     * Ensure thumbnail directory exists
     */
    protected function ensureDirectory(string $thumbPath): void
    {
        $thumbDir = dirname(storage_path("app/public/{$thumbPath}"));

        if (!is_dir($thumbDir)) {
            mkdir($thumbDir, 0755, true);
        }
    }

    /**
     * Delete thumbnail for image
     */
    public function delete(string $originalPath): bool
    {
        $thumbPath = $this->getThumbPath($originalPath);

        if (Storage::disk('public')->exists($thumbPath)) {
            return Storage::disk('public')->delete($thumbPath);
        }

        return false;
    }
}

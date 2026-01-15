<?php

declare(strict_types=1);

namespace App\Services\Media;

use App\Models\Media;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * ImageProcessor Service - Image Processing and Optimization
 *
 * Handles:
 * - WebP conversion (optimized format)
 * - Thumbnail generation (small, medium, large)
 * - Image optimization (quality, compression)
 * - Metadata extraction (EXIF, dimensions)
 *
 * Requires: intervention/image package
 * Install: composer require intervention/image
 *
 * ETAP_07d Phase 1.2.2: Services Layer
 * Max 300 lines (zgodnie z CLAUDE.md)
 *
 * @package App\Services\Media
 * @version 1.0
 */
class ImageProcessor
{
    /**
     * Thumbnail sizes configuration
     */
    public const THUMBNAIL_SIZES = [
        'small' => ['width' => 150, 'height' => 150],
        'medium' => ['width' => 300, 'height' => 300],
        'large' => ['width' => 600, 'height' => 600],
    ];

    /**
     * Default WebP quality (0-100)
     */
    public const WEBP_QUALITY = 85;

    /**
     * Supported input formats
     */
    public const SUPPORTED_FORMATS = ['jpeg', 'jpg', 'png', 'gif', 'webp', 'bmp'];

    /**
     * Storage disk
     */
    private string $disk;

    /**
     * Whether GD/Imagick is available
     */
    private bool $processingAvailable;

    /**
     * Create new ImageProcessor instance
     *
     * @param string|null $disk Storage disk
     */
    public function __construct(?string $disk = null)
    {
        $this->disk = $disk ?? config('filesystems.default', 'local');
        $this->processingAvailable = $this->checkProcessingAvailable();
    }

    /**
     * Convert image to WebP format
     *
     * @param string $sourcePath Source file path in storage
     * @param int $quality WebP quality (0-100)
     * @return string|null New WebP path or null on failure
     */
    public function convertToWebp(string $sourcePath, int $quality = self::WEBP_QUALITY): ?string
    {
        if (!$this->processingAvailable) {
            Log::warning('Image processing not available - GD/Imagick required');
            return null;
        }

        if (!Storage::disk($this->disk)->exists($sourcePath)) {
            Log::error('Source file not found for WebP conversion', ['path' => $sourcePath]);
            return null;
        }

        try {
            $fullPath = Storage::disk($this->disk)->path($sourcePath);
            $webpPath = $this->changeExtension($sourcePath, 'webp');
            $webpFullPath = Storage::disk($this->disk)->path($webpPath);

            // Use GD for WebP conversion
            $image = $this->loadImage($fullPath);
            if (!$image) {
                return null;
            }

            // Ensure directory exists
            $dir = dirname($webpFullPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            // Convert to WebP
            $result = imagewebp($image, $webpFullPath, $quality);
            imagedestroy($image);

            if (!$result) {
                Log::error('Failed to save WebP image', ['path' => $webpPath]);
                return null;
            }

            Log::info('Image converted to WebP', [
                'source' => $sourcePath,
                'webp' => $webpPath,
                'quality' => $quality,
            ]);

            return $webpPath;

        } catch (\Exception $e) {
            Log::error('WebP conversion failed', [
                'path' => $sourcePath,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Generate all thumbnail sizes
     *
     * @param string $sourcePath Source file path
     * @return array ['small' => path, 'medium' => path, 'large' => path]
     */
    public function generateThumbnails(string $sourcePath): array
    {
        $thumbnails = [];

        foreach (self::THUMBNAIL_SIZES as $size => $dimensions) {
            $thumbPath = $this->generateThumbnail(
                $sourcePath,
                $dimensions['width'],
                $dimensions['height'],
                $size
            );

            if ($thumbPath) {
                $thumbnails[$size] = $thumbPath;
            }
        }

        return $thumbnails;
    }

    /**
     * Generate single thumbnail
     *
     * @param string $sourcePath Source file path
     * @param int $width Target width
     * @param int $height Target height
     * @param string $suffix Size suffix (small, medium, large)
     * @return string|null Thumbnail path or null on failure
     */
    public function generateThumbnail(
        string $sourcePath,
        int $width,
        int $height,
        string $suffix = 'thumb'
    ): ?string {
        if (!$this->processingAvailable) {
            return null;
        }

        if (!Storage::disk($this->disk)->exists($sourcePath)) {
            return null;
        }

        try {
            $fullPath = Storage::disk($this->disk)->path($sourcePath);
            $thumbPath = $this->getThumbnailPath($sourcePath, $suffix);
            $thumbFullPath = Storage::disk($this->disk)->path($thumbPath);

            $image = $this->loadImage($fullPath);
            if (!$image) {
                return null;
            }

            // Get original dimensions
            $origWidth = imagesx($image);
            $origHeight = imagesy($image);

            // Calculate crop dimensions (center crop for square thumbnails)
            $cropSize = min($origWidth, $origHeight);
            $cropX = (int) (($origWidth - $cropSize) / 2);
            $cropY = (int) (($origHeight - $cropSize) / 2);

            // Create thumbnail
            $thumb = imagecreatetruecolor($width, $height);

            // Preserve transparency for PNG/WebP
            imagealphablending($thumb, false);
            imagesavealpha($thumb, true);

            // Resample with crop
            imagecopyresampled(
                $thumb,
                $image,
                0, 0,           // dest x, y
                $cropX, $cropY, // source x, y
                $width, $height,
                $cropSize, $cropSize
            );

            // Ensure directory exists
            $dir = dirname($thumbFullPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            // Save as WebP
            $result = imagewebp($thumb, $thumbFullPath, self::WEBP_QUALITY);

            imagedestroy($image);
            imagedestroy($thumb);

            if (!$result) {
                return null;
            }

            Log::debug('Thumbnail generated', [
                'source' => $sourcePath,
                'thumb' => $thumbPath,
                'size' => "{$width}x{$height}",
            ]);

            return $thumbPath;

        } catch (\Exception $e) {
            Log::error('Thumbnail generation failed', [
                'path' => $sourcePath,
                'size' => "{$width}x{$height}",
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get image dimensions
     *
     * @param string $path Image path in storage
     * @return array ['width' => int, 'height' => int] or empty on failure
     */
    public function getDimensions(string $path): array
    {
        if (!Storage::disk($this->disk)->exists($path)) {
            return [];
        }

        try {
            $fullPath = Storage::disk($this->disk)->path($path);
            $info = getimagesize($fullPath);

            if ($info) {
                return [
                    'width' => $info[0],
                    'height' => $info[1],
                    'mime' => $info['mime'] ?? null,
                ];
            }
        } catch (\Exception $e) {
            Log::warning('Failed to get image dimensions', ['error' => $e->getMessage()]);
        }

        return [];
    }

    /**
     * Optimize image (reduce file size)
     *
     * @param string $path Image path
     * @param int $quality Quality level (0-100)
     * @return bool Success
     */
    public function optimize(string $path, int $quality = 80): bool
    {
        if (!$this->processingAvailable) {
            return false;
        }

        try {
            $fullPath = Storage::disk($this->disk)->path($path);
            $image = $this->loadImage($fullPath);

            if (!$image) {
                return false;
            }

            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

            $result = match ($ext) {
                'webp' => imagewebp($image, $fullPath, $quality),
                'png' => imagepng($image, $fullPath, (int) ((100 - $quality) / 10)),
                'jpg', 'jpeg' => imagejpeg($image, $fullPath, $quality),
                default => false,
            };

            imagedestroy($image);

            return $result;

        } catch (\Exception $e) {
            Log::error('Image optimization failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Update Media model with thumbnail paths
     *
     * @param Media $media Media model
     * @param array $thumbnails Thumbnail paths
     * @return Media Updated media
     */
    public function updateMediaThumbnails(Media $media, array $thumbnails): Media
    {
        // Store thumbnail paths in prestashop_mapping for now
        // Could be moved to dedicated column if needed
        $mapping = $media->prestashop_mapping ?? [];
        $mapping['thumbnails'] = $thumbnails;
        $media->prestashop_mapping = $mapping;
        $media->save();

        return $media;
    }

    /**
     * Check if image processing is available
     *
     * @return bool
     */
    private function checkProcessingAvailable(): bool
    {
        return extension_loaded('gd') || extension_loaded('imagick');
    }

    /**
     * Load image from file path
     *
     * @param string $fullPath Full filesystem path
     * @return \GdImage|false
     */
    private function loadImage(string $fullPath): \GdImage|false
    {
        $info = getimagesize($fullPath);
        if (!$info) {
            return false;
        }

        return match ($info[2]) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($fullPath),
            IMAGETYPE_PNG => imagecreatefrompng($fullPath),
            IMAGETYPE_GIF => imagecreatefromgif($fullPath),
            IMAGETYPE_WEBP => imagecreatefromwebp($fullPath),
            IMAGETYPE_BMP => imagecreatefrombmp($fullPath),
            default => false,
        };
    }

    /**
     * Change file extension
     *
     * @param string $path Original path
     * @param string $newExt New extension
     * @return string Path with new extension
     */
    private function changeExtension(string $path, string $newExt): string
    {
        $info = pathinfo($path);
        return ($info['dirname'] !== '.' ? $info['dirname'] . '/' : '') .
            $info['filename'] . '.' . ltrim($newExt, '.');
    }

    /**
     * Get thumbnail path for given size
     *
     * @param string $sourcePath Source file path
     * @param string $suffix Size suffix
     * @return string Thumbnail path
     */
    private function getThumbnailPath(string $sourcePath, string $suffix): string
    {
        $dir = dirname($sourcePath);
        $filename = pathinfo($sourcePath, PATHINFO_FILENAME);

        return "{$dir}/thumbs/{$filename}_{$suffix}.webp";
    }
}

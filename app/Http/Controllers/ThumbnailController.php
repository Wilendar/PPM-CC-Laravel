<?php

namespace App\Http\Controllers;

use App\Models\Media;
use App\Models\VariantImage;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

/**
 * ThumbnailController - On-demand thumbnail generation
 *
 * Generates and caches thumbnails for Media images.
 * Uses Intervention Image 3.x for processing.
 *
 * Performance optimizations:
 * - Thumbnails are cached on disk after first generation
 * - Subsequent requests serve cached files directly
 * - Browser caching with proper headers
 */
class ThumbnailController extends Controller
{
    /**
     * Default thumbnail dimensions
     */
    private const DEFAULT_WIDTH = 200;
    private const DEFAULT_HEIGHT = 200;
    private const MAX_WIDTH = 800;
    private const MAX_HEIGHT = 800;
    private const JPEG_QUALITY = 80;

    /**
     * Generate or serve cached thumbnail for a Media record
     *
     * @param Request $request
     * @param int $mediaId
     * @return Response
     */
    public function show(Request $request, int $mediaId)
    {
        // Find media record
        $media = Media::find($mediaId);

        if (!$media || !$media->fileExists()) {
            return $this->placeholderResponse();
        }

        // Get requested dimensions (with limits)
        $width = min((int) $request->get('w', self::DEFAULT_WIDTH), self::MAX_WIDTH);
        $height = min((int) $request->get('h', self::DEFAULT_HEIGHT), self::MAX_HEIGHT);

        // Generate thumbnail path
        $thumbPath = $this->getThumbnailPath($media, $width, $height);

        // Check if cached thumbnail exists
        if (Storage::disk('public')->exists($thumbPath)) {
            return $this->serveImage($thumbPath);
        }

        // Generate thumbnail
        try {
            $this->generateThumbnail($media, $thumbPath, $width, $height);
            return $this->serveImage($thumbPath);
        } catch (\Exception $e) {
            \Log::error('Thumbnail generation failed', [
                'media_id' => $mediaId,
                'error' => $e->getMessage()
            ]);

            // Fallback to original image if thumbnail generation fails
            return $this->serveImage($media->file_path);
        }
    }

    /**
     * Generate thumbnail path based on media and dimensions
     */
    private function getThumbnailPath(Media $media, int $width, int $height): string
    {
        $pathInfo = pathinfo($media->file_path);
        $directory = $pathInfo['dirname'];
        $filename = $pathInfo['filename'];
        $extension = $pathInfo['extension'] ?? 'jpg';

        return "{$directory}/thumbs/{$filename}_{$width}x{$height}.{$extension}";
    }

    /**
     * Generate thumbnail using Intervention Image 3.x
     */
    private function generateThumbnail(Media $media, string $thumbPath, int $width, int $height): void
    {
        // Get full path to original image
        $originalPath = Storage::disk('public')->path($media->file_path);

        // Create thumbnail directory if needed
        $thumbDir = dirname($thumbPath);
        if (!Storage::disk('public')->exists($thumbDir)) {
            Storage::disk('public')->makeDirectory($thumbDir);
        }

        // Create ImageManager instance with GD driver
        $manager = new ImageManager(new Driver());

        // Load image and resize using Intervention Image 3.x
        $image = $manager->read($originalPath);

        // Cover resize (crop to fit exact dimensions)
        $image->cover($width, $height);

        // Determine output format based on mime type
        $extension = strtolower(pathinfo($thumbPath, PATHINFO_EXTENSION));

        // Encode based on format
        if ($extension === 'png') {
            $encoded = $image->toPng();
        } elseif ($extension === 'webp') {
            $encoded = $image->toWebp(quality: self::JPEG_QUALITY);
        } else {
            $encoded = $image->toJpeg(quality: self::JPEG_QUALITY);
        }

        // Save to storage
        Storage::disk('public')->put($thumbPath, (string) $encoded);
    }

    /**
     * Serve image with proper headers
     */
    private function serveImage(string $path): Response
    {
        $fullPath = Storage::disk('public')->path($path);

        if (!file_exists($fullPath)) {
            return $this->placeholderResponse();
        }

        $mimeType = mime_content_type($fullPath);
        $content = file_get_contents($fullPath);
        $lastModified = filemtime($fullPath);

        return response($content)
            ->header('Content-Type', $mimeType)
            ->header('Cache-Control', 'public, max-age=31536000') // 1 year
            ->header('Last-Modified', gmdate('D, d M Y H:i:s', $lastModified) . ' GMT')
            ->header('ETag', md5_file($fullPath));
    }

    /**
     * Return placeholder image response
     */
    private function placeholderResponse(): Response
    {
        // Simple 1x1 transparent PNG
        $placeholder = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');

        return response($placeholder)
            ->header('Content-Type', 'image/png')
            ->header('Cache-Control', 'public, max-age=3600');
    }

    /**
     * Generate or serve cached thumbnail for a VariantImage record
     *
     * @param Request $request
     * @param int $variantImageId
     * @return Response
     */
    public function showVariant(Request $request, int $variantImageId)
    {
        $variantImage = VariantImage::find($variantImageId);

        if (!$variantImage || empty($variantImage->image_path)) {
            return $this->placeholderResponse();
        }

        // Check if file exists
        if (!Storage::disk('public')->exists($variantImage->image_path)) {
            return $this->placeholderResponse();
        }

        // Get requested dimensions (with limits)
        $width = min((int) $request->get('w', self::DEFAULT_WIDTH), self::MAX_WIDTH);
        $height = min((int) $request->get('h', self::DEFAULT_HEIGHT), self::MAX_HEIGHT);

        // Generate thumbnail path
        $thumbPath = $this->getVariantThumbnailPath($variantImage, $width, $height);

        // Check if cached thumbnail exists
        if (Storage::disk('public')->exists($thumbPath)) {
            return $this->serveImage($thumbPath);
        }

        // Generate thumbnail
        try {
            $this->generateVariantThumbnail($variantImage, $thumbPath, $width, $height);
            return $this->serveImage($thumbPath);
        } catch (\Exception $e) {
            \Log::error('Variant thumbnail generation failed', [
                'variant_image_id' => $variantImageId,
                'error' => $e->getMessage()
            ]);

            // Fallback to original image
            return $this->serveImage($variantImage->image_path);
        }
    }

    /**
     * Generate thumbnail path for variant image
     */
    private function getVariantThumbnailPath(VariantImage $variantImage, int $width, int $height): string
    {
        $pathInfo = pathinfo($variantImage->image_path);
        $directory = $pathInfo['dirname'];
        $filename = $pathInfo['filename'];
        $extension = $pathInfo['extension'] ?? 'webp';

        return "{$directory}/thumbs/{$filename}_{$width}x{$height}.{$extension}";
    }

    /**
     * Generate thumbnail for variant image
     */
    private function generateVariantThumbnail(VariantImage $variantImage, string $thumbPath, int $width, int $height): void
    {
        $originalPath = Storage::disk('public')->path($variantImage->image_path);

        // Create thumbnail directory if needed
        $thumbDir = dirname($thumbPath);
        if (!Storage::disk('public')->exists($thumbDir)) {
            Storage::disk('public')->makeDirectory($thumbDir);
        }

        $manager = new ImageManager(new Driver());
        $image = $manager->read($originalPath);
        $image->cover($width, $height);

        $extension = strtolower(pathinfo($thumbPath, PATHINFO_EXTENSION));

        if ($extension === 'png') {
            $encoded = $image->toPng();
        } elseif ($extension === 'webp') {
            $encoded = $image->toWebp(quality: self::JPEG_QUALITY);
        } else {
            $encoded = $image->toJpeg(quality: self::JPEG_QUALITY);
        }

        Storage::disk('public')->put($thumbPath, (string) $encoded);
    }
}

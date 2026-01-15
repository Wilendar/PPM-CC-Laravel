<?php

declare(strict_types=1);

namespace App\Services\Media;

use App\Models\PrestaShopShop;
use App\Models\ProductVariant;
use App\Models\VariantImage;
use App\Services\PrestaShop\PrestaShop8Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * VariantImageDownloadService - Download variant images from PrestaShop
 *
 * Handles downloading variant/combination images from PrestaShop API
 * and storing them locally in PPM storage.
 *
 * Storage Structure:
 * - storage/app/public/variants/{variant_id}/{filename}.jpg
 *
 * @package App\Services\Media
 * @version 1.0
 */
class VariantImageDownloadService
{
    /**
     * Storage disk for variant images
     */
    private const STORAGE_DISK = 'public';

    /**
     * Base path for variant images
     */
    private const BASE_PATH = 'variants';

    /**
     * Rate limit delay between API calls (milliseconds)
     */
    private const RATE_LIMIT_DELAY_MS = 300;

    /**
     * Download variant image from PrestaShop and store locally
     *
     * @param ProductVariant $variant PPM Variant
     * @param int $psProductId PrestaShop product ID
     * @param int $psImageId PrestaShop image ID
     * @param PrestaShopShop $shop Shop instance
     * @param int $position Image position (0-based)
     * @param bool $isCover Is this the cover image
     * @return VariantImage|null Created VariantImage or null on failure
     */
    public function downloadAndStore(
        ProductVariant $variant,
        int $psProductId,
        int $psImageId,
        PrestaShopShop $shop,
        int $position = 0,
        bool $isCover = false
    ): ?VariantImage {
        try {
            // Create API client
            $client = new PrestaShop8Client($shop);

            // Rate limit to avoid overloading PrestaShop
            usleep(self::RATE_LIMIT_DELAY_MS * 1000);

            // Download image binary data from PrestaShop API
            $imageData = $client->downloadProductImage($psProductId, $psImageId);

            if (empty($imageData)) {
                Log::warning('[VARIANT IMG DOWNLOAD] Empty image data from API', [
                    'variant_id' => $variant->id,
                    'ps_product_id' => $psProductId,
                    'ps_image_id' => $psImageId,
                ]);
                return null;
            }

            // Determine image extension from content
            $extension = $this->detectImageExtension($imageData);

            // Generate storage path
            $directory = self::BASE_PATH . '/' . $variant->id;
            $filename = $this->generateFilename($variant, $psImageId, $position, $extension);
            $storagePath = $directory . '/' . $filename;

            // Ensure directory exists
            Storage::disk(self::STORAGE_DISK)->makeDirectory($directory);

            // Store the image
            $stored = Storage::disk(self::STORAGE_DISK)->put($storagePath, $imageData);

            if (!$stored) {
                Log::error('[VARIANT IMG DOWNLOAD] Failed to store image', [
                    'variant_id' => $variant->id,
                    'storage_path' => $storagePath,
                ]);
                return null;
            }

            // Build PrestaShop API URL for reference
            $shopUrl = rtrim($shop->shop_url ?? '', '/');
            $psImageUrl = "{$shopUrl}/api/images/products/{$psProductId}/{$psImageId}";

            // Create VariantImage record
            $variantImage = VariantImage::create([
                'variant_id' => $variant->id,
                'image_path' => $storagePath,
                'image_thumb_path' => null, // Thumbnail can be generated later if needed
                'image_url' => $psImageUrl, // Keep reference to PS URL
                'is_cover' => $isCover,
                'position' => $position,
                'is_cached' => true,
                'cache_path' => $storagePath,
                'cached_at' => now(),
            ]);

            Log::info('[VARIANT IMG DOWNLOAD] Image downloaded and stored', [
                'variant_id' => $variant->id,
                'variant_image_id' => $variantImage->id,
                'ps_image_id' => $psImageId,
                'storage_path' => $storagePath,
                'size_bytes' => strlen($imageData),
            ]);

            return $variantImage;

        } catch (\Exception $e) {
            Log::error('[VARIANT IMG DOWNLOAD] Failed to download image', [
                'variant_id' => $variant->id,
                'ps_product_id' => $psProductId,
                'ps_image_id' => $psImageId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Download multiple variant images
     *
     * @param ProductVariant $variant PPM Variant
     * @param int $psProductId PrestaShop product ID
     * @param array $psImageIds Array of PrestaShop image IDs
     * @param PrestaShopShop $shop Shop instance
     * @return array ['downloaded' => int, 'failed' => int, 'images' => VariantImage[]]
     */
    public function downloadMultiple(
        ProductVariant $variant,
        int $psProductId,
        array $psImageIds,
        PrestaShopShop $shop
    ): array {
        $result = [
            'downloaded' => 0,
            'failed' => 0,
            'images' => [],
        ];

        foreach ($psImageIds as $position => $psImageId) {
            $isCover = ($position === 0);

            $variantImage = $this->downloadAndStore(
                $variant,
                $psProductId,
                (int) $psImageId,
                $shop,
                $position,
                $isCover
            );

            if ($variantImage) {
                $result['downloaded']++;
                $result['images'][] = $variantImage;
            } else {
                $result['failed']++;
            }
        }

        return $result;
    }

    /**
     * Detect image extension from binary data
     *
     * @param string $imageData Binary image data
     * @return string File extension (jpg, png, gif, webp)
     */
    private function detectImageExtension(string $imageData): string
    {
        // Check magic bytes
        $header = substr($imageData, 0, 16);

        // JPEG: FF D8 FF
        if (str_starts_with($header, "\xFF\xD8\xFF")) {
            return 'jpg';
        }

        // PNG: 89 50 4E 47 0D 0A 1A 0A
        if (str_starts_with($header, "\x89PNG\r\n\x1a\n")) {
            return 'png';
        }

        // GIF: 47 49 46 38
        if (str_starts_with($header, "GIF8")) {
            return 'gif';
        }

        // WebP: 52 49 46 46 ... 57 45 42 50
        if (str_starts_with($header, "RIFF") && strpos($header, "WEBP") !== false) {
            return 'webp';
        }

        // Default to jpg
        return 'jpg';
    }

    /**
     * Generate filename for variant image
     *
     * Pattern: variant_{variant_id}_ps{ps_image_id}_{position}.{ext}
     *
     * @param ProductVariant $variant Variant
     * @param int $psImageId PrestaShop image ID
     * @param int $position Position index
     * @param string $extension File extension
     * @return string Generated filename
     */
    private function generateFilename(
        ProductVariant $variant,
        int $psImageId,
        int $position,
        string $extension
    ): string {
        $positionStr = str_pad((string) $position, 2, '0', STR_PAD_LEFT);
        return "variant_{$variant->id}_ps{$psImageId}_{$positionStr}.{$extension}";
    }

    /**
     * Check if variant already has locally stored images
     *
     * @param ProductVariant $variant Variant to check
     * @return bool True if has local images
     */
    public function hasLocalImages(ProductVariant $variant): bool
    {
        return $variant->images()
            ->whereNotNull('image_path')
            ->where('image_path', '!=', '')
            ->exists();
    }

    /**
     * Delete all locally stored images for a variant
     *
     * @param ProductVariant $variant Variant
     * @return int Number of deleted files
     */
    public function deleteLocalImages(ProductVariant $variant): int
    {
        $deleted = 0;
        $directory = self::BASE_PATH . '/' . $variant->id;

        // Delete all files in variant directory
        if (Storage::disk(self::STORAGE_DISK)->exists($directory)) {
            $files = Storage::disk(self::STORAGE_DISK)->files($directory);
            foreach ($files as $file) {
                if (Storage::disk(self::STORAGE_DISK)->delete($file)) {
                    $deleted++;
                }
            }

            // Remove empty directory
            Storage::disk(self::STORAGE_DISK)->deleteDirectory($directory);
        }

        return $deleted;
    }
}

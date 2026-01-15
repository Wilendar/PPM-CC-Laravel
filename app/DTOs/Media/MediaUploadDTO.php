<?php

declare(strict_types=1);

namespace App\DTOs\Media;

use Illuminate\Http\UploadedFile;
use InvalidArgumentException;

/**
 * MediaUploadDTO - Data Transfer Object for Media Uploads
 *
 * Type-safe validation of upload parameters with immutable properties.
 * Ensures data integrity before processing by MediaManager service.
 *
 * ETAP_07d Phase 1.4.1: Core Infrastructure - DTOs
 *
 * @package App\DTOs\Media
 * @version 1.0
 */
final readonly class MediaUploadDTO
{
    /**
     * Maximum images allowed per product (01-99 naming convention)
     */
    public const MAX_IMAGES_PER_PRODUCT = 99;

    /**
     * Allowed MIME types for image uploads
     */
    public const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
    ];

    /**
     * Maximum file size in bytes (10MB)
     */
    public const MAX_FILE_SIZE = 10485760;

    /**
     * Thumbnail sizes configuration
     */
    public const THUMBNAIL_SIZES = [
        'small' => ['width' => 150, 'height' => 150],
        'medium' => ['width' => 300, 'height' => 300],
        'large' => ['width' => 600, 'height' => 600],
    ];

    /**
     * Create new MediaUploadDTO instance
     *
     * @param UploadedFile $file Uploaded file instance
     * @param string $mediableType Class name (App\Models\Product or App\Models\ProductVariant)
     * @param int $mediableId ID of the related model
     * @param string|null $altText SEO alt text
     * @param bool $isPrimary Set as primary/cover image
     * @param int|null $sortOrder Position in gallery (auto-assigned if null)
     * @param bool $convertToWebp Auto-convert to WebP format
     * @param bool $generateThumbnails Generate thumbnail sizes
     */
    public function __construct(
        public UploadedFile $file,
        public string $mediableType,
        public int $mediableId,
        public ?string $altText = null,
        public bool $isPrimary = false,
        public ?int $sortOrder = null,
        public bool $convertToWebp = true,
        public bool $generateThumbnails = true,
    ) {
        $this->validate();
    }

    /**
     * Create DTO from array data (useful for batch uploads)
     *
     * @param array $data Upload data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            file: $data['file'],
            mediableType: $data['mediable_type'],
            mediableId: (int) $data['mediable_id'],
            altText: $data['alt_text'] ?? null,
            isPrimary: (bool) ($data['is_primary'] ?? false),
            sortOrder: isset($data['sort_order']) ? (int) $data['sort_order'] : null,
            convertToWebp: (bool) ($data['convert_to_webp'] ?? true),
            generateThumbnails: (bool) ($data['generate_thumbnails'] ?? true),
        );
    }

    /**
     * Create DTO for product upload
     *
     * @param UploadedFile $file Uploaded file
     * @param int $productId Product ID
     * @param string|null $altText Alt text
     * @param bool $isPrimary Is primary image
     * @return self
     */
    public static function forProduct(
        UploadedFile $file,
        int $productId,
        ?string $altText = null,
        bool $isPrimary = false
    ): self {
        return new self(
            file: $file,
            mediableType: 'App\\Models\\Product',
            mediableId: $productId,
            altText: $altText,
            isPrimary: $isPrimary,
        );
    }

    /**
     * Create DTO for product variant upload
     *
     * @param UploadedFile $file Uploaded file
     * @param int $variantId ProductVariant ID
     * @param string|null $altText Alt text
     * @param bool $isPrimary Is primary image
     * @return self
     */
    public static function forVariant(
        UploadedFile $file,
        int $variantId,
        ?string $altText = null,
        bool $isPrimary = false
    ): self {
        return new self(
            file: $file,
            mediableType: 'App\\Models\\ProductVariant',
            mediableId: $variantId,
            altText: $altText,
            isPrimary: $isPrimary,
        );
    }

    /**
     * Validate DTO data
     *
     * @throws InvalidArgumentException
     */
    private function validate(): void
    {
        // Validate file exists and is valid
        if (!$this->file->isValid()) {
            throw new InvalidArgumentException(
                'Invalid file upload: ' . $this->file->getErrorMessage()
            );
        }

        // Validate MIME type
        $mimeType = $this->file->getMimeType();
        if (!in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
            throw new InvalidArgumentException(
                "Invalid MIME type: {$mimeType}. Allowed: " . implode(', ', self::ALLOWED_MIME_TYPES)
            );
        }

        // Validate file size
        if ($this->file->getSize() > self::MAX_FILE_SIZE) {
            $maxMb = self::MAX_FILE_SIZE / 1048576;
            throw new InvalidArgumentException(
                "File too large. Maximum allowed: {$maxMb}MB"
            );
        }

        // Validate mediable type
        $allowedTypes = ['App\\Models\\Product', 'App\\Models\\ProductVariant'];
        if (!in_array($this->mediableType, $allowedTypes, true)) {
            throw new InvalidArgumentException(
                "Invalid mediable type: {$this->mediableType}. Allowed: " . implode(', ', $allowedTypes)
            );
        }

        // Validate mediable ID
        if ($this->mediableId <= 0) {
            throw new InvalidArgumentException('Mediable ID must be positive integer');
        }

        // Validate sort order if provided
        if ($this->sortOrder !== null && ($this->sortOrder < 1 || $this->sortOrder > self::MAX_IMAGES_PER_PRODUCT)) {
            throw new InvalidArgumentException(
                "Sort order must be between 1 and " . self::MAX_IMAGES_PER_PRODUCT
            );
        }
    }

    /**
     * Get original filename
     *
     * @return string
     */
    public function getOriginalName(): string
    {
        return $this->file->getClientOriginalName();
    }

    /**
     * Get file extension
     *
     * @return string
     */
    public function getExtension(): string
    {
        return $this->file->getClientOriginalExtension();
    }

    /**
     * Get file MIME type
     *
     * @return string
     */
    public function getMimeType(): string
    {
        return $this->file->getMimeType() ?? 'application/octet-stream';
    }

    /**
     * Get file size in bytes
     *
     * @return int
     */
    public function getFileSize(): int
    {
        return $this->file->getSize();
    }

    /**
     * Check if file is image
     *
     * @return bool
     */
    public function isImage(): bool
    {
        return str_starts_with($this->getMimeType(), 'image/');
    }

    /**
     * Convert to array for logging/debugging
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'original_name' => $this->getOriginalName(),
            'mime_type' => $this->getMimeType(),
            'file_size' => $this->getFileSize(),
            'mediable_type' => $this->mediableType,
            'mediable_id' => $this->mediableId,
            'alt_text' => $this->altText,
            'is_primary' => $this->isPrimary,
            'sort_order' => $this->sortOrder,
            'convert_to_webp' => $this->convertToWebp,
            'generate_thumbnails' => $this->generateThumbnails,
        ];
    }
}

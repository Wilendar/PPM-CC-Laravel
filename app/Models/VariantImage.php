<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Variant Image Model
 *
 * Zdjęcie wariantu produktu
 * Wspiera cover image i pozycjonowanie
 *
 * UPDATED 2025-12-04: Synced $fillable with migration schema
 * Migration has: image_path, image_thumb_path (NOT filename, path)
 *
 * @property int $id
 * @property int $variant_id
 * @property string $image_path Sciezka do pliku (main image)
 * @property string|null $image_thumb_path Sciezka do miniaturki
 * @property string|null $image_url PrestaShop URL (for API imports)
 * @property bool $is_cached Czy obraz jest w cache lokalnym
 * @property string|null $cache_path Sciezka do cache
 * @property bool $is_cover Czy glowne zdjecie
 * @property int|null $position Kolejnosc wyswietlania
 * @property \Illuminate\Support\Carbon|null $cached_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class VariantImage extends Model
{
    use HasFactory;

    /**
     * Table name
     */
    protected $table = 'variant_images';

    /**
     * Fillable attributes
     *
     * UPDATED 2025-12-04: Synced with migration schema
     * - image_path (was: path) - main image path
     * - image_thumb_path (was: filename) - thumbnail path
     * - Added: image_url, is_cached, cache_path, cached_at (from extend migration)
     */
    protected $fillable = [
        'variant_id',
        'image_path',
        'image_thumb_path',
        'image_url',
        'is_cached',
        'cache_path',
        'cached_at',
        'is_cover',
        'position',
    ];

    /**
     * Attribute casts
     */
    protected $casts = [
        'variant_id' => 'integer',
        'is_cover' => 'boolean',
        'is_cached' => 'boolean',
        'position' => 'integer',
        'cached_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Storage disk
     */
    protected const STORAGE_DISK = 'public';

    /**
     * Base path for variant images
     */
    protected const BASE_PATH = 'variants';

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    /**
     * Parent variant
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /**
     * Scope: Only cover images
     */
    public function scopeCover($query)
    {
        return $query->where('is_cover', true);
    }

    /**
     * Scope: Ordered by position
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('position', 'asc')->orderBy('id', 'asc');
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS (Laravel Attribute Pattern)
    |--------------------------------------------------------------------------
    | FIX 2025-12-04: Added accessors for property-style access in Blade
    | Blade templates use $image->url not $image->getUrl()
    */

    /**
     * Get public URL (accessor for $image->url)
     *
     * FIX 2025-12-10: Priority changed - local files first, then external URLs
     * Previously: image_url (PS API) was checked first, causing broken images
     * Now: Local cached images have priority over external URLs
     */
    public function url(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                // PRIORITY 1: Local cached image (is_cached=true + image_path exists)
                if ($this->is_cached && !empty($this->image_path) && Storage::disk(self::STORAGE_DISK)->exists($this->image_path)) {
                    return Storage::disk(self::STORAGE_DISK)->url($this->image_path);
                }

                // PRIORITY 2: Local image without cache flag (for manually uploaded)
                if (!empty($this->image_path) && Storage::disk(self::STORAGE_DISK)->exists($this->image_path)) {
                    return Storage::disk(self::STORAGE_DISK)->url($this->image_path);
                }

                // PRIORITY 3: External URL (PrestaShop API) - only if it's a full URL
                if (!empty($this->image_url) && filter_var($this->image_url, FILTER_VALIDATE_URL)) {
                    return $this->image_url;
                }

                // Fallback to placeholder
                return asset('/images/placeholders/default-placeholder.jpg');
            }
        );
    }

    /**
     * Get thumbnail URL (accessor for $image->thumbnail_url)
     *
     * FIX 2025-12-15: Uses on-demand thumbnail generation
     * instead of falling back to full-size image
     */
    public function thumbnailUrl(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                // First try explicit thumb path (pre-generated)
                if (!empty($this->image_thumb_path) && Storage::disk(self::STORAGE_DISK)->exists($this->image_thumb_path)) {
                    return Storage::disk(self::STORAGE_DISK)->url($this->image_thumb_path);
                }

                // Use on-demand thumbnail generation (48x48 for ProductList)
                if (!empty($this->id) && !empty($this->image_path)) {
                    return route('thumbnail.variant', ['variantImageId' => $this->id, 'w' => 48, 'h' => 48]);
                }

                // Fallback to placeholder
                return asset('/images/placeholders/default-placeholder.jpg');
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Get full file path
     */
    public function getFullPath(): string
    {
        return Storage::disk(self::STORAGE_DISK)->path($this->image_path);
    }

    /**
     * Get public URL (method version for backward compatibility)
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * Get thumbnail path (if exists)
     */
    public function getThumbPath(): ?string
    {
        // First check if image_thumb_path is set
        if ($this->image_thumb_path) {
            if (Storage::disk(self::STORAGE_DISK)->exists($this->image_thumb_path)) {
                return $this->image_thumb_path;
            }
        }

        // Fallback: try to construct thumb path from image_path
        $filename = basename($this->image_path);
        $thumbPath = str_replace($filename, 'thumb_' . $filename, $this->image_path);

        if (Storage::disk(self::STORAGE_DISK)->exists($thumbPath)) {
            return $thumbPath;
        }

        return null;
    }

    /**
     * Get thumbnail URL (method version for backward compatibility)
     */
    public function getThumbUrl(): ?string
    {
        return $this->thumbnail_url;
    }

    /**
     * Delete image file from storage
     */
    public function deleteFile(): bool
    {
        $deleted = Storage::disk(self::STORAGE_DISK)->delete($this->image_path);

        // Delete thumbnail if exists
        if ($this->image_thumb_path) {
            Storage::disk(self::STORAGE_DISK)->delete($this->image_thumb_path);
        }

        return $deleted;
    }

    /**
     * Set as cover image (unset others)
     */
    public function setAsCover(): bool
    {
        // Unset other cover images for this variant
        static::where('variant_id', $this->variant_id)
            ->where('id', '!=', $this->id)
            ->update(['is_cover' => false]);

        $this->is_cover = true;
        return $this->save();
    }
}
